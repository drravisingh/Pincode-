<?php
/**
 * scan_server.php
 * Single-file diagnostic script to inspect folder/file structure, permissions, .htaccess, and try to read common Apache error logs.
 *
 * SECURITY: Delete this file after use.
 *
 * Usage:
 *  - Upload to your site's document root (public_html)
 *  - Visit: https://your-domain.com/scan_server.php
 *  - Optional query params:
 *      ?path=admin            (relative to script location or absolute path)
 *      ?depth=4              (max recursion depth, default 5)
 *      ?download=json        (to download JSON report)
 *
 * IMPORTANT: This script will NOT print contents of files named config.php or files matching "*config*.php".
 */

set_time_limit(120);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ====== Helpers ======
function octal_perms($file) {
    return substr(sprintf('%o', fileperms($file)), -4);
}

function owner_name($file) {
    if (function_exists('posix_getpwuid')) {
        $info = @posix_getpwuid(fileowner($file));
        return $info ? $info['name'] : fileowner($file);
    }
    return fileowner($file);
}

function safe_read_file($path, $maxBytes = 2000) {
    $basename = basename($path);
    $lower = strtolower($basename);
    // Never return contents of config-like files
    if (preg_match('/config/i', $basename) || $lower === 'config.php') {
        return "[skipped: sensitive filename]";
    }
    if (!is_readable($path)) return "[unreadable]";
    $contents = @file_get_contents($path, false, null, 0, $maxBytes);
    if ($contents === false) return "[failed to read]";
    // Trim and limit
    $contents = trim($contents);
    if (strlen($contents) > $maxBytes - 20) {
        $contents = substr($contents, 0, $maxBytes - 20) . "\n...[truncated]";
    }
    return htmlspecialchars($contents);
}

// ====== Main scanner ======
$requested = isset($_GET['path']) ? $_GET['path'] : '.';
$depth_limit = isset($_GET['depth']) ? intval($_GET['depth']) : 5;
$base = $requested;

// Normalize: allow relative to script dir
if (!preg_match('#^(\/|[A-Za-z]:\\\\)#', $base)) {
    $base = __DIR__ . DIRECTORY_SEPARATOR . ltrim($base, '/\\');
}

$report = [
    'scanned_path' => $base,
    'exists' => file_exists($base),
    'is_dir' => is_dir($base) ? true : false,
    'generated_at' => date('Y-m-d H:i:s'),
    'tree' => [],
    'htaccess_files' => [],
    'index_presence' => [],
    'common_logs' => [],
];

// If not exists -> bail
if (!file_exists($base)) {
    echo "<h2>Path not found: " . htmlspecialchars($base) . "</h2>";
    echo "<p>Ensure the path is correct and accessible by the webserver user.</p>";
    exit;
}

// Recursive scan (depth-limited)
function scan_dir($path, $depth, $limit, &$report) {
    $items = @scandir($path);
    if ($items === false) return [];
    $out = [];
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') continue;
        $full = $path . DIRECTORY_SEPARATOR . $it;
        $is_dir = is_dir($full);
        $node = [
            'name' => $it,
            'path' => $full,
            'is_dir' => $is_dir,
            'size' => $is_dir ? null : @filesize($full),
            'perms' => @file_exists($full) ? octal_perms($full) : null,
            'readable' => is_readable($full),
            'writable' => is_writable($full),
            'executable' => is_executable($full),
            'owner' => @owner_name($full),
            'modified' => date('Y-m-d H:i:s', @filemtime($full)),
        ];

        // If file is .htaccess, read safe contents
        if (!$is_dir && strtolower($it) === '.htaccess') {
            $node['htaccess_content'] = safe_read_file($full, 4000);
            $report['htaccess_files'][] = $full;
        }

        // Detect index files in directories
        if ($is_dir) {
            $indexFound = false;
            foreach (['index.php','index.html','index.htm','default.php'] as $idx) {
                if (file_exists($full . DIRECTORY_SEPARATOR . $idx)) {
                    $report['index_presence'][$full] = $idx;
                    $indexFound = true;
                    break;
                }
            }
            $node['has_index'] = $indexFound;
        } else {
            // If file is config-like, mark as sensitive (do not dump)
            if (preg_match('/config/i', $it) || strtolower($it) === 'config.php') {
                $node['sensitive'] = true;
            } else {
                $node['sensitive'] = false;
            }
        }

        // If directory and depth allows, recurse
        if ($is_dir && $depth < $limit) {
            $node['children'] = scan_dir($full, $depth+1, $limit, $report);
        }
        $out[] = $node;
    }
    return $out;
}

$report['tree'] = scan_dir($base, 0, $depth_limit, $report);

