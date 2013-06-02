<?php

/**
 * @file
 *   All functions of this file must be adapted to your needs, except names and
 *   parameters.
 *
 * @todo Integrate this in the configuration form.
 * @todo Use an abstract model class.
 * @todo Use Omeka 2.0 search functions.
 */

/**
 * Contains helpers to manage LiveBook.
 *
 * @package LiveBook
 */
class LiveBook {
    /**
     * Get the title of the page from the file object via a regex or other code.
     *
     * @param File $file
     *   An Omeka file object.
     *
     * @return
     *   The label of the file, else null.
     */
    public static function get_label_page($file) {
        if (is_null($file)) {
            return '';
        }

        $txt = metadata($file, array('Dublin Core', 'Title'));
        if (!$txt) {
            $txt = basename($file->original_filename);
        }

        return $txt;
    }

    /**
     * Find the page from a text inside a list of images files.
     *
     * @param string $pageToFind
     *   The input text to search.
     * @param array $imagesFiles
     *   The array of all images files of the item where to search the input text.
     *
     * @return integer|boolean
     *   If text is found, return the key of the matching file, else return false.
     */
    public static function find_page($pageToFind, $imagesFiles) {
        $pageToFind = strtolower($pageToFind);
        foreach ($imagesFiles as $key => $file) {
            $label = self::get_label_page($file);
            if (strpos(strtolower($label), $pageToFind) !== false) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Check if an item has transcripted text in files.
     *
     * @todo In this example, we search text in files descriptions.
     * @todo Use a direct database search.
     *      *
     * @param Item $item
     *   An Omeka item object with or without text from image files.
     *
     * @return boolean
     */
    public static function has_text($item) {
        foreach ($item->getFiles() as $file) {
            // Currently, this plugin manages only text on images.
            if ($file->hasThumbnail()) {
                $txt = metadata($file, array('Dublin Core', 'Description'));
                if ($txt) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Search keywords in the transcripted text of image files, if any.
     *
     * @todo In this example, we search text in files descriptions.
     * @todo Use a direct database search.
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
     *   values the text.
     */
    public static function search_text($keywords, $item, $maxResults = 100) {
        // Results should be an array.
        // Need a result by page and not by paragraph.
        $results_array = array();
        $countResult = 0;
        $keywords = strtolower($keywords);
        $key = 0;
        foreach ($item->getFiles() as $file) {
            // Currently, this plugin manages only text on images.
            if ($file->hasThumbnail()) {
                $key++;
                $txt = strtolower(metadata($file, array('Dublin Core', 'Description')));
                if (strpos($txt, $keywords)) {
                    $results_array[$file->id] = $txt;
                    $countResult++;
                    if ($countResult > $maxResults) {
                        break;
                    }
                }
            }
        }
        return $results_array;
    }

    /**
     * Get the table of content of an item.
     *
     * The function extracts table of content from the attached pdf file.
     */
    public static function get_table_of_content($item) {
        $txt = metadata($item, array('Dublin Core', 'Description'));

        return $txt;
    }

    /**
     * Get the notes of an item: can be full text, specific metadata or anything
     * else.
     */
    public static function get_notes($item) {
        $notes = metadata($item, array('Item Type Metadata', 'Text'));

        return $notes;
    }
}
