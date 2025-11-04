<?php
declare(strict_types=1);

/**
 * Admin Panel - Index
 * Provides tools for managing PIN code data, content templates and SEO assets.
 */

$pdo = require dirname(__DIR__, 2) . '/app/bootstrap.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function check_csrf(?string $token): bool
{
    return isset($token, $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function ensure_admin_environment(PDO $pdo): void
{
    // Create required tables if they do not exist.
    $pdo->exec("CREATE TABLE IF NOT EXISTS pincode_master (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        pincode VARCHAR(6) NOT NULL,
        officename VARCHAR(255) NOT NULL,
        officetype VARCHAR(32) NOT NULL DEFAULT 'BO',
        delivery VARCHAR(32) NOT NULL DEFAULT 'Delivery',
        district VARCHAR(150) NOT NULL,
        statename VARCHAR(150) NOT NULL,
        circlename VARCHAR(150) DEFAULT NULL,
        regionname VARCHAR(150) DEFAULT NULL,
        divisionname VARCHAR(150) DEFAULT NULL,
        contact VARCHAR(100) DEFAULT NULL,
        remarks TEXT DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        slug VARCHAR(255) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        views_count INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_pincode_office (pincode, officename),
        UNIQUE KEY uniq_slug (slug),
        KEY idx_state (statename),
        KEY idx_district (district),
        KEY idx_pincode (pincode),
        KEY idx_views (views_count)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try {
        $indexCheck = $pdo->query("SHOW INDEX FROM pincode_master WHERE Key_name = 'uniq_pincode'");
        if ($indexCheck !== false && $indexCheck->fetch()) {
            $pdo->exec('ALTER TABLE pincode_master DROP INDEX uniq_pincode');
        }
    } catch (Throwable $exception) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Failed to drop legacy uniq_pincode index: ' . $exception->getMessage());
        }
    }

    try {
        $slugNullability = $pdo->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pincode_master' AND COLUMN_NAME = 'slug'");
        if ($slugNullability !== false && $slugNullability->fetchColumn() === 'YES') {
            $pdo->exec('ALTER TABLE pincode_master MODIFY slug VARCHAR(255) NOT NULL');
        }
    } catch (Throwable $exception) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('Failed to enforce non-null slug column: ' . $exception->getMessage());
        }
    }

    foreach ([
        'uniq_slug' => 'ALTER TABLE pincode_master ADD UNIQUE KEY uniq_slug (slug)',
        'uniq_pincode_office' => 'ALTER TABLE pincode_master ADD UNIQUE KEY uniq_pincode_office (pincode, officename)',
        'idx_pincode' => 'ALTER TABLE pincode_master ADD KEY idx_pincode (pincode)',
    ] as $indexName => $alterSql) {
        try {
            $indexExists = $pdo->prepare('SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
            $indexExists->execute(['pincode_master', $indexName]);
            if ((int) $indexExists->fetchColumn() === 0) {
                $pdo->exec($alterSql);
            }
        } catch (Throwable $exception) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log('Failed to ensure index ' . $indexName . ': ' . $exception->getMessage());
            }
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        email VARCHAR(190) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','editor','viewer') NOT NULL DEFAULT 'editor',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS content_templates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(190) NOT NULL UNIQUE,
        title_template VARCHAR(255) NOT NULL,
        body_template MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS generated_posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        body MEDIUMTEXT NOT NULL,
        pincode VARCHAR(6) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE,
        value TEXT DEFAULT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS search_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        search_query VARCHAR(255) NOT NULL,
        results_found INT UNSIGNED NOT NULL DEFAULT 0,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_query (search_query),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS import_history (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        total_rows INT UNSIGNED NOT NULL DEFAULT 0,
        imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
        failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
        status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
        error_log TEXT DEFAULT NULL,
        imported_by INT UNSIGNED DEFAULT NULL,
        started_at TIMESTAMP NULL DEFAULT NULL,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status (status),
        CONSTRAINT fk_import_history_admin FOREIGN KEY (imported_by) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure new map settings exist for backwards compatibility with manual upgrades.
    $settingStmt = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE name = ?');
    foreach ([
        'maps_api_key' => '',
        'maps_nearby_categories' => "Post Office\nATM\nBank\nHospital\nPolice Station",
    ] as $settingName => $defaultValue) {
        $settingStmt->execute([$settingName]);
        if ((int) $settingStmt->fetchColumn() === 0) {
            $insert = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?)');
            $insert->execute([$settingName, $defaultValue]);
        }
    }

    // Ensure a default template exists for the editor/generator.
    $tplStmt = $pdo->prepare('SELECT COUNT(*) FROM content_templates WHERE slug = ?');
    $tplStmt->execute(['pincode_page']);
    if ((int) $tplStmt->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO content_templates (slug, title_template, body_template) VALUES (?, ?, ?)')
            ->execute([
                'pincode_page',
                'PIN Code {{pincode}} — {{officename}}, {{district}}',
                "<h1>PIN Code {{pincode}}</h1>\n<p>{{officename}} serves {{district}}, {{statename}}.</p>"
            ]);
    }

    // Ensure a default admin account exists to avoid lock-out on fresh installs.
    $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($adminCount === 0) {
        $passwordHash = password_hash('admin@123', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO admin_users (username, email, password, role) VALUES (?, ?, ?, ?)')
            ->execute(['admin', 'admin@example.com', $passwordHash, 'admin']);
        $_SESSION['admin_setup_notice'] = 'Default admin credentials created (username: admin, password: admin@123). Please change them after logging in.';
    }
}

function resolve_pincode_slug(PDO $pdo, string $pincode, string $officeName): string
{
    static $officeSlugCache = [];
    static $reservedSlugs = [];
    static $lookupStmt = null;
    static $slugCheckStmt = null;

    $normalizedOffice = trim($officeName);
    $cacheKey = strtolower($pincode . '|' . $normalizedOffice);
    if (isset($officeSlugCache[$cacheKey])) {
        return $officeSlugCache[$cacheKey];
    }

    if ($lookupStmt === null) {
        $lookupStmt = $pdo->prepare('SELECT slug FROM pincode_master WHERE pincode = ? AND officename = ? LIMIT 1');
    }
    $lookupStmt->execute([$pincode, $normalizedOffice]);
    $existing = $lookupStmt->fetchColumn();
    if ($existing) {
        $reservedSlugs[$existing] = true;
        $officeSlugCache[$cacheKey] = $existing;
        return $existing;
    }

    $base = trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($normalizedOffice)), '-');
    $baseSlug = $base !== '' ? $pincode . '-' . $base : $pincode;
    $candidate = $baseSlug;
    $suffix = 2;

    if ($slugCheckStmt === null) {
        $slugCheckStmt = $pdo->prepare('SELECT 1 FROM pincode_master WHERE slug = ? LIMIT 1');
    }

    while (isset($reservedSlugs[$candidate])) {
        $candidate = $baseSlug . '-' . $suffix;
        $suffix++;
    }

    while (true) {
        if (!isset($reservedSlugs[$candidate])) {
            $slugCheckStmt->execute([$candidate]);
            if ($slugCheckStmt->fetchColumn() === false) {
                break;
            }
        }
        $candidate = $baseSlug . '-' . $suffix;
        $suffix++;
    }

    $reservedSlugs[$candidate] = true;
    $officeSlugCache[$cacheKey] = $candidate;

    return $candidate;
}

