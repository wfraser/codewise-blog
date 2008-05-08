<?php

/*
** Image Fetch and Cache
** for CodewiseBlog
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2008 Codewise.org
*/

function default_icon()
{
	header("Content-Type: image/jpeg");
	readfile("default_icon.jpg");
	exit();
}

require("settings.php");
require("l1_mysql.php");

$db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

$q = $db->issue_query("SELECT photo FROM blogs WHERE blogid = ".$db->prepare_value($_GET['blogid']));
if ($db->num_rows[$q] == 0 || ($img_url = $db->fetch_var($q)) == "") {
	default_icon();
}

if (!file_exists("cache/" . md5($img_url))) {

	$size = 75; 

	$image = @imagecreatefromstring(file_get_contents($img_url));
	if (!$image) {
		default_icon();
	}

	$w = imagesx($image);
	$h = imagesy($image);

	if ($w <= $size && $h <= $size)
	{
		imagejpeg($image, FSPATH . "/cache/" . md5($img_url));
		header("Content-Type: image/jpeg");
		readfile(FSPATH . "/cache/" . md5($img_url));
		exit;
	}

	$scalefactor = $size / (($w > $h) ? $w : $h);

	$new_w = $w * $scalefactor;
	$new_h = $h * $scalefactor;

	$thumb = imagecreatetruecolor($new_w, $new_h);

	imagecopyresampled($thumb, $image, 0, 0, 0, 0, $new_w, $new_h, $w, $h);

	imagejpeg($thumb, FSPATH . "/cache/" . md5($img_url));

}

header("Content-Type: image/jpeg");

readfile(FSPATH . "/cache/" . md5($img_url));

exit();

?>
