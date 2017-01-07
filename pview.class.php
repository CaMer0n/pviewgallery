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
// Include plugin language file, check first for site's preferred language
if (file_exists(e_PLUGIN . "pviewgallery/languages/" . e_LANGUAGE . ".php")){
include_once(e_PLUGIN."pviewgallery/languages/".e_LANGUAGE.".php");
}
else
{
include_once(e_PLUGIN . "pviewgallery/languages/German.php");
} 

// globals!!!!!!!!!!!!!!!
$ImageSort = "name";
$SortOrder ="";
$outStat_Totals = array();
$applImages = array();

class PView {

	private $pref = array();
	private $userImgCount = array();

	function __construct()
	{

		$sql = e107::getDb();
		$tmp = $sql->retrieve('pview_config', 'configName,configValue',"configName !=''", true);
		foreach($tmp as $row)
		{
			$key = $row['configName'];
			$this->pref[$key] = $row['configValue'];
		}


		// get user image counts.
		$tmp = $sql->retrieve("pview_image", "imageId,uploaderUserId", "WHERE uploaderUserId !=''", true);
		foreach($tmp as $row)
		{
			$key = $row['uploaderUserId'];
			if(!isset($this->userImgCount[$key]))
			{
				$this->userImgCount[$key] = 0;
			}

			if ($this -> getPermission("image",$row['imageId'],"View"))
			{
				$this->userImgCount[$key]++;
			}

		}




	}



function getSortArray() {
// returns array with sortstring as key and Wording as value, used for sorting-select box
	$sortArray = array('name'=>LAN_IMAGE_9,'albumId'=>LAN_ADMIN_156,'uploaderUserId'=>LAN_ADMIN_157,'uploadDate'=>LAN_ADMIN_158,'cat'=>LAN_IMAGE_45,'views'=>LAN_ADMIN_159,'commentscount'=>LAN_ADMIN_110,'ratingvalue'=>LAN_IMAGE_3);
	return $sortArray;
}

function getUserData($userid) {
// returns user data for nested functions, normally use Shortcodes
	global $sql;
	$sql->db_Select("user", "user_class,user_name,user_admin,user_email,user_image,user_signature", "WHERE user_id='$userid'", "nowhere");
	if ($UserData = $sql -> db_Fetch()) {
	return $UserData;
	} else {
	$UserData = array('user_class' => '255', 'user_name' => LAN_IMAGE_35, 'user_admin' => '0');
	return $UserData;
	}
}
function getUserclasses() {
// returns a 2D array of all userclasses (userclass_names)
	global $sql;
	$UserClasses = array();
	$sql->db_Select("userclass_classes", "userclass_name, userclass_id","", "nowhere");
	while ($UserClass = $sql -> db_Fetch())
	{
	array_push ($UserClasses, $UserClass);
	}
	return $UserClasses;
}
function getPView_config($pv_config)
 {
// returns preference values

	if(isset($this->pref[$pv_config]))
	{
		return $this->pref[$pv_config];
	}

/*
	$conf_SQL = new db;
	if(!$conf_SQL->db_Select("pview_config", "configValue", "WHERE configName='$pv_config'", "nowhere"))
	{

	}

	list($ConfigValue) = $conf_SQL -> db_Fetch();

	return $ConfigValue;*/
}
function getPath() {
// returns the way from rootgallery to destination for all views (used in tablerender call)
	$Appl = $this -> getAppl();
	global $tp;
	if ($Appl[0] == "gallery") {
		if ($Appl[1] == "0") {
		$out_Path = $tp -> toHTML($this -> getPView_config("pview_name"));
		} else {
		$out_Path = "<a href='pviewgallery.php'>".$tp -> toHTML($this -> getPView_config("pview_name"))."</a>: ".$tp -> toHTML($this -> getGalleryName());
		}
	}
	if ($Appl[0] == "album") {
		$ParentsArray = array();
		$AlbumData = $this -> getAlbumData($Appl[1]);
		$isParent = $this -> getParentAlbum($Appl[1]);
		while ($isParent) {
			$AlbumData = $this -> getAlbumData($isParent);
			array_push ($ParentsArray,"<a href='pviewgallery.php?album=".$isParent."'>".$tp -> toHTML($AlbumData['name'])."</a>");
			$isParent = $this -> getParentAlbum($isParent);
		}
		if ($AlbumData['galleryId']) {
			array_push ($ParentsArray, "<a href='pviewgallery.php?gallery=".$AlbumData['galleryId']."'>".$tp -> toHTML($this -> getGalleryName($AlbumData['galleryId']))."</a>");
		}
		array_push ($ParentsArray, "<a href='pviewgallery.php'>".$tp -> toHTML($this -> getPView_config("pview_name"))."</a>");
		krsort($ParentsArray);
		foreach ($ParentsArray as $dataset) {
			$out_Path .= $dataset.": ";
		}
	}
	if ($Appl[0] == "image") {
		$ParentsArray = array();
		$ImageAlbum = $this -> getImageAlbum($Appl[1]);
		$AlbumData = $this -> getAlbumData($ImageAlbum);
		$isParent = $this -> getParentAlbum($ImageAlbum);
		array_push ($ParentsArray,"<a href='pviewgallery.php?album=".$ImageAlbum."'>".$tp -> toHTML($AlbumData['name'])."</a>");
		while ($isParent) {
			$AlbumData = $this -> getAlbumData($isParent);
			array_push ($ParentsArray,"<a href='pviewgallery.php?album=".$isParent."'>".$tp -> toHTML($AlbumData['name'])."</a>");
			$isParent = $this -> getParentAlbum($isParent);
		}
		if ($AlbumData['galleryId']) {
			array_push ($ParentsArray, "<a href='pviewgallery.php?gallery=".$AlbumData['galleryId']."'>".$tp -> toHTML($this -> getGalleryName($AlbumData['galleryId']))."</a>");
		}
		array_push ($ParentsArray, "<a href='pviewgallery.php'>".$tp -> toHTML($this -> getPView_config("pview_name"))."</a>");
		krsort($ParentsArray);
		foreach ($ParentsArray as $dataset) {
			$out_Path .= $dataset.": ";
		}
	}
	if ($Appl[0] == "cat") {
		if ($Appl[1] == "list"){
			$out_Path .= "<a href='pviewgallery.php'>".$tp -> toHTML($this -> getPView_config("pview_name"))."</a>: ".LAN_ADMIN_151;
		} else {
			$catData = $this->getCatData($Appl[1]);
			$out_Path .= "<a href='pviewgallery.php'>".$tp -> toHTML($this -> getPView_config("pview_name"))."</a>: <a href='pviewgallery.php?cat=list'>".LAN_ADMIN_151."</a>: ".$catData['name'];			
		}
	}
	
	if ($Appl[0] == "user") {
		if ($Appl[1] == "list"){
			$out_Path .= "<a href='pviewgallery.php'>".$tp -> toHTML($this -> getPView_config("pview_name"))."</a>: ".LAN_ADMIN_153;
		} else {
			$userData = $this->getUserData($Appl[1]);
			$out_Path .= "<a href='pviewgallery.php'>".$tp -> toHTML($this -> getPView_config("pview_name"))."</a>: <a href='pviewgallery.php?user=list'>".LAN_ADMIN_153."</a>: ".$userData['user_name'];			
		}
	}	
	return $out_Path;
}
function getParentAlbum($albumid) {
// returns parent album Id
	$parentAlbum = $this -> getAlbumData($albumid);
	return $parentAlbum['parentAlbumId'];
}
function getPermission($object,$objectid,$request) {
// returns true or false, DO NOT EDIT THIS FUNCTION!!!
	// $object: gallerie, album, image, config
	// $objectid: Id / configName
	// $request: Edit, View, CreateAlbum, Upload
	global $sql2;
	
	//Admins have ALL rights in admin Mode
	if (ADMIN && $this -> getPView_config("admin_Mode")) { return 1;}
	//-------------------------------------------------------------------------------------
	
	//objects in deactivated gallerytypes (MAIN or USER) will handled as: NO PERMISSION (also Admins in normal Mode)
	if ($object == "gallery" && $objectid == "0" && !$this -> getGalleryActive("0")) { return 0;}
	if ($object == "gallery" && $objectid <> "0" && !$this -> getPView_config("member_galleries")) { return 0;}
		//usergallery active?
	if ($object == "gallery" && $objectid <> "0" && !ADMIN && USERID <> $objectid && !$this -> getGalleryActive($objectid)) { return 0;}
	
	if ($object == "album") {
		$sql2->db_Select("pview_album", "*", "WHERE albumId='$objectid'", "nowhere");
		$tmp_AlbumData = $sql2 -> db_Fetch();
		if ($tmp_AlbumData['galleryId'] == "0" && !$this -> getGalleryActive("0")) { return 0;}
		if ($tmp_AlbumData['galleryId'] <> "0" && !$this -> getPView_config("member_galleries")) { return 0;}
			//corresponding usergallery active?
		if ($tmp_AlbumData['galleryId'] <> "0" && !ADMIN && USERID <> $tmp_AlbumData['galleryId'] && !$this -> getGalleryActive($tmp_AlbumData['galleryId'])) { return 0;}
	}
	
	if ($object == "image") {
		$sql2->db_Select("pview_image", "*", "WHERE imageId='$objectid'", "nowhere");
		$tmp_ImageData = $sql2 -> db_Fetch();
		$tmp_ImageData = $tmp_ImageData['albumId'];
		$sql2->db_Select("pview_album", "*", "WHERE albumId='$tmp_ImageData'", "nowhere");
		$tmp_AlbumData = $sql2 -> db_Fetch();
		if ($tmp_AlbumData['galleryId'] == "0" && !$this -> getGalleryActive("0")) { return 0;}
		if ($tmp_AlbumData['galleryId'] <> "0" && !$this -> getPView_config("member_galleries")) { return 0;}
			//corresponding usergallery active?
		if ($tmp_AlbumData['galleryId'] <> "0" && !ADMIN && USERID <> $tmp_AlbumData['galleryId'] && !$this -> getGalleryActive($tmp_AlbumData['galleryId'])) { return 0;}
	}
	//-------------------------------------------------------------------------------------
	
	//Admins have all other rights
	if (ADMIN) { return 1;}
	//-------------------------------------------------------------------------------------
	
	//Owners of usergalleryobjects have all other rights
	if (USERID <> 0){	//since e107 v7.12 guest has USERID=0
		if ($object == "gallery" && USERID == $objectid) { return 1;}
		if ($object == "album") {
			$sql2->db_Select("pview_album", "*", "WHERE albumId='$objectid'", "nowhere");
			$tmp_AlbumData = $sql2 -> db_Fetch();
			if (USERID == $tmp_AlbumData['galleryId']) { return 1;}
		}	
		if ($object == "image") {
			$sql2->db_Select("pview_image", "*", "WHERE imageId='$objectid'", "nowhere");
			$tmp_ImageData = $sql2 -> db_Fetch();
			$tmp_ImageData = $tmp_ImageData['albumId'];
			$sql2->db_Select("pview_album", "*", "WHERE albumId='$tmp_ImageData'", "nowhere");
			$tmp_AlbumData = $sql2 -> db_Fetch();
			if (USERID == $tmp_AlbumData['galleryId']) { return 1;}
		}
	}
	//-------------------------------------------------------------------------------------
	
	//Image specials: approved?; album permView, where image is located
	if ($object == "image") {
		$sql2->db_Select("pview_image", "approved,albumId", "WHERE imageId='$objectid'", "nowhere");
		list($approved,$albumId) = $sql2 -> db_Fetch();
		//if image not approved: return false
		if (!$approved) { return 0;}
		//if parentalbum have no "View" Permission: return false
		if (!$this->getPermissionRequest("album",strval($albumId),"View")){ return 0 ;}
	}
	// call normal permRequest function
	return $this->getPermissionRequest($object,$objectid,$request);
}
	
function getPermissionRequest($object,$objectid,$request) {
// returns true or false, DO NOT EDIT THIS FUNCTION!!!
	// $object: gallerie, album, image, config
	// $objectid: Id / configName
	// $request: Edit, View, CreateAlbum, Upload
	
	//prepare sql variables
	if ($object == "config") { //config based Permission
		$objectTable = "pview_".$object;
		$requestField = "configValue";
		$objectField = "configName";
	} else { // object based Permission
		$objectTable = "pview_".$object;
		$requestField = "perm".$request;
		$objectField = $object."Id";
	}
	//-------------------------------------------------------------------------------------
	
	//Main Database Request
	
	$perm_SQL = new db;
	$perm_SQL->db_Select("$objectTable", "$requestField", "WHERE $objectField='$objectid'", "nowhere");
	list($Perm) = $perm_SQL -> db_Fetch();
	//if result NULL: return false
	if (!$Perm) { return 0;}
	//if result ALL: return true
	if ($Perm == "ALL") { return 1;}
	//if result MEMBER: USER?: true/false
	if ($Perm == "MEMBER") {
		if (USER) {
		return 1;
		} else {
		return 0;
		}
	}
	//if other result: push PermArray , push UserclassArray, compare all with all: if one of them true: return true
	if (!USERCLASS) { return 0;}
	$PermArray = array();
	$UserClassArray = array();
	$PermArray = explode ( ',', $Perm );
	$UserClassArray= explode ( ',', USERCLASS );
	foreach ($UserClassArray as $UserClass) {
		if (in_array ($UserClass,$PermArray)) { return 1;}
	}
	//default:
	return 0;
}

function getNoPerm($appl) {
// returns a forbidden message with a back button
	switch ($appl) {
	case "gallery":
	    $applText = LAN_GALLERY_5;
	    break;
	case "album":
	    $applText = LAN_ALBUM_5;
	    break;
	case "image":
	    $applText = LAN_IMAGE_5;
	    break;
	default:
		$applText = LAN_IMAGE_36;
	    break;
	}
	$out_Message = "<div style='padding:10px;'>".$applText."</div>";
	$out_Message.= "<div style='padding:10px;'><a href='javascript:history.back();'>".LAN_IMAGE_37."</div></a>";
	return $out_Message;
}

function getAppl() {
// returns array for many functions
	$pv_Appl = array ("gallery","0"); //default
	if ($_GET['album'])	{
		$pv_Appl = array ("album",$_GET['album']);
	}
	if ($_GET['gallery'])	{
		if ($_GET['gallery'] <> "classic"){
			$pv_Appl = array ("gallery",$_GET['gallery']);
		}
	}
	if ($_GET['image'])	{
		$pv_Appl = array ("image",$_GET['image']);
	}
	if ($_GET['user'])	{
		$pv_Appl = array ("user",$_GET['user']);
	}
	if ($_GET['cat'])	{
		$pv_Appl = array ("cat",$_GET['cat']);
	}
	return $pv_Appl;
}


function sc_Replace($tmpText) {
// returns HTML with Shortcodes replaced
	$Template = new Template;
	global $tp;
	global $outStat_Totals;
	$Appl = $this -> getAppl();
	
	// Gallery
	if ($Appl[0] == "gallery") {
	$tmpText = ereg_replace("{pv_MemberGalleryName}",$tp -> toHTML($this -> getPView_config("usergallery_name")),$tmpText);
	$tmpText = ereg_replace("{pv_MainGalleryName}",$tp -> toHTML($this -> getGalleryName("0")),$tmpText);
	$tmpText = ereg_replace("{pv_MainGalleryMenu}",$Template -> getGalleryMenu("main"),$tmpText);
	$tmpText = ereg_replace("{pv_UserGalleryMenu}",$Template -> getGalleryMenu("user"),$tmpText);
	$tmpText = ereg_replace("{pv_RootalbumCount}",$this -> getRootAlbumsCount(),$tmpText);
	$tmpText = ereg_replace("{pv_UserGalleryCount}",$this -> getUserGalleryCount(),$tmpText);
	$tmpText = ereg_replace("{pv_UserGalleryName}",$tp -> toHTML($this -> getGalleryName()),$tmpText);
	$tmpText = ereg_replace("{pv_UserGalleries}",$Template -> getUserGalleries(),$tmpText);
	$tmpText = ereg_replace("{pv_RootAlbums}",$Template -> getAllAlbums('root'),$tmpText);
	$tmpText = ereg_replace("{pv_sumImg}",$tp -> toHTML($outStat_Totals['imgCount']),$tmpText);
	$tmpText = ereg_replace("{pv_sumRating}",$tp -> toHTML($outStat_Totals['aRating']),$tmpText);
	$tmpText = ereg_replace("{pv_sumViews}",$tp -> toHTML($outStat_Totals['viewsCount']),$tmpText);
	$tmpText = ereg_replace("{pv_sumComm}",$tp -> toHTML($outStat_Totals['commCount']),$tmpText);
	$tmpText = ereg_replace("{pv_sumAlbum}",$tp -> toHTML($outStat_Totals['albumCount']),$tmpText);
	$tmpText = ereg_replace("{pv_sumCat}",$tp -> toHTML($outStat_Totals['catCount']),$tmpText);
	$tmpText = ereg_replace("{pv_sumUploader}",$tp -> toHTML($outStat_Totals['uploaderCount']),$tmpText);
	$tmpText = ereg_replace("{pv_sumGal}",$tp -> toHTML($outStat_Totals['galCount']),$tmpText);
	$tmpText = ereg_replace("{pv_ratedImgs}",$tp -> toHTML($outStat_Totals['ratedImgs']),$tmpText);
	$tmpText = ereg_replace("{pv_commImgs}",$tp -> toHTML($this->getStatistic_CommImgs()),$tmpText);
	$tmpText = ereg_replace("{pv_viewedImgs}",$tp -> toHTML($this->getStatistic_ViewedImgs()),$tmpText);
	$tmpText = ereg_replace("{pv_imgNoUser}",$tp -> toHTML($this->getNoUserImages()),$tmpText);
	$tmpText = ereg_replace("{pv_CommUser}",$tp -> toHTML(count($this -> getStatistic_Comm())),$tmpText);
	}
	// Album
	if ($Appl[0] == "album") {
	$tmpText = ereg_replace("{pv_AlbumName}",$tp -> toHTML($this -> getAlbumName()),$tmpText);
	$tmpText = ereg_replace("{pv_AlbumImageCount}",$this -> getAlbumImageCount(),$tmpText);
	$tmpText = ereg_replace("{pv_SubAlbumCount}",$this -> getSubAlbumsCount(),$tmpText);
	$tmpText = ereg_replace("{pv_AlbumMenu}",$Template -> getAlbumMenu(),$tmpText);

	$tmpText = ereg_replace("{pv_SubAlbums}",$Template -> getAllAlbums('sub'),$tmpText);
	$tmpText = ereg_replace("{pv_AlbumImages}",$Template -> getAllImages(),$tmpText);
	$tmpText = ereg_replace("{pv_Pages}",$Template -> getPages(),$tmpText);	
	}
	//Image
	if ($Appl[0] == "image") {
	$tmpText = ereg_replace("{pv_ImageName}",$tp -> toHTML($this -> getImageName()),$tmpText);
	$tmpText = ereg_replace("{pv_ImageMenu}",$Template -> getImageMenu(),$tmpText);
	$tmpText = ereg_replace("{pv_ResizeImage}",$this -> getResizeImage(),$tmpText);
	$tmpText = ereg_replace("{pv_ImageData}",$Template -> getImageInfo(),$tmpText);
	$tmpText = ereg_replace("{pv_NavMenu}",$Template -> getNavMenu('album'),$tmpText);
	$tmpText = ereg_replace("{pv_CommentCount}",$this -> getCommentsCount(),$tmpText);
	$tmpText = ereg_replace("{pv_Comments}",$Template -> getComments(),$tmpText);
	$tmpText = ereg_replace("{pv_AddComment}",$Template -> getButton_addComment(),$tmpText);
	$tmpText = ereg_replace("{pv_RatingResult}",$Template -> getRating(),$tmpText);
	$tmpText = ereg_replace("{pv_RatingRate}",$Template -> getRate(),$tmpText);
	}
	// Category
	if ($Appl[0] == "cat") {
	$tmpText = ereg_replace("{pv_CatImages}",$Template -> getAllImages(),$tmpText);
	$tmpText = ereg_replace("{pv_CatName}",$tp -> toHTML($this -> getCatName()),$tmpText);
	$tmpText = ereg_replace("{pv_CatImageCount}",$this -> getCatImageCount($Appl[1]),$tmpText);
	$tmpText = ereg_replace("{pv_CatMenu}",$Template -> getCatMenu(),$tmpText);
	$tmpText = ereg_replace("{pv_CatList}",$Template -> getAllCats(),$tmpText);
	$tmpText = ereg_replace("{pv_Pages}",$Template -> getPages(),$tmpText);
	}
	// User
	if ($Appl[0] == "user") {
	$tmpText = ereg_replace("{pv_UserImages}",$Template -> getAllImages(),$tmpText);
	$tmpText = ereg_replace("{pv_UserName}",$tp -> toHTML($this -> getUserName()),$tmpText);
	$tmpText = ereg_replace("{pv_UserImageCount}",$this -> getUserImageCount($Appl[1]),$tmpText);
	$tmpText = ereg_replace("{pv_UserMenu}",$Template -> getUserMenu(),$tmpText);
	$tmpText = ereg_replace("{pv_Pages}",$Template -> getPages(),$tmpText);	}
	return $tmpText;
}
function getGalleryAlbumCount($galleryid,$ignorePerm) {
// returns count of albums in gallery
	$Appl = $this -> getAppl();
	if ($Appl[0] == "gallery" && !$galleryid) {
		$galleryid = $Appl [1];
	}
	$Count = 0;
	global $sql;
	$sql->db_Select("pview_album", "*", "WHERE galleryId='$galleryid' && parentAlbumId='0'", "nowhere");
	while ($AlbumCount = $sql -> db_Fetch()) {
		// PERMISSION!!!
		if ($this -> getPermission("image",$AlbumCount['albumId'],"View") OR $ignorePerm) {
			$Count++;
		}
	}
	return strval ($Count);
}
function getGalleryAlbums($galleryid) {
// returns a 2D array of albums in called gallery
	global $sql;
	$galleryAlbums = array();
	$sql->db_Select("pview_album", "*", "WHERE galleryId='$galleryid'", "nowhere");
	while ($galleryAlbum = $sql -> db_Fetch()) {
		array_push($galleryAlbums,$galleryAlbum);
	}
	return $galleryAlbums;
}
function getStatistic_Albums($mode) {
// returns a 2D array of albums for statistic, $mode: latest/random
	global $sql;
	$statAlbums = array();
	if ($mode == "random") {
		$arg = "ORDER BY RAND()";
	} else {
		$arg = "ORDER BY albumId DESC";
	}
	$sql->db_Select("pview_album", "*", $arg, "nowhere");
	while ($statAlbum = $sql -> db_Fetch()) {
		if ($this -> getPermission("album",$statAlbum['albumId'],"View")){
			array_push($statAlbums,$statAlbum);
		}	
	}
	return $statAlbums;
}
function getStatistic_UserGals($mode = "latest") {
// returns a 2D array of usergalleries for statistic, $mode: latest/random
	global $sql;
	$statGals = array();
	if ($mode == "random") {
		$arg = "WHERE galleryId<>'0' ORDER BY RAND()";
	} else {
		$arg = "WHERE galleryId<>'0' ORDER BY galleryId DESC ";
	}
	// list main gallery first
	if ($this -> getPermission("gallery","0","View")){
		$mainGal = array("galleryId"=>"classic","name"=>$this->getGalleryName("0")); // important for link in gallery view
		array_push($statGals,$mainGal);
	}

	$sql->db_Select("pview_gallery", "*", $arg, "nowhere");
	while ($statUserGal = $sql -> db_Fetch()) {
		if ($this -> getPermission("gallery",$statUserGal['galleryId'],"View")){
			array_push($statGals,$statUserGal);
		}	
	}
	return $statGals;
}
function getStatistic_Cats($mode = "latest") {
// returns a 2D array of categories for statistic, $mode: latest/random
	global $sql;
	$statCats = array();
	if ($mode == "random") {
		$arg = "ORDER BY RAND()";
	} else {
		$arg = "ORDER BY catId DESC";
	}
	$sql->db_Select("pview_cat", "*", $arg, "nowhere");
	while ($statCat = $sql -> db_Fetch()) {
		array_push($statCats,$statCat);
	}
	return $statCats;
}
function getStatistic_Uploader() {
// returns a array of uploader for statistic, key is userId
	$u_SQL = new db;
	$userArray = array();
	$u_SQL -> db_Select("user", "user_id","", "nowhere");
	while($userTmp = $u_SQL -> db_Fetch()) {
		if ($userImagesCount = $this->getUserImageCount($userTmp['user_id'])){
			$userArray[$userTmp['user_id']] = $userImagesCount;
		}
	}
	arsort($userArray);
	return $userArray;
}
function getStatistic_Comm() {
// returns a array of user commented images for statistic, key is userId
	$c_SQL = new db;
	$userArray = array();
	$c_SQL -> db_Select("user", "user_id","", "nowhere");
	while($userTmp = $c_SQL -> db_Fetch()) {
		if ($userCommCount = $this->getUserCommCount($userTmp['user_id'])){
			$userArray[$userTmp['user_id']] = $userCommCount;
		}
	}
	arsort($userArray);
	return $userArray;
}
function getUserCommCount($userid) {
// returns count of comments as string	
	$Count = 0;
	global $sql;
	$sql->db_Select("pview_comment", "*", "WHERE commente107userId='$userid'", "nowhere");
	while ($CommCount = $sql -> db_Fetch())	{
	// PERMISSION!!!
		if ($this -> getPermission("image",$CommCount['commentImageId'],"View")) {
			$Count++;
		}
	}
	return strval($Count);
}
function getStatistic_CommImgs() {
// returns count of commented images
	global $applImages;
	$allImgs = $applImages;
	//$allImgs = $this->getApplImages();
	$statCommImgs = 0;
	foreach ($allImgs as $dataset){
		if ($dataset['commentscount']){
			$statCommImgs++;
		}
	}
	return strval($statCommImgs);
}
function getStatistic_ViewedImgs() {
// returns count of commented images
	global $applImages;
	$allImgs = $applImages;
	//$allImgs = $this->getApplImages();
	$statViewedImgs = 0;
	foreach ($allImgs as $dataset){
		if ($dataset['views']){
			$statViewedImgs++;
		}
	}
	return strval($statViewedImgs);
}
function getRootAlbumsCount() {
// returns count of rootalbums for gallery view as string
	$Appl = $this -> getAppl();
	if ($Appl[0] == "gallery") {
		$galleryid = $Appl [1];
	}
	$Count = 0;
	global $sql;
	$sql->db_Select("pview_album", "*", "WHERE galleryId='$galleryid'&&parentAlbumId=0", "nowhere");
	while ($AlbumCount = $sql -> db_Fetch()) 
	{
	// PERMISSION!!!
	if ($this -> getPermission("album",$AlbumCount['albumId'],"View")) {
		$Count++;
	}
	}
	return strval ($Count);
}
function getUserGalleryCount () {
// returns count of usergalleries for gallery view as string
	$Count = 0;
	global $sql;
	$sql->db_Select("pview_gallery", "*", "WHERE galleryId<>0", "nowhere");
	while ($GalleryCount = $sql -> db_Fetch()) 
	{
	// PERMISSION!!!
	if ($this -> getPermission("gallery",$GalleryCount['galleryId'],"View")) {
		$Count++;
	}
	}
	return strval ($Count);
}
function getGalleryImageCount($galleryId) {
// returns count of images in selected gallery	
	$allImagesCount = 0;
	$galAlbums = $this->getGalleryAlbums($galleryId);
	foreach($galAlbums as $dataset) {
		$allImagesCount = $allImagesCount + $this->getAlbumImageCount($dataset['albumId']);
	}
	return $allImagesCount;
}
function getAlbumImageCount($albumid,$ignorePerm) {
// returns count of images in album for album- and gallery view as string
	$Appl = $this -> getAppl();
	if ($Appl[0] == "album" && !$albumid) {
		$albumid = $Appl [1];
	}
	$Count = 0;
	global $sql;
	$sql->db_Select("pview_image", "*", "WHERE albumId='$albumid'", "nowhere");
	while ($ImageCount = $sql -> db_Fetch())
	{
	// PERMISSION!!!
		if ($this -> getPermission("image",$ImageCount['imageId'],"View") OR $ignorePerm) {
			$Count++;
		}
	}
	return strval ($Count);
}
function getCatImageCount($catid) {
// returns count of images in cat view as string
	$Count = 0;
	global $sql;
	$sql->db_Select("pview_image", "*", "WHERE cat='$catid'", "nowhere");
	while ($ImageCount = $sql -> db_Fetch())
	{
	// PERMISSION!!!
		if ($this -> getPermission("image",$ImageCount['imageId'],"View")) {
			$Count++;
		}
	}
	return strval ($Count);
}
function getUserImageCount($userid)
{
// returns count of images in user view as string

	if(isset($this->userImgCount[$userid]))
	{
		return (string) $this->userImgCount[$userid];
	}

	return null;
	/*$Count = 0;
	global $sql;
	$sql->db_Select("pview_image", "*", "WHERE uploaderUserId='$userid'", "nowhere");
	while ($ImageCount = $sql -> db_Fetch())
	{
	// PERMISSION!!!
		if ($this -> getPermission("image",$ImageCount['imageId'],"View")) {
			$Count++;
		}
	}
	return strval ($Count);*/
}

function getSubAlbumsCount($albumid,$ignorePerm) {
// returns count of subalbums for album- and gallery view as string
	$Appl = $this -> getAppl();
	if ($Appl[0] == "album" && !$albumid) {
		$albumid = $Appl [1];
	}
	$Count = 0;
	global $sql;
	$sql->db_Select("pview_album", "*", "WHERE parentAlbumId='$albumid'", "nowhere");
	while ($AlbumCount = $sql -> db_Fetch()) 
	{
	// PERMISSION!!!
	if ($this -> getPermission("album",$AlbumCount['albumId'],"View") OR $ignorePerm) {
		$Count++;
	}
	}
	return strval ($Count);
}
function getAllGalleryData () {
// returns a 2D array of all galleries data
	global $sql;
	$out_GalleryData = array();
	$sql->db_Select("pview_gallery", "*", "", "nowhere");
	while ($GalleryData = $sql -> db_Fetch()) 
	{
		// PERMISSION???
		array_push ($out_GalleryData, $GalleryData);
	}
	return $out_GalleryData;
}

function getGalleryActive($galleryid) {
// returns true if gallery is active
	$gal_SQL = new db;
	$gal_SQL -> db_Select("pview_gallery", "active", "WHERE galleryId='$galleryid'", "nowhere");
	list($galActive) = $gal_SQL -> db_Fetch();
	return $galActive;
}
function getRootAlbumData ($galleryid) {
// returns a 2D array of rootalbums data
	$Appl = $this -> getAppl();
	if ($Appl[0] == "gallery" && !$galleryid) {
		$galleryid = $Appl [1];
	}
	global $sql;
	$out_RootAlbum = array();
	$sql->db_Select("pview_album", "*", "WHERE galleryId='$galleryid'&&parentAlbumId=0", "nowhere");
	while ($RootAlbum = $sql -> db_Fetch())
	{
	array_push ($out_RootAlbum, $RootAlbum);
	}
	return $out_RootAlbum;
}
function getSubAlbumData () {
// returns a 2D array of subalbums data
	$Appl = $this -> getAppl();
	if ($Appl[0] == "album") {
		$albumid = $Appl [1];
	}
	global $sql;
	$out_SubAlbum = array();
	$sql->db_Select("pview_album", "*", "WHERE parentAlbumId='$albumid'", "nowhere");
	while ($SubAlbum = $sql -> db_Fetch())
	{
	// PERMISSION???
	array_push ($out_SubAlbum, $SubAlbum);
	}
	return $out_SubAlbum;
}
function getAlbumData($albumid) {
// returns array of album data
	global $sql;
	$sql->db_Select("pview_album", "*", "WHERE albumId='$albumid'", "nowhere");
	return $sql -> db_Fetch();
}
function getGalleryData($galleryid) {
// returns array of gallery data
	global $sql;
	$sql->db_Select("pview_gallery", "*", "WHERE galleryId='$galleryid'", "nowhere");
	return $sql -> db_Fetch();
}
function getGalleryName($galleryid) {
// returns gallery name
	$Appl = $this -> getAppl();
	if ($Appl[0] == "gallery" && !$galleryid) {
		$galleryid = $Appl [1];
	}	
	$GalleryData = $this -> getGalleryData($galleryid);
	return $GalleryData['name'];
}
function getAlbumImage($albumid) {
// returns albumimage file or alternate file
	$Appl = $this -> getAppl();
	if ($Appl[0] == "album" && !$albumid) {
		$albumid = $Appl [1];
	}
	$AlbumData = $this -> getAlbumData($albumid);
	if (!$AlbumData['albumImage']) {
		$out_AlbumImage = SITEURLBASE.e_PLUGIN_ABS."pviewgallery/templates/".$this -> getPView_config("template")."/images/nopicture.png";
		//$out_AlbumImage = e_PLUGIN."pviewgallery/templates/".$this -> getPView_config("template")."/images/nopicture.png";
	} else {
		$out_AlbumImage = SITEURLBASE.e_PLUGIN_ABS."pviewgallery/gallery/album".$albumid."/". $AlbumData['albumImage'];
		//$out_AlbumImage = e_PLUGIN."pviewgallery/gallery/album".$albumid."/". $AlbumData['albumImage'];
	}
	return $out_AlbumImage;
}
function getAlbumName($albumid) {
// returns album name
	$Appl = $this -> getAppl();
	if ($Appl[0] == "album" && !$albumid) {
		$albumid = $Appl [1];
	}
	$AlbumData = $this -> getAlbumData($albumid);
	return $AlbumData['name'];
}
function getImageName($imageid) {
// returns image name
	$Appl = $this -> getAppl();
	if ($Appl[0] == "image" && !$imageid) {
		$imageid = $Appl [1];
	}
	$ImageData = $this -> getImageData($imageid);
	return $ImageData['name'];
}
function getCatName($catid) {
// returns category name
	$Appl = $this -> getAppl();
	if ($Appl[0] == "cat" && !$catid) {
		$catid = $Appl [1];
	}	
	$catData = $this->getCatData($catid);
	return $catData['name'];
}
function getUserName($userid) {
// returns user name
	$Appl = $this -> getAppl();
	if ($Appl[0] == "user" && !$userid) {
		$userid = $Appl [1];
	}	
	$userData = $this->getUserData($userid);
	return $userData['user_name'];
}
function getResizeImage() {
// returns resized image file OR thumbnail as fallback
	$Appl = $this -> getAppl();
	return "<a href='javascript:showImage();'><img src='".$this -> getResizePath($Appl[1])."'></a>";
}
function getImageData($imageid) {
// returns array of image data
	global $sql;
	$sql->db_Select("pview_image", "*", "WHERE imageId='$imageid'", "nowhere");
	return $sql -> db_Fetch();
}
function getImageAlbum($imageid) {
// returns album Id of image, used to inherit album permission to image
	$Appl = $this -> getAppl();
	if ($Appl[0] == "image" && !$imageid) {
		$imageid = $Appl [1];
	}
	$ImageAlbum = $this -> getImageData($imageid);
	return $ImageAlbum['albumId'];
}

function getApplImages(){
// returns a 2D array of images data, acc. to selected view, incl. rating and commentscount for sorting 	
	$Appl = $this -> getAppl();
	$i_SQL = new db;
	$out_Images = array();
	$Image = array();
	global $ImageSort;
	
	if ($Appl[0] == "image") {
		$imageData = $this->getImageData($Appl[1]);
		if (isset($_GET['view'])){
			if ($_GET['view'] == "cat"){
				$arg = "WHERE cat=".$imageData['cat'];
				$ImageSort = $this->getPView_config("cat_sort");
			}
			if ($_GET['view'] == "user"){
				$arg = "WHERE uploaderUserId=".$imageData['uploaderUserId'];
				$ImageSort = $this->getPView_config("user_sort");
			}
			if ($_GET['view'] == "album" OR $_GET['view'] == ""){
				$arg = "WHERE albumId=".$imageData['albumId'];
				$ImageSort = $this->getPView_config("album_sort");
			}
			
			// check viewer sorting and overwrite default!!!
			if (isset($_SESSION['pv_'.$_GET['view'].'_sort']) && $this->getPView_config('viewer_sort')) {
				$ImageSort = $_SESSION['pv_'.$_GET['view'].'_sort'];
			}			
		} else {
			$arg = "WHERE albumId=".$imageData['albumId'];
			$ImageSort = $this->getPView_config("album_sort");
		}
		// check viewer sorting and overwrite default!!!	
		if (isset($_SESSION['pv_album_sort']) && $this->getPView_config('viewer_sort')) {
			$ImageSort = $_SESSION['pv_album_sort'];
		}	
	}
	
	if ($Appl[0] == "album") {
		$arg = "WHERE albumId=".$Appl[1];
		$ImageSort = $this->getPView_config("album_sort");
		// check viewer sorting and overwrite default!!!
		if (isset($_SESSION['pv_album_sort']) && $this->getPView_config('viewer_sort')) {
			$ImageSort = $_SESSION['pv_album_sort'];
		}		
	}
	if ($Appl[0] == "cat") {
		$arg = "WHERE cat=".$Appl[1];
		$ImageSort = $this->getPView_config("cat_sort");
		// check viewer sorting and overwrite default!!!
		if (isset($_SESSION['pv_cat_sort']) && $this->getPView_config('viewer_sort')) {
			$ImageSort = $_SESSION['pv_cat_sort'];
		}		
	}
	if ($Appl[0] == "user") {
		$arg = "WHERE uploaderUserId=".$Appl[1];
		$ImageSort = $this->getPView_config("user_sort");
		// check viewer sorting and overwrite default!!!
		if (isset($_SESSION['pv_user_sort']) && $this->getPView_config('viewer_sort')) {
			$ImageSort = $_SESSION['pv_user_sort'];
		}		
	}
	$i_SQL->db_Select("pview_image","*",$arg." ORDER BY imageId ASC","nowhere");
	while ($Image = $i_SQL -> db_Fetch()) {
		// PERMISSION!!!
		if ($this -> getPermission("image",$Image['imageId'],"View")){
			$rating = $this -> getRatingData($Image['imageId']);
			$Image['ratingvalue'] = $rating['value'];
			$Image['commentscount'] = $this -> getCommentsCount($Image['imageId']);
			array_push ($out_Images, $Image);			
		}

	}
	
	return $out_Images;
}

function imagepicker($type,$curImageId,$albumId) {
// returns imageId of requested image for image view (navigation menu)
	global $sql;
	switch ($type) {
	case "first":
		if ($this -> getPView_config('album_dir') == "ASC"){
	    	$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId' ORDER BY uploadDate LIMIT 1", "nowhere");
	    } else {
			$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId' ORDER BY uploadDate DESC LIMIT 1", "nowhere");
		}
	    break;
	case "prev":
		if ($this -> getPView_config('album_dir') == "ASC"){
	    	$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId'&& imageId<'$curImageId' ORDER BY uploadDate DESC LIMIT 1", "nowhere");
	    } else {
			$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId'&& imageId>'$curImageId' ORDER BY uploadDate LIMIT 1", "nowhere");
		}
	    break;
	case "next":
		if ($this -> getPView_config('album_dir') == "ASC"){
	    	$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId'&& imageId>'$curImageId' ORDER BY uploadDate LIMIT 1", "nowhere");
	    } else {
			$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId'&& imageId<'$curImageId' ORDER BY uploadDate DESC LIMIT 1", "nowhere");
		}
	    break;
	case "last":
		if ($this -> getPView_config('album_dir') == "ASC"){
	    	$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId' ORDER BY uploadDate DESC LIMIT 1", "nowhere");
	    } else {
			$sql->db_Select("pview_image", "imageId", "WHERE albumId='$albumId' ORDER BY uploadDate LIMIT 1", "nowhere");
		}
	    break;
	}
	$ImageRequest = $sql -> db_Fetch();
	return $ImageRequest['imageId'];
}
function setImageViews($imageid) {
// no return, increases image views
	global $sql;
	$curViews = $this -> getImageData($imageid);
	$actViews = $curViews['views'] + 1;
	$sql->db_Update("pview_image", "views=".$actViews." WHERE imageId='".$imageid."'");
}
function getCommentsCount($imageid) {
// returns total count of comments for this image
	$Appl = $this -> getAppl();
	if ($Appl[0] == "image" && !$imageid) {
		$imageid = $Appl [1];
	}
	global $sql;
	$Count = 0;
	$sql->db_Select("pview_comment", "*", "WHERE commentImageId='$imageid'", "nowhere");
	while ($CommentsCount = $sql -> db_Fetch()) 	{
		$Count++;
	}
	return strval($Count);
}
function getCommentsData($imageid) {
// returns a 2D array of comment data
	$Appl = $this -> getAppl();
	if ($Appl[0] == "image" && !$imageid) {
		$imageid = $Appl [1];
	}
	global $sql;
	$out_CommentsData = array();
	$sql->db_Select("pview_comment", "*", "WHERE commentImageId='$imageid' ORDER BY commentDate", "nowhere");
	while ($CommentData = $sql -> db_Fetch()) {
		array_push ($out_CommentsData, $CommentData);
	}
	return $out_CommentsData;
}
function getUserRated($imageid) {
// returns true if user has rated this image
	$Appl = $this -> getAppl();
	if ($Appl[0] == "image" && !$imageid) {
		$imageid = $Appl [1];
	}
	global $sql;
	$userid = USERID;
	$sql->db_Select("pview_rating", "ratingValue", "WHERE ratingImageId='$imageid' && rating107userId='$userid'", "nowhere");
	if ($sql -> db_Fetch()) { return 1;}
	return 0;
}
function getRatingData($imageid) {
// returns rating result and rating count
	$Appl = $this -> getAppl();
	if ($Appl[0] == "image" && !$imageid) {
		$imageid = $Appl [1];
	}
	$r_SQL = new db;
	$Count = 0;
	$Value = 0;
	$r_SQL->db_Select("pview_rating", "ratingValue", "WHERE ratingImageId='$imageid'", "nowhere");
	while ($tmp_Value = $r_SQL -> db_Fetch()) {
		$Count++;
		$Value = $Value + intval($tmp_Value['ratingValue']);
	}
	$RatingData = array("value"=>$Value/$Count,"count"=>$Count);
	return $RatingData;
}
function getTemplates() {
// returns all available templates
	$tmp_dir  = opendir(e_PLUGIN."pviewgallery/templates");
	while ($file = readdir($tmp_dir)){
		if (is_dir(e_PLUGIN."pviewgallery/templates/".$file)){
			if ($file != "." && $file != ".."){
				$files[]=$file;
			}
		}
	}
	closedir($tmp_dir);
	sort($files);
	return $files;
}
function getTemplateInfo(){
// returns Infotext for selected template	
	if (file_exists(e_PLUGIN . "pviewgallery/templates/".$this->getPView_config("template")."/language/".e_LANGUAGE.".php")){
		include_once(e_PLUGIN. "pviewgallery/templates/".$this->getPView_config("template")."/language/".e_LANGUAGE.".php");
	}
	else{
		include_once(e_PLUGIN . "pviewgallery/templates/".$this->getPView_config("template")."/language/German.php");
	}
	return "<br><b>".LAN_TMP_DES."</b><br>".LAN_TMP_FEAT_1;
}
function deleteIPs() {
// no return, delete all outdated db entries
	global $sql;
	$validTime = time() - $this -> getPView_config("ip_valid_time");
	$sql->db_Delete("pview_tmpip", "time<'$validTime'");
}
function getImagesfromIP($ip) {
// returns a string of all viewed images
	global $sql;
	$sql->db_Select("pview_tmpip", "images", "WHERE ip_addr='$ip'", "nowhere");
	return $sql -> db_Fetch();
}
function getnonapprovedImages() {
// returns a 2D array of images data for admin panel
	global $sql;
	$out_Images = array();
	$sql->db_Select("pview_image", "*", "WHERE approved='0' ORDER BY uploaderUserId", "nowhere");
	while ($Image = $sql -> db_Fetch())
	{
	array_push ($out_Images, $Image);
	}
	return $out_Images;
}
function setImageRating($imageid) {
// no return, insert imagerating
	global $sql;
	$Value = $_GET['rate'];
	$arg = "0,".USERID.",".time().",".$Value.",".$imageid;
	$sql -> db_Insert("pview_rating","$arg");
}
function getLink($submit) {
// return link for comments
	$out_Link = "pviewgallery.php?image=";
	$out_Link.= $_GET['image'];
	if ($_GET['page']) {
		$out_Link.= "&page=".$_GET['page'];
	}
	if ($_GET['view']) {
		$out_Link.= "&view=".$_GET['view'];
	}	
	if ($submit == "submit") {
		if ($_GET['comment']) {
			$out_Link.= "&comment=".$_GET['comment'];
		}
		$out_Link.= "#a_preview";
	}
	return $out_Link;
}
function setImageComment($appl) {
// no return, insert or update comment
	global $tp;
	global $sql;
	$text = $tp -> toDB($_POST['pview_comment']);
	if ($appl == "ADD") {
		$arg = "0,".USERID.",".time().",'".$text."',".$_GET['image'];
		$sql -> db_Insert("pview_comment","$arg");
	} else {
		$arg = "commentText='".$text."' WHERE commentId=".$_GET['comment'];
		$sql -> db_Update("pview_comment","$arg");
	}
}
function deleteComment ($cid) {
// no return, delete comment
	global $sql;
	$sql->db_Delete("pview_comment", "commentId='$cid'");
}

function getHiddenFields() {
// returns all hidden fields for comments
	if ($_POST['appl'] == "ADD") {
		$out_Fields = "<input type='hidden' name='appl' id='appl' value='ADD' />";
	} else {
		$out_Fields = "<input type='hidden' name='appl' id='appl' value='EDIT' />";
	}
	
	return $out_Fields;
}
function getDelConfirm($obj) {
// returns a confirmation text with 2 buttons: confirm, cancel
		$outText = "<div style='padding:20px; color:red; text-align:center;'>";
		$outText.= "<p>".LAN_IMAGE_43;
		$outText.= $obj;
		$outText.= "</p><p><a href='".e_SELF."?".e_QUERY."&confirm=1'><span class='button' style='padding-left:4px; padding-right:4px;'>".LAN_IMAGE_42."</span></a> ";
		$outText.= "<a href='javascript:history.back()'><span class='button' style='padding-left:4px; padding-right:4px;'>".LAN_IMAGE_27."</span></a>";
		$outText.= "</p></div>";
		return $outText;
}
function getAdminMsg($status) {
// return feedback for Config Changes
	if ($status) { // default
		$msg_HTML = LAN_ADMIN_56;
	}
	if ($status == 3 ) { // update: no changes
		$msg_HTML = LAN_ADMIN_58;
	}
	if ($status == 2 ) { // image delete: db OK, file error
		$msg_HTML = LAN_ADMIN_90." ".LAN_ADMIN_92;
	}
	if ($status == 4 ) { // gallery delete: incl. album could not deleted
		$msg_HTML = LAN_ADMIN_106;
	}
	if ($status == 5 ) { // album delete: db OK, folder error
		$msg_HTML = LAN_ADMIN_107;
	}	
	if (!$status) { // error
		$msg_HTML = LAN_ADMIN_57;
	}
	return $msg_HTML;
}
function getpermClasses($appl) {
// returns perm Class string
	foreach ($_POST as $key => $dataset) {
		if (is_array ($dataset)) {
			foreach ($dataset as $skey => $sdataset) {
				if ($key == $appl) {
					if ($skey == "all") {
						$permClasses = "ALL";
						break;
					}
					if ($skey == "member") {
						$permClasses = "MEMBER";
						break;
					}
					$permClasses.= $skey.",";
				}
			}
		}
	}
	$permClasses = rtrim($permClasses,",");
	if ($permClasses){
		return $permClasses;
	}
	return "0";
}
function getScroller($imgArray) {
// returns complete html for menu-scroller

	$pv_path = e_PLUGIN."pviewgallery/";

	$box_dir = $this -> getPView_config("menu_dir");
	
	if ($this -> getPView_config("img_Link_menu_extJS")) {
		$script = $this -> getPView_config("img_Link_extJS");
	} else {
		$script = "noscript";
	}
	
//	$box_pics = intval($this -> getPView_config("menu_pics"));
	$menuPics = array();
	$menuPics = explode(",",$this -> getPView_config("menu_pics"));
	if ($menuPics[1] > count($imgArray) OR !$menuPics[1]){
		$menuPics[1] = count($imgArray);
	}
	if ($menuPics[1] <= $menuPics[0]){
		if ($menuPics[1] > 1){
			$menuPics[0] = $menuPics[1] - 1;
		} else {
			$menuPics[0] = 1;
		}
		
	}
	$scroll_speed = $this -> getPView_config("scroll_speed");
	
	$imageCount = 0;
	if ($this -> getPView_config("force_imageSize")){
		$hor_grid = intval($this -> getPView_config("force_Height"));
		$vert_grid = intval($this -> getPView_config("force_Width"));
	} else {
		if ($this -> getPView_config("thumb_height")){
			$hor_grid = intval($this -> getPView_config("thumb_height"));
		} else {
			$hor_grid = 150; // default
		}
		if ($this -> getPView_config("thumb_width")){
			$vert_grid = intval($this -> getPView_config("thumb_width"));
		} else {
			$vert_grid = 150; // default
		}
	}
	$hor_grid = $hor_grid + 10; // min. space between pics
	$vert_grid = $vert_grid + 10; // min. space between pics	
	
	// image area dimension
	if ($box_dir == "hor"){
		$box_height = $hor_grid;
		$box_width = ($menuPics[0]) * $vert_grid;
	} else {
		$box_height = ($menuPics[0]) * $hor_grid;
		$box_width = $vert_grid;
	}	

	$pv_text .= "<script type='text/javascript' src = '".$pv_path."pview.js'></script>";
	$pv_text .= "<br /><table align='center'><tr><td align='center'>";
	if ($scroll_speed) {
		$pv_text .= "<div  style='position:relative; width:".$box_width."px; height: ".$box_height."px; overflow: hidden;' onmouseover='pv_stop()' onmouseout='pv_start()'>";
	} else {
		$pv_text .= "<div  style='position:relative; width:".$box_width."px; height: ".$box_height."px; overflow: hidden;'>";
	}
	
	foreach($imgArray as $dataset){
		$thumb = $this -> getThumbPath($dataset['imageId']);
		$resize = $this -> getResizePath($dataset['imageId']);
		if ($this -> getPView_config("force_imageSize")){
			$imgHeight = intval($this -> getPView_config("force_Height"));
			$imgWidth = intval($this -> getPView_config("force_Width"));
		} else {
			$ImageSize = getimagesize($thumb);
			$imgHeight = $ImageSize[1];
			$imgWidth = $ImageSize[0]; 
		}
		
		switch ($script) {
					case "noscript":
					// image will open in pviewgallery
					$pv_text.= "<a href='".e_PLUGIN."pviewgallery/pviewgallery.php?image=".$dataset['imageId']."'>";
					break;
					case "lightbox":
					// image will open in lightbox group	
					$pv_text.= "<a href='".$resize."' rel='lightbox[pview_scroller]' title='".$dataset['name']."'>";
					break;
					case "shadowbox":
					// image will open in shadowbox group	
					$pv_text.= "<a href='".$resize."' rel='shadowbox[pview_scroller]' title='".$dataset['name']."'>";
					break;
					case "highslide":
					// image will open in highslide group
					if ($this->getPView_config("img_Link_extJS_pview"))	{
						$pv_text.= "<a href='".$resize."' class='highslide' onclick=\"return hs.expand(this,pview_scroller)\" title='".$dataset['name']."'>";
					} else {
						// ehighslide plugin compatible
						$pv_text.= "<a href='".$resize."' class='highslide' onclick='return hs.expand(this)' title='".$dataset['name']."'>";
					}
					
					break;																
					
		}
		
		if ($box_dir == "vert") {
			$pv_text .= "<img title='".$dataset['name']."' name='pv_menu' id='pv_menu' src='".$thumb."' width='".$imgWidth."' height='".$imgHeight."' style='position:absolute; top:".$imageCount * $hor_grid."px; left:".round(($box_width - $imgWidth) / 2) ."px;'>";
		} 
		if ($box_dir == "hor") {
			$pv_text .= "<img title='".$dataset['name']."' name='pv_menu' id='pv_menu' src='".$thumb."' width='".$imgWidth."' height='".$imgHeight."' style='position:absolute; left:".$imageCount * $vert_grid."px; top:".round(($box_height - $imgHeight) / 2) ."px;'>";
		}		
		
		$pv_text.= "</a>";			
		
		$imageCount++;
	}		
	$pv_text .= "</div></td></tr></table><br />";
	if ($scroll_speed) {
		$pv_text .= "<script type='text/javascript'>
					var pv_space_hor = ".$vert_grid.";
					var pv_space_vert = ".$hor_grid.";
					var pv_direction = '".$box_dir."';
					var pv_speed = ".$scroll_speed.";
					var pv_boxwidth = ".$box_width.";
					var pv_boxheight = ".$box_height.";
					var pv_img_count = ".intval($menuPics[1]).";
					var pv_img_view = ".intval($menuPics[0]).";		
					window.setTimeout('pv_start()',1000);		
					</script>";
	}
	return $pv_text;
}
function getCatData($catId){
//returns array of category data
	global $sql;
	$sql->db_Select("pview_cat", "*", "WHERE catId='$catId'", "nowhere");
	return $sql -> db_Fetch();
}
function getThumbPath($imageid, $pathtype = "ABS"){
//returns thumbnail path or fallback (nothumb)	
	$out_file = $this -> getImageData($imageid);
	if ($out_file['thumbnail']){
		if ($pathtype == "ABS") {
			return SITEURLBASE.e_PLUGIN_ABS."pviewgallery/gallery/album".$out_file['albumId']."/".$out_file['thumbnail'];
		} else {
			return e_PLUGIN."pviewgallery/gallery/album".$out_file['albumId']."/".$out_file['thumbnail'];
		}
	} else {
		if ($pathtype == "ABS") {
			return SITEURLBASE.e_PLUGIN_ABS."pviewgallery/templates/".$this -> getPView_config("template")."/images/nothumb.png";
		} else {
			return e_PLUGIN."pviewgallery/templates/".$this -> getPView_config("template")."/images/nothumb.png";
		}
	}
}
function getResizePath($imageid,$pathtype = "ABS"){
//returns resize path or fallback (thumb)
	$out_file = $this -> getImageData($imageid);
	if ($out_file['filenameResized']){
		if ($pathtype == "ABS") {
			return SITEURLBASE.e_PLUGIN_ABS."pviewgallery/gallery/album".$out_file['albumId']."/".$out_file['filenameResized'];
		} else {
			return e_PLUGIN."pviewgallery/gallery/album".$out_file['albumId']."/".$out_file['filenameResized'];
		}
	} else {
		return $this -> getThumbPath($imageid,$pathtype);
	}
}
function getOrigPath($imageid,$pathtype = "ABS"){
//returns original file path or fallback (resize)

	$out_file = $this -> getImageData($imageid);
// normal gallery image
	if (!$out_file['externalImage']){
		if ($out_file['filename']){
			if ($pathtype == "ABS") {
				return SITEURLBASE.e_PLUGIN_ABS."pviewgallery/gallery/album".$out_file['albumId']."/".$out_file['filename'];
			} else {
				return e_PLUGIN."pviewgallery/gallery/album".$out_file['albumId']."/".$out_file['filename'];
			}
		} else {
			return $this -> getResizePath($imageid,$pathtype);
		}
	}
// external image
	if ($out_file['externalImage']){
		if ($out_file['filename']){
			return $out_file['filename'];
		} else {
			return $this -> getResizePath($imageid);
		}
	}	
}
function deleteImage($imageid){
// delete image in DB and files, 
// returns 0: not deleted (ERR)
// returns 1: deleted (OK)
// returns 2: deleted in DB, files not deleted (ERR)
	global $sql;
	$imgData = $this -> getImageData($imageid);
	$albumData = $this -> getAlbumData($imgData['albumId']);
	$del_OK = 1;
	if ($sql -> db_Delete("pview_image", "imageid='$imageid'")){
		// delete corresponding comments
		$this -> deleteImageComments($imageid);
		
		// delete corresponding ratings
		$this -> deleteImageRatings($imageid);
		
		//delete files only if database entry is deleted
		if ($imgData['filename'] && !$imgData['externalImage']){
			$del_OK = unlink(e_PLUGIN."pviewgallery/gallery/album".$imgData['albumId']."/".$imgData['filename']);
		}
		if ($imgData['filenameResized']){
			$del_OK = $del_OK & unlink(e_PLUGIN."pviewgallery/gallery/album".$imgData['albumId']."/".$imgData['filenameResized']);
		}
		if ($imgData['thumbnail']){
			if ($imgData['thumbnail'] <> $albumData['albumImage']) { // do not delete, if thumbnail is albumimage
				$del_OK = $del_OK & unlink(e_PLUGIN."pviewgallery/gallery/album".$imgData['albumId']."/".$imgData['thumbnail']);	
			}
		}
		if ($del_OK){ 
			return 1;
		} else{
			return 2;
		}
	}
	return 0;
}
function deleteImageComments($imageid) {
// delete all image-comments
	global $sql;
	$sql->db_Delete("pview_comment", "commentImageId=$imageid");
}
function deleteImageRatings($imageid) {
// delete all image-ratings
	global $sql;
	$sql->db_Delete("pview_rating", "ratingImageId=$imageid");
}

function getExist($appl, $applId) {
// returns true if element exists (gallery, album, image)
	global $sql;
	$sql->db_Select("pview_".$appl, "*", "WHERE ".$appl."Id='$applId'", "nowhere");
	if ($sql -> db_Fetch()){
		return 1;
	}
	return 0;
}
function pviewUpdateDb($table,$field,$newvalue,$type,$row) {
// returns 0: error
// returns 1: OK
// returns 3: no change
	global $sql;
	global $tp;
	
	if ($type == "checkbox") {
		if ($newvalue == "on") {
			$newvalue = "1";
		} else {
			$newvalue = "0";
		}
	}
	if ($type == "text") {
		$newvalue = $tp -> toDB($newvalue);
	}
	
	// config values
	if ($table == "pview_config") {
		if ($newvalue <> $this -> getPView_config($field)) {	
			if (!$sql -> db_Update("pview_config","configValue='$newvalue' WHERE configName='$field'") ) {
				return 0 ;
			}
			return 1 ;
		} else {
			return 3 ;
		}
	// content values
	} else {
		$argWhere = "WHERE ".$table."Id=".$row;
		$sql->db_Select("pview_$table", "$field", $argWhere, "nowhere");
		if (!list($currentvalue) = $sql -> db_Fetch()){
			return 0;
		} else {
			if ($newvalue <> $currentvalue) {
				$argUpdate = $field."='".$newvalue."' WHERE ".$table."Id=".$row;
				if (!$sql -> db_Update("pview_".$table,$argUpdate) ) {
					return 0 ;
				}
				return 1;
			} else {
				return 3;
			}
		}
	}
}
function removeAlbum($albumId) {
// delete db und directory
// returns: deletd row, 0 or FALSE
	$path = e_PLUGIN."pviewgallery/gallery/album".$albumId."/";
	if (!$this -> getAlbumImageCount($albumId,1) && !$this -> getSubAlbumsCount($albumId,1)) {
		global $sql;
		if ($sql->db_Delete("pview_album", "albumId=$albumId")) {
			// delete remained albumimages
			$dir = opendir ($path);
			while (($entry = @readdir($dir)) !== false) {
				if ($entry == '.' || $entry == '..') { continue; }
				if (is_file ($path.'/'.$entry)) {
					unlink ($path.'/'.$entry);
				}
			}
			closedir ($dir);
			// remove directory
			$delOK = rmdir($path);
			if ($delOK) {return 1;} else {return 5;}
		}
		return 0; // db error
	} else {
		// album not emty!
		return 4;
	}
}
function deleteAlbum($albumId) {
// prepare album-removal (check for sub-albums and images)
// returns: 1 on delete OK
// returns: 0 on error
// returns: 4 (5) on error deleting sub-album
	$sql_del =& new db;
	if ($this -> getAlbumImageCount($albumId,1)) {
		$sql_del->db_Select("pview_image","imageId","WHERE albumId=$albumId","nowhere");
		while ($delImage = $sql_del->db_Fetch()) {
			$this -> deleteImage($delImage['imageId']);
		}
	}
	if ($this -> getSubAlbumsCount($albumId,1))  {
		$sql_del->db_Select("pview_album","albumId","WHERE parentAlbumId=$albumId","nowhere");
		while ($delAlbum = $sql_del->db_Fetch()) {
			// delete sub-album
			if (!$this -> deleteAlbum($delAlbum['albumId'])) {
				return 4; // escape on error
			}
		}
	}
	return $this -> removeAlbum($albumId);
}
function deleteGallery($galleryId) {
// prepare gallery-removal (check for root-albums), delete gallery (db)
// returns: 1 on delete OK
// returns: 0 on error
// returns: 4 on error deleting included album
	global $sql;
	if ($this -> getGalleryAlbumCount($galleryId)) {
		// delete all albums inside this gallery
		$rootAlbums = $this -> getRootAlbumData($galleryId);
		foreach ($rootAlbums as $dataset) {
			$delOK = $this -> deleteAlbum($dataset['albumId']);
			if ($delOK <> 1) {return 4;}
		}
	}
	$sqlOK = $sql->db_Delete("pview_gallery", "galleryId=$galleryId");
	if ($sqlOK) {return 1;} else {return 0;}
}
function getCatArray() {
// returns array with catId as key and name as value
// used to find catId by name
	global $sql;
	$catArray = array();
	$sql -> db_Select("pview_cat", "catId,name", "ORDER BY catId", "nowhere");
	while ($catTmp = $sql -> db_Fetch()){	
		$catArray[$catTmp['catId']] = $catTmp['name'];
	}
	return $catArray;
}
function getSortBox($view,$tmp){
// returns a selectbox for sorting elements in different views
// $tmp is for temporary viewer sorting
	$sortArray = $this->getSortArray();
	if ($tmp && $_SESSION['pv_'.$view.'_sort']) {
		$sortkey = $_SESSION['pv_'.$view.'_sort'];
	}else {
		$sortkey = $this->getPView_config($view."_sort");
	}
	$sortBox = "<select class='tbox' id='pv_".$view."_sort' name='pv_".$view."_sort'";
	if ($tmp){
		// activate on gallery page only
		$sortBox.= " onchange='this.form.submit();'>";
	} else {
		$sortBox.= ">";
	}
		
	foreach ($sortArray as $key => $dataset) {
		if ($key == $sortkey) {
			$selText = "selected";
		} else {
			$selText = "";
		}
		$sortBox.= "<option ".$selText.">".$dataset."</option>";
	}
	$sortBox.= "</select>";
	return $sortBox;
}



function getOrderSelector($name,$maxValue,$selValue){
// returns a selectbox for element order
	$orderBox = "<select class='tbox' id='pv_".$name."' name='pv_".$name."'>";
		
	for ($curValue = 1; $curValue <= $maxValue; $curValue++) {
		if ($curValue == $selValue) {
			$selText = "selected";
		} else {
			$selText = "";
		}
		$orderBox.= "<option ".$selText.">".$curValue."</option>";
		
	}
	$orderBox.= "</select>";
	return $orderBox;
}
function getFrontpageArray() {
// returns a array with defined order for admin_startpage and advanced frontpage in gallery view
	$frontpageArray = array("stat_short" => $this->getPView_config("stat_short"),"stat_album" => $this->getPView_config("stat_album"),"stat_cat" => $this->getPView_config("stat_cat"),
	"stat_comm" => $this->getPView_config("stat_comm"),"stat_img" => $this->getPView_config("stat_img"),"stat_imgRating" => $this->getPView_config("stat_imgRating"),
	"stat_imgViews" => $this->getPView_config("stat_imgViews"),"stat_Uploader" => $this->getPView_config("stat_Uploader"),"stat_userComm" => $this->getPView_config("stat_userComm"),
	"stat_userGals" => $this->getPView_config("stat_userGals"));
	asort($frontpageArray,SORT_NUMERIC);
	return $frontpageArray;
}
function getStatistic_Totals() {
// no return; feeds $outStat_Totals to save ressources	
	global $outStat_Totals;
	global $sql;
	global $applImages;
	$allImages = $applImages;
	//$allImages = $this->getApplImages();
	$allUploadersArray = array();
	$allAlbums = $sql->db_Select("pview_album","albumId", "", "nowhere");
	while ($allAlbums = $sql -> db_Fetch()) {
		// PERMISSION!!!
		if ($this->getPermission("album",$allAlbums['albumId'],"View")) {
			$albumCount++;
		}
	}
	$allGalleries = $sql->db_Select("pview_gallery","galleryId", "", "nowhere");
	while ($allGalleries = $sql -> db_Fetch()) {
		// PERMISSION!!!
		if ($this->getPermission("gallery",$allGalleries['galleryId'],"View")) {
			$galCount++;
		}
	}	
	$catCount = $sql->db_count("pview_cat");
	foreach($allImages as $key => $dataset) {
		// average Rating
		if ($dataset['ratingvalue']) {
			$aRating = $aRating + $dataset['ratingvalue'];
			$ratingCounter++;
		}
		// total Uploaders
		if (array_search($dataset['uploaderUserId'],$allUploadersArray) === false && $dataset['uploaderUserId']) {
			array_push($allUploadersArray,$dataset['uploaderUserId']);
			$uploaderCount++;
		}
		// total Views
		$imgData = $this ->getImageData($key);
		$viewsCount = $viewsCount + $imgData['views'];
		// total Comments
		$commCount = $commCount + $dataset['commentscount'];
	}
	$aRating = $aRating / $ratingCounter;
	$outStat_Totals = array("ratedImgs"=>strval($ratingCounter),"imgCount"=>strval(count($allImages)),"viewsCount"=>$viewsCount,"commCount"=>$commCount,"aRating"=>round($aRating,2),"albumCount"=>$albumCount,"catCount"=>$catCount,"uploaderCount"=>$uploaderCount,"galCount"=>$galCount);
}
function getNoUserImages() {
// returns images count for invalid users
	global $outStat_Totals;
	$uploader = $this->getStatistic_Uploader();	
	foreach ($uploader as $key=>$dataset) {
		$validImgCount = $validImgCount + $dataset;
	}
	return $outStat_Totals['imgCount'] - $validImgCount;
}
function sortApplImages($ImagesArray,$order="") {
// returns sorted ImagesArray
	// Sort with $ImageSort(global)
	$PView = new PView;
	global $SortOrder;
	if (!$order) {
		$SortOrder = $PView->getPView_config("album_dir");
	} else {
		$SortOrder = $order;
	}
	usort($ImagesArray,"pv_cmp_class");
	return $ImagesArray;
}

} // PView class end
function pv_cmp_class($a, $b) {
	global $ImageSort;
	global $SortOrder;
	$PView = new PView;
    if (strip_tags(strtolower($a[$ImageSort])) == strip_tags(strtolower($b[$ImageSort]))) {
        return 0;
    }
    if ($SortOrder == "DESC"){
	    if ($ImageSort == "ratingvalue") {
	    	return ($a[$ImageSort] > $b[$ImageSort]) ? -1 : 1;
	    }	
    	return (strip_tags(strtolower($a[$ImageSort])) > strip_tags(strtolower($b[$ImageSort]))) ? -1 : 1;
    }
    if ($ImageSort == "ratingvalue") {
    	return ($a[$ImageSort] < $b[$ImageSort]) ? -1 : 1;
    }    
    return (strip_tags(strtolower($a[$ImageSort])) < strip_tags(strtolower($b[$ImageSort]))) ? -1 : 1;
}
?>