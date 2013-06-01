<?php

/**
 * @file
 *   All functions of this file must be adapted to your needs, except names and
 *   parameters.
 *
 * @todo Integrate this in the configuration form.
 * @todo Use an abstract model class.
 *
 * @note These functions are an example used by Université de Rennes 2 and have
 *   not been fully checked.
 */

/**
 * Contains helpers to manage LiveBook.
 *
 * @package LiveBook
 */
class LiveBook
{
    /**
     * Get the title of the page from the file object via a regex or other code.
     *
     * @param File $file
     *   An Omeka file object.
     *
     * @return
     *   The label of the file, else null.
     */
    public static function get_label_page($file)
    {
        if (is_null($file)) {
            return '';
        }

        $txt = $file->original_filename;

        $re1 = '.*?'; # Non-greedy match on filler
        $re2 = '(page)';  # Word 1
        $re3 = '(\\d+)';  # Integer Number 1
        if ($c = preg_match_all('/' . $re1 . $re2 . $re3 . '/is', $txt, $matches)) {
            $word1 = $matches[1][0];
            $int1 = $matches[2][0];
            $int1 = preg_replace('/^[0]{0,6}/', '', $int1);
            return ucwords($word1) . ' ' . $int1;
        }

        $re1 = '.*?'; # Non-greedy match on filler
        $re2 = '((?:[a-z][a-z]+))'; # Word 1
        $re3 = '(.)'; # Any Single Character 1
        $re4 = '((?:[a-z][a-z]+))'; # Word 2

        if ($c = preg_match_all('/' . $re1 . $re2 . $re3 . $re4 . '/is', $txt, $matches)) {
            $word1 = $matches[1][0];
            $c1 = $matches[2][0];
            $word2 = $matches[3][0];
            return ucwords($word1 . $c1 . $word2);
        }
    }

    /**
     * Find the page from a text inside a list of images files.
     *
     * @param string $pageToFind
     *   The input text to search.
     * @param array $imagesFiles
     *   The array of all images files of the item where to search the input text.
     *
     * @return
     *   If text is found, return the matching key of the list of names, else
     *   return NULL.
     */
    public static function find_page($pageToFind, $imagesFiles)
    {
        $listRecords = array();
        foreach ($imagesFiles as $key => $file) {
            $listRecords[$key] = $file->id;
        }

        $pageToFind = strtolower($pageToFind);

        $re1 = '.*?'; # Non-greedy match on filler
        $re2 = '(page0{0,6}' . $pageToFind . ')';  # Alphanum 1
        $re3 = '(\\.)';
        $match = '/' . $re1 . $re2 . $re3 . '/is';

        // Récupération du n° de page.
        foreach ($imagesFiles as $key => $file) {
            if (preg_match($match, $file->original_filename)) {
                // Récupération de la vue correspodant au n° de page recherchée.
                $foundPage = $listRecords[$key];
                return $foundPage;
            }
        }
    }

    /**
     * Check if an item has transcripted text.
     *
     * @param Item $item
     *   An Omeka item object with or without text from image files.
     *
     * @return boolean
     */
    public static function has_text($item)
    {
        $source = self::source_xml($item);
        return (boolean) $source;
    }

    /**
     * Search keywords in the transcripted text of image files, if any.
     *
     * The xml file of the text can be generated via the OCR of the PDF file.
     * Example of xml file:
     * <pdf2xml>
     *      <page number="X">
     *          <text>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</text>
     *          <text>Maecenas diam lacus, blandit sit amet tempor auctor, luctus at eros</text>
     *          <text>Morbi ligula arcu, aliquam non ornare nec, venenatis eget lorem.</text>
     *      </page>
     *      <page number="X">...
     *
     * @param string $keywords
     *   Keywords to search.
     * @param Item $item
     *   An Omeka item object with text from image files.
     * @param integer $maxResults
     *   Number maximum of result to return.
     *
     * @return array
     *   Array where keys are file record ids where the keywords are found and
     *   values the element text id.
     */
    public static function search_text($keywords, $item, $maxResults = 100)
    {
        $source = self::source_xml($item);
        if (empty($source)) {
            return array();
        }

        // Traitement des mots recherchés (accents, encodage, etc)
        $match = trim($keywords);
        // Suppression des caractères diacritiques via utf8 et regex.
        $match = utf8_decode($match);
        $match = LiveBookPlugin::regex_accents($match);
        $match = utf8_encode($match);

        // Traitement du fichier XML avec simpleXML.
        $xml = new SimpleXMLElement($source, null, true);
        $results = $xml->xpath('page');

        // Transform results into an array: need a result by page and not by
        // paragraph.
        $results_array = array();
        $i = 0;
        foreach ($results as $page) {
            $results_array[$page['number']] = implode(" \n", $page->text);
            $i++;
            if ($i > $maxResults) {
                break;
            }
        }

        return $results_array;
    }