enforce_session_lifetime();
ensure_admin_environment($pdo);
initialize_settings_cache($pdo, true);

function enforce_session_lifetime(): void
{
    if (!isset($_SESSION['LAST_ACTIVITY'])) {
        $_SESSION['LAST_ACTIVITY'] = time();
        return;
    }
    if (time() - (int) $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header('Location: /admin');
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

function render_flash_message(): void
{
    if (!empty($_SESSION['admin_setup_notice'])) {
        echo '<div class="notice">' . htmlspecialchars($_SESSION['admin_setup_notice']) . '</div>';
        unset($_SESSION['admin_setup_notice']);
    }
}

function render_csv_import_form_and_process(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
        if (!check_csrf($_POST['csrf_token'] ?? null)) {
            echo '<div class="error">Invalid CSRF token.</div>';
            return;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error">CSV upload failed. Please try again.</div>';
            return;
        }

        if ($_FILES['csv_file']['size'] > 12 * 1024 * 1024) {
            echo '<div class="error">File too large. Maximum allowed size is 12MB.</div>';
            return;
        }

        $ext = strtolower((string) pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            echo '<div class="error">Only CSV files are supported.</div>';
            return;
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'rb');
        if ($handle === false) {
            echo '<div class="error">Unable to open uploaded file.</div>';
            return;
        }

        $historyId = null;
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO import_history (filename, status, imported_by, started_at) VALUES (?, ?, ?, NOW())')
                ->execute([
                    $_FILES['csv_file']['name'],
                    'processing',
                    $_SESSION['admin_id'] ?? null,
                ]);
            $historyId = (int) $pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            echo '<div class="error">CSV is empty.</div>';
            return;
        }

        $columns = array_map(static fn($value) => strtolower(trim((string) $value)), $header);
        $map = [
            'pincode' => ['pincode', 'pin', 'postalcode', 'postal_code'],
            'officename' => ['officename', 'office_name', 'post_office', 'office'],
            'officetype' => ['officetype', 'office_type', 'type'],
            'delivery' => ['delivery', 'delivery_status', 'status'],
            'district' => ['district', 'district_name'],
            'statename' => ['statename', 'state', 'state_name'],
            'circlename' => ['circle', 'circlename'],
            'regionname' => ['region', 'regionname'],
            'divisionname' => ['division', 'divisionname'],
            'contact' => ['contact', 'phone', 'telephone'],
            'remarks' => ['remarks', 'note', 'notes'],
            'latitude' => ['latitude', 'lat'],
            'longitude' => ['longitude', 'lng', 'lon'],
            'views_count' => ['views', 'views_count', 'hits'],
            'is_active' => ['is_active', 'active', 'status_flag'],
        ];

        $columnIndex = [];
        foreach ($map as $target => $aliases) {
            foreach ($aliases as $alias) {
                $index = array_search($alias, $columns, true);
                if ($index !== false) {
                    $columnIndex[$target] = $index;
                    break;
                }
            }
        }

        if (!isset($columnIndex['pincode'])) {
            fclose($handle);
            echo '<div class="error">The CSV file must include a pincode column.</div>';
            return;
        }

        $insertSql = 'INSERT INTO pincode_master (
            pincode, officename, officetype, delivery, district, statename,
            circlename, regionname, divisionname, contact, remarks, latitude,
            longitude, slug, is_active, views_count
        ) VALUES (
            :pincode, :officename, :officetype, :delivery, :district, :statename,
            :circlename, :regionname, :divisionname, :contact, :remarks, :latitude,
            :longitude, :slug, :is_active, :views_count
        ) ON DUPLICATE KEY UPDATE
            officename = VALUES(officename),
            officetype = VALUES(officetype),
            delivery = VALUES(delivery),
            district = VALUES(district),
            statename = VALUES(statename),
            circlename = VALUES(circlename),
            regionname = VALUES(regionname),
            divisionname = VALUES(divisionname),
            contact = VALUES(contact),
            remarks = VALUES(remarks),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            slug = VALUES(slug),
            is_active = VALUES(is_active),
            views_count = VALUES(views_count),
            updated_at = CURRENT_TIMESTAMP';

        $stmt = $pdo->prepare($insertSql);

        $total = 0;
        $imported = 0;
        $failed = 0;
        $errorRows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            $pincode = preg_replace('/\D+/', '', (string) ($row[$columnIndex['pincode']] ?? ''));
            if (strlen($pincode) !== 6) {
                $failed++;
                $errorRows[] = "Row {$total}: invalid pincode";
                continue;
            }

            $officeName = trim((string) ($row[$columnIndex['officename']] ?? ''));
            if ($officeName === '') {
                $officeName = 'Unknown Office';
            }

            $latitudeRaw = isset($columnIndex['latitude']) ? trim((string) ($row[$columnIndex['latitude']] ?? '')) : '';
            $longitudeRaw = isset($columnIndex['longitude']) ? trim((string) ($row[$columnIndex['longitude']] ?? '')) : '';
            $latitude = $latitudeRaw !== '' ? (float) $latitudeRaw : null;
            $longitude = $longitudeRaw !== '' ? (float) $longitudeRaw : null;

            $payload = [
                'pincode' => $pincode,
                'officename' => $officeName,
                'officetype' => strtoupper(trim((string) ($row[$columnIndex['officetype']] ?? 'BO'))),
                'delivery' => ucfirst(strtolower(trim((string) ($row[$columnIndex['delivery']] ?? 'Delivery')))),
                'district' => trim((string) ($row[$columnIndex['district']] ?? '')),
                'statename' => trim((string) ($row[$columnIndex['statename']] ?? '')),
                'circlename' => trim((string) ($row[$columnIndex['circlename']] ?? '')) ?: null,
                'regionname' => trim((string) ($row[$columnIndex['regionname']] ?? '')) ?: null,
                'divisionname' => trim((string) ($row[$columnIndex['divisionname']] ?? '')) ?: null,
                'contact' => trim((string) ($row[$columnIndex['contact']] ?? '')) ?: null,
                'remarks' => trim((string) ($row[$columnIndex['remarks']] ?? '')) ?: null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_active' => isset($columnIndex['is_active']) ? (int) ((bool) $row[$columnIndex['is_active']]) : 1,
                'views_count' => isset($columnIndex['views_count']) && is_numeric($row[$columnIndex['views_count']]) ? (int) $row[$columnIndex['views_count']] : 0,
            ];

            $payload['slug'] = resolve_pincode_slug($pdo, $pincode, $officeName);

            try {
                $stmt->execute($payload);
                $imported++;
            } catch (Throwable $exception) {
                $failed++;
                $errorRows[] = "Row {$total}: " . $exception->getMessage();
            }
        }
        fclose($handle);

        if ($historyId) {
            $pdo->prepare('UPDATE import_history SET total_rows = ?, imported_rows = ?, failed_rows = ?, status = ?, error_log = ?, completed_at = NOW() WHERE id = ?')
                ->execute([
                    $total,
                    $imported,
                    $failed,
                    $failed > 0 ? 'completed' : 'completed',
                    $errorRows ? implode("\n", array_slice($errorRows, 0, 50)) : null,
                    $historyId,
                ]);
        }

        echo '<div class="success">Import finished. Processed ' . number_format($total) . ' rows, ' . number_format($imported) . ' saved, ' . number_format($failed) . ' failed.</div>';
        if ($errorRows) {
            echo '<details class="error" style="margin-top:10px;"><summary>View first ' . min(50, count($errorRows)) . ' issues</summary><pre>' . htmlspecialchars(implode("\n", array_slice($errorRows, 0, 50))) . '</pre></details>';
        }
    }

    ?>
    <div class="card">
        <h3>Import PIN Codes (CSV)</h3>
        <p class="muted">Upload a CSV file containing pin code information. Files up to 12MB are accepted.</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="import_csv" class="action-btn">Upload &amp; Import</button>
        </form>
    </div>
    <?php
}

