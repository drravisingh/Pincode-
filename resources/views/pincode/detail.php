<style>
    .content.container { margin-top: 30px; }
    .back-link {
        display: inline-block;
        padding: 10px 20px;
        background: #f0f0f0;
        text-decoration: none;
        color: #333;
        border-radius: 5px;
        margin-bottom: 20px;
        transition: background 0.3s;
    }
    .back-link:hover { background: #e0e0e0; }
    .page-title { color: #667eea; margin-bottom: 10px; }
    .page-subtitle { font-size: 20px; color: #666; margin-bottom: 30px; }
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .detail-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }
    .detail-label { color: #666; margin-bottom: 5px; }
    .detail-value { font-size: 18px; font-weight: 600; }
    .nearby-section { margin-top: 40px; }
    .nearby-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    .nearby-card {
        display: block;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #eee;
        text-decoration: none;
        color: inherit;
        transition: all 0.3s;
    }
    .nearby-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    .nearby-pin { display: inline-block; margin-top: 10px; font-weight: 600; color: #667eea; }
</style>

<div class="content container">
    <a href="/" class="back-link">← Back</a>

    <h1 class="page-title">PIN Code: <?php echo $pincode; ?></h1>
    <p class="page-subtitle">
        <?php echo htmlspecialchars($data['officename']); ?>,
        <?php echo htmlspecialchars($data['district']); ?>,
        <?php echo htmlspecialchars($data['statename']); ?>
    </p>

    <div class="detail-grid">
        <?php if (!empty($GLOBALS['adsense_placements']['incontent'])): ?>
        <div class="ad-slot ad-incontent" style="grid-column: 1 / -1;">
            <?php renderAdPlacement('incontent'); ?>
        </div>
        <?php endif; ?>
        <div class="detail-card">
            <div class="detail-label">Post Office Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['officename']); ?></div>
        </div>
        <div class="detail-card">
            <div class="detail-label">PIN Code</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['pincode']); ?></div>
        </div>
        <div class="detail-card">
            <div class="detail-label">Office Type</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['officetype'] ?? 'N/A'); ?></div>
        </div>
        <div class="detail-card">
            <div class="detail-label">Delivery Status</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['delivery'] ?? 'Available'); ?></div>
        </div>
        <div class="detail-card">
            <div class="detail-label">District</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['district']); ?></div>
        </div>
        <div class="detail-card">
            <div class="detail-label">State</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['statename']); ?></div>
        </div>
        <?php if (!empty($data['divisionname'])): ?>
        <div class="detail-card">
            <div class="detail-label">Postal Division</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['divisionname']); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($data['regionname'])): ?>
        <div class="detail-card">
            <div class="detail-label">Postal Region</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['regionname']); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($data['circlename'])): ?>
        <div class="detail-card">
            <div class="detail-label">Postal Circle</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['circlename']); ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($data['contact'])): ?>
        <div class="detail-card">
            <div class="detail-label">Contact</div>
            <div class="detail-value"><?php echo htmlspecialchars($data['contact']); ?></div>
        </div>
        <?php endif; ?>
        <?php
            $updatedLabel = 'N/A';
            if (!empty($data['updated_at'])) {
                $timestamp = strtotime($data['updated_at']);
                if ($timestamp) {
                    $updatedLabel = date('d M Y', $timestamp);
                }
            }
        ?>
        <div class="detail-card">
            <div class="detail-label">Last Updated</div>
            <div class="detail-value"><?php echo htmlspecialchars($updatedLabel); ?></div>
        </div>
        <div class="detail-card">
            <div class="detail-label">Views</div>
            <div class="detail-value"><?php echo number_format((int) $data['views_count']); ?></div>
        </div>
    </div>

    <?php if (!empty($data['remarks'])): ?>
    <div class="detail-card" style="margin-top: 20px;">
        <div class="detail-label">Remarks</div>
        <div class="detail-value" style="font-size: 16px; font-weight: 400; line-height: 1.6;">
            <?php echo nl2br(htmlspecialchars($data['remarks'])); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($map_embed_url)): ?>
    <section class="nearby-section">
        <h2>Location Map</h2>
        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;box-shadow:0 10px 30px rgba(102,126,234,0.2);">
            <iframe
                src="<?php echo htmlspecialchars($map_embed_url); ?>"
                width="600"
                height="450"
                style="border:0; position:absolute; top:0; left:0; width:100%; height:100%;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <?php if (!empty($maps_categories) && !empty($map_query_url)): ?>
        <div style="margin-top:20px;display:flex;flex-wrap:wrap;gap:10px;">
            <?php foreach ($maps_categories as $category): ?>
                <a class="nearby-card" style="display:inline-flex;align-items:center;gap:10px;padding:12px 18px;border-radius:30px;border:1px solid #dde1ff;background:#f8f9ff;font-weight:600;"
                   href="<?php echo htmlspecialchars(sprintf($map_query_url, rawurlencode($category))); ?>" target="_blank" rel="noopener">
                    <?php echo htmlspecialchars($category); ?> near <?php echo htmlspecialchars($data['officename']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if (!empty($GLOBALS['adsense_placements']['sidebar'])): ?>
    <div class="ad-slot ad-sidebar">
        <?php renderAdPlacement('sidebar'); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($other_offices)): ?>
    <section class="nearby-section">
        <h2>More Post Offices with PIN <?php echo htmlspecialchars($pincode); ?></h2>
        <div class="nearby-grid">
            <?php foreach ($other_offices as $office): ?>
                <a href="/<?php echo htmlspecialchars($office['slug'] ?: $office['pincode']); ?>" class="nearby-card">
                    <h3><?php echo htmlspecialchars($office['officename']); ?></h3>
                    <p><?php echo htmlspecialchars($office['district']); ?>, <?php echo htmlspecialchars($office['statename']); ?></p>
                    <span class="nearby-pin">PIN: <?php echo htmlspecialchars($office['pincode']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($nearby_offices)): ?>
    <section class="nearby-section">
        <h2>Nearby PIN Codes</h2>
        <div class="nearby-grid">
            <?php foreach ($nearby_offices as $office): ?>
                <a href="/<?php echo htmlspecialchars($office['slug'] ?: $office['pincode']); ?>" class="nearby-card">
                    <h3><?php echo htmlspecialchars($office['officename']); ?></h3>
                    <p><?php echo htmlspecialchars($office['district']); ?>, <?php echo htmlspecialchars($office['statename']); ?></p>
                    <span class="nearby-pin">PIN: <?php echo htmlspecialchars($office['pincode']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