// Try reading common Apache error log files (best-effort)
$common_logs = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/apache2/error_log',
    '/usr/local/apache/logs/error_log',
    __DIR__ . DIRECTORY_SEPARATOR . 'error_log', // some PHP setups write here
];

foreach ($common_logs as $log) {
    if (is_readable($log) && is_file($log)) {
        $lines = @file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            $last = array_slice($lines, -40);
            $report['common_logs'][$log] = $last;
        } else {
            $report['common_logs'][$log] = "[exists but failed to read]";
        }
    } else {
        $report['common_logs'][$log] = "[not readable or not present]";
    }
}

// Also try user-specific logs inside home dirs (best-effort)
$home_guess = dirname(__DIR__); // one level up from webroot guess
$possible = [
    $home_guess . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error_log',
    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error_log',
];
foreach ($possible as $p) {
    $p = realpath($p) ?: $p;
    if (is_readable($p) && is_file($p)) {
        $report['common_logs'][$p] = array_slice(@file($p), -40);
    } else {
        $report['common_logs'][$p] = "[not readable or not present]";
    }
}

// Prepare HTML output
function render_table($report) {
    echo "<style>body{font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:16px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:6px;text-align:left;}th{background:#f2f2f2;}</style>";
    echo "<h2>Server scan report</h2>";
    echo "<p><strong>Scanned:</strong> " . htmlspecialchars($report['scanned_path']) . " &nbsp; | &nbsp; Generated: " . htmlspecialchars($report['generated_at']) . "</p>";

    echo "<h3>Index presence (folders with detected index file)</h3>";
    if (count($report['index_presence'])===0) {
        echo "<p><em>None detected within scanned depth.</em></p>";
    } else {
        echo "<table><tr><th>Folder</th><th>Detected index</th></tr>";
        foreach ($report['index_presence'] as $f => $idx) {
            echo "<tr><td>" . htmlspecialchars($f) . "</td><td>" . htmlspecialchars($idx) . "</td></tr>";
        }
        echo "</table>";
    }

    echo "<h3>.htaccess files found</h3>";
    if (count($report['htaccess_files'])===0) {
        echo "<p><em>No .htaccess files found in scanned tree</em></p>";
    } else {
        foreach ($report['htaccess_files'] as $ht) {
            echo "<h4>" . htmlspecialchars($ht) . "</h4>";
            echo "<pre style='background:#111;color:#fff;padding:8px;max-height:240px;overflow:auto;'>" . safe_read_file($ht, 8000) . "</pre>";
        }
    }

    echo "<h3>Directory/File tree (first-level view)</h3>";
    echo "<table><tr><th>Name</th><th>Type</th><th>Perms</th><th>Owner</th><th>Readable</th><th>Writable</th><th>Modified</th></tr>";
    foreach ($report['tree'] as $node) {
        echo "<tr>";
        echo "<td style='max-width:420px;word-break:break-word;'>" . htmlspecialchars($node['path']) . "</td>";
        echo "<td>" . ($node['is_dir'] ? 'dir' : 'file') . "</td>";
        echo "<td>" . htmlspecialchars($node['perms']) . "</td>";
        echo "<td>" . htmlspecialchars($node['owner']) . "</td>";
        echo "<td>" . ($node['readable'] ? 'yes' : 'no') . "</td>";
        echo "<td>" . ($node['writable'] ? 'yes' : 'no') . "</td>";
        echo "<td>" . htmlspecialchars($node['modified']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Common Apache/PHP error log probes (best-effort)</h3>";
    foreach ($report['common_logs'] as $path => $val) {
        echo "<h4>" . htmlspecialchars($path) . "</h4>";
        if (is_array($val)) {
            echo "<pre style='background:#eee;padding:8px;max-height:300px;overflow:auto;'>" . htmlspecialchars(implode("\n", array_slice($val, -40))) . "</pre>";
        } else {
            echo "<p><em>" . htmlspecialchars($val) . "</em></p>";
        }
    }

    echo "<hr><p style='color:#a00;'>Security note: Delete this file from server after use. This report may reveal sensitive info.</p>";
}

// If download requested, supply JSON
if (isset($_GET['download']) && $_GET['download'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit;
}

// Render HTML
render_table($report);

// Show quick usage hints for next steps
echo "<h3>Next steps (what to copy & paste here)</h3>";
echo "<ol>
<li>If you see any folder under <code>/public_html/admin</code> with <strong>no index file</strong>, create an <code>index.php</code> redirecting to login or protect the folder.</li>
<li>Share the <strong>table rows</strong> for the <code>/public_html/admin</code> entry here (copy path, perms, owner, readable/writable).</li>
<li>If any .htaccess content shows <code>Require all denied</code> or <code>Deny from all</code>, paste that block here.</li>
<li>If any of the common logs returned readable content, paste the last 10 lines shown here.</li>
</ol>";
?>
