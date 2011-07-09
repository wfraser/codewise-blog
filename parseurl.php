<?php

/*
** Path URL Parsing Functions
** for CodewiseBlog Multi-User
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2007-2008 Codewise.org
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
 * Ultra-Experimental Warning: MAY CAUSE MAJOR BORKAGE
 *     -WRF
 */

function path_parse_url()
{
    global $db;

    $parts = explode("/", $_SERVER['PATH_INFO']);

    // first entry is empty...
    array_shift($parts);

    if (!SUBDOMAIN_MODE || $_GET['subdomain_mode'] === 0) {
        array_shift($parts);
    }

    if (count($parts) == 0 || (count($parts) == 1 && $parts[0] == "")) {
        // PATH_INFO contains nothing
        return;
    }

    switch ($parts[0]) {
case "article":
        $title = str_replace("_", "%", $db->prepare_value($parts[1], false));
        $q = $db->issue_query("SELECT tid FROM topics WHERE title LIKE \"$title\" AND blogid = ".BLOGID);
        if ($db->num_rows[$q] == 1) {
            $tid = $db->fetch_var($q);
            if ($parts[2] == "reply") {
                $_GET['reply'] = $tid;
            } else {
                if (is_numeric($parts[2]))
                    $_GET['pid'] = $parts[2];
                $_GET['tid'] = $tid;
            }
        } else {
            $_GET['tid'] = -1;
        }
        break;
case "archive":
        $_GET['year'] = $parts[1];
        $_GET['month'] = $parts[2];
        break;
    }
}

function string_to_url_goodness($string)
{
    $string = strtolower(preg_replace("/[^a-zA-Z0-9-]+/", "_", $string));
    return $string;
}

?>
