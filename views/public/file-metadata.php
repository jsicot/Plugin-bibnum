<div id="file-metadata">
    <?php if (!($metadata = all_element_texts($file))): ?>
    <p><?php echo __('No file metadata.'); ?></p>
    <?php else: ?>
        <?php echo $metadata; ?>
    <?php endif; ?>
</div>
