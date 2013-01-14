<?php
define('bibnum_PLUGIN_VERSION', get_plugin_ini('bibnum', 'version'));

add_plugin_hook('install', 'bibnum_install');
add_plugin_hook('uninstall', 'bibnum_uninstall');
add_plugin_hook('config_form', 'bibnum_config_form');
add_plugin_hook('config', 'bibnum_config');
add_plugin_hook('public_theme_header', 'bibnum_public_theme_header');
add_plugin_hook('public_append_to_items_show', 'bibnum_append_to_item');
add_plugin_hook('public_append_to_items_show', 'bibnum_tableOfContent');
add_plugin_hook('public_append_to_items_show', 'bibnum_searchContent');


define('BIBNUM_PLUGIN_DIRECTORY', dirname(__FILE__));
define('BIBNUM_XML_DIRECTORY', get_option('bibnum_xml_directory'));
define('BIBNUM_PDFTOHTML', get_option('bibnum_pdftohtml_path'));
define('BIBNUM_PDFTK', get_option('bibnum_pdftk_path'));


//installation du plugin dans omeka
function bibnum_install()
{
	set_option('bibnum_plugin_version', BIBNUM_PLUGIN_VERSION);
	set_option('bibnum_pdftohtml_path', $pdftohtmlPath);
	set_option('bibnum_pdftk_path', $pdftkPath);   
	set_option('bibnum_xml_directory', BIBNUM_PLUGIN_DIRECTORY.
		DIRECTORY_SEPARATOR.
		'xml');                                                                                                                      
}

//désinstallation du plugin
function bibnum_uninstall()
{
	delete_option('bibnum_plugin_version');
	delete_option('bibnum_pdftohtml_path');
	delete_option('bibnum_pdftk_path');
	delete_option('bibnum_xml_directory');
}

//ajout d'une css et de codes js ds le header du theme
function bibnum_public_theme_header($request) 
{
	//js
	echo bibnum_javascripts();
	//css
	$html .= bibnum_css('bibnum', 'public');

}


/**
* Shows the configuration form.
*/
function bibnum_config_form()
{
	$pdftohtmlPath = get_option('bibnum_pdftohtml_path');
	$pdftkPath = get_option('bibnum_pdftk_path');
	$xmlDirectory = get_option('bibnum_xml_directory');

	include 'config_form.php';
}

/**
* Processes the configuration form.
*/
function bibnum_config()
{
	set_option('bibnum_pdftohtml_path', $_POST['bibnum_pdftohtml_path']);
	set_option('bibnum_pdftk_path', $_POST['bibnum_pdftk_path']);
	set_option('bibnum_xml_directory', $_POST['bibnum_xml_directory']);
}


//fonction qui va permettre, à l'aide d'expressions régulières, de récupérer le "libellé de la page " en fonction du nom de fichier
//A modifier en fonction de votre système de nommage
function label_page($txt) 
{
	$re1='.*?';	# Non-greedy match on filler
	$re2='(page)';	# Word 1
	$re3='(\\d+)';	# Integer Number 1
	if ($c=preg_match_all ("/".$re1.$re2.$re3."/is", $txt, $matches))
	{
		$word1=$matches[1][0];
		$int1=$matches[2][0];
		$int1 = preg_replace( "/^[0]{0,6}/", "", $int1 );  
		return ucwords($word1)." ". $int1 ;
	}
	  $re1='.*?';	# Non-greedy match on filler
	  $re2='((?:[a-z][a-z]+))';	# Word 1
	  $re3='(.)';	# Any Single Character 1
	  $re4='((?:[a-z][a-z]+))';	# Word 2

	  if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4."/is", $txt, $matches))
	  {
	 
		$word1=$matches[1][0];
		$c1=$matches[2][0];
		$word2=$matches[3][0];
		return  ucwords($word1.$c1.$word2);
	}

}

