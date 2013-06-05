<div class="field">
    <label for="live_book_custom_library"><?php echo __('Custom library file'); ?></label>
    <div class="inputs">
        <?php echo get_view()->formText('live_book_custom_library', get_option('live_book_custom_library'), array('size' => 80)); ?>
        <p class="explanation"><?php echo __('Full path to the library file used to customize Live Book (search inside items, get labels of pages...).'); ?></p>
    </div>
</div>
<div class="field">
    <label for="live_book_pdftohtml_path"><?php echo __('Path to PDFtoHTML'); ?></label>
    <div class="inputs">
        <?php echo get_view()->formText('live_book_pdftohtml_path', get_option('live_book_pdftohtml_path'), array('size' => 80)); ?>
        <p class="explanation"><?php echo __('Path to PDFtoHTML. The path must point to a command-line binary. Check with your web host for more information. This tool is only used in conjunction with the default library used for search.'); ?></p>
    </div>
</div>
<div class="field">
    <label for="live_book_pdftk_path"><?php echo __('Path to PDFtoolkit'); ?></label>
    <div class="inputs">
        <?php echo get_view()->formText('live_book_pdftk_path', get_option('live_book_pdftk_path'), array('size' => 80)); ?>
        <p class="explanation"><?php echo __('Path to PDFtoolkit (pdftk). The path must point to a command-line binary. Check with your web host for more information. This tool is only used in conjunction with the default library used for search.'); ?></p>
    </div>
</div>
<div class="field">
    <label for="live_book_xml_directory"><?php echo __('Directory of cache for XML files'); ?></label>
    <div class="inputs">
        <?php echo get_view()->formText('live_book_xml_directory', get_option('live_book_xml_directory'), array('size' => 80)); ?>
        <p class="explanation"><?php echo __('The directory on the server where generated XML from PDF files will be saved. This directory must be writable by the web server for reporting to function. This folder is only used in conjunction with the default library used for search.'); ?></p>
    </div>
</div>
<div class="field">
    <label for="live_book_zoomify"><?php echo __('Zoomify items with only one image');?></label>
    <div class="inputs">
    <?php echo get_view()->formCheckbox('live_book_zoomify', TRUE, array('checked' => (boolean) get_option('live_book_zoomify')));?>
    <p class="explanation">
        <?php echo __('If checked, items with one image will be displayed via Zoomify. Images should be prepared before enabling this option.');?>
    </p>
    </div>
</div>
<!-- TODO Dynamic management of thumbnails in css.
<div class="field">
    <label for="live_book_thumbnails_by_view"><?php echo __('Maximum number of thumbnails by view'); ?></label>
    <div class="inputs">
        <?php echo get_view()->formText('live_book_thumbnails_by_view', $thumbnailsByView); ?>
        <p class="explanation"><?php echo __('Number of images to display in the thumbnails view (format: column x row; example: "5x7").'); ?></p>
    </div>
</div>
-->
