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
$pv_path = e_PLUGIN."pviewgallery/";

if ($PView -> getPView_config("img_Link_menu_extJS")) {
	$script = $PView -> getPView_config("img_Link_extJS");
} else {
	$script = "noscript";
}

// image count (own pics) + nonapproved images count + top uploader + top rating + view count
$imgArray = array();
$uploaderArray = array();
$ratingArray = array();
$viewArray = array();
$imgArray['all'] = 0;
$imgArray['mypics'] = 0;
$imgArray['nonapproved'] = 0;
$sql->db_Select("pview_image", "*", "", "nowhere");
while ($image = $sql -> db_Fetch()) {
	// PERMISSION!!!
	if ($PView->getPermission("image",$image['imageId'],"View")) {
		// all pics
		$imgArray['all']++;
		
	 	// top uploader
	 	$uploaderArray[$image['uploaderUserId']]++;
	 	
	 	// pics view count
	 	$viewArray[$image['imageId']]['value'] = $image['views'];
	 	$viewArray[$image['imageId']]['thumbnail'] = $image['thumbnail'];
	 	$viewArray[$image['imageId']]['albumId'] = $image['albumId'];
	 	$viewArray[$image['imageId']]['name'] = $image['name'];
	 	
	 	// top ratings
	 	if ($PView -> getPView_config("Rating")) {
		 	$ratings = $PView -> getRatingData($image['imageId']);
		 	if ($ratings['count']){
			    $ratingArray[$image['imageId']]['value'] = round($ratings['value'],2); 
			    $ratingArray[$image['imageId']]['count'] = $ratings['count'];
			    $ratingArray[$image['imageId']]['thumbnail'] = $image['thumbnail'];
			    $ratingArray[$image['imageId']]['albumId'] = $image['albumId'];
			    $ratingArray[$image['imageId']]['name'] = $image['name'];
		    }
	    }
    }
    if (!$image['approved']) {
		// nonapproved pics
	 	$imgArray['nonapproved']++;
	}
	if ($image['uploaderUserId'] == USERID) {
		// my pics
		$imgArray['mypics']++;
	}
}
uasort($ratingArray, "pv_cmp"); // Rating
arsort($uploaderArray); // Uploader
uasort($viewArray, "pv_cmp"); // Views

// album count
$albumCount = 0;
$sql->db_Select("pview_album", "albumId", "", "nowhere");
while ($album = $sql -> db_Fetch()) {
	if ($PView->getPermission("album",$album['albumId'],"View")) {
		$albumCount++;
	}
}
// usergallery count
$galCount = 0;
$sql->db_Select("pview_gallery", "galleryId", "WHERE galleryId <> 0", "nowhere");
while ($gallery = $sql -> db_Fetch()) {
	if ($PView->getPermission("gallery",$gallery['galleryId'],"View")) {
		$galCount++;
	}
}
// comment count (own comments)
$commentArray = array();
$commentArray['all'] = 0;
$commentArray['mycomments'] = 0;

$sql->db_Select("pview_comment", "commentImageId,commente107userId", "", "nowhere");
while ($comment = $sql -> db_Fetch()) {
	if ($PView->getPermission("image",$comment['commentImageId'],"View")) {
		$commentArray['all']++;
		if ($comment['commente107userId'] == USERID) {
			$commentArray['mycomments']++;
		}
	}
}
// category count
if (!$catCount = $sql->db_Count("pview_cat", "(*)")) {
	$catCount = 0;
}


// here starts the html code
// script for DIV opener
$pv_text = "";
$pv_text .= "<script type='text/javascript' src = '".$pv_path."pview.js'></script>";