function regexAccents($chaine) {
	$accent = array('a','à','á','â','ã','ä','å','c','ç','e','è','é','ê','ë','i','ì','í','î','ï','o','ð','ò','ó','ô','õ','ö','u','ù','ú','û','ü','y','ý','ý','ÿ');
	$inter = array('%01','%02','%03','%04','%05','%06','%07','%08','%09','%10','%11','%12','%13','%14','%15','%16','%17','%18','%19','%20','%21','%22','%23','%24','%25','%26','%27','%28','%29','%30','%31','%32','%33','%34','%35');
	$regex = array('(a|à|á|â|ã|ä|å)','(a|à|á|â|ã|ä|å)','(a|à|á|â|ã|ä|å)','(a|à|á|â|ã|ä|å)','(a|à|á|â|ã|ä|å)','(a|à|á|â|ã|ä|å)','(a|à|á|â|ã|ä|å)',
		'(c|ç)','(c|ç)',
		'(è|e|é|ê|ë)','(è|e|é|ê|ë)','(è|e|é|ê|ë)','(è|e|é|ê|ë)','(è|e|é|ê|ë)',
		'(i|ì|í|î|ï)','(i|ì|í|î|ï)','(i|ì|í|î|ï)','(i|ì|í|î|ï)','(i|ì|í|î|ï)',   '(o|ð|ò|ó|ô|õ|ö)','(o|ð|ò|ó|ô|õ|ö)','(o|ð|ò|ó|ô|õ|ö)','(o|ð|ò|ó|ô|õ|ö)','(o|ð|ò|ó|ô|õ|ö)','(o|ð|ò|ó|ô|õ|ö)','(o|ð|ò|ó|ô|õ|ö)',         '(u|ù|ú|û|ü)','(u|ù|ú|û|ü)','(u|ù|ú|û|ü)','(u|ù|ú|û|ü)',
		'(y|ý|ý|ÿ)','(y|ý|ý|ÿ)','(y|ý|ý|ÿ)','(y|ý|ý|ÿ)');
	$chaine = str_ireplace($accent, $inter, $chaine);
	$chaine = str_replace($inter, $regex, $chaine);      
	return $chaine;
};


//fonction de surlignage d'un texte
function highlight($mots,$chaine)
	//met en gras les mots cles pour les resultats du moteur de recherche
{ return eregi_replace($mots,"<span class='highlight'>\\0</span>",$chaine); }   

/**
* Returns HTML code to embed a shared css file of the plugin
*  Note: If the controller's module is that of another plugin, 
*  then the js() and css() functions will not find this plugin's javascripts or css files.
*  This is a bug. Until this bug is fixed we must use image_annotation_js and image_annotation_css
* 
* @param string $file The name of the css file without the extension.
* @param string $themeType The type of theme ('public', 'admin', or 'shared')
* @return string The HTML code to embed a shared css file of the plugin
*/

function bibnum_css($file, $themeType='public')
{
	$cssURL = WEB_PLUGIN . '/bibnum/views/' . $themeType . '/css/' . $file . '.css';
	echo '<link rel="stylesheet" media="screen" href="' . $cssURL . '" />'."\n";
}

function bibnum_img($file) 
{
	$imgURL = WEB_PLUGIN . '/bibnum/views/public/images/' . $file . '.png';
	return '<img src="'. $imgURL  .'"/>'."\n";
}


/**
* Returns HTML code to embed a shared javascript file of the plugin
*  Note: If the controller's module is that of another plugin, 
*  then the js() and css() functions will not find this plugin's javascripts or css files.
*  This is a bug. Until this bug is fixed we must use image_annotation_js and image_annotation_css
* 
* @param string $file The name of the javascript file without the extension. 
* @return string The HTML code to embed a shared javascript file of the plugin
*/
function bibnum_js($file) 
{
	$jsURL = WEB_PLUGIN . '/bibnum/shared/javascripts/' . $file . '.js';
	return '<script type="text/javascript" src="'. $jsURL  .'" charset="utf-8"></script>'."\n";
}

function bibnum_javascripts()
{
	$html = '';
	//lien vers javascript pour le zoom sur les images (utilisation de la librairie TJPZoom)
	//$html .= bibnum_js('tjpzoom');
	//$html .= bibnum_js('tjpzoom_config_purity');     
	return $html;
}  

