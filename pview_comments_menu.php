<?php
/*
+---------------------------------------------------------------+
|        e107 website system
|        http://e107.org
|
|        PView Gallery by R.F. Carter
|        ronald.fuchs@hhweb.de
+---------------------------------------------------------------+
*/
if (!defined('e107_INIT')) { exit; }
// Include plugin language file, check first for site's preferred language
if (file_exists(e_PLUGIN . "pviewgallery/languages/" . e_LANGUAGE . ".php")){
	include_once(e_PLUGIN."pviewgallery/languages/".e_LANGUAGE.".php");
} else {
	include_once(e_PLUGIN . "pviewgallery/languages/German.php");
} 
require_once(e_PLUGIN."pviewgallery/pview.class.php");
$PView = new PView;
global $sql;
global $tp;
$pv_path = e_PLUGIN."pviewgallery/";

if ($PView -> getPView_config("img_Link_menu_extJS")) {
	$script = $PView -> getPView_config("img_Link_extJS");
} else {
	$script = "noscript";
}

// comment count (own comments)
$commentArray = array();

$sql->db_Select("pview_comment", "*", "ORDER BY commentDate DESC", "nowhere");
while ($comment = $sql -> db_Fetch()) {
	if ($PView->getPermission("image",$comment['commentImageId'],"View")) {
		array_push($commentArray,$comment);
	}
}


// here starts the html code
// script for DIV opener
$pv_text = "";
$pv_text .= "<script type='text/javascript' src = '".$pv_path."pview.js'></script>";

// basic stats

$pv_text .= "<center>";
$pv_text .= "<input class='button' type='button' value='".LAN_MENU_23."' onclick='pv_NewComments()'> ";
$pv_text .= "<input class='button' type='button' value='".LAN_MENU_24."' onclick='pv_OwnComments()'> ";
$pv_text .= "</center>";


$pv_text .= "<div name='pview_menu_NewComments' id='pview_menu_NewComments' style='display:block;'>";
$pv_text .= "<br /><table width='95%'>";
if (count($commentArray)) {
	$comm_count = 1;
	foreach($commentArray as $key => $dataset) {
		$user = $PView ->getUserData($dataset['commente107userId']);
		$resize = $PView -> getResizePath($dataset['commentImageId']);
		$name = $PView -> getImageName($dataset['commentImageId']);
			switch ($script) {
						case "noscript":
						// image will open in pviewgallery
						$pv_Link = "<a href='".e_PLUGIN."pviewgallery/pviewgallery.php?image=".$dataset['commentImageId']."'>";
						break;
						case "lightbox":
						// image will open in lightbox group	
						$pv_Link = "<a href='".$resize."' rel='lightbox[pview_menu_coml]' title='".$name."'>";
						break;
						case "shadowbox":
						// image will open in shadowbox group	
						$pv_Link = "<a href='".$resize."' rel='shadowbox[pview_menu_coml]' title='".$name."'>";
						break;
						case "highslide":
						// image will open in highslide group
						if ($PView->getPView_config("img_Link_extJS_pview"))	{
							$pv_Link = "<a href='".$resize."' class='highslide' onclick=\"return hs.expand(this,pview_menu_coml)\" title='".$name."'>";
						} else {
							// ehighslide plugin compatible
							$pv_Link = "<a href='".$resize."' class='highslide' onclick='return hs.expand(this)' title='".$name."'>";
						}
						break;																	
						
			}		
			// Preview text without html format (delete also [html] tags if WYSIWYG is used for write comment)	
			$preview_Text = mb_substr(strip_tags($tp->toHTML($dataset['commentText'], TRUE)),0,$PView -> getPView_config('menu_comm_length'),'UTF-8');
			
			
			$pv_text .= "<tr><td style='padding-bottom:5px;'>".$user['user_name'].", ".date('d.m.Y',$dataset['commentDate']).":<br />".$pv_Link.$preview_Text." ...</a></td></tr>";
			if ($comm_count++ > $PView -> getPView_config('menu_comm_count')-1) { break; }
	}
	$pv_text .= "<tr><td><br />".LAN_MENU_25." ".$PView -> getPView_config('menu_comm_count')." ".LAN_MENU_26."</td></tr>";
} else {
	$pv_text .= "<tr><td>".LAN_IMAGE_28."</td></tr>";
}
$pv_text .= "</table></div>";


$pv_text .= "<div name='pview_menu_OwnComments' id='pview_menu_OwnComments' style='display:none;'>";
$pv_text .= "<br /><table width='95%'>";
if (count($commentArray) AND USERID <> 0) {
	$comm_count = 1;
	foreach($commentArray as $key => $dataset) {
		if (USERID == $dataset['commente107userId']){
			$user = $PView ->getUserData($dataset['commente107userId']);
            $resize = $PView -> getResizePath($dataset['commentImageId']);
			$name = $PView -> getImageName($dataset['commentImageId']);
			switch ($script) {
						case "noscript":
						// image will open in pviewgallery
						$pv_Link = "<a href='".e_PLUGIN."pviewgallery/pviewgallery.php?image=".$dataset['commentImageId']."'>";
						break;
						case "lightbox":
						// image will open in lightbox group	
						$pv_Link = "<a href='".$resize."' rel='lightbox[pview_menu_como]' title='".$name."'>";
						break;
						case "shadowbox":
						// image will open in shadowbox group	
						$pv_Link = "<a href='".$resize."' rel='shadowbox[pview_menu_como]' title='".$name."'>";
						break;
						case "highslide":
						// image will open in highslide group
						if ($PView->getPView_config("img_Link_extJS_pview"))	{
							$pv_Link = "<a href='".$resize."' class='highslide' onclick=\"return hs.expand(this,pview_menu_como)\" title='".$name."'>";
						} else {
							// ehighslide plugin compatible
							$pv_Link = "<a href='".$resize."' class='highslide' onclick='return hs.expand(this)' title='".$name."'>";
						}
						break;																	
						
			}

			// Preview text without html format (delete also [html] tags if WYSIWYG is used for write comment)	
			$preview_Text = mb_substr(strip_tags($tp->toHTML($dataset['commentText'], TRUE)),0,$PView -> getPView_config('menu_comm_length'),'UTF-8');
			$pv_text .= "<tr><td style='padding-bottom:5px;'>".$user['user_name'].", ".date('d.m.Y',$dataset['commentDate']).":<br />".$pv_Link.$preview_Text." ...</a></td></tr>";
			
			if ($comm_count++ > $PView -> getPView_config('menu_comm_count')-1) { break; }
		}
	}
	if ($comm_count == 1){
		$pv_text .= "<tr><td>".LAN_IMAGE_28."</td></tr>";
	} else {
		$pv_text .= "<tr><td><br />".LAN_MENU_25." ".$PView -> getPView_config('menu_comm_count')." ".LAN_MENU_26."</td></tr>";
	}
} else {
	$pv_text .= "<tr><td>".LAN_IMAGE_28."</td></tr>";
}
$pv_text .= "</table></div>";

$ns->tablerender(LAN_MENU_27, $pv_text,'pview');

?>