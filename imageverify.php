<?php

/*
** Image Verification Generator
** for CodewiseBlog
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2006 Codewise.org
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

if(basename($_SERVER['SCRIPT_NAME']) == "imageverify.php")
{

    header("Content-Type: text/jpeg");

    require("settings2.php");
    require("l1_mysql.php");

    $db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

    $q = $db->issue_query("SELECT text FROM imageverify WHERE id = ".($prepared_id = $db->prepare_value($_GET['id'])));
    $text = $db->fetch_var($q);

    $db->issue_query("DELETE FROM imageverify WHERE id = $prepared_id");

    // 150 x 50 white image
    $image = imagecreatetruecolor(150, 50);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, 150, 50, $white);

    $fonts = glob(FSPATH . "fonts/*.pfa");

    $x = 5;

    for($i = 0; $i < strlen($text); $i++)
    {
        $letter = $text[$i];

        $font = imagepsloadfont( $fonts[ mt_rand(0, count($fonts) - 1) ] );

        $color = imagecolorallocate($image, mt_rand(0, 192), mt_rand(0, 192), mt_rand(0, 192));

        // rotate between +/- .50
        imagepsslantfont($font, (mt_rand(-50, 50) * .01));

        // shrink or stretch between +/- 2.0
        //imagepsextendfont($font, (mt_rand(-20, 20) * .1));

        $pos = imagepstext($image, $letter, $font, 40, $color, $white, $x, 40);

        $x += $pos[2] + 3;

        imagepsfreefont($font);
    }

    imagejpeg($image);
}

function genivid()
{
    global $db;

    // i or l, they're too similar, and no uppercase O or D as they are too similar
    $letters = array("A","a","B","b","C","c","d","E","e","F","f","G","g","H",
        "h","J","j","K","k","M","m","N","n","P","p","Q","q","R","r","S","s","T",
        "t","U","u","V","v","W","w","X","x","Y","y","Z","z");

    $ivtext = "";
    for($i = 0; $i < 4; $i++)
        $ivtext .= $letters[ mt_rand(0, count($letters) - 1) ];

    $ivid = md5(strtolower($ivtext));

    $db->insert("imageverify", array("id" => $ivid, "text" => $ivtext));

    return $ivid;
}

?>