/**
Patch suite v1.3 récupère nom du fichier archivé
*/
function findFilePath ($image){
	$db = get_db();
	$query = $db->select()->from(array($db->Files), 'archive_filename')->where('original_filename = ?', $image);
	return $db->fetchOne($query);  }


	function vignette($listing)	{
		$vignette=$listing[$i];
		$page=$vignette;
		$page=label_page($page);

		if ($vignette!="")
		{
			$vignette = findFilePath($vignette);
			$vignettes.="<li class=\"Object\"><span class='numero'>$page</span><br/>\n";
			$vignettes.="<a class='vignette' href=\"?image=$i#bibnum\"><img src=\"$thumb/$vignette\"  alt=\"image-".$i."\" class=\"object-representation\" title=\"consulter cette page\"/></a><br /></li>\n";
		} 
		echo $vignettes;
	} 

/**
Fonction principale du plugin, celle qui sera appelée dans le thème
*/
function bibnum_append_to_item()
{

	//création d'un tableau composé de l'ensemble des images de l'item consulté
	$listing= array();
	$i=0;

	while(loop_files_for_item($item)) {    	 
		$file = get_current_file();
		if ($file->hasThumbnail()) {			
			//$listing[$i]=$file->archive_filename;//Création du tableau
			$listing[$i]=item_file('Original Filename');

		}
		$i++;
	}
	//compte le nb d'imgages dans le tableau
	$nbimg = count($listing); 

	// DESSOUS : si $listing n'est pas un tableau, message, sinon, traitement
	if(!is_array($listing))
	{
		$html .="<br><br>\n";
		$html .= "problème :-( <br><br>\n";
		$html .= "</a>\n";
		$html .= "</div>\n";
	}
	else
	{    	
		// TRI DE LA LISTE DES FICHIERS
		sort($listing); 	
	}


	$id=item('Id');//id de l'ouvrage
	$find_page=$_GET['find_page'];// si utilisateur a recherché une page
	$image=$_GET['image'];// si image courante (au niveau de l'url)
	$v=$_GET['v'];	// vignettage actif
	$permaRep = CURRENT_BASE_URL .'/files/display';
	$rep= WEB_FULLSIZE ;//répertoire dans lequel se trouvent les images de grande taille
	$thumb= WEB_THUMBNAILS ;// répertoire des vignettes
	$files=WEB_FILES ;// répertoire des images originales

	if($_GET['image']==true){
		$image=$_GET['image'];
		$image = $listing[$image];		
	}
	else
	{
		$image = $listing[0];			
	}


	// LISTING DE TOUTES LES IMAGES DANS LE REPERTOIRE POUR VIGNETTAGE	
	if ($v=="1") {	
		// on cherche l'image courante dans le tableau et on récupère sa position	
		$imageCourante = array_search($image, $listing);	
		// on calcule les postions des photos précédentes et suivantes, debut et fin
		//Il y a 35 vues par page dans la fonction vignettage

		//page précédente	    
		if (($imageCourante < 35)&&($imageCourante!=0))
		{		
			$precedent=0;		
			$prec='1';
		}
		else 
		{	
			$prec = $imageCourante - 35;
			$precedent=$prec;
			$prec=($prec+1);
		}	
		if ($prec<0){$prec="NULL";}

		//page suivante	
		$suiv = $imageCourante + 35;
		$suivant=$listing[$suiv];
		$suivant = array_search($suivant, $listing);
		if ($suivant!=""){ $suiv=($suiv+1);}
		else {$suiv="";}

		$nombre = count($listing);

		$finnb=($nombre-1);	
		$fin= $listing[$finnb];
		//page de fin
		$fin = array_search($fin, $listing);	
		$debut=0;

		//NBRE de PAGES au total dans le répertoire
		$nb_pages=$result = count ($listing);
		


		//1ere ligne
		$vignettes.="<ul class=\"row\">\n";	
		for($i=$imageCourante;$i<$imageCourante+5;$i++) 
		{ 
			$vignette=$listing[$i];
			$page=$vignette;
			$page=label_page($page);

			if ($vignette!="")
			{
				$vignette = findFilePath($vignette);
				$vignettes.="<li class=\"Object\"><span class='numero'>$page</span><br/>\n";
				$vignettes.="<a class='vignette' href=\"?image=$i#bibnum\"><div id=\"imgitem\"><img src=\"$thumb/$vignette\"  alt=\"image-".$i."\" class=\"object-representation\" title=\"consulter cette page\"/></div></a><br /></li>\n";
			} 

		}
		$vignettes.="</ul>\n"; 

		//2nde ligne
		$vignettes.="<ul class=\"row\">\n";	
		for($i=$imageCourante+5;$i<$imageCourante+10;$i++) 
		{ 	
			$vignette=$listing[$i];			
			$page=$vignette;
			$page=label_page($page);

			if ($vignette!="")
			{
				$vignette = findFilePath($vignette);
				$vignettes.="<li class=\"Object\"><span class='numero'>$page</span><br/>\n";
				$vignettes.="<a class='vignette' href=\"?image=$i#bibnum\"><div id=\"imgitem\"><img src=\"$thumb/$vignette\"  alt=\"image-".$i."\" class=\"object-representation\" title=\"consulter cette page\"/></div></a><br /></li>\n";
			} 
		}
		$vignettes.="</ul>\n";  

		//3eme ligne	
		$vignettes.="<ul class=\"row\">\n";	
		for($i=$imageCourante+10;$i<$imageCourante+15;$i++) 
		{ 
			$vignette=$listing[$i];			
			$page=$vignette;
			$page=label_page($page);

			if ($vignette!="")
			{
				$vignette = findFilePath($vignette);
				$vignettes.="<li class=\"Object\"><span class='numero'>$page</span><br/>\n";
				$vignettes.="<a class='vignette' href=\"?image=$i#bibnum\"><div id=\"imgitem\"><img src=\"$thumb/$vignette\"  alt=\"image-".$i."\" class=\"object-representation\" title=\"consulter cette page\"/></div></a><br /></li>\n";
			} 
		}
		$vignettes.="</ul>\n"; 

		//4eme ligne	
		$vignettes.="<ul class=\"row\">\n";	
		for($i=$imageCourante+15;$i<$imageCourante+20;$i++) 
		{	
			$vignette=$listing[$i];			
			$page=$vignette;
			$page=label_page($page);
			if ($vignette!="")
			{
				$vignette = findFilePath($vignette);	
				$vignettes.="<li class=\"Object\"><span class='numero'>$page</span><br/>\n";
				$vignettes.="<a class='vignette' href=\"?image=$i#bibnum\"><div id=\"imgitem\"><img src=\"$thumb/$vignette\"  alt=\"image-".$i."\" class=\"object-representation\" title=\"consulter cette page\"/></div></a></li>\n";
			} 
		}
		$vignettes.="</ul>\n"; 

		//5eme ligne	
		$vignettes.="<ul class=\"row\">\n";	
		for($i=$imageCourante+20;$i<$imageCourante+25;$i++) 
		{ 	
			$vignette=$listing[$i];
			//
			$page=$vignette;
			$page=label_page($page);
			if ($vignette!="")
			{	
				$vignette = findFilePath($vignette);		
				$vignettes.="<li class=\"Object\"><span class='numero'>$page</span><br/>\n";
				$vignettes.="<a class='vignette' href=\"?image=$i#bibnum\"><div id=\"imgitem\"><img src=\"$thumb/$vignette\"  alt=\"image-".$i."\" class=\"object-representation\" title=\"consulter cette page\"/></div></a><br /></li>\n";
			} 
		}
		$vignettes.="</ul>\n";  

		//dernière ligne
		$vignettes.="<ul class=\"row\">\n";	
		for($i=$imageCourante+25;$i<$imageCourante+30;$i++) 
		{ 	
			$vignette=$listing[$i];		
			$page=$vignette;
			$page=label_page($page);

			if ($vignette==true)
			{
				$vignette = findFilePath($vignette);
				$vignettes.="<li class=\"Object\"><span class='numero'>$page</span><br/>\n";
				$vignettes.="<a class='vignette' href=\"?image=$i#bibnum\"><div id=\"imgitem\"><img src=\"$thumb/$vignette\"  alt=\"image-".$i."\" class=\"object-representation\" title=\"consulter cette page\"/></div></a><br /></li>\n";
			}
		}
		$vignettes.="</ul>\n";
	}//fin du if (vignettage)

	// LISTING DE TOUTES LES IMAGES DANS LE REPERTOIRE POUR AFFICHAGE NORMALE
	else 
	{
		//Si l'utilisateur a cherché une page
		if ($find_page==true)
		{ 
			$find_page= strtolower($find_page); 
			
			$re1='.*?';	# Non-greedy match on filler
			$re2='(page0{0,6}'.$find_page.')';	# Alphanum 1
			$re3='(\\.)';
			
			$match = "/".$re1.$re2.$re3."/is";//récupération du n° de page
			foreach ($listing as $j => $value) 
			{
				if (preg_match($match, $value))
				{		
					$find=$j;
					$image=$listing[$find];//récupération de la vue correspodant au n° de page recherchée
				}
				else { echo "";}
			} 
		}
		else{$imageCourante=0;} // si aucune page recherchée, on se positionne sur l'image courante

		//Numéro de la page courante	   
		$imageCourante = array_search($image, $listing);
		$imageCourante=($imageCourante+1); 

		//page de début
		$debut=$listing[0];
		$debut = array_search($debut, $listing);


		//page précédente
		$prec = ($imageCourante - 1);	
		if ($prec!=""){$pre=($prec-1);}
		else {$prec="NULL";}
		$precedent=$listing[$pre];
		$precedent = array_search($precedent, $listing);


		//page suivante
		$suiv = ($imageCourante + 1) ;	
		if ($suiv!=""){$sui=($suiv-1);;}
		else {$suiv="";}
		$suivant=$listing[$sui];
		$suivant = array_search($suivant, $listing);


		//NBRE de PAGES au total dans le répertoire
		$nb_pages=$result = count ($listing);

		//Page de fin
		$fin=($nb_pages-1);
		$fin= $listing[$fin]; 
		$fin= array_search($fin, $listing);

	}

	// TABLEAU EN-TETE Si besoin
	$keyimg= array_search($image, $listing);

	// TABLEAU EN-TETE Si besoin
	$tableau1="";
	//NAVIGATION

	// DESSOUS : calcul des liens sur images navigG/D et navigFinG/D, 
	if ($prec!="NULL")     // partie gauche de la barre : Page précédente
	{
		//vers la premère page
		$navigFinG="<a rev=\"start\" href=\"?image=$debut&amp;v=$v#bibnum\" title=\"première page\" id=\"premiere\"></a>";
		//page précédente (bouton barre de navigation)                   	
		$prec="<a rel=\"prev\" id=\"precedente\" href=\"?image=$precedent&amp;v=$v#bibnum\"  title=\"page précédente\"></a>";
		//page précédente (survol sur l'image)
		$prev="<a href='?image=$precedent&amp;v=$v#bibnum' id='prev' title=\"Page précédente\" ></a>";
	}
	else
	{
		//si pas fr page précédente
		$navigFinG="<a class=\"blankG\"></a>";                    
		$prec="<a class=\"blankG\"></a>";
		$prev="";
	}
	if ($suivant!="")// partie droite de la barre : Page suivante
	{
		//dernière page
		$navigFinD="<a href=\"?image=$fin&amp;v=$v#bibnum\"  title=\"dernière page\" id=\"derniere\"></a>";
		//page suivante (bouton)
		$suiv="<a rel=\"next\" id=\"suivante\" href=\"?image=$suivant&amp;v=$v#bibnum\" title=\"page suivante\"></a>";
		//page suivante (survol sur l'image)
		$next="<a href='?image=$suivant&amp;v=$v#bibnum' id='next' title=\"Page suivante\"></a>";

	}
	else
	{
		//si pas de page suivante
		$navigFinD="<a class=\"blank\"></a>";                   
		$suiv="<a class=\"blank\"></a>";
		$next="";
	}

	// DESSOUS : calcul de l'icone et du lien pour le "zoom" ; 	
	//$magnifier = bibnum_img('magnifier');//loupe active
	//$magnifier_off = bibnum_img('magnifier_off');// loupe désactivée 

	//$zoomP='<a href="#" title="loupe" id="loupe" onclick="if(TJPzoomswitch(document.getElementById(\'imgZ\'))) {this.innerHTML=\'<img src='. WEB_PLUGIN . '/bibnum/views/public/images/magnifier_off.png>\'} else {this.innerHTML=\'<img src='. WEB_PLUGIN . '/bibnum/views/public/images/magnifier.png>\'}; return false;"><img src="'. WEB_PLUGIN . '/bibnum/views/public/images/magnifier.png"></a>';


	$page=$image;
	$page=label_page($page); //nom de la page
	$image = findFilePath ($image);


	//$zoom="<div id='lightbox'><div class='item-file image-jpeg'><a href=\"$files/$image\" title=\"Vue en plein écran\"><img class='page_num' src=\"$rep/$image\"   onmouseover=\"TJPzoomif(this,'$files/$image?adsfg');\" id=\"imgZ\"></a></div></div> ";
	
	$zoom="<div><div class='item-file image-jpeg'><a class='fancyitem' href=\"$files/$image\"   title=\"".$page."\" rel=\"fancy_group\"><img class='page_num'   src=\"$rep/$image\" alt=\"".$page."\" title=\"".$page."\" /></a></div></div> ";


	//DESSOUS : affiche tableau contenant la barre de navigation	  
	$FORM="<form class='ouvnum' action=\" \" method=\"get\"> 
		  <p>Page <input class='outil' type='text' name='find_page' value= '' size='3' maxlength='10'/></p></form>";
	$VIGN1="<a href=\"?image=$keyimg&amp;v=1#bibnum\" title=\"Affichage vignettes\"  id=\"vign1\" ></a>";

	//affichage vue simple
	$img.="<div id=\"pagprev\">$prev</div>$zoom<div id=\"pagnext\">$next</div>";


	// FONCTION ZOOMIFY Pour les items avec une seule image
	// Si Item ne possède qu'une seule image, on afffcihe fonction Zoomify (l'image doit au préalable avoir été traitée avec zoomify
	if($nbimg=="1") 
	{  	
		while(loop_files_for_item($item)) 
		{    	 
			$file = get_current_file();
			if ($file->hasThumbnail()) 
			{
				$imgZoom=$file->original_filename;//Création du tableau
				$imgZoom = preg_replace('#.jpg#', '', $imgZoom);
			}
			$i++;
		}	
		$ZoomRep = CURRENT_BASE_URL .'/archive/zoomify';//lien vers répertoire zoomify	
		$html.='<div align="center">';
		$html.="\n";
		$html.='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" CODEBASE="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" WIDTH="550" HEIGHT="450" ID="theMovie">';
		$html.="\n";
		$html.='<param name="flashvars" value="zoomifyImagePath='.$ZoomRep.'/'.$imgZoom.'">';
		$html.="\n";
		$html.='<param name="menu" value="false">';
		$html.="\n";
		$html.='<param name="src" value="'.$ZoomRep.'/ZoomifyViewer.swf">';
		$html.="\n";
		$html.='<embed flashvars="zoomifyImagePath='.$ZoomRep.'/'.$imgZoom.'" src="'.$ZoomRep.'/ZoomifyViewer.swf" menu="false" pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?p1_prod_version=shockwaveflash"  width="550" height="450" name="themovie"></embed>';
		$html.="\n";
		$html.='</object>';
		$html.="\n";
		$html.='</div>';
	}
	else 
	{ 
		// AFFICHAGE DES TABLEAUX  
		//on affiche barre de navigation
		$html.= '<div id="viewer"><div id="item-images">';
		$toolbar.='<div id="bibnum">';		
		$toolbar.='<div id="tools">';				
		$toolbar.= $navigFinG;				
		$toolbar.= $prec; 


		if ($v=="1") {$toolbar.="<a class=\"blankL\"></a>";}//si vignettes, on désactive la loupe
		else{$toolbar.= "<a class=\"blankL\"></a>";} //$zoomp
		$toolbar.= $FORM; 
		$toolbar.= $VIGN1;
		$toolbar.= $suiv; 				
		$toolbar.= $navigFinD; 
		$toolbar.='</div>';			
		$toolbar.='</div>';	
		$html.=$toolbar;	

		//si fonction vignettage activée, on affiche les vues miniatures
		if ($v=="1") 
		{
			$html.= "<div id=\"vignettes\" ><div id=\"pagprev\">$prev</div>$vignettes<div id=\"pagnext\">$next</div></div>";
		}
		else 
		{



			$html.="<div id='numero'><b>".$page."</b></div>";//numéro de la page
			$html.= "<div id=\"main_page\" >$img</div>";				
		}	
		$html.=$toolbar;
		$html.= '</div></div>';	

	}  
	return $html ;//on affiche l'ensemble
}



