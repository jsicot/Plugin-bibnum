<div id="item-image">
    <?php if ($countImages == 0): ?>
    <p><?php echo __('No picture.'); ?></p>
    <?php elseif ($countImages == 1 && get_option('live_book_zoomify')): ?>
    <div align="center">
        <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" CODEBASE="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" width="550" height="450" id="theMovie">
            <param name="flashvars" value="zoomifyImagePath=<?php echo $ZoomRep . '/' . $imgZoom; ?>">
            <param name="menu" value="false">
            <param name="src" value="<?php echo $ZoomRep . '/'; ?>ZoomifyViewer.swf">
            <embed flashvars="zoomifyImagePath=<?php echo $ZoomRep . '/' . $imgZoom; ?>" src="<?php echo $ZoomRep . '/'; ?>ZoomifyViewer.swf" menu="false" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?p1_prod_version=shockwaveflash" width="550" height="450" name="themovie"></embed>
        </object>
    </div>
    <?php else: ?>
        <?php include 'item-image-toolbar.php'; ?>
    <?php if ($thumbnailing): ?>
    <div id="vignettes">
        <?php if ($prevImage): ?>
        <div id="pagprev">
            <a id="prev" href="?image=<?php echo $prevImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('Previous page'); ?>" ></a>
        </div>
        <?php endif; ?>
        <?php foreach ($thumbnails as $row): ?>
        <ul class="row">
            <?php foreach ($row as $thumb): ?>
            <li class="Object"><span class="numero"><?php echo $thumb['label']; ?></span><br />
                <a class="vignette" href="?image=<?php echo $thumb['numero']; ?>#live_book">
                    <div id="imgitem">
                        <img src="<?php echo $thumb['src']; ?>" alt="image-<?php echo $thumb['numero']; ?>" class="object-representation" title="<?php echo __('View this page'); ?>" />
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endforeach; ?>
        <?php if ($nextImage): ?>
        <div id="pagnext">
            <a id="next" href="?image=<?php echo $nextImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('Next page'); ?>"></a>
        </div>
        <?php endif; ?>
    </div>
        <?php else: ?>
    <div id="numero">[<?php echo $labelPage; ?>]</div>
    <div id="main_page">
        <?php if ($prevImage): ?>
        <div id="pagprev">
            <a id="prev" href="?image=<?php echo $prevImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('Previous page'); ?>" ></a>
        </div>
        <?php endif; ?>
        <div id="live_book_view">
            <div class="item-file image-jpeg">
                <a class="fancyitem" href="<?php echo $imageOriginal; ?>" title="<?php echo $labelPage; ?>" rel="fancy_group">
                    <img class="page_num" src="<?php echo $imageFullsize; ?>" alt="<?php echo $labelPage; ?>" title="<?php echo $labelPage; ?>" />
                </a>
            </div>
        </div>
        <?php if ($nextImage): ?>
        <div id="pagnext">
            <a id="next" href="?image=<?php echo $nextImage; ?>&amp;v=<?php echo (int) $thumbnailing; ?>#live_book" title="<?php echo __('Next page'); ?>"></a>
        </div>
        <?php endif; ?>
    </div>
        <?php endif; ?>
        <?php include 'item-image-toolbar.php'; ?>
    <?php endif; ?>
</div>
