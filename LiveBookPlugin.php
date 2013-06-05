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
 * Live Book plugin.
 */
class LiveBookPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'config_form',
        'config',
        'admin_theme_header',
        'public_theme_header',
        'live_book_tabs',
        'live_book_item_image',
        'live_book_item_metadata',
        'live_book_file_metadata',
        'live_book_table_of_content',
        'live_book_notes',
        'live_book_search_content',
    );

    /**
     * @var array Options and their default values.
     */
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
     * Current images files.
     */
    protected $_imagesFiles;

    /**
     * Checked query values.
     */
    protected $_checkedQueryValues;

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_options['live_book_custom_library'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'live_book_custom.php';
        // TODO Uses and checks archive/cache/live_book folder.
        $this->_options['live_book_xml_directory'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'xml';

        $this->_installOptions();
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Shows plugin configuration page.
     *
     * @return void
     */
    public function hookConfigForm()
    {
        require 'config_form.php';

        // TODO Include options of specific libraries too.
    }

    /**
     * Processes the configuration form.
     *
     * @return void
     */
    public function hookConfig($args)
    {
        $post = $args['post'];

        set_option('live_book_custom_library', $post['live_book_custom_library']);
        set_option('live_book_zoomify', (boolean) $post['live_book_zoomify']);
        // TODO Add this configuration option.
        // set_option('live_book_thumbnails_by_view', $post['live_book_thumbnails_by_view']);
        // TODO To be moved to specific libraries.
        set_option('live_book_pdftohtml_path', $post['live_book_pdftohtml_path']);
        set_option('live_book_pdftk_path', $post['live_book_pdftk_path']);
        set_option('live_book_xml_directory', $post['live_book_xml_directory']);
    }

    /**
     * Add css and js in the header of the theme.
     */
    public function hookAdminThemeHeader($args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->getControllerName() == 'items' && $request->getActionName() == 'show') {
            queue_css_file('live_book');
            queue_js_file('live_book');
            // Little differences in the admin css.
            queue_css_file('live_book_admin');
        }
    }

    /**
     * Add css and js in the header of the theme.
     */
    public function hookPublicThemeHeader($args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->getControllerName() == 'items' && $request->getActionName() == 'show') {
            queue_css_file('live_book');
            queue_js_file('live_book');
        }
    }

    /**
     * Display selected tabs of the Live Book.
     *
     * @return void
     *   Display selected tabs.
     */
    public function hookLiveBookTabs($args)
    {
        $view = $args['view'];
        $tabs = $args['tabs'];

        if ($tabs) {
            include_once 'views'. DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'tabs.php';
        }
    }

    /**
     * Display the view tab of the Live Book.
     *
     * @return void
     *   Display the viewer.
     */
    public function hookLiveBookItemImage($args)
    {
        $item = $args['view']->item;
        if ($item == NULL) {
            $item = get_current_record('item');
        }

        // Get images files attached to the item.
        $imagesFiles = $this->_getImagesFiles($item);
        $countImages = count($imagesFiles);
        if ($countImages) {
            list($currentImage, $thumbnailing, $pageToFind) = $this->_getCheckedQueryValues();
            $currentImageFile = $this->_getCurrentImageFile();

            // Fonction Zoomify pour les items avec une seule image.
            // Si Item ne possède qu'une seule image, on affiche Zoomify.
            // L'image doit au préalable avoir été traitée avec Zoomify.
            if ($countImages == 1 && get_option('live_book_zoomify')) {
                //Création du tableau
                $imgZoom = $currentImageFile->original_filename;
                $imgZoom = preg_replace('#.jpg#', '', $imgZoom);

                //lien vers répertoire zoomify
                $ZoomRep = CURRENT_BASE_URL . '/files/zoomify';
            }

            // Notice avec plusieurs images : affiche les vignettes ou la page.
            // Thumbnailing.
            elseif ($thumbnailing) {
                // TODO Add width, height and automatic parameters.
                list($thumbnailsColumns, $thumbnailsRows) = explode('x', get_option('live_book_thumbnails_by_view'));
                $thumbnailsByPage = $thumbnailsColumns * $thumbnailsRows;

                $currentImage = ($currentImage <= $thumbnailsByPage) ? 1 : $currentImage;

                $thumbnails = array();
                $shiftImage = 0;
                for ($row = 0; $row < $thumbnailsRows; $row++) {
                    $thumbnails[$row] = array();
                    for ($i = $currentImage + $shiftImage; ($i < $currentImage + $shiftImage + $thumbnailsColumns) and ($i <= $countImages); $i++) {
                        $thumbnails[$row][$i] = array(
                            'numero' => $i,
                            'label' => LiveBook::get_label_page($imagesFiles[$i]),
                            'src' => WEB_FILES . '/thumbnails/' . $imagesFiles[$i]->filename,
                        );
                    }
                    $shiftImage = $shiftImage + $thumbnailsColumns;
                }

                // Détermination des références pour la toolbar.
                $firstImage = 1;
                $lastImage = $countImages;
                $prevImage = ($currentImage <= $firstImage) ? '' : $currentImage - $thumbnailsByPage;
                if ($prevImage !== '' && $prevImage < $firstImage) {
                    $prevImage = $firstImage;
                }
                $nextImage = ($currentImage >= $lastImage - $thumbnailsByPage) ? '' : $currentImage + $thumbnailsByPage;
                if ($nextImage !== '' && $nextImage > $lastImage) {
                    $nextImage = $lastImage;
                }
            }

            // Page view.
            else {
                // Détermination des références pour la toolbar.
                $firstImage = 1;
                $lastImage = $countImages;
                $prevImage = ($currentImage <= $firstImage) ? '' : $currentImage - 1;
                $nextImage = ($currentImage >= $lastImage) ? '' : $currentImage + 1;
                $labelPage = LiveBook::get_label_page($imagesFiles[$currentImage]);
                $imageOriginal = WEB_FILES . '/original/' . $imagesFiles[$currentImage]->filename;
                $imageFullsize = WEB_FILES . '/fullsize/' . $imagesFiles[$currentImage]->filename;
            }
        }

        include_once 'views' . DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'item-image.php';
    }

    /**
     * Display the metadata of an item.
     *
     * @return void
     *   Display the metadata of an item.
     */
    public function hookLiveBookItemMetadata($args)
    {
        $item = $args['view']->item;
        if ($item == NULL) {
            $item = get_current_record('item');
        }

        include_once 'views' . DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'item-metadata.php';
    }

    /**
     * Display the metadata of a file.
     *
     * @return void
     *   Display the metadata of a file.
     */
    public function hookLiveBookFileMetadata($args)
    {
        $view = $args['view'];
        $file = $this->_getCurrentImageFile();

        include_once 'views' . DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'file-metadata.php';
    }

    /**
     * Display the table of content of an item, if any.
     *
     * @return void
     *   Display the table of content.
     */
    public function hookLiveBookTableOfContent($args)
    {
        $item = $args['view']->item;
        if ($item == NULL) {
            $item = get_current_record('item');
        }

        $table_of_content = LiveBook::get_table_of_content($item);

        include_once 'views' . DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'table-of-content.php';
    }

    /**
     * Display the notes of an item, if any.
     *
     * @return void
     *   Display the notes.
     */
    public function hookLiveBookNotes($args)
    {
        $item = $args['view']->item;
        if ($item == NULL) {
            $item = get_current_record('item');
        }

        $notes = LiveBook::get_notes($item);

        include_once 'views' . DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'notes.php';
    }

    /**
     * Display a search field for the current item, and display search results.
     *
     * @return void
     *   Display the search/result.
     */
    public function hookLiveBookSearchContent($args)
    {
        $item = $args['view']->item;
        if ($item == NULL) {
            $item = get_current_record('item');
        }

        $hasText = LiveBook::has_text($item);

        $keywords = isset($_POST['words_to_search'])
            ? $this->sanitize_string($_POST['words_to_search'])
            : null;
        $hasKeywords = !empty($keywords);

        // Something to get only if there is text and keywords.
        if ($hasText && $hasKeywords) {
            $maxResults = 100;
            $results = LiveBook::search_text($keywords, $item, $maxResults);
            $countResults = count($results);

            // Prepare list of files and a mapping between image numbers and ids.
            $imagesFiles = $this->_getImagesFiles();
            $imagesMap = array();
            foreach ($imagesFiles as $key => $file) {
                $imagesMap[$file->id] = $key;
            }

            // Display found text of all pages where there is a result.
            // TODO Display with multipage. Currently, use simply a max.
            foreach ($results as $file_id => $textPage) {
                $currentImage = $imagesMap[$file_id];
                $labelPage = LiveBook::get_label_page($imagesFiles[$currentImage]);
                if (version_compare(PHP_MAJOR_VERSION, '5.4', '>=')) {
                    $textPage = htmlspecialchars($textPage, ENT_COMPAT | ENT_HTML401 | ENT_DISALLOWED, 'UTF-8');
                }
                else {
                    $textPage = htmlspecialchars($textPage, ENT_COMPAT, 'UTF-8');
                }
                $highlightText = $this->highlight($keywords, $textPage);

                $results[$file_id] = array(
                    'currentImage' => $currentImage,
                    'labelPage' => $labelPage,
                    'highlightText' => $highlightText,
                );
            }
        }

        include_once 'views' . DIRECTORY_SEPARATOR . 'public'. DIRECTORY_SEPARATOR . 'search-content.php';
    }

    /**
     * Return list of values of the Live Book url query.
     *
     * @return array
     *   Return array with current image, thumbnailing and page to find, for
     *   extraction via list().
     */
    private function _getCheckedQueryValues()
    {
        if ($this->_checkedQueryValues === null) {
            // Current image.
            $currentImage = (isset($_GET['image']) && $_GET['image'] > 0) ? (int) $_GET['image'] : 1;
            // Vignettage actif ou non.
            $thumbnailing = isset($_GET['v']) ? (boolean) $_GET['v'] : false;
            // Si utilisateur a recherché une page.
            $pageToFind = isset($_GET['find_page']) ? $this->sanitize_string($_GET['find_page']) : null;

            // The current image can be different if the user is looking for one.
            if (!$thumbnailing) {
                // Si l'utilisateur a cherché une page
                if ($pageToFind !== null) {
                    $foundPage = LiveBook::find_page($pageToFind, $this->_getImagesFiles());
                    if ($foundPage !== false) {
                        $currentImage = $foundPage;
                    }
                }
            }

            $this->_checkedQueryValues =  array(
                $currentImage,
                $thumbnailing,
                $pageToFind,
            );
        }

        return $this->_checkedQueryValues;
    }

    /**
     * Return list of images files of an item.
     *
     * @param Item $item
     *
     * @return array
     *   Return ordered array of images files, numbered from 1 (first image).
     */
    private function _getImagesFiles()
    {
        if ($this->_imagesFiles === null) {
            $item = get_current_record('item');

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

            $this->_imagesFiles = $imagesFiles;
        }
        return $this->_imagesFiles;
    }

    /**
     * Return the current image file.
     *
     * @param
     *
     * @return object File|null
     *   Return current file or null if none.
     */
    private function _getCurrentImageFile()
    {
        list($currentImage, $thumbnailing, $pageToFind) = $this->_getCheckedQueryValues();
        $imagesFiles = $this->_getImagesFiles();

        return isset($imagesFiles[$currentImage])
            ? $imagesFiles[$currentImage]
            : null;
    }

    private function _getIcon($file)
    {
        $imgURL = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views'. DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $file . '.png';
        return '<img src="' . $imgURL  . '">' . PHP_EOL;
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
     * Returns a sanitized string.
     *
     * @param string $string The string to sanitize.
     *
     * @return string The sanitized string (and cut it at 10000 characters).
     */
    public static function sanitize_string($string)
    {
        $string = strip_tags($string);
        $string = trim($string, ' /\\?<>:*%|"\'`&;');
        $string = preg_replace('/[\(\{]/', ' ', $string);
        $string = preg_replace('/[\)\}]/', ' ', $string);
        $string = preg_replace('/[[:cntrl:]\/\\\_\?<>:\*\%\|\"\'`\&\;#+\^\$\s]/', ' ', $string);
        return substr(preg_replace('/\s+/', ' ', $string), 0, 10000);
    }
}
