<?php

/*
** Shoutbox Functions
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

function shoutbox()
{
    global $db;

    $q = $db->issue_query("SELECT * FROM shoutbox WHERE blogid = '" . BLOGID . "' ORDER BY timestamp DESC LIMIT ".SHOUTS_PER_PAGE);
    $data = $db->fetch_all($q, L1SQL_ASSOC);
    $data = array_reverse($data);

    $contents = "";
    for($i = 0; $row = $data[$i]; $i++)
    {
        $text = preg_replace("/\n$/", "", textprocess($row['text']));

        if($i % 2)  $sect = "row_odd";
        else        $sect = "row_even";

        $contents .= skinvoodoo("shoutbox", $sect, array("link" => $row['link'], "name" => $row['name'], "text" => $text, "date" => date(DATE_FORMAT, $row['timestamp'])));
    }

    if($db->num_rows[$q] == 0)
        $contents = skinvoodoo("shoutbox", "nothing");

    if(isset($_SESSION['postername']))
        $name = $_SESSION['postername'];
    else
        $name = "";

    if(isset($_SESSION['posterlink']) && $_SESSION['posterlink'] != "")
        $link = $_SESSION['posterlink'];
    else
        $link = "http://";

    return skinvoodoo("shoutbox", "", array("contents" => $contents, "posturl" => INDEX_URL . "?shoutbox", "name" => $name, "link" => $link));
}

function shoutbox_process()
{
    global $db;

    $name = strip_tags($_POST['name']);
    if($name == "")
        $name = ANONYMOUS_NAME;

    if($_POST['link'] == "http://" || $_POST['link'] == "")
        $link = null;
    else
        $link = $_POST['link'];

    $filter = in_text_filter($_POST['text']);

    if(is_array($filter))
    {
        $text = $filter[0];
        $text_filter_msg = $filter[1];
    } else {
        $text = $filter;
        $text_filter_msg = "";
    }

    if($text_filter_msg)
    {
        return "<div style=\"border: 1px solid black; background: red; color: black;\">$text_filter_msg</div>"
            . "<br />Your input:<div style=\"border: 1px solid black; background: #eee; color: black;\">" . htmlspecialchars($_POST['text']) . "</div>"
            . "<a href=\"" . INDEX_URL . "\">Back...</a>";
    }

    if($text == "")
        return "<div style=\"color:red\">Text cannot be empty.</div>Please <a href=\"" . INDEX_URL . "\">go back</a> and fix it.";

    $_SESSION['postername'] = $name;
    $_SESSION['posterlink'] = $link;

    $data = array
    (
        "blogid" => BLOGID,
        "name" => $name,
        "timestamp" => time(),
        "link" => $link,
        "text" => $text,
        "extra" => "ip: " . $_SERVER['REMOTE_ADDR'] . "\nuseragent: " . $_SERVER['HTTP_USER_AGENT'] . "\n",
    );

    $db->insert("shoutbox", $data);

    return "Your shout has been recorded successfully. :)<br /><br /><a href=\"" . INDEX_URL . "\">Go Back</a>";
}

?>
