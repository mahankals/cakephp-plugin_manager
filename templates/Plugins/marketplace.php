<?php
/**
 * PluginManager - Marketplace View
 *
 * @var \Cake\View\View $this
 * @var array<int, array<string, mixed>> $plugins
 * @var array<int, string> $categories
 * @var array<string, array<string, mixed>> $installedPlugins
 */
?>
<style>
.marketplace-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.marketplace-header h3 {
    margin: 0;
}

.marketplace-nav {
    display: flex;
    gap: 8px;
}

.marketplace-search {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.marketplace-search input[type="text"] {
    flex: 1;
    min-width: 200px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.marketplace-search select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}

.category-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.category-tab {
    padding: 6px 16px;
    border: 1px solid #ddd;
    border-radius: 20px;
    background: #fff;
    color: #666;
    text-decoration: none;
    font-size: 0.9em;
    cursor: pointer;
    transition: all 0.2s;
}

.category-tab:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.category-tab.active {
    background: #6366f1;
    border-color: #6366f1;
    color: #fff;
}

.plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.plugin-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    transition: box-shadow 0.2s;
}

.plugin-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.plugin-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.plugin-name {
    font-size: 1.1em;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.plugin-version {
    font-size: 0.85em;
    color: #6b7280;
}

.plugin-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75em;
    font-weight: 600;
    text-transform: uppercase;
}

.plugin-badge.installed {
    background: #d1fae5;
    color: #065f46;
}

.plugin-badge.update {
    background: #fef3c7;
    color: #92400e;
}

.plugin-description {
    color: #4b5563;
    font-size: 0.9em;
    line-height: 1.5;
    margin-bottom: 16px;
    min-height: 3em;
}

.plugin-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    font-size: 0.85em;
    color: #6b7280;
}

.plugin-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.plugin-author {
    font-size: 0.85em;
    color: #9ca3af;
    margin-bottom: 16px;
}

.plugin-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.plugin-tag {
    padding: 2px 8px;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 0.75em;
    color: #6b7280;
}

.plugin-actions {
    display: flex;
    gap: 8px;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}

.plugin-actions .button {
    flex: 1;
    text-align: center;
}

.button-small {
    padding: 6px 12px;
    font-size: 0.9em;
}