// basic stats
$pv_text .= "<table width='95%'><tr><td>".LAN_MENU_5.":</td><td>".$imgArray['all']."</td></tr>";
$pv_text .= "<tr><td>".LAN_MENU_6.":</td><td>".$imgArray['mypics']."</td></tr>";
$pv_text .= "<tr><td>".LAN_MENU_7.":</td><td>".$albumCount."</td></tr>";
$pv_text .= "<tr><td>".LAN_MENU_8.":</td><td>".$galCount."</td></tr>";
$pv_text .= "<tr><td>".LAN_MENU_9.":</td><td>".$commentArray['all']."</td></tr>";
$pv_text .= "<tr><td>".LAN_MENU_10.":</td><td>".$commentArray['mycomments']."</td></tr>";
if ($imgArray['nonapproved'] && ADMIN) {
	$pv_text .= "<tr><td><a href='".$pv_path."admin_activate.php'>
				".LAN_MENU_11.":</a></td><td>".$imgArray['nonapproved']."</td></tr>";
} else {
	$pv_text .= "<tr><td>".LAN_MENU_11.":</td><td>".$imgArray['nonapproved']."</td></tr>";
}
$pv_text .= "<tr><td>".LAN_MENU_12.":</td><td>".$catCount."</td></tr>";
$pv_text .= "</table><hr /><center>";
$pv_text .= "<input class='button' type='button' value='".LAN_MENU_13."' onclick='pv_uploader()'> ";
if ($PView -> getPView_config("Rating")) {
	$pv_text .= "<input class='button' type='button' value='".LAN_MENU_14."' onclick='pv_Rating()'> ";
}
$pv_text .= "<input class='button' type='button' value='".LAN_MENU_15."' onclick='pv_Views()'></center>";
$pv_text .= "<div name='pview_menu_uploader' id='pview_menu_uploader' style='display:block;'>";
$pv_text .= "<table width='95%'><tr><td colspan='2' height='30px' valign='middle'>".LAN_MENU_16.":</td></tr>";
if (count($uploaderArray)) {
	foreach($uploaderArray as $key => $dataset) {
		$user = $PView ->getUserData($key);
		$pv_text .= "<tr><td>".$user['user_name'] .":</td><td valign='top'>".$dataset."</td></tr>";
		if ($u_count++ > 1) { break; }// shows the top 3
	}
} else {
	$pv_text .= "<tr><td colspan='2'>".LAN_MENU_4."</td></tr>";
}
$pv_text .= "</table></div>";
$pv_text .= "<div name='pview_menu_rating' id='pview_menu_rating' style='display:none;'>";
$pv_text .= "<table width='95%'><tr><td colspan='2' height='30px' valign='middle'>".LAN_MENU_17.":</td></tr>";
if (count($ratingArray)) {
	foreach ($ratingArray as $key => $dataset) {
		$thumb = $PView -> getThumbPath($key);
		$resize = $PView -> getResizePath($key);
		if ($PView -> getPView_config("force_imageSize")){
			$imgHeight = intval($PView -> getPView_config("force_Height"));
			$imgWidth = intval($PView -> getPView_config("force_Width"));
		} else {
			$ImageSize = getimagesize($thumb);
			$imgHeight = $ImageSize[1];
			$imgWidth = $ImageSize[0]; 
		}
		
		switch ($script) {
					case "noscript":
					// image will open in pviewgallery
					$pv_Link = "<a href='pviewgallery.php?image=".$key."'>";
					break;
					case "lightbox":
					// image will open in lightbox group	
					$pv_Link = "<a href='".$resize."' rel='lightbox[pview_stats_rating]' title='".$dataset['name']."'>";
					break;
					case "shadowbox":
					// image will open in shadowbox group	
					$pv_Link = "<a href='".$resize."' rel='shadowbox[pview_stats_rating]' title='".$dataset['name']."'>";
					break;
					case "highslide":
					// image will open in highslide group
					if ($PView->getPView_config("img_Link_extJS_pview"))	{
						$pv_Link = "<a href='".$resize."' class='highslide' onclick=\"return hs.expand(this,pview_stats_rating)\" title='".$dataset['name']."'>";
					} else {
						// ehighslide plugin compatible
						$pv_Link = "<a href='".$resize."' class='highslide' onclick='return hs.expand(this)' title='".$dataset['name']."'>";
					}
					break;																	
					
		}		
		
		$pv_text .= "<tr><td colspan='2' style='text-align:center;'>".$pv_Link."<img src='".$thumb."' height='".$imgHeight."' width='".$imgWidth."'></a></td></tr>";
		$pv_text .= "<tr><td height='40px' valign='top'>".LAN_MENU_19.": ".$dataset['value']."</td><td height='40px' valign='top'>(".$dataset['count'].LAN_MENU_20.")</td></tr>";
		if ($r_count++ > 1) { break; }// shows the top 3
	}
} else {
	$pv_text .= "<tr><td colspan='2'>".LAN_MENU_4."</td></tr>";
}

$pv_text .= "</table></div>";
$pv_text .= "<div name='pview_menu_views' id='pview_menu_views' style='display:none;'>";
$pv_text .= "<table width='95%'><tr><td colspan='2' height='30px' valign='middle'>".LAN_MENU_18.":</td></tr>";
if (count($viewArray)) {
	foreach ($viewArray as $key => $dataset) {
		$thumb = $PView -> getThumbPath($key);
		$resize = $PView -> getResizePath($key);
		if ($PView -> getPView_config("force_imageSize")){
			$imgHeight = intval($PView -> getPView_config("force_Height"));
			$imgWidth = intval($PView -> getPView_config("force_Width"));
		} else {
			$ImageSize = getimagesize($thumb);
			$imgHeight = $ImageSize[1];
			$imgWidth = $ImageSize[0]; 
		}
		
		switch ($script) {
					case "noscript":
					// image will open in pviewgallery
					$pv_Link = "<a href='pviewgallery.php?image=".$key."'>";
					break;
					case "lightbox":
					// image will open in lightbox group	
					$pv_Link = "<a href='".$resize."' rel='lightbox[pview_stats_views]' title='".$dataset['name']."'>";
					break;
					case "shadowbox":
					// image will open in shadowbox group	
					$pv_Link = "<a href='".$resize."' rel='shadowbox[pview_stats_views]' title='".$dataset['name']."'>";
					break;
					case "highslide":
					// image will open in highslide group	
					if ($PView->getPView_config("img_Link_extJS_pview"))	{
						$pv_Link = "<a href='".$resize."' class='highslide' onclick=\"return hs.expand(this,pview_stats_views)\" title='".$dataset['name']."'>";
					} else {
						// ehighslide plugin compatible
						$pv_Link = "<a href='".$resize."' class='highslide' onclick='return hs.expand(this)' title='".$dataset['name']."'>";
					}
					break;																	
					
		}
			
		$pv_text .= "<tr><td colspan='2' style='text-align:center;'>".$pv_Link."<img src='".$thumb."' height='".$imgHeight."' width='".$imgWidth."'></a></td></tr>";
		$pv_text .= "<tr><td height='40px' valign='top'>".LAN_MENU_21.":</td><td height='40px' valign='top'>".$dataset['value'].LAN_MENU_22."</td></tr>";
		if ($v_count++ > 1) { break; }// shows the top 3
	}
} else {
	$pv_text .= "<tr><td colspan='2'>".LAN_MENU_4."</td></tr>";
}

$pv_text .= "</table></div>";

$ns->tablerender(LAN_MENU_3, $pv_text,'pview');

function pv_cmp($a, $b) { 
// numeric comparison for sorting 2D Arrays
    if ($a['value'] == $b['value']) {return 0;}
    if ($a['value'] - $b['value'] < 0) {return 1;}
    if ($a['value'] - $b['value'] > 0) {return -1;}
} 

?>