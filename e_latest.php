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
}
else
{
    include_once(e_PLUGIN . "pviewgallery/languages/German.php");
} 
$image_approve = $sql->db_Count('pview_image', '(*)', "WHERE approved='0'");
if ($image_approve)
{
    $text .= "<div style='padding-bottom: 2px;'><img src='" . e_PLUGIN . "pviewgallery/images/icon_16.png' style='width: 16px; height: 16px; vertical-align: bottom;border:0;' alt='' /> ";
	$text .= "<a href='" . e_PLUGIN . "pviewgallery/admin_activate.php'>" . LAN_ADMIN_38 . ": " . $image_approve . "</a>";
} 
$text .= '</div>';

?>