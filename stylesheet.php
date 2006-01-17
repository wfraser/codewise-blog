<?php

/*
** Stylesheet Script
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2005-2006 Codewise.org
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

header("Content-type: text/css");

require("settings.php");
require("l1_mysql.php");

$db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

$url_parts = parse_url($_SERVER['HTTP_REFERER']);
parse_str($url_parts['query'], $vars = array());

if(isset($vars['skinid']) && $db->num_rows[ $db->issue_query("SELECT skinid FROM skins WHERE skinid = ".$db->prepare_value($vars['skinid'])) ] > 0)
{
    $skinid = $db->prepare_value($vars['skinid'], FALSE);
} elseif( $db->num_rows[ $q = $db->issue_query("SELECT skinid FROM blogs WHERE blogid = ".$db->prepare_value($_GET['id'])) ] > 0 ) {
    $skinid = $db->fetch_var($q);
} else {
    $skinid = "00000000000000000000000000000000";
}

$q = $db->issue_query("SELECT css FROM skins WHERE skinid = '$skinid'");

if($db->num_rows[$q] == 0 || ($text = $db->fetch_var($q)) === NULL)
    $text = $db->fetch_var($db->issue_query("SELECT css FROM skins WHERE skinid = '00000000000000000000000000000000'"));

// optimize by removing all unnecessary text
$text = preg_replace('/\/\*.*\*\//Us', " ", $text);
$text = preg_replace('/\s+/', " ", $text);

echo $text;

?>
