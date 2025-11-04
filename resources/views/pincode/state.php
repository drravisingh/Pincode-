<style>
    .state-back { display: inline-block; padding: 10px 20px; background: #f0f0f0; text-decoration: none; color: #333; border-radius: 5px; margin-bottom: 20px; }
    .state-back:hover { background: #e0e0e0; }
    .state-grid { display: grid; gap: 15px; }
    .state-card-item { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; }
    .state-card-item h3 { margin-bottom: 10px; }
    .state-card-item p { color: #666; }
    .state-link { display: inline-block; margin-top: 10px; padding: 8px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 20px; }
    .state-link:hover { background: #5568d3; }
</style>

<div class="content container">
    <a href="/" class="state-back">← Back</a>

    <h1 style="color: #667eea;"><?php echo htmlspecialchars($state_name); ?> PIN Codes</h1>

    <?php if (!empty($results)): ?>
        <p style="margin: 10px 0 30px;">Found <?php echo count($results); ?> PIN code(s)</p>

        <div class="state-grid">
            <?php foreach ($results as $row): ?>
                <div class="state-card-item">
                    <h3><?php echo htmlspecialchars($row['officename']); ?></h3>
                    <p>📍 <?php echo htmlspecialchars($row['district']); ?></p>
                    <a href="/<?php echo $row['pincode']; ?>" class="state-link">PIN: <?php echo $row['pincode']; ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="margin-top: 20px;">No PIN codes found.</p>
    <?php endif; ?>
</div>
