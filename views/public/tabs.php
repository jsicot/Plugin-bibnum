<div id="live_book_tabs" class="navigation">
    <ul>
<?php foreach ($tabs as $tab): ?>
        <li><a href="#<?php echo $tab . '">';
    switch ($tab):
    case 'item-image': echo __('View'); break;
    case 'item-metadata': echo __('Record'); break;
    case 'file-metadata': echo __('File'); break;
    case 'table-of-content': echo __('Index'); break;
    case 'notes': echo __('Notes'); break;
    case 'search-content': echo __('Search'); break;
    endswitch; ?></a></li>
<?php endforeach; ?>
    </ul>
    <div id="live_book_viewer">
<?php foreach ($tabs as $tab):
    switch ($tab):
    case 'item-image': fire_plugin_hook('live_book_item_image', array('view' => $view)); break;
    case 'item-metadata': fire_plugin_hook('live_book_item_metadata', array('view' => $view)); break;
    case 'file-metadata': fire_plugin_hook('live_book_file_metadata', array('view' => $view)); break;
    case 'table-of-content': fire_plugin_hook('live_book_table_of_content', array('view' => $view)); break;
    case 'notes': fire_plugin_hook('live_book_notes', array('view' => $view)); break;
    case 'search-content': fire_plugin_hook('live_book_search_content', array('view' => $view)); break;
    endswitch;
endforeach; ?>
    </div>
</div>