.no-plugins {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.star-rating {
    color: #f59e0b;
}

@media (max-width: 768px) {
    .marketplace-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .marketplace-search {
        flex-direction: column;
    }

    .marketplace-search input[type="text"] {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss flash messages
    const flashMessages = document.querySelectorAll('.message');
    flashMessages.forEach(function(msg) {
        let timeoutId = null;
        let isHovered = false;

        function dismissMessage() {
            if (isHovered) return;
            msg.style.transition = 'opacity 0.5s ease-out';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        }

        function startTimer() {
            if (timeoutId) clearTimeout(timeoutId);
            timeoutId = setTimeout(dismissMessage, 3000);
        }

        msg.addEventListener('mouseenter', () => { isHovered = true; clearTimeout(timeoutId); });
        msg.addEventListener('mouseleave', () => { isHovered = false; startTimer(); });
        startTimer();
    });

    // Category filtering
    const categoryTabs = document.querySelectorAll('.category-tab');
    const pluginCards = document.querySelectorAll('.plugin-card');

    categoryTabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.dataset.category;

            // Update active tab
            categoryTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Filter cards
            pluginCards.forEach(function(card) {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Search functionality
    const searchInput = document.getElementById('marketplace-search');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = this.value.toLowerCase();
                pluginCards.forEach(function(card) {
                    const name = card.querySelector('.plugin-name').textContent.toLowerCase();
                    const desc = card.querySelector('.plugin-description').textContent.toLowerCase();
                    const tags = card.dataset.tags?.toLowerCase() || '';

                    if (name.includes(query) || desc.includes(query) || tags.includes(query)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }, 300);
        });
    }
});
</script>

<div class="plugins marketplace content">
    <div class="marketplace-header">
        <h3>Plugin Marketplace</h3>
        <div class="marketplace-nav">
            <a href="/plugin-manager" class="button button-outline">Back to Installed</a>
            <?= $this->Form->create(null, [
                'url' => ['plugin' => 'PluginManager', 'controller' => 'Plugins', 'action' => 'refreshMarketplace'],
                'style' => 'display: inline;'
            ]) ?>
                <button type="submit" class="button button-outline">Refresh Catalog</button>
            <?= $this->Form->end() ?>
        </div>
    </div>

    <div class="marketplace-search">
        <input type="text" id="marketplace-search" placeholder="Search plugins..." />
        <select id="sort-select">
            <option value="downloads">Most Popular</option>
            <option value="rating">Highest Rated</option>
            <option value="name">Name A-Z</option>
            <option value="updated">Recently Updated</option>
        </select>
    </div>

    <div class="category-tabs">
        <a href="#" class="category-tab active" data-category="all">All</a>
        <?php foreach ($categories as $category): ?>
            <a href="#" class="category-tab" data-category="<?= h($category) ?>">
                <?= h(ucfirst($category)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($plugins)): ?>
        <div class="no-plugins">
            <p>No plugins available in the marketplace.</p>
            <p>Try refreshing the catalog or check your internet connection.</p>
        </div>
    <?php else: ?>
        <div class="plugins-grid">
            <?php foreach ($plugins as $plugin): ?>
                <?php
                $packageName = $plugin['name'] ?? '';
                $encodedPackage = urlencode($packageName);
                $isInstalled = $plugin['installed'] ?? false;
                $hasUpdate = $plugin['updateAvailable'] ?? false;
                $tags = $plugin['tags'] ?? [];
                ?>
                <div class="plugin-card"
                     data-category="<?= h($plugin['category'] ?? 'other') ?>"
                     data-tags="<?= h(implode(',', $tags)) ?>">
                    <div class="plugin-card-header">
                        <div>
                            <h4 class="plugin-name"><?= h($plugin['displayName'] ?? $packageName) ?></h4>
                            <span class="plugin-version">v<?= h($plugin['version'] ?? '0.0.0') ?></span>
                        </div>
                        <?php if ($isInstalled): ?>
                            <?php if ($hasUpdate): ?>
                                <span class="plugin-badge update">Update Available</span>
                            <?php else: ?>
                                <span class="plugin-badge installed">Installed</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <p class="plugin-description">
                        <?= h($plugin['description'] ?? 'No description available.') ?>
                    </p>

                    <div class="plugin-meta">
                        <span class="plugin-meta-item">
                            <span class="star-rating">&#9733;</span>
                            <?= number_format($plugin['rating'] ?? 0, 1) ?>
                            (<?= number_format($plugin['ratingCount'] ?? 0) ?>)
                        </span>
                        <span class="plugin-meta-item">
                            <?= number_format($plugin['downloads'] ?? 0) ?> downloads
                        </span>
                    </div>

                    <?php if (!empty($plugin['author']['name'])): ?>
                        <p class="plugin-author">
                            by <?= h($plugin['author']['name']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($tags)): ?>
                        <div class="plugin-tags">
                            <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
                                <span class="plugin-tag"><?= h($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="plugin-actions">
                        <?php if ($isInstalled): ?>
                            <?php if ($hasUpdate): ?>
                                <?= $this->Form->create(null, [
                                    'url' => '/plugin-manager/update/' . $encodedPackage,
                                    'style' => 'flex: 1;'
                                ]) ?>
                                    <button type="submit" class="button button-small" style="width: 100%;">
                                        Update to v<?= h($plugin['version']) ?>
                                    </button>
                                <?= $this->Form->end() ?>
                            <?php else: ?>
                                <span class="button button-outline button-small" style="flex: 1; text-align: center; opacity: 0.6; cursor: default;">
                                    Installed v<?= h($plugin['installedVersion'] ?? $plugin['version']) ?>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= $this->Form->create(null, [
                                'url' => '/plugin-manager/install/' . $encodedPackage,
                                'style' => 'flex: 1;'
                            ]) ?>
                                <button type="submit" class="button button-small" style="width: 100%;">
                                    Install
                                </button>
                            <?= $this->Form->end() ?>
                        <?php endif; ?>

                        <?php if (!empty($plugin['homepage'])): ?>
                            <a href="<?= h($plugin['homepage']) ?>" target="_blank" rel="noopener"
                               class="button button-outline button-small" title="View on GitHub">
                                Details
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p style="margin-top: 24px; color: #666; font-size: 0.9em;">
        Plugins are installed via Composer. After installation, enable the plugin from the
        <a href="/plugin-manager">Plugin Manager</a>.
    </p>
</div>