function render_data_search_tool(PDO $pdo): void
{
    $query = trim((string) ($_GET['q'] ?? ''));
    $state = trim((string) ($_GET['state'] ?? ''));
    $district = trim((string) ($_GET['district'] ?? ''));
    $delivery = trim((string) ($_GET['delivery'] ?? ''));
    $perPage = 50;
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $states = $pdo->query('SELECT DISTINCT statename FROM pincode_master ORDER BY statename')->fetchAll(PDO::FETCH_COLUMN);
    $districts = [];
    if ($state !== '') {
        $stmt = $pdo->prepare('SELECT DISTINCT district FROM pincode_master WHERE statename = ? ORDER BY district');
        $stmt->execute([$state]);
        $districts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $conditions = [];
    $params = [];
    if ($query !== '') {
        $conditions[] = '(pincode LIKE :query OR officename LIKE :query OR district LIKE :query OR statename LIKE :query)';
        $params[':query'] = '%' . $query . '%';
    }
    if ($state !== '') {
        $conditions[] = 'statename = :state';
        $params[':state'] = $state;
    }
    if ($district !== '') {
        $conditions[] = 'district = :district';
        $params[':district'] = $district;
    }
    if ($delivery !== '') {
        $conditions[] = 'delivery = :delivery';
        $params[':delivery'] = $delivery;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $sql = "SELECT pincode, officename, officetype, delivery, district, statename, regionname, divisionname, views_count FROM pincode_master {$where} ORDER BY statename, district, pincode";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="pincode-export-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'wb');
        fputcsv($out, ['Pincode', 'Office', 'Type', 'Delivery', 'District', 'State', 'Region', 'Division', 'Views']);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS pincode, officename, officetype, delivery, district, statename, views_count
            FROM pincode_master {$where}
            ORDER BY statename, district, pincode
            LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = (int) $pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
    $pages = (int) ceil($total / $perPage);

    ?>
    <div class="card">
        <h3>Search / View PIN Code Data</h3>
        <form method="GET" class="filter-form">
            <input type="hidden" name="action" value="search">
            <div class="filter-grid">
                <label>
                    Keyword
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Pincode, office, district">
                </label>
                <label>
                    State
                    <select name="state">
                        <option value="">All States</option>
                        <?php foreach ($states as $stateName): ?>
                            <option value="<?php echo htmlspecialchars($stateName); ?>" <?php echo $stateName === $state ? 'selected' : ''; ?>><?php echo htmlspecialchars($stateName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    District
                    <select name="district">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $districtName): ?>
                            <option value="<?php echo htmlspecialchars($districtName); ?>" <?php echo $districtName === $district ? 'selected' : ''; ?>><?php echo htmlspecialchars($districtName); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Delivery Status
                    <select name="delivery">
                        <option value="">All</option>
                        <option value="Delivery" <?php echo $delivery === 'Delivery' ? 'selected' : ''; ?>>Delivery</option>
                        <option value="Non Delivery" <?php echo $delivery === 'Non Delivery' ? 'selected' : ''; ?>>Non Delivery</option>
                    </select>
                </label>
            </div>
            <div class="filter-actions">
                <button type="submit" class="action-btn">Search</button>
                <a class="secondary-btn" href="?action=search">Reset</a>
                <button type="submit" name="export" value="csv" class="secondary-btn">Export CSV</button>
            </div>
        </form>

        <?php if ($total === 0): ?>
            <p class="muted">Use the filters above to find matching PIN codes.</p>
        <?php else: ?>
            <p class="muted">Showing <?php echo count($results); ?> of <?php echo number_format($total); ?> results.</p>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Pincode</th>
                            <th>Office</th>
                            <th>Type</th>
                            <th>Delivery</th>
                            <th>District</th>
                            <th>State</th>
                            <th>Views</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['pincode']); ?></td>
                                <td><?php echo htmlspecialchars($row['officename']); ?></td>
                                <td><?php echo htmlspecialchars($row['officetype']); ?></td>
                                <td><?php echo htmlspecialchars($row['delivery']); ?></td>
                                <td><?php echo htmlspecialchars($row['district']); ?></td>
                                <td><?php echo htmlspecialchars($row['statename']); ?></td>
                                <td><?php echo number_format((int) $row['views_count']); ?></td>
                                <td><a href="/pincode/<?php echo urlencode($row['pincode']); ?>" class="table-link" target="_blank">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <a class="<?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_page_url($i)); ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

function build_page_url(int $page): string
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

function render_post_generator(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_post'])) {
        if (!check_csrf($_POST['csrf_token'] ?? null)) {
            echo '<div class="error">Invalid CSRF token.</div>';
        } else {
            $templateId = (int) ($_POST['template_id'] ?? 0);
            $limit = max(1, min(200, (int) ($_POST['limit'] ?? 50)));

            $tplStmt = $pdo->prepare('SELECT * FROM content_templates WHERE id = ? LIMIT 1');
            $tplStmt->execute([$templateId]);
            $template = $tplStmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                echo '<div class="error">Template not found.</div>';
            } else {
                $rowsStmt = $pdo->prepare('SELECT pincode, officename, district, statename FROM pincode_master ORDER BY views_count DESC LIMIT ?');
                $rowsStmt->bindValue(1, $limit, PDO::PARAM_INT);
                $rowsStmt->execute();
                $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

                $created = 0;
                foreach ($rows as $row) {
                    $search = ['{{pincode}}', '{{officename}}', '{{district}}', '{{statename}}'];
                    $replace = [
                        $row['pincode'],
                        $row['officename'],
                        $row['district'],
                        $row['statename'],
                    ];
                    $title = str_replace($search, $replace, $template['title_template']);
                    $body = str_replace($search, $replace, $template['body_template']);

                    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', substr($title, 0, 80)));
                    $slug = trim($slug, '-') . '-' . $row['pincode'];

                    $insert = $pdo->prepare('INSERT INTO generated_posts (slug, title, body, pincode) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), body = VALUES(body), pincode = VALUES(pincode)');
                    $insert->execute([$slug, $title, $body, $row['pincode']]);
                    if ($insert->rowCount() > 0) {
                        $created++;
                    }
                }

                echo '<div class="success">Generated ' . number_format($created) . ' entries and stored them in generated_posts.</div>';
            }
        }
    }

    $templates = $pdo->query('SELECT id, slug, title_template FROM content_templates ORDER BY updated_at DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="card">
        <h3>Post Generator</h3>
        <?php if (!$templates): ?>
            <p class="error">No templates available. Create one in the Pincode Template editor first.</p>
        <?php else: ?>
            <form method="POST">
                <label>Choose Template
                    <select name="template_id" required>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo (int) $template['id']; ?>"><?php echo htmlspecialchars($template['slug'] . ' — ' . $template['title_template']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>How many top PIN codes?
                    <input type="number" name="limit" value="50" min="1" max="200">
                </label>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" name="generate_post" class="action-btn">Generate Posts</button>
            </form>
            <p class="muted">Generated entries are saved inside <code>generated_posts</code>. Integrate this table with your CMS or export as needed.</p>
        <?php endif; ?>
    </div>
    <?php
}

function render_sitemap_tool(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_sitemap'])) {
        if (!check_csrf($_POST['csrf_token'] ?? null)) {
            echo '<div class="error">Invalid CSRF token.</div>';
        } else {
            $limit = max(100, min(50000, (int) ($_POST['limit'] ?? 5000)));
            $siteUrl = rtrim(get_setting($pdo, 'site_url') ?: SITE_URL, '/');
            if ($siteUrl === '') {
                $siteUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            }

            $stmt = $pdo->prepare('SELECT pincode, updated_at FROM pincode_master WHERE is_active = 1 ORDER BY views_count DESC LIMIT ?');
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($rows as $row) {
                $loc = htmlspecialchars($siteUrl . '/pincode/' . urlencode($row['pincode']), ENT_XML1);
                $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
                $xml .= "  <url>\n    <loc>{$loc}</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>monthly</changefreq>\n    <priority>0.6</priority>\n  </url>\n";
            }
            $xml .= '</urlset>';

            $path = dirname(__DIR__) . '/sitemap.xml';
            try {
                file_put_contents($path, $xml);
                echo '<div class="success">Sitemap generated with ' . count($rows) . ' URLs at <code>' . htmlspecialchars($path) . '</code>.</div>';
            } catch (Throwable $exception) {
                echo '<div class="error">Unable to write sitemap: ' . htmlspecialchars($exception->getMessage()) . '</div>';
            }
        }
    }

    ?>
    <div class="card">
        <h3>Sitemap / Router Tool</h3>
        <form method="POST">
            <label>Maximum URLs
                <input type="number" name="limit" value="5000" min="100" max="50000">
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="generate_sitemap" class="action-btn">Generate sitemap.xml</button>
        </form>
        <p class="muted">The sitemap is saved to <code>public/sitemap.xml</code>. Adjust permissions on your server if the file cannot be written.</p>
    </div>
    <?php
}

