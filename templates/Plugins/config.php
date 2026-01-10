<?php
/**
 * PluginManager - Plugin Configuration View
 *
 * @var \Cake\View\View $this
 * @var string $pluginName
 * @var array<string, mixed> $pluginStatus
 * @var array<string, mixed> $currentConfig
 */

use Cake\Core\Plugin;

$dbConfigLoaded = Plugin::isLoaded('DbConfig');
?>
<style>
.config-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.config-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.config-card-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    font-size: 0.95em;
    word-break: break-word;
}
.config-card input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 0.9em;
}
.config-card input:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.1);
}
</style>
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
<div class="plugins config content">
    <h3>Configure: <?= h($pluginName) ?></h3>

    <p>
        <a href="<?= $this->Url->build(['plugin' => 'PluginManager', 'controller' => 'Plugins', 'action' => 'index']) ?>">&larr; Back to Plugin List</a>
    </p>

    <div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
        <strong>Plugin:</strong> <?= h($pluginName) ?><br>
        <strong>Version:</strong> <?= h($pluginStatus['version'] ?? 'unknown') ?><br>
        <strong>Status:</strong>
        <?php if ($pluginStatus['isLoaded'] ?? false): ?>
            <span style="color: green;">Loaded</span>
        <?php else: ?>
            <span style="color: gray;">Not Loaded</span>
        <?php endif; ?>
        <?php if ($dbConfigLoaded): ?>
            <br><span style="color: #2196F3; font-size: 0.9em;">Settings will also be saved to database.</span>
        <?php endif; ?>
    </div>

    <?php if (empty($currentConfig)): ?>
        <p style="color: #666; padding: 20px; background: #f9f9f9; border-radius: 5px; text-align: center;">
            No configuration available for this plugin.
        </p>
    <?php else: ?>
        <?= $this->Form->create(null, [
            'url' => '/plugin-manager/config/' . urlencode($pluginName) . '/save',
        ]) ?>

        <div class="config-cards">
            <?php foreach ($currentConfig as $key => $value):
                if (is_array($value)) {
                    $value = json_encode($value);
                }
            ?>
                <div class="config-card">
                    <div class="config-card-title"><?= h($key) ?></div>
                    <input type="hidden" name="config[<?= h($key) ?>][key]" value="<?= h($key) ?>">
                    <input type="text" name="config[<?= h($key) ?>][value]" value="<?= h($value) ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="button">Save Configuration</button>
        </div>

        <?= $this->Form->end() ?>
    <?php endif; ?>
</div>
