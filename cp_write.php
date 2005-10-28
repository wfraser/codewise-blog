<?php

/*
** Control Panel :: Write Post
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

$current = "write";

if(empty($_POST))
{
    $body = skinvoodoo(
        "controlpanel_write", "",
        array(
            "posturl" => INDEX_URL . "?controlpanel:write",
            "quicktags" => INDEX_URL . "?quicktags.js",
            "text" => "",
            "title" => "Title",
        )
    );
} else {
    if(isset($_POST['preview']))
    {
        $body = skinvoodoo("controlpanel_write", "preview_topic", array("text" => output_topic_text($_POST['text'])));

        $body .= skinvoodoo(
            "controlpanel_write", "",
            array(
                "posturl" => INDEX_URL . "?controlpanel:write",
                "quicktags" => INDEX_URL . "?quicktags.js",
                "text" => $_POST['text'],
                "title" => $_POST['title'],
            )
        );

    } else {

        // tid blogid title timestamp text extra
        $data = array
        (
            "blogid" => BLOGID,
            "title" => $_POST['title'], // ToDo: check for uniqueness
            "timestamp" => time(),
            "text" => $_POST['text'],
        );

        //$db->insert("topics", $data);

        //$tid = $db->fetch_var( $db->issue_query("SELECT tid FROM topics WHERE timestamp = " . $data['timestamp']) );

        $body = skinvoodoo("controlpanel_write", "success_redirect", array("topic_url" => INDEX_URL . "?tid=$tid"));

    }
}

?>