function get_setting(PDO $pdo, string $name): ?string
{
    if (!isset($GLOBALS['app_settings']) || !array_key_exists($name, $GLOBALS['app_settings'])) {
        initialize_settings_cache($pdo, true);
    }

    return get_app_setting($name);
}

function render_pincode_template_editor(PDO $pdo): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pincode_template'])) {
        if (!check_csrf($_POST['csrf_token'] ?? null)) {
            echo '<div class="error">Invalid CSRF token.</div>';
        } else {
            $title = trim((string) ($_POST['title_template'] ?? ''));
            $body = trim((string) ($_POST['body_template'] ?? ''));

            if ($title === '' || $body === '') {
                echo '<div class="error">Both title and body templates are required.</div>';
            } else {
                $stmt = $pdo->prepare('INSERT INTO content_templates (slug, title_template, body_template) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE title_template = VALUES(title_template), body_template = VALUES(body_template), updated_at = CURRENT_TIMESTAMP');
                $stmt->execute(['pincode_page', $title, $body]);
                echo '<div class="success">Template saved successfully.</div>';
            }
        }
    }

    $stmt = $pdo->prepare('SELECT title_template, body_template FROM content_templates WHERE slug = ? LIMIT 1');
    $stmt->execute(['pincode_page']);
    $template = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'title_template' => 'PIN Code {{pincode}} — {{officename}}, {{district}}',
        'body_template' => '<h1>PIN Code {{pincode}}</h1><p>{{officename}} serves {{district}}, {{statename}}.</p>',
    ];
    ?>
    <div class="card">
        <h3>Pincode Page Template</h3>
        <form method="POST">
            <label>Title Template
                <input type="text" name="title_template" value="<?php echo htmlspecialchars($template['title_template']); ?>" required>
            </label>
            <label>Body Template
                <textarea name="body_template" rows="10" required><?php echo htmlspecialchars($template['body_template']); ?></textarea>
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="save_pincode_template" class="action-btn">Save Template</button>
        </form>
        <p class="muted">Available placeholders: <code>{{pincode}}</code>, <code>{{officename}}</code>, <code>{{district}}</code>, <code>{{statename}}</code>.</p>
    </div>
    <?php
}

