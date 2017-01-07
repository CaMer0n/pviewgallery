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
require_once("../../class2.php");
// insert all wysiwyg textareas, comma separated
$e_wysiwyg = "pview_comment";
require_once(HEADERF);
// Include plugin language file, check first for site's preferred language
if (file_exists(e_PLUGIN . "pviewgallery/languages/" . e_LANGUAGE . ".php")){
include_once(e_PLUGIN."pviewgallery/languages/".e_LANGUAGE.".php");
}
else
{
include_once(e_PLUGIN . "pviewgallery/languages/German.php");
} 
// Include pview.class
require_once(e_PLUGIN."pviewgallery/pview.class.php");
$PView = new PView;

// Include Template
include_once(e_PLUGIN."pviewgallery/templates/template.php");
if (!class_exists('Template')) {
	$ns -> tablerender($PView -> getPView_config("pview_name"), LAN_ADMIN_61);
	require_once(FOOTERF);
    exit;
}
$Template = new Template;

// --------------------------- SESSION Handling for Viewer Sorting --------------------------------------------------------------

if ($PView->getPView_config("viewer_sort")){
	session_start();
	$sortArray = $PView->getSortArray();
	if (isset($_POST['pv_album_sort'])){
		$_SESSION['pv_album_sort'] = array_search($_POST['pv_album_sort'],$sortArray);
	}
	if (isset($_POST['pv_cat_sort'])){
		$_SESSION['pv_cat_sort'] = array_search($_POST['pv_cat_sort'],$sortArray);
	}
	if (isset($_POST['pv_user_sort'])){
		$_SESSION['pv_user_sort'] = array_search($_POST['pv_user_sort'],$sortArray);
	}	
}

// ----------------------------------- Render Gallery ---------------------------------------------------------------------------

$tmpHTML = $PView -> sc_Replace($Template -> getContent());
if (!$tmpHTML) {
	$tmpHTML = "<div style='padding:10px;'>".LAN_GALLERY_9."</div>";
}

$ns -> tablerender($PView -> getPath(), $tmpHTML);

// ------------------------------------------ Views Count -----------------------------------------------------------------------
// Cookie handling for views count
if ($PView -> getPView_config('viewControl_by') == "cookie") {
	// prepare cookie for cookies enabled check
	if(!isset($_COOKIE['PView'])) {
		if ($_GET['gallery'] OR $_GET['album']) {
			setcookie("PView","ON");
		}
	} else {
	// count imageviews if cookies enabled
		if ($_GET['image']) {
			$pv_Cookie = $_COOKIE['PView'];
			if (strpos ($pv_Cookie, ",".$_GET['image'].",") === false) {
				// incViews
				$PView -> setImageViews($_GET['image']);
				$pv_Cookie = $pv_Cookie.",".$_GET['image'].",";
				setcookie ("PView", $pv_Cookie);
			}
		}
	}
}

// Session handling for views count
if ($PView -> getPView_config('viewControl_by') == "session") {
	session_start();
	if ($_GET['image']) {
		$pv_Session = $_SESSION['pv_images'];
		if (strpos ($pv_Session, ",".$_GET['image'].",") === false & !SID) {
			// incViews
			$PView -> setImageViews($_GET['image']);
			$_SESSION['pv_images'] = $_SESSION['pv_images']. ",".$_GET['image'].",";
		}
	}
}
// IP Addr. handling for views count
if ($PView -> getPView_config('viewControl_by') == "ip") {
	// delete outdated entries
	$PView -> deleteIPs();
	if ($_GET['image']) {
		$currentIP = $_SERVER['REMOTE_ADDR'];
		$imagesViewed = $PView -> getImagesfromIP($currentIP);
		if (strpos ($imagesViewed['images'], ",".$_GET['image'].",") === false) {
			// incViews
			$PView -> setImageViews($_GET['image']);
			global $sql;
			 if ($imagesViewed) {
				$newImagestring = $imagesViewed['images'].",".$_GET['image'].",";
				$sql -> db_Update("pview_tmpip","images='$newImagestring',time='".time()."' WHERE ip_addr='$currentIP'");
			} else {
				$sql -> db_Insert("pview_tmpip","'$currentIP',',".$_GET['image'].",','".time()."'");
			}
		}
	}
}

// views count without reload interlock
if (!$PView -> getPView_config('viewControl_by')) {
	if ($_GET['image']) {
		// incViews
		$PView -> setImageViews($_GET['image']);
	}
}
// ------------------------------------------ Views Count End --------------------------------------------------------------------

require_once(FOOTERF);

?>