<div id="item-metadata">
    <?php if (!($metadata = all_element_texts($item))): ?>
    <p><?php echo __('No item metadata.'); ?></p>
    <?php else: ?>
        <?php echo $metadata; ?>
    <?php endif; ?>
</div>
