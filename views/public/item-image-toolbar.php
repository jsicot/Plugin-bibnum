<div id="live_book">
    <div id="live_book_toolbar">
    <?php if ($prevImage === ''): ?>
        <a class="blankP"></a>
    <?php else: ?>
        <a id="premiere" href="?image=<?php echo $firstImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('First page'); ?>" rev="start"></a>
        <a id="precedente" href="?image=<?php echo $prevImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('Previous page'); ?>" rel="prev"></a>
    <?php endif; ?>
    <?php if ($thumbnailing): ?>
        <a class="blankL"></a>
    <?php else: // TODO Enable zoom. ?>
        <a class="blankL"></a>
    <?php endif; ?>
        <form class="openpage" action="#live_book" method="get" name="page">
            <input class="outil" type="text" name="find_page" size="4" maxlength="10" value="<?php echo ($t = ($pageToFind ?: __('Page ?'))); ?>" onblur="if(this.value == '') { this.value= '<?php echo $t; ?>'; }" onclick="this.value = '';" />
        </form>
        <a href="?image=<?php echo $currentImage; ?>&amp;v=1#live_book" title="<?php echo __('Display thumbnails'); ?>" id="thumbs"></a>
    <?php if ($nextImage === ''): ?>
        <a class="blankN"></a>
    <?php else: ?>
        <a id="derniere" href="?image=<?php echo $lastImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('Last page'); ?>" rev="end"></a>
        <a id="suivante" href="?image=<?php echo $nextImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('Next page'); ?>" rel="next"></a>
    <?php endif; ?>
    </div>
</div>