    /**
     * Get the table of content of an item.
     *
     * The function extracts table of content from the attached pdf file.
     */
    public static function get_table_of_content($item)
    {
        //création condition : fichier est un pdf
        $SupportedFormats = array('pdf' => 'Portable Document Format File',);
        // Set the regular expression to match selected/supported formats.
        $supportedFormatRegEx = '/\.' . implode('|', array_keys($SupportedFormats)) . '$/';

        // Iterate through the item's files afin de récupérer le fichier pdf de l'ouvrage
        $toc = '';
        $i = 1;
        while (loop_files_for_item($item)) {
            $file = get_current_file();
            // Embed only those files that end with the selected/supported formats.
            if (preg_match($supportedFormatRegEx, $file->archive_filename)) {
                // Set the document's absolute URL.
                // Note: file_download_uri($file) does not work here. It results
                // in the iPaper error: "Unable to reach provided URL."
                $documentUrl = WEB_FILES . DIRECTORY_SEPARATOR . $file->archive_filename;
                $documentfile = FILES_DIR. DIRECTORY_SEPARATOR . $file->archive_filename;
                $output = get_option('live_book_xml_directory') . DIRECTORY_SEPARATOR . $item->id;
                $source = self::source_xml($item);

                // Si le fichier n'existe pas déjà, création du fichier xml
                // comprenant l'ocr du PDF
                // Nécessite l'installation de la librairie pdftohtml (poppler-utils)
                if (!file_exists($source)) {
                    exec('' . get_option('live_book_pdftohtml_path') . " -xml -hidden $documentfile $output", $retour);
                }
                $report = $output . '.txt'; //préparation sortie report.txt
                // Si le fichier n'existe pas déjà, création du fichier txt
                // comprenant les métadonnées et bookmarks du PDF
                // Nécessite l'installation de la librairie pdftk (poppler-utils)
                if (!file_exists($report)) {
                    exec('' . get_option('live_book_pdftk_path') . " $documentUrl dumpdata output $report", $return);
                }

                //Création de la table des matières
                $pdftoc = array();
                $BookmarkTitle = '#BookmarkTitle: #'; // pour repérer les libellés des bookmarks
                $BookmarkLevel = '#BookmarkLevel: #'; // pour repérer le niveau des bookmarks
                $BookmarkPageNumber = '#BookmarkPageNumber: #'; // pour repérer le numéro de page du bookmark
                // Initialisation des tableaux (libellés, niveaux et numéros de page).
                $btitle = array();
                $level = array();
                $pgnb = array();
                $trimmed = file("$report", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($trimmed as $j => $value) {
                    if (preg_match($BookmarkTitle, $value)) {
                        $valtit = preg_replace($BookmarkTitle, '', $value);
                        $btitle[$j] = $valtit;
                    }

                    if (preg_match($BookmarkLevel, $value)) {
                        $valev = preg_replace($BookmarkLevel, '', $value);
                        $k = $j - 1;
                        $level[$k] = $valev;
                    }

                    if (preg_match($BookmarkPageNumber, $value)) {
                        $valpg = preg_replace($BookmarkPageNumber, '', $value);
                        $l = $j - 2;
                        $pgnb[$l] = $valpg;
                    }
                }
                // Préparation de la table des matières.
                $toc .= '<div class="index">';
                foreach ($btitle as $j => $val) {
                    if ($level[$j] > 3) {
                    }
                    // Zappe certains bookmarks inutiles.
                    elseif (preg_match('#(page|garde|Garde|Page)#', $val)) {
                    }
                    else {
                        $toc .= '<div class="toc_section' . $level[$j] . '">';
                        $nb = $pgnb[$j] - 1;
                        $toc .= '<a HREF="?image=' . $nb . '">';
                        $toc .= $val . '</a></div>';
                    }
                }
                $toc .= '</div>';
                $i++;
            }
        }
        return $toc ;
    }

    /**
     * @note Below functions are specific to this process and can be removed if not
     * used.
     */

    /**
     * Get the xml source that contains the transcripted text of an item.
     *
     * @param Item $item
     *   An Omeka item object.
     *
     * @return
     *   Path of source file if any, else null.
     */
    private function source_xml($item)
    {
        $source = get_option('live_book_xml_directory') . DIRECTORY_SEPARATOR . $item->id . '.xml';
        if (file_exists($source)) {
            return $source;
        };
    }
}