//Fonction permettant de récupérer la table des matière d'un PDF
function bibnum_tableOfContent($item=null) 
{	
	if ($item == null) 
	{
		$item = get_current_item();//si null, récupère l'item actuellement consulté
	}	

	//création condition : fichier est un pdf
	$SupportedFormats = array('pdf' => 'Portable Document Format File',);
	// Set the regular expression to match selected/supported formats.
	$supportedFormatRegEx = '/\.'.implode('|', array_keys($SupportedFormats)).'$/';
	$i = 1;


	// Iterate through the item's files afin de récupérer le fichier pdf de l'ouvrage
	while(loop_files_for_item($item)) 
	{
		$file = get_current_file();
		// Embed only those files that end with the selected/supported formats.
		if (preg_match($supportedFormatRegEx, $file->archive_filename)) 
		{                
			// Set the document's absolute URL.
			// Note: file_download_uri($file) does not work here. It results 
			// in the iPaper error: "Unable to reach provided URL."
			$documentUrl = WEB_FILES . DIRECTORY_SEPARATOR . $file->archive_filename;			
			$documentfile = FILES_DIR. DIRECTORY_SEPARATOR .$file->archive_filename; 
			$namexml = $item->id;
			$output = BIBNUM_XML_DIRECTORY . DIRECTORY_SEPARATOR . $namexml;
			$source = $output.'.xml'; //préparation de la sortie en xml			

			//si le fichier n'existe pas déjà, création du fichier xml comprenant l'ocr du PDF
			//nécessite l'installation de la librairie pdftohtml (poppler-utils)
			if (file_exists($source)) {} 
			else 
			{
				exec("". BIBNUM_PDFTOHTML ." -xml -hidden $documentfile $output", $retour);		 

			}				
			$report = $output.'.txt'; //préparation sortie report.txt
			//si le fichier n'existe pas déjà, création du fichier txt comprenant les métadonnées et bookmarks du PDF
			//nécessite l'installation de la librairie pdftohtml (poppler-utils)		
			if (file_exists($report)) {} 
			else 
			{		
				exec("". BIBNUM_PDFTK ." $documentUrl dumpdata output $report", $return);
			}		 

			//Création de la table des matières
			$pdftoc =array();
			$BookmarkTitle = '#BookmarkTitle: #'; //pr repérer les libellés des bookmarks
			$BookmarkLevel = '#BookmarkLevel: #'; //pr repérer le niveau des bookmarks
			$BookmarkPageNumber = '#BookmarkPageNumber: #';//pour repérer le numéro de page du bookmark
			//initialisation des tableaux (libellés, niveaux et numéros de page)
			$btitle=array();
			$level=array();
			$pgnb=array();
			$trimmed = file("$report", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); 

			foreach ($trimmed as $j => $value) 
			{	 	
				if (preg_match($BookmarkTitle, $value)) 
				{
					$valtit = preg_replace($BookmarkTitle, '', $value);
					$btitle[$j]=$valtit;			
				}
				else {}

				if (preg_match($BookmarkLevel, $value)) 
				{
					$valev = preg_replace($BookmarkLevel, '', $value);
					$k = ($j-1);
					$level[$k]=$valev;			
				}
				else {}

				if (preg_match($BookmarkPageNumber, $value)) {
					$valpg = preg_replace($BookmarkPageNumber, '', $value);
					$l = ($j-2);
					$pgnb[$l]=$valpg;			
				}
				else {}
			}
			//préparation de la table des matières
			$toc.= '<div class="toc">';		
			foreach ($btitle as $j => $val) 
			{
				if ($level[$j]>3){}
				//zappe certains bookmarks inutiles
				elseif (preg_match('#(page|garde|Garde|Page)#', $val)) {}
				else{$toc.='<div class="toc_section'.$level[$j].'">';
				$nb=($pgnb[$j]-1);
				$toc.= '<a HREF="?image='.$nb.'">';
				$toc.= $val.'</a></div>';}
			}
			$i++;
		}
	}
	//on affiche le résultat
	$toc.= '</div>';
	return $toc ;
}


