<?php

/*
** Image Verification
** for CodewiseBlog
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2006-2008 Codewise.org
*/

/*
** This file is part of CodewiseBlog
**
** CodewiseBlog is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** CodewiseBlog is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with CodewiseBlog; if not, write to the Free Software
** Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
** This file is require()d by cwbmulti.php and requested directly.
** Only generate the image when requested directly.
*/
if(basename($_SERVER['SCRIPT_NAME']) == "imageverify.php")
{

    header("Content-Type: image/jpeg");

    require("settings.php");
    require("l1_mysql.php");

    if(isset($_GET['id']))
    {
        $db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

        $q = $db->issue_query("SELECT text FROM imageverify WHERE id = ".($prepared_id = $db->prepare_value($_GET['id'])));

        if($db->num_rows[$q] == 0)
            error_image("Bogus IVID: {$_GET['id']}");

        $text = $db->fetch_var($q);
    } else {
        /* if no IVID was given, generate some random letters and display them
         * anyways. It's good for debugging. */
        $text = genivtext();
    }

    // 150 x 50 white image
    $image = imagecreatetruecolor(150, 50);
    $white = imagecolorallocate($image, 0xff,0xff,0xff);
    imagefilledrectangle($image, 0, 0, 150, 50, $white);

    $fonts = glob(FSPATH . "/fonts/*.pfa");

    // this is the distance from the left of the image to draw the first letter
    $x = 5;

    for($i = 0; $i < strlen($text); $i++)
    {
        $letter = $text[$i];

        $fontname = $fonts[ mt_rand(0, count($fonts) - 1) ];

        $font = imagepsloadfont( $fontname );
        if($font === FALSE)
            error_image("Failed to load font: $fontname");

        // random colors, hopefully visible ;)
        $color = imagecolorallocate($image, mt_rand(0, 192), mt_rand(0, 192), mt_rand(0, 192));

        // slant letter between +/- .50
        imagepsslantfont($font, (mt_rand(-50, 50) * .01));

        $pos = imagepstext($image, $letter, $font, 40, $color, $white, $x, 40);

        // draw the next letter 3px right of where the last letter ended
        $x += $pos[2] + 3;

        imagepsfreefont($font);
    }

    imagejpeg($image);
}

// display some useful error message
function error_image($text)
{
    $font = 5;

    // length and width based on text and font size
    $x = (strlen($text) + 7) * imagefontwidth($font);
    $y = (imagefontheight($font) + 2);

    $image = imagecreatetruecolor($x, $y);
    $white = imagecolorallocate($image, 0xff,0xff,0xff);
    $black = imagecolorallocate($image, 0,0,0);
    imagefilledrectangle($image, 0,0, $x,$y, $white);

    imagestring($image, $font, 0,0, "Error: $text", $black);

    imagejpeg($image);
    exit;
}

/*
** generate 4 random letters suitable for display
** this should not be used except by this script.
*/
function genivtext()
{
    // exclude i,I,l,L and O,D; they're too similar
    $letters = array("A","a","B","b","C","c","d","E","e","F","f","G","g","H",
        "h","J","j","K","k","M","m","N","n","P","p","Q","q","R","r","S","s","T",
        "t","U","u","V","v","W","w","X","x","Y","y","Z","z");

    $ivtext = "";
    for($i = 0; $i < 4; $i++)
        $ivtext .= $letters[ mt_rand(0, count($letters) - 1) ];

    return $ivtext;
}

/*
** Use this to generate letters and hash to feed to the image generator.
** The return value should be the ?id= argument to imageverify.php
*/
function genivid()
{
    global $db;

    $ivtext = genivtext();
    $timestamp = time();

    $db->insert("imageverify", array("text" => $ivtext, "timestamp" => $timestamp));

    $q = $db->issue_query("SELECT id FROM imageverify WHERE text = '$ivtext' AND timestamp = $timestamp");
    $id = $db->fetch_var($q);

    // delete records more than 1 day old.
    $db->issue_query("DELETE FROM imageverify WHERE timestamp < " . (time() - 60*60*24*1));

    return $id;
}

?>
