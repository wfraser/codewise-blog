<?php

/*
** Stylesheet Script
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2005 Codewise.org
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

$q = $db->issue_query("SELECT css FROM skin WHERE blogid = " . $db->prepare_value($_GET['id']));

if($db->num_rows[$q] == 0 || ($text = $db->fetch_var($q)) === NULL)
    $text = $db->fetch_var($db->issue_query("SELECT css FROM skin WHERE blogid = '1'"));

echo $text;

?>
