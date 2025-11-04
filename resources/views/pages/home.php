<section class="hero">
    <div class="container">
        <h1>🏛️ India PIN Code Directory</h1>
        <p>Find Complete Postal Information for Any Location in India</p>
    </div>
</section>

<section class="container">
    <div class="search-section">
        <div class="search-box">
            <form action="/search" method="GET" class="search-form">
                <input type="text" name="q" class="search-input" placeholder="Search by PIN code, City, District, or State..." required>
                <button type="submit" class="search-btn">🔍 Search</button>
            </form>
        </div>
    </div>
</section>

<?php if (!empty($GLOBALS['adsense_placements']['home_featured'])): ?>
<section class="container">
    <div class="ad-slot ad-home-featured">
        <?php renderAdPlacement('home_featured'); ?>
    </div>
</section>
<?php endif; ?>

<section class="stats">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📮</div>
            <div class="stat-number"><?php echo number_format($total_pincodes); ?></div>
            <div class="stat-label">Total PIN Codes</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🗺️</div>
            <div class="stat-number"><?php echo $total_states; ?></div>
            <div class="stat-label">States & UTs</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🏘️</div>
            <div class="stat-number"><?php echo number_format($total_districts); ?></div>
            <div class="stat-label">Districts</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-number">24/7</div>
            <div class="stat-label">Always Available</div>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <h2 class="section-title">Why Choose Us?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <h3 class="feature-title">Accurate Information</h3>
                <p class="feature-desc">Complete and verified PIN code data</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🚀</div>
                <h3 class="feature-title">Fast Search</h3>
                <p class="feature-desc">Quick results in seconds</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Mobile Friendly</h3>
                <p class="feature-desc">Works on all devices</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🆓</div>
                <h3 class="feature-title">Free to Use</h3>
                <p class="feature-desc">Completely free information</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3 class="feature-title">Regular Updates</h3>
                <p class="feature-desc">Latest information</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3 class="feature-title">Easy Navigation</h3>
                <p class="feature-desc">User-friendly interface</p>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($GLOBALS['adsense_placements']['incontent'])): ?>
<section class="container">
    <div class="ad-slot ad-incontent">
        <?php renderAdPlacement('incontent'); ?>
    </div>
</section>
<?php endif; ?>

<section class="states-section" id="states">
    <div class="container">
        <h2 class="section-title">Browse PIN Codes by State</h2>
        <div class="states-grid">
            <?php foreach ($states as $state): ?>
                <a href="/state/<?php echo urlencode(strtolower(str_replace(' ', '-', $state))); ?>" class="state-card">
                    <div class="state-icon">📍</div>
                    <div><?php echo htmlspecialchars($state); ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