//Fonction permettant de rechercher dans le fichier xml généré à partir de l'ocr du PDF
function bibnum_searchContent($item=null) { 


	//si null, récupère l'item actuellement consulté
	if ($item == null)
	{
		$item = get_current_item();
	}
	//récupération du fichier xml à traiter en fonction de l'id de l'item
	$namexml = $item->id; 
	$output = BIBNUM_XML_DIRECTORY . DIRECTORY_SEPARATOR . $namexml;
	$source = $output.'.xml'; 

	//Formulaire de recherche
	$html=''; 
	$html.='<div id="search-ti">';
	$html.='<h2>Rechercher dans ce document</h2>';
	$html.='<form action="#search-ri" method="post">';
	$html.='<input type="hidden" name="action" value="seek">';
	$html.='<input class="search" type="text" name="mots" size=35 maxlength=100 value="">';
	$html.='<input type="submit" value="ok" id="submit_search">';
	$html.='</form>';
	$html.='</div>';
	$mots=$_POST['mots'];


	//si mots-clés recherchés 
	if ($mots!="") 
	{ 
		$listing= array();
		$i=0;
		while(loop_files_for_item($item)) 
		{
			$file = get_current_file();
			if ($file->hasThumbnail()) 
			{
				$listing[$i]=$file->original_filename;//Création du tableau avec les images de l'item
			}
			$i++;
		}
		sort($listing);	// trie le tableau


		//traitement des mots recherchés (accents, encodage, etc)
		$match = trim($mots);//Supprime les espaces (ou d'autres caractères) en début et fin de chaîne
		$match =utf8_decode($match);//decodage utf8
		$match =regexAccents($match);//traitement des accents
		$match =utf8_encode($match);//encodage accents

		//traitement du fichier XML avec simpleXML 
		// exemple fichier xml :
		//<pdf2xml>
		//	<page number="X">	
		//		<text>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</text>
		//		<text>Maecenas diam lacus, blandit sit amet tempor auctor, luctus at eros</text>
		//		<text>Morbi ligula arcu, aliquam non ornare nec, venenatis eget lorem.</text>
		//	</page>
		//	<page number="X">...


		$xml = new SimpleXMLElement($source,null,true);
		$results = $xml->xpath('page');
		$html.='<div id="vtext">';
		$i=0;

		//si mots recherchés apparaissent dans la chaîne <text>, on renvoie mots clés recherchés surlignés et son contexte ainsi que le numéro de page et le lien vers la vue "image".
		foreach($results as $page) 
		{
			foreach($page->text as $text) 
			{
				if (preg_match("/($match)+/i", $text)) 
				{
					$num_pg = ($page['number']-1);
					$pg = $listing["$num_pg"];
					$pg = label_page($pg);//libellé de la page
					$html.= "<a href=\"?image=$num_pg#bibnum\">$pg</a> : \r\n";//lien vers l'image
					$html.= highlight($match,$text) ."<br/>";//surlignage
					$i++;
				}
				else {}
			}
		}

		if ($i=='0'){$html.='<b>Aucun résultat trouvé pour cette recherche</b>';}//si aucun résultat
		$html.='</div><br/>';}
		else {}
		$html.='</div>';
		return $html;//on retourne les résultats
	}
