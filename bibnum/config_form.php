<?php 
/**
 * Config form include
 *
 * Included in the configuration page for the plugin to change settings.
 *
 * @package Reports
 * @copyright Copyright (c) 2009 Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */
?>
<div class="field">
    <label for="bibnum_pdftohtml_path">Path to PDFtoHTML</label>
    <?php echo __v()->formText('bibnum_pdftohtml_path', $pdftohtmlPath);?>
    <p class="explanation">Path to PDFtoHTML.  The path must point to a 
    commnand-line binary.  Check with your web host for more 
    information.</p>
</div>
<div class="field">
    <label for="bibnum_pdftk_path">Path to PDFtoolkit</label>
    <?php echo __v()->formText('bibnum_pdftk_path', $pdftkPath);?>
    <p class="explanation">Path to PDFtoolkit.  The path must point to a 
    commnand-line binary.  Check with your web host for more 
    information.</p>
</div>


<div class="field">
    <label for="bibnum_xml_directory">XML save directory</label>
    <?php echo __v()->formText('bibnum_xml_directory', $xmlDirectory);?>
    <p class="explanation">The directory on the server where generated XML from PDF files 
    will be saved.  This directory must be writable by the web server for 
    reporting to function.</p>
</div>
