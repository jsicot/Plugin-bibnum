<div id="notes">
    <?php if ($notes == ''): ?>
    <p><?php echo __('No notes.'); ?></p>
    <?php else: ?>
        <?php echo $notes; ?>
    <?php endif; ?>
</div>