function render_seo_and_marketing_suite(PDO $pdo): void
{
    $csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (
        isset($_POST['save_onpage_seo']) ||
        isset($_POST['save_offpage_seo']) ||
        isset($_POST['save_search_console']) ||
        isset($_POST['save_analytics']) ||
        isset($_POST['save_adsense']) ||
        isset($_POST['save_maps'])
    )) {
        if (!check_csrf($_POST['csrf_token'] ?? null)) {
            echo '<div class="error">Invalid CSRF token.</div>';
        } else {
            if (isset($_POST['save_onpage_seo'])) {
                $fields = [
                    'seo_default_title' => trim((string) ($_POST['seo_default_title'] ?? '')),
                    'seo_default_description' => trim((string) ($_POST['seo_default_description'] ?? '')),
                    'seo_default_keywords' => trim((string) ($_POST['seo_default_keywords'] ?? '')),
                    'seo_additional_head_html' => trim((string) ($_POST['seo_additional_head_html'] ?? '')),
                    'seo_structured_data' => trim((string) ($_POST['seo_structured_data'] ?? '')),
                ];
                foreach ($fields as $key => $value) {
                    persist_app_setting($pdo, $key, $value === '' ? null : $value);
                }
                initialize_settings_cache($pdo, true);
                echo '<div class="success">On-page SEO defaults saved successfully.</div>';
            } elseif (isset($_POST['save_offpage_seo'])) {
                $fields = [
                    'seo_focus_keywords' => trim((string) ($_POST['seo_focus_keywords'] ?? '')),
                    'seo_backlink_targets' => trim((string) ($_POST['seo_backlink_targets'] ?? '')),
                    'seo_content_calendar' => trim((string) ($_POST['seo_content_calendar'] ?? '')),
                    'seo_outreach_notes' => trim((string) ($_POST['seo_outreach_notes'] ?? '')),
                ];
                foreach ($fields as $key => $value) {
                    persist_app_setting($pdo, $key, $value === '' ? null : $value);
                }
                initialize_settings_cache($pdo, true);
                echo '<div class="success">Off-page SEO roadmap updated.</div>';
            } elseif (isset($_POST['save_search_console'])) {
                $value = trim((string) ($_POST['search_console_meta_tag'] ?? ''));
                persist_app_setting($pdo, 'search_console_meta_tag', $value === '' ? null : $value);
                initialize_settings_cache($pdo, true);
                echo '<div class="success">Search Console verification updated.</div>';
            } elseif (isset($_POST['save_analytics'])) {
                $fields = [
                    'analytics_measurement_id' => trim((string) ($_POST['analytics_measurement_id'] ?? '')),
                    'analytics_additional_script' => trim((string) ($_POST['analytics_additional_script'] ?? '')),
                ];
                foreach ($fields as $key => $value) {
                    persist_app_setting($pdo, $key, $value === '' ? null : $value);
                }
                initialize_settings_cache($pdo, true);
                echo '<div class="success">Analytics tracking preferences saved.</div>';
            } elseif (isset($_POST['save_adsense'])) {
                $fields = [
                    'adsense_publisher_id' => trim((string) ($_POST['adsense_publisher_id'] ?? '')),
                    'adsense_auto_ads_code' => trim((string) ($_POST['adsense_auto_ads_code'] ?? '')),
                    'adsense_top_banner' => trim((string) ($_POST['adsense_top_banner'] ?? '')),
                    'adsense_home_featured' => trim((string) ($_POST['adsense_home_featured'] ?? '')),
                    'adsense_incontent_unit' => trim((string) ($_POST['adsense_incontent_unit'] ?? '')),
                    'adsense_sidebar_unit' => trim((string) ($_POST['adsense_sidebar_unit'] ?? '')),
                    'adsense_footer_unit' => trim((string) ($_POST['adsense_footer_unit'] ?? '')),
                    'adsense_strategy_notes' => trim((string) ($_POST['adsense_strategy_notes'] ?? '')),
                ];
                foreach ($fields as $key => $value) {
                    persist_app_setting($pdo, $key, $value === '' ? null : $value);
                }
                initialize_settings_cache($pdo, true);
                echo '<div class="success">AdSense placements and strategy saved.</div>';
            } elseif (isset($_POST['save_maps'])) {
                $fields = [
                    'maps_api_key' => trim((string) ($_POST['maps_api_key'] ?? '')),
                    'maps_nearby_categories' => trim((string) ($_POST['maps_nearby_categories'] ?? '')),
                ];
                foreach ($fields as $key => $value) {
                    persist_app_setting($pdo, $key, $value === '' ? null : $value);
                }
                initialize_settings_cache($pdo, true);
                echo '<div class="success">Maps integration settings updated.</div>';
            }
        }
    }

    $onPage = get_app_settings([
        'seo_default_title',
        'seo_default_description',
        'seo_default_keywords',
        'seo_additional_head_html',
        'seo_structured_data',
    ], '');
    $offPage = get_app_settings([
        'seo_focus_keywords',
        'seo_backlink_targets',
        'seo_content_calendar',
        'seo_outreach_notes',
    ], '');
    $searchConsole = get_app_settings(['search_console_meta_tag'], '');
    $analytics = get_app_settings([
        'analytics_measurement_id',
        'analytics_additional_script',
    ], '');
    $adsense = get_app_settings([
        'adsense_publisher_id',
        'adsense_auto_ads_code',
        'adsense_top_banner',
        'adsense_home_featured',
        'adsense_incontent_unit',
        'adsense_sidebar_unit',
        'adsense_footer_unit',
        'adsense_strategy_notes',
    ], '');
    $maps = get_app_settings([
        'maps_api_key',
        'maps_nearby_categories',
    ], '');

    ?>
    <div class="card">
        <h3>On-Page SEO Defaults</h3>
        <form method="POST">
            <label>Default Title Structure
                <input type="text" name="seo_default_title" value="<?php echo htmlspecialchars($onPage['seo_default_title'] ?? '', ENT_QUOTES); ?>" placeholder="India PIN Code Directory - Complete Postal Code Information">
            </label>
            <label>Default Meta Description
                <textarea name="seo_default_description" rows="3" placeholder="Primary description for search snippets."><?php echo htmlspecialchars($onPage['seo_default_description'] ?? ''); ?></textarea>
            </label>
            <label>Default Meta Keywords
                <input type="text" name="seo_default_keywords" value="<?php echo htmlspecialchars($onPage['seo_default_keywords'] ?? '', ENT_QUOTES); ?>" placeholder="pin code, postal code, india post">
            </label>
            <label>Extra &lt;head&gt; HTML (meta tags, link tags)
                <textarea name="seo_additional_head_html" rows="4" placeholder="Custom meta tags, hreflang, etc."><?php echo htmlspecialchars($onPage['seo_additional_head_html'] ?? ''); ?></textarea>
            </label>
            <label>Structured Data (JSON-LD)
                <textarea name="seo_structured_data" rows="6" placeholder="Paste valid JSON-LD script without wrapping tags."><?php echo htmlspecialchars($onPage['seo_structured_data'] ?? ''); ?></textarea>
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <button type="submit" name="save_onpage_seo" class="action-btn">Save On-Page SEO</button>
        </form>
        <p class="muted">Defaults are applied automatically when page-specific SEO values are not provided.</p>
    </div>

    <div class="card">
        <h3>Off-Page SEO &amp; Outreach Planner</h3>
        <form method="POST">
            <label>Primary Keyword Themes
                <textarea name="seo_focus_keywords" rows="3" placeholder="List 5-10 focus keyword clusters."><?php echo htmlspecialchars($offPage['seo_focus_keywords'] ?? ''); ?></textarea>
            </label>
            <label>Backlink / Partnership Targets
                <textarea name="seo_backlink_targets" rows="4" placeholder="Curate outreach targets, directories, or media."><?php echo htmlspecialchars($offPage['seo_backlink_targets'] ?? ''); ?></textarea>
            </label>
            <label>Content Calendar Highlights
                <textarea name="seo_content_calendar" rows="4" placeholder="Outline upcoming blog, landing page, or PR ideas."><?php echo htmlspecialchars($offPage['seo_content_calendar'] ?? ''); ?></textarea>
            </label>
            <label>Outreach &amp; Authority Notes
                <textarea name="seo_outreach_notes" rows="4" placeholder="Track influencer pitches, citations, and follow-ups."><?php echo htmlspecialchars($offPage['seo_outreach_notes'] ?? ''); ?></textarea>
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <button type="submit" name="save_offpage_seo" class="action-btn">Save Off-Page Plan</button>
        </form>
        <p class="muted">Keep your off-site promotion roadmap in one place for quick reference before campaign launches.</p>
    </div>

    <div class="card">
        <h3>Search Console Verification</h3>
        <form method="POST">
            <label>Verification Meta Tag or Code
                <textarea name="search_console_meta_tag" rows="3" placeholder="Paste the meta tag or verification token from Google Search Console."><?php echo htmlspecialchars($searchConsole['search_console_meta_tag'] ?? ''); ?></textarea>
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <button type="submit" name="save_search_console" class="action-btn">Update Verification</button>
        </form>
        <p class="muted">Provide the exact meta tag or the verification code. The head section will render it automatically.</p>
    </div>

    <div class="card">
        <h3>Analytics &amp; Tracking</h3>
        <form method="POST">
            <label>Google Analytics / GA4 Measurement ID
                <input type="text" name="analytics_measurement_id" value="<?php echo htmlspecialchars($analytics['analytics_measurement_id'] ?? '', ENT_QUOTES); ?>" placeholder="G-XXXXXXXXXX">
            </label>
            <label>Additional Tracking Script
                <textarea name="analytics_additional_script" rows="4" placeholder="Custom analytics, pixels, or events."><?php echo htmlspecialchars($analytics['analytics_additional_script'] ?? ''); ?></textarea>
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <button type="submit" name="save_analytics" class="action-btn">Save Analytics Settings</button>
        </form>
        <p class="muted">Add extra scripts such as Hotjar, Meta Pixel, or event tracking snippets.</p>
    </div>

    <div class="card">
        <h3>Maps &amp; Nearby Places</h3>
        <form method="POST">
            <label>Google Maps API Key (optional)
                <input type="text" name="maps_api_key" value="<?php echo htmlspecialchars($maps['maps_api_key'] ?? '', ENT_QUOTES); ?>" placeholder="AIza...">
            </label>
            <label>Nearby Service Categories
                <textarea name="maps_nearby_categories" rows="4" placeholder="One category per line, e.g. Hospital, ATM, Bank."><?php echo htmlspecialchars($maps['maps_nearby_categories'] ?? ''); ?></textarea>
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <button type="submit" name="save_maps" class="action-btn">Save Maps Settings</button>
        </form>
        <p class="muted">Provide a Google Maps Embed API key for dynamic maps. The categories generate quick search links for nearby government offices, ATMs, hospitals, and more.</p>
    </div>

    <div class="card">
        <h3>AdSense &amp; Monetisation Strategy</h3>
        <form method="POST">
            <label>AdSense Publisher ID / Client
                <input type="text" name="adsense_publisher_id" value="<?php echo htmlspecialchars($adsense['adsense_publisher_id'] ?? '', ENT_QUOTES); ?>" placeholder="ca-pub-xxxxxxxxxxxxxxxx">
            </label>
            <label>Auto Ads Script Override
                <textarea name="adsense_auto_ads_code" rows="4" placeholder="Paste full auto ads script if you prefer custom configuration."><?php echo htmlspecialchars($adsense['adsense_auto_ads_code'] ?? ''); ?></textarea>
            </label>
            <div class="filter-grid">
                <label>Header / Top Banner Slot
                    <textarea name="adsense_top_banner" rows="4" placeholder="Responsive ad code displayed beneath the header."><?php echo htmlspecialchars($adsense['adsense_top_banner'] ?? ''); ?></textarea>
                </label>
                <label>Homepage Feature Slot
                    <textarea name="adsense_home_featured" rows="4" placeholder="Ad code displayed after the homepage hero/search."><?php echo htmlspecialchars($adsense['adsense_home_featured'] ?? ''); ?></textarea>
                </label>
            </div>
            <div class="filter-grid">
                <label>In-Content Unit
                    <textarea name="adsense_incontent_unit" rows="4" placeholder="Ad inserted inside content blocks."><?php echo htmlspecialchars($adsense['adsense_incontent_unit'] ?? ''); ?></textarea>
                </label>
                <label>Sidebar / Detail Page Slot
                    <textarea name="adsense_sidebar_unit" rows="4" placeholder="Ad displayed alongside detail pages."><?php echo htmlspecialchars($adsense['adsense_sidebar_unit'] ?? ''); ?></textarea>
                </label>
            </div>
            <label>Footer / Wrap-Up Placement
                <textarea name="adsense_footer_unit" rows="4" placeholder="Ad code rendered above the global footer."><?php echo htmlspecialchars($adsense['adsense_footer_unit'] ?? ''); ?></textarea>
            </label>
            <label>Strategy Notes &amp; Experiments
                <textarea name="adsense_strategy_notes" rows="4" placeholder="Document A/B tests, viewability goals, and optimisation takeaways."><?php echo htmlspecialchars($adsense['adsense_strategy_notes'] ?? ''); ?></textarea>
            </label>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <button type="submit" name="save_adsense" class="action-btn">Save Monetisation Settings</button>
        </form>
        <p class="muted">Configure where ads render on the site and keep track of revenue ideas to improve AdSense RPM.</p>
    </div>
    <?php
}

