<style>
    .search-back { display: inline-block; padding: 10px 20px; background: #f0f0f0; text-decoration: none; color: #333; border-radius: 5px; margin-bottom: 20px; }
    .search-back:hover { background: #e0e0e0; }
    .search-result { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #667eea; }
    .search-result h3 { margin-bottom: 10px; }
    .search-result p { color: #666; }
    .search-link { display: inline-block; margin-top: 10px; padding: 8px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 20px; }
    .search-link:hover { background: #5568d3; }
</style>

<div class="content container">
    <a href="/" class="search-back">← Back</a>

    <h1 style="color: #667eea;">Search Results</h1>
    <p style="color: #666; margin: 10px 0 30px;">Results for "<?php echo htmlspecialchars($query); ?>"</p>

    <?php if (!empty($results)): ?>
        <p style="margin-bottom: 20px;">Found <?php echo count($results); ?> result(s)</p>

        <?php foreach ($results as $row): ?>
            <div class="search-result">
                <h3><?php echo htmlspecialchars($row['officename']); ?></h3>
                <p>📍 <?php echo htmlspecialchars($row['district']); ?>, <?php echo htmlspecialchars($row['statename']); ?></p>
                <a href="/<?php echo $row['pincode']; ?>" class="search-link">PIN: <?php echo $row['pincode']; ?></a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px;">
            <h2>No results found</h2>
        </div>
    <?php endif; ?>
</div>
