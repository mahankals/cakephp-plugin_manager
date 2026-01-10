<?php
/**
 * PluginManager - Plugin List View
 *
 * @var \Cake\View\View $this
 * @var array<string, array<string, mixed>> $plugins
 */
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss flash messages after 3 seconds, but pause on hover
    const flashMessages = document.querySelectorAll('.message');
    flashMessages.forEach(function(msg) {
        let timeoutId = null;
        let isHovered = false;

        function dismissMessage() {
            if (isHovered) return;
            msg.style.transition = 'opacity 0.5s ease-out';
            msg.style.opacity = '0';
            setTimeout(function() {
                msg.remove();
            }, 500);
        }

        function startTimer() {
            if (timeoutId) clearTimeout(timeoutId);
            timeoutId = setTimeout(dismissMessage, 3000);
        }

        msg.addEventListener('mouseenter', function() {
            isHovered = true;
            if (timeoutId) clearTimeout(timeoutId);
        });

        msg.addEventListener('mouseleave', function() {
            isHovered = false;
            startTimer();
        });

        startTimer();
    });
});
</script>
<div class="plugins index content">
    <h3>Plugin Manager</h3>

    <div style="margin-bottom: 20px;">
        <?= $this->Form->create(null, [
            'url' => ['plugin' => 'PluginManager', 'controller' => 'Plugins', 'action' => 'refresh'],
            'style' => 'display: inline;'
        ]) ?>
            <button type="submit" class="button">Refresh Plugin Cache</button>
        <?= $this->Form->end() ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Plugin Name</th>
                <th>Version</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($plugins)): ?>
                <tr>
                    <td colspan="5">No plugins discovered.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($plugins as $name => $plugin): ?>
                    <tr>
                        <td><?= h($plugin['name']) ?></td>
                        <td><?= h($plugin['version'] ?? 'unknown') ?></td>
                        <td><?= h($plugin['description'] ?? '-') ?></td>
                        <td>
                            <?php
                            $isLoaded = $plugin['isLoaded'] ?? false;
                            $isEnabled = $plugin['isEnabled'] ?? false;
                            $isSystemPlugin = $isLoaded && !$isEnabled;
                            ?>
                            <?php if ($isSystemPlugin): ?>
                                <span style="color: #2196F3;">System</span>
                            <?php elseif ($isEnabled): ?>
                                <span style="color: green;">Enabled</span>
                            <?php else: ?>
                                <span style="color: gray;">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions" style="white-space: nowrap;">
                            <?php $encodedName = urlencode($name); ?>
                            <?php if ($isSystemPlugin): ?>
                                <span style="color: #999; font-size: 0.9em;">Core</span>
                            <?php elseif ($isEnabled): ?>
                                <?= $this->Form->create(null, [
                                    'url' => '/plugin-manager/disable/' . $encodedName,
                                    'style' => 'display: inline;'
                                ]) ?>
                                    <button type="submit" class="button button-outline" onclick="return confirm('Disable <?= h($name) ?>?');">Disable</button>
                                <?= $this->Form->end() ?>
                            <?php else: ?>
                                <?= $this->Form->create(null, [
                                    'url' => '/plugin-manager/enable/' . $encodedName,
                                    'style' => 'display: inline;'
                                ]) ?>
                                    <button type="submit" class="button">Enable</button>
                                <?= $this->Form->end() ?>
                            <?php endif; ?>
                            <?php if (($plugin['hasConfig'] ?? false) && ($isLoaded || $isEnabled)): ?>
                                <a href="/plugin-manager/config/<?= $encodedName ?>" class="button button-outline" style="margin-left: 8px;">Config</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="margin-top: 20px; color: #666; font-size: 0.9em;">
        <strong>System</strong> plugins are core plugins and cannot be disabled.<br>
        <strong>Enabled/Disabled</strong> plugins can be managed from this page.
    </p>
</div>
