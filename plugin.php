<?php
/**
 * Live Book (plugin for Omeka)
 *
 * Manages digitalized books or images with Omeka: zoom and flip of pages,
 * extraction and displaying of table of contents from PDF, full text search
 * from an XML content (KWIC)...
 *
 * @see README.md
 *
 * @copyright Daniel Berthereau, 2013
 * @copyright Julien Sicot (Université Rennes 2), 2010-2013 [bibnum v0.5]
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package LiveBook
 */

require_once file_exists(get_option('live_book_custom_library'))
    ? get_option('live_book_custom_library')
    // We need a hard coded path to avoid error when module isn't yet installed.
    : dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'live_book_custom.php';

/**
 * Contains code used to integrate the plugin into Omeka.
 *
 * @package LiveBook
 */
class LiveBookPlugin extends Omeka_Plugin_Abstract
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'config_form',
        'config',
        'admin_theme_header',
        'public_theme_header',
        'admin_append_to_items_show_primary',
        'public_append_to_item',
    // TODO To be cleaned and finished.
    // add_plugin_hook('public_append_to_items_show', 'live_book_append_to_item');
    // add_plugin_hook('public_append_to_items_show', 'live_book_tableOfContent');
    // add_plugin_hook('public_append_to_items_show', 'live_book_searchContent');
    );

    protected $_options = array(
        'live_book_custom_library' => 'libraries/live_book_custom.php',
        'live_book_zoomify' => false,
        'live_book_thumbnails_by_view' => '5x7',
        // TODO To be moved to specific libraries.
        'live_book_pdftohtml_path' => '/usr/bin/pdftohtml',
        'live_book_pdftk_path' => '/usr/bin/pdftk',
        'live_book_xml_directory' => 'xml',
    );

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_options['live_book_custom_library'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'live_book_custom.php';
        // TODO Uses and checks archive/cache/live_book folder.
        $this->_options['live_book_xml_directory'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'xml';

        self::_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $options = $this->_options;
        if (!is_array($options)) {
            return;
        }
        foreach ($options as $name => $value) {
            delete_option($name);
        }
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm()
    {
        include_once 'config_form.php';
        // TODO Include options of specific libraries too.
    }

    /**
     * Processes the configuration form.
     */
    public function hookConfig($post)
    {
        set_option('live_book_custom_library', $post['live_book_custom_library']);
        set_option('live_book_zoomify', (boolean) $post['live_book_zoomify']);
        // TODO Add this configuraiton option.
        // set_option('live_book_thumbnails_by_view', $post['live_book_thumbnails_by_view']);
        // TODO To be moved to specific libraries.
        set_option('live_book_pdftohtml_path', $post['live_book_pdftohtml_path']);
        set_option('live_book_pdftk_path', $post['live_book_pdftk_path']);
        set_option('live_book_xml_directory', $post['live_book_xml_directory']);
    }

    /**
     * Add css and js in the header of the theme.
     */
    public function hookAdminThemeHeader($request)
    {
        if ($request->getControllerName() == 'items' && $request->getActionName() == 'show') {
            queue_css('live_book');
            // Little differences in the admin css.
            queue_css('live_book_admin');

            // No tabs effect for admin view.
        }
    }

    /**
     * Add css and js in the header of the theme.
     */
    public function hookPublicThemeHeader($request)
    {
        if ($request->getControllerName() == 'items' && $request->getActionName() == 'show') {
            queue_css('live_book');

            // Add tabs effect.
            queue_css('jquery-ui', 'all', FALSE, 'css/ui');
            queue_js(array(
                'jquery.min',
                'jquery.ui.core.min',
                'jquery.ui.widget.min',
                'jquery.ui.tabs.min',
                'jquery.ui.document_ready',
            ));
        }
    }

    /**
     * Append the viewer to the admin view.
     *
     * Note: Currently, you should replace the call to display_files_for_item()
     * in the admin theme (admin/themes/default/items/show.php) by the call to
     * this function (or live_book_admin_append_to_item()).
     *
     * @todo Replace display_files_for_item automatically (or wait for a hook on
     * display_files_for_item()).
     */
    public function hookAdminAppendToItemsShowPrimary($item = NULL)
    {
        return self::hookPublicAppendToItem($item);
    }

    /**
     * Fonction principale du plugin.
     */
    public function hookPublicAppendToItem($item = NULL)
    {
        if ($item == NULL) {
            $item = get_current_item();
        }

        // Initialize return.
        $html = '<div id="view">';

        // Get images files attached to the item.
        $imagesFiles = self::get_images_files($item);

        // Avertissement si pas d'image, sinon traitement.
        if (empty($imagesFiles)) {
            $html .= '<div id="item-images">' . PHP_EOL;
            $html .= '<br />' . PHP_EOL;
            $html .= __("No picture.") . '<br />' . PHP_EOL;
            $html .= '<br />' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;

            return $html;
        }

        // Current image.
        $currentImage = ($_GET['image'] == true) ? $_GET['image'] : 1;
        // Si utilisateur a recherché une page.
        $pageToFind = trim($_GET['find_page']);
        $foundPage = '';
        // Vignettage actif ou non.
        $thumbnailing = $_GET['v'];

        // Define some variables according to type of display.
        // Page view.
        if ($thumbnailing != '1') {
            $thumbnailing = '0';

            // Si l'utilisateur a cherché une page
            if ($pageToFind) {
                $foundPage = LiveBook::find_page($pageToFind, $imagesFiles);
                if ($foundPage) {
                    $currentImage = $foundPage;
                }
            }

            // Détermination des références pour la toolbar.
            $firstImage = 1;
            $lastImage = count($imagesFiles);
            $prevImage = ($currentImage <= $firstImage) ? '' : $currentImage - 1;
            $nextImage = ($currentImage >= $lastImage) ? '' : $currentImage + 1;
            $labelPage = LiveBook::get_label_page($imagesFiles[$currentImage]);
        }

        // Thumbnailing.
        else {
            // TODO Add width, height and automatic parameters.
            list($thumbnailsColumns, $thumbnailsRows) = explode('x', get_option('live_book_thumbnails_by_view'));
            $thumbnailsByPage = $thumbnailsColumns * $thumbnailsRows;

            $thumbnails = '';
            $shiftImage = 0;
            for ($row = 0; $row < $thumbnailsRows; $row++) {
                $thumbnails .= '<ul class="row">' . PHP_EOL;
                for ($i = $currentImage + $shiftImage; ($i < $currentImage + $shiftImage + $thumbnailsColumns) and ($i <= count($imagesFiles)); $i++) {
                    $labelPage = LiveBook::get_label_page($imagesFiles[$i]);
                    $thumbnails .= '<li class="Object"><span class="numero">' . $labelPage . '</span><br />' . PHP_EOL;
                    $thumbnails .= '<a class="vignette" href="?image=' . $i . '#live_book">';
                    $thumbnails .= '<div id="imgitem"><img src="' . WEB_THUMBNAILS . '/' . $imagesFiles[$i]->archive_filename . '" alt="image-' . $i . '" class="object-representation" title="' . __('View this page') . '" /></div>';
                    $thumbnails .= '</a></li>' . PHP_EOL;
                }
                $thumbnails .= '</ul>' . PHP_EOL;

                $shiftImage = $shiftImage + $thumbnailsColumns;
            }

            // Détermination des références pour la toolbar.
            $firstImage = 1;
            $lastImage = count($imagesFiles);
            $prevImage = ($currentImage <= $firstImage) ? '' : $currentImage - $thumbnailsByPage;
            if ($prevImage !== '' && $prevImage < $firstImage) {
                $prevImage = $firstImage;
            }
            $nextImage = ($currentImage >= $lastImage - $thumbnailsByPage) ? '' : $currentImage + $thumbnailsByPage;
            if ($nextImage !== '' && $nextImage > $lastImage) {
                $nextImage = $lastImage;
            }
        }

        // Prépare la barre d'outils.
        // Partie gauche de la barre : Page précédente
        if ($prevImage !== '') {
            // vers la premère page
            $firstPage = '<a id="premiere" href="?image=' . $firstImage . '&amp;v=' . $thumbnailing . '#live_book" title="' . __('First page') . '" rev="start"></a>';
            // page précédente (bouton barre de navigation)
            $prevPage = '<a id="precedente" href="?image=' . $prevImage . '&amp;v=' . $thumbnailing . '#live_book"  title="' . __('Previous page') . '" rel="prev"></a>';
            // page précédente (survol sur l'image)
            $prevHover = '<a id="prev" href="?image=' . $prevImage . '&amp;v=' . $thumbnailing . '#live_book" title="' . __('Previous page') . '" ></a>';
        }
        // Si pas de page précédente
        else {
            $firstPage = '<a class="blankG"></a>';
            $prevPage = '<a class="blankG"></a>';
            $prevHover = '';
        }

        // Partie droite de la barre : Page suivante
        if ($nextImage !== '') {
            // dernière page
            $lastPage = '<a id="derniere" href="?image=' . $lastImage . '&amp;v=' . $thumbnailing . '#live_book" title="' . __('Last page') . '" rev="end"></a>';
            // page suivante (bouton)
            $nextPage = '<a id="suivante" href="?image=' . $nextImage . '&amp;v=' . $thumbnailing . '#live_book" title="' . __('Next page') . '" rel="next"></a>';
            // page suivante (survol sur l'image)
            $nextHover = '<a id="next" href="?image=' . $nextImage . '&amp;v=' . $thumbnailing . '#live_book" title="' . __('Next page') . '"></a>';
        }
        // Si pas de page suivante
        else {
            $lastPage = '<a class="blank"></a>';
            $nextPage = '<a class="blank"></a>';
            $nextHover = '';
        }

        // Si vue simple, on active la loupe.
        if ($thumbnailing != '1') {
            // Calcul de l'icone et du lien pour le "zoom".
            // Loupe activée.
            // $magnifier = self::get_icon('magnifier');
            $zoomIcon = '<a class="blankL"></a>';

            $zoom = '<div><div class="item-file image-jpeg">';
            $zoom .= '<a class="fancyitem" href="' . WEB_FILES . '/' . $imagesFiles[$currentImage]->archive_filename . '" title="' . $labelPage . '" rel="fancy_group">';
            $zoom .= '<img class="page_num" src="' . WEB_FULLSIZE . '/' . $imagesFiles[$currentImage]->archive_filename . '" alt="' . $labelPage . '" title="' . $labelPage . '" />';
            $zoom .= '</a></div></div>' . PHP_EOL;

            // Prépare la vue simple.
            $img = '<div id="pagprev">' . $prevHover . '</div>' . PHP_EOL;
            $img .= $zoom;
            $img .= '<div id="pagnext">' . $nextHover . '</div>' . PHP_EOL;
        }

        // Si vignettes, on désactive la loupe.
        else {
            // Loupe désactivée.
            // $magnifier_off = self::get_icon('magnifier_off');
            $zoomIcon = '<a class="blankL"></a>';
        }

        // Préparation du mini champ de recherche par page.
        $form = '<form class="ouvnum" action="#live_book" method="get" name="page">';
        $form .= '<p>' . __('Page') . ' ';
        $form .= '<input class="outil" type="text" name="find_page" value="' . ($pageToFind ?: '') . '" size="3" maxlength="10">';
        $form .= '</p></form>';

        // Lien vers l'image courante.
        $vign1 = '<a href="?image=' . $currentImage . '&amp;v=1#live_book" title="Affichage vignettes" id="vign1"></a>';

        // Prépare la barre d'outils.
        $toolbar .= '<div id="live_book">' . PHP_EOL;
        $toolbar .= '<div id="tools">' . PHP_EOL;
        $toolbar .= $firstPage;
        $toolbar .= $prevPage;
        $toolbar .= $zoomIcon;
        $toolbar .= $form;
        $toolbar .= $vign1;
        $toolbar .= $nextPage;
        $toolbar .= $lastPage;
        $toolbar .= '</div>' . PHP_EOL;
        $toolbar .= '</div>' . PHP_EOL;

        // Fonction Zoomify pour les items avec une seule image
        // Si Item ne possède qu'une seule image, on afffcihe fonction Zoomify
        // L'image doit au préalable avoir été traitée avec Zoomify.
        if (count($imagesFiles) == 1 && get_option('live_book_zoomify')) {
            while (loop_files_for_item($item)) {
                $file = get_current_file();
                if ($file->hasThumbnail()) {
                    //Création du tableau
                    $imgZoom = $file->original_filename;
                    $imgZoom = preg_replace('#.jpg#', '', $imgZoom);
                }
                $i++;
            }

            //lien vers répertoire zoomify
            $ZoomRep = CURRENT_BASE_URL .'/archive/zoomify';
            $html .= '<div align="center">' . PHP_EOL;
            $html .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" CODEBASE="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" width="550" height="450" id="theMovie">' . PHP_EOL;
            $html .= '<param name="flashvars" value="zoomifyImagePath=' . $ZoomRep . '/' . $imgZoom . '">' . PHP_EOL;
            $html .= '<param name="menu" value="false">' . PHP_EOL;
            $html .= '<param name="src" value="' . $ZoomRep . '/ZoomifyViewer.swf">' . PHP_EOL;
            $html .= '<embed flashvars="zoomifyImagePath=' . $ZoomRep . '/' . $imgZoom . '" src="' . $ZoomRep . '/ZoomifyViewer.swf" menu="false" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?p1_prod_version=shockwaveflash" width="550" height="450" name="themovie"></embed>' . PHP_EOL;
            $html .= '</object>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
        }

        // Notice avec plusieurs images.
        else {
            // Affichage de la barre de navigation et de la page.
            $html .= '<div id="item-images">' . PHP_EOL;
            $html .= $toolbar;

            // Affichage de la page.
            if ($thumbnailing != '1') {
                $html .= '<div id="numero">[' . $labelPage . ']</div>' . PHP_EOL;
                $html .= '<div id="main_page">' . $img . '</div>' . PHP_EOL;
            }
            // Affichage des vues miniatures.
            else {
                $html .= '<div id="vignettes">' . PHP_EOL;
                $html .= '<div id="pagprev">' . $prevHover . '</div>' . PHP_EOL;
                $html .= $thumbnails;
                $html .= '<div id="pagnext">' . $nextHover . '</div>' . PHP_EOL;
                $html .= '</div>' . PHP_EOL;
            }

            $html .= $toolbar;
            $html .= '</div>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
        }

        // Return html code of live book viewer.
        return $html;
    }

    /**
     * Get the table of content of an itemn, if any.
     */
    public static function tableOfContent($item = null)
    {
        if ($item == null) {
            $item = get_current_item();
        }

        return LiveBook::get_table_of_content($item);
    }

    /**
     * Allows to search something in the current document.
     */
    public static function searchContent($item = null)
    {
        if ($item == null) {
            $item = get_current_item();
        }

        $html = '';
        $maxResults = 100;

        if (!LiveBook::has_text($item)) {
            return $html;
        }

        $html .= '<div id="search_content">';

        // Local search form.
        $html .= '<div id="search-ti">' . PHP_EOL;
        $html .= '    <h2>' . __('Search inside this document') . '</h2>' . PHP_EOL;
        $html .= '    <form action="#search_content" method="post">' . PHP_EOL;
        $html .= '        <input type="hidden" name="action" value="seek">' . PHP_EOL;
        $html .= '        <input class="search" type="text" name="words_to_search" size=35 maxlength=100 value="">' . PHP_EOL;
        $html .= '        <input type="submit" value="ok" id="submit_search">' . PHP_EOL;
        $html .= '    </form>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;
        $keywords = trim($_POST['words_to_search']);

        if (empty($keywords)) {
            $html .= '</div>' . PHP_EOL;
            return $html;
        }

        $html .= '<div id="vtext">';

        $results = LiveBook::search_text($keywords, $item, $maxResults);

        $countResults = count($results);
        $html .= '<strong><em>';
        if ($countResults == 0) {
            $html .= __('"%s" appears nowhere in the document.', $keywords);
            $html .= '</em></strong>';
            $html .= '</div>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
            return $html;
        }
        elseif ($countResults == 1) {
            $html .= __('"%s" appears in one page:', $keywords);
        }
        elseif ($countResults < $maxResults) {
            $html .= __('"%s" appears in %d pages:', $keywords, $countResults);
        }
        else {
            $html .= __('"%s" appears in more than %d pages:', $keywords, $countResults);
        }
        $html .= '</em></strong><br />' . PHP_EOL;

        // Prepare list of files and a mapping between image numbers and ids.
        $imagesFiles = self::get_images_files($item);
        $imagesMap = array();
        foreach ($imagesFiles as $key => $file) {
            $imagesMap[$file->id] = $key;
        }

        // Display found text of all pages where there is a result.
        // TODO Display with multipage. Currently, use simply a max.
        foreach ($results as $file_id => $textPage) {
            $currentImage = $imagesMap[$file_id];

            $labelPage = LiveBook::get_label_page($imagesFiles[$currentImage]);
            $textPage = htmlspecialchars($textPage, ENT_COMPAT | ENT_HTML401 | ENT_DISALLOWED, 'UTF-8');

            $html .= '<a href="?image=' . $currentImage . '#live_book">' . $labelPage . '</a> : ' . PHP_EOL;
            $html .= self::highlight($keywords, $textPage) . '<br />' . PHP_EOL;
        }

        if ($countResults > $maxResults) {
            $html .= '<strong><em>' . __('Too many results. Next ones are hidden.') . '</em></strong>';
        }

        $html .= '</div>' . PHP_EOL;
        $html .= '</div>' . PHP_EOL;

        return $html;
    }

    /**
     * Highlights the selected text.
     *
     * This function is used to show the results of the local search engine.
     *
     */
    public static function highlight($textToHighlight, $text)
    {
        $search = preg_quote($textToHighlight);
        $match = self::regex_accents($search);
        // Results come from mysql "like" and don't manage words boundaries,
        // so we don't use the '\b' regex around text to highlight.
        return preg_replace('/' . $match . '/iu', '<span class="highlight">$0</span>', $text);
    }

    /**
     * Clean a string.
     *
     * @todo Use a more generic function (utf8 can manage diacritics with mb).
     */
    public static function regex_accents($string)
    {
        $accent = array(
            'a', 'à', 'á', 'â', 'ã', 'ä', 'å',
            'c', 'ç',
            'e', 'è', 'é', 'ê', 'ë',
            'i', 'ì', 'í', 'î', 'ï',
            'o', 'ð', 'ò', 'ó', 'ô', 'õ', 'ö',
            'u', 'ù', 'ú', 'û', 'ü',
            'y', 'ý', 'ý', 'ÿ',
        );

        $inter = array('%01', '%02', '%03', '%04', '%05', '%06', '%07', '%08', '%09', '%10', '%11', '%12', '%13', '%14', '%15', '%16', '%17', '%18', '%19', '%20', '%21', '%22', '%23', '%24', '%25', '%26', '%27', '%28', '%29', '%30', '%31', '%32', '%33', '%34', '%35');

        $regex = array(
            '(a|à|á|â|ã|ä|å)', '(a|à|á|â|ã|ä|å)', '(a|à|á|â|ã|ä|å)', '(a|à|á|â|ã|ä|å)', '(a|à|á|â|ã|ä|å)', '(a|à|á|â|ã|ä|å)', '(a|à|á|â|ã|ä|å)',
            '(c|ç)', '(c|ç)',
            '(è|e|é|ê|ë)', '(è|e|é|ê|ë)', '(è|e|é|ê|ë)', '(è|e|é|ê|ë)', '(è|e|é|ê|ë)',
            '(i|ì|í|î|ï)', '(i|ì|í|î|ï)', '(i|ì|í|î|ï)', '(i|ì|í|î|ï)', '(i|ì|í|î|ï)',
            '(o|ð|ò|ó|ô|õ|ö)', '(o|ð|ò|ó|ô|õ|ö)', '(o|ð|ò|ó|ô|õ|ö)', '(o|ð|ò|ó|ô|õ|ö)', '(o|ð|ò|ó|ô|õ|ö)', '(o|ð|ò|ó|ô|õ|ö)', '(o|ð|ò|ó|ô|õ|ö)',
            '(u|ù|ú|û|ü)', '(u|ù|ú|û|ü)', '(u|ù|ú|û|ü)', '(u|ù|ú|û|ü)',
            '(y|ý|ý|ÿ)', '(y|ý|ý|ÿ)', '(y|ý|ý|ÿ)', '(y|ý|ý|ÿ)',
        );

        $string = str_ireplace($accent, $inter, $string);
        $string = str_replace($inter, $regex, $string);

        return $string;
    }

    /**
     * Return list of images files of an item.
     *
     * @param Item $item
     *
     * @return array
     *   Return ordered array of images files, numbered from 1 (first image).
     */
    private static function get_images_files($item)
    {
        $imagesFiles = $item->Files;
        foreach ($imagesFiles as $key => $value) {
            if (!$value->hasThumbnail()) {
                $imagesFiles[$key] = null;
            }
        }
        $imagesFiles = array_values(array_filter($imagesFiles));
        // Start array at 1 to simplify the count of items and avoid errors.
        array_unshift($imagesFiles, true);
        unset($imagesFiles[0]);

        return $imagesFiles;
    }

    private static function get_icon($file)
    {
        $imgURL = WEB_PLUGIN . '/LiveBook/views/shared/images/' . $file . '.png';
        return '<img src="' . $imgURL  . '">' . PHP_EOL;
    }
}

/** Installation of the plugin. */
$liveBook = new LiveBookPlugin();
$liveBook->setUp();

/**
 * Wrapper called by theme.
 *
 * @note Currently, you should replace the call to display_files_for_item()
 * in the admin theme (default is admin/themes/default/items/show.php) by the
 * call to this function.
 *
 * @todo Replace automatically admin call to display_files_for_item().
 */
function live_book_admin_append_to_item($item = NULL) {
    $liveBook = new LiveBookPlugin();
    return $liveBook->hookAdminAppendToItemsShowPrimary($item);
}

/**
 * Wrapper called by theme.
 */
function live_book_append_to_item($item = NULL) {
    $liveBook = new LiveBookPlugin();
    return $liveBook->hookPublicAppendToItem($item);
}

/**
 * Wrapper called by theme.
 */
function live_book_tableOfContent($item = NULL) {
    $liveBook = new LiveBookPlugin();
    return $liveBook->tableOfContent($item);
}

/**
 * Wrapper called by theme.
 */
function live_book_searchContent($item = NULL) {
    $liveBook = new LiveBookPlugin();
    return $liveBook->searchContent($item);
}