// -------------------------------------------------------------------------
// Authentication handling
// -------------------------------------------------------------------------

enforce_session_lifetime();

if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout']) && check_csrf($_POST['csrf_token'] ?? null)) || (isset($_GET['logout']) && $_GET['logout'] == 1)) {
    session_unset();
    session_destroy();
    header('Location: /admin');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!check_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid CSRF token.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
            header('Location: /admin');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}

$is_logged_in = isset($_SESSION['admin_id']);
$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$allowed_actions = ['import', 'search', 'post_generator', 'sitemap', 'template_pincode', 'seo_tools'];
$admin_action_output = '';

if ($action !== '') {
    if (!$is_logged_in) {
        $admin_action_output = '<div class="error">Please login to use admin tools.</div>';
    } elseif (!in_array($action, $allowed_actions, true)) {
        $admin_action_output = '<div class="error">Invalid action.</div>';
    } else {
        ob_start();
        switch ($action) {
            case 'import':
                render_csv_import_form_and_process($pdo);
                break;
            case 'search':
                render_data_search_tool($pdo);
                break;
            case 'post_generator':
                render_post_generator($pdo);
                break;
            case 'sitemap':
                render_sitemap_tool($pdo);
                break;
            case 'template_pincode':
                render_pincode_template_editor($pdo);
                break;
            case 'seo_tools':
                render_seo_and_marketing_suite($pdo);
                break;
        }
        $admin_action_output = ob_get_clean();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Panel - <?php echo defined('SITE_NAME') ? SITE_NAME : 'PIN Code Website'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height: 100vh; }
        .login-container { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .login-box { background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,.3); width: 100%; max-width: 420px; }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h1 { color: #667eea; font-size: 28px; margin-bottom: 10px; }
        .login-header p { color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { width: 100%; padding: 14px; background: #667eea; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all .3s; }
        .btn:hover { background: #5568d3; transform: translateY(-2px); }
        .error { background: #fee; color: #c00; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .success { background: #e6ffed; color: #20603b; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .notice { background: #fff3cd; color: #856404; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .muted { color: #666; font-size: 14px; }
        .secondary-btn { display: inline-block; padding: 10px 20px; border-radius: 6px; border: 1px solid #667eea; color: #667eea; text-decoration: none; background: #fff; transition: all .2s; }
        .secondary-btn:hover { background: #eef1ff; }
        .dashboard { min-height: 100vh; background: #f5f5f5; }
        .header { background: #fff; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,.1); display: flex; justify-content: space-between; align-items: center; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #667eea; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout-btn { padding: 8px 20px; background: #dc3545; color: #fff; border-radius: 5px; font-weight: 600; border: none; cursor: pointer; }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px 20px; }
        .welcome { background: #fff; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(250px,1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
        .stat-icon { font-size: 40px; margin-bottom: 15px; }
        .stat-number { font-size: 32px; font-weight: bold; color: #667eea; margin-bottom: 5px; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(300px,1fr)); gap: 20px; }
        .action-card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); text-align: center; }
        .action-icon { font-size: 48px; margin-bottom: 15px; }
        .action-title { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
        .action-desc { color: #666; margin-bottom: 15px; }
        .action-btn { display: inline-block; padding: 10px 25px; background: #667eea; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; border: none; cursor: pointer; }
        .action-btn:hover { background: #5568d3; }
        .card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-bottom: 20px; }
        .filter-form { display: flex; flex-direction: column; gap: 20px; margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 16px; }
        .filter-form label { display: flex; flex-direction: column; font-weight: 600; color: #333; gap: 6px; }
        .filter-form input[type="text"], .filter-form select, .filter-form input[type="number"], .filter-form textarea { padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
        .filter-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 12px 10px; border-bottom: 1px solid #eee; text-align: left; }
        table th { background: #f7f8ff; }
        .table-link { color: #667eea; text-decoration: none; }
        .pagination { margin-top: 20px; display: flex; gap: 8px; flex-wrap: wrap; }
        .pagination a { padding: 8px 12px; border-radius: 6px; border: 1px solid #667eea; color: #667eea; text-decoration: none; }
        .pagination a.active { background: #667eea; color: #fff; }
        @media (max-width: 768px) {
            .header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .user-info { flex-wrap: wrap; }
            .actions-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php if (!$is_logged_in): ?>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🔐 Admin Login</h1>
                <p>PIN Code Website Administration</p>
            </div>

            <?php render_flash_message(); ?>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" name="login" class="btn">Login</button>
            </form>

            <div class="back-link" style="text-align:center;margin-top:20px;">
                <a href="/" style="color:#667eea;text-decoration:none;">← Back to Website</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="dashboard">
        <div class="header">
            <div class="header-left">
                <div class="logo">🏛️ Admin Panel</div>
                <a href="/" style="color:#666;text-decoration:none;">View Website →</a>
            </div>
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="submit" name="logout" class="logout-btn">Logout</button>
                </form>
                <a href="?logout=1" style="margin-left:10px;color:#999;text-decoration:none;font-size:12px;">(logout)</a>
            </div>
        </div>

        <div class="container">
            <div class="welcome">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                <p>Manage your PIN code website from this dashboard.</p>
                <?php render_flash_message(); ?>
            </div>

            <?php
            if ($admin_action_output !== '') {
                echo '<div style="margin-bottom:20px;">' . $admin_action_output . '</div>';
            }

            $total_pincodes = (int) $pdo->query('SELECT COUNT(*) FROM pincode_master')->fetchColumn();
            $total_states = (int) $pdo->query('SELECT COUNT(DISTINCT statename) FROM pincode_master')->fetchColumn();
            $total_districts = (int) $pdo->query('SELECT COUNT(DISTINCT district) FROM pincode_master')->fetchColumn();
            $total_views = (int) $pdo->query('SELECT COALESCE(SUM(views_count),0) FROM pincode_master')->fetchColumn();
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📮</div>
                    <div class="stat-number"><?php echo number_format($total_pincodes); ?></div>
                    <div class="muted">Total PIN Codes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🗺️</div>
                    <div class="stat-number"><?php echo number_format($total_states); ?></div>
                    <div class="muted">States</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏘️</div>
                    <div class="stat-number"><?php echo number_format($total_districts); ?></div>
                    <div class="muted">Districts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-number"><?php echo number_format($total_views); ?></div>
                    <div class="muted">Total Views</div>
                </div>
            </div>

            <h3 style="margin-bottom:20px;color:#333">Quick Actions</h3>
            <div class="actions-grid">
                <div class="action-card">
                    <div class="action-icon">📥</div>
                    <div class="action-title">Import Data</div>
                    <div class="action-desc">Import PIN codes from CSV file</div>
                    <a href="?action=import" class="action-btn">Import CSV</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">🔎</div>
                    <div class="action-title">Search / View Data</div>
                    <div class="action-desc">Search, filter, export PIN codes</div>
                    <a href="?action=search" class="action-btn">Open Search Tool</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">✍️</div>
                    <div class="action-title">Post Generator</div>
                    <div class="action-desc">Generate templated posts/pages</div>
                    <a href="?action=post_generator" class="action-btn">Open Post Generator</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">🗺️</div>
                    <div class="action-title">Sitemap / Router</div>
                    <div class="action-desc">Generate sitemap routes</div>
                    <a href="?action=sitemap" class="action-btn">Open Sitemap Tool</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">📄</div>
                    <div class="action-title">Pincode Template</div>
                    <div class="action-desc">Edit pincode page template</div>
                    <a href="?action=template_pincode" class="action-btn">Edit Template</a>
                </div>
                <div class="action-card">
                    <div class="action-icon">📈</div>
                    <div class="action-title">SEO &amp; Monetisation</div>
                    <div class="action-desc">SEO defaults, verification, ads</div>
                    <a href="?action=seo_tools" class="action-btn">Open SEO Suite</a>
                </div>
            </div>

            <div style="background:#fff;padding:25px;border-radius:10px;margin-top:30px;">
                <h3 style="margin-bottom:15px;">📚 Quick Guide</h3>
                <ol style="line-height:2;color:#666">
                    <li><strong>Import PIN Code Data:</strong> Use the Import CSV tool to seed or update records.</li>
                    <li><strong>Review Data:</strong> Use the Search tool to filter and export results.</li>
                    <li><strong>Customize Content:</strong> Edit the PIN code template to change generated pages.</li>
                    <li><strong>Generate Content:</strong> Use the Post Generator to populate <code>generated_posts</code>.</li>
                    <li><strong>SEO:</strong> Generate an updated sitemap after imports.</li>
                </ol>
            </div>

            <div style="background:#fff3cd;padding:20px;border-radius:10px;margin-top:20px;border-left:4px solid #ffc107;">
                <h4 style="margin-bottom:10px;">⚠️ Security Notes</h4>
                <ul style="line-height:1.8;color:#856404">
                    <li>Update the default admin password immediately after the first login.</li>
                    <li>Restrict access to the admin panel via IP whitelisting or authentication middleware.</li>
                    <li>Keep regular database backups, especially before imports.</li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
