<?php

/*
** Topic Functions
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

function display_topic($topic, $topic_page = FALSE, $bare_minimum = FALSE)
{
    global $db;

    $tid = $topic['tid'];
    $title = $topic['title'];
    $date = date(DATE_FORMAT, $topic['timestamp']);
    $text = $topic['text'];

    $parts = preg_split("/(<noautobr>(.*)<\/noautobr>)/Us", $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $text = "";
    for($i = 0; $i < count($parts); $i++)
    {
        if(strpos($parts[$i], "<noautobr>") === 0)
            $text .= $parts[++$i];
        else
            $text .= textprocess($parts[$i]);
    }

    $q = $db->issue_query("SELECT pid FROM replies WHERE tid = '$tid'");
    $num_replies = $db->num_rows[$q];

    $out = "";

    if(!$bare_minimum)
        $out .= skinvoodoo("topic", "topicheader", array(
            "date" => $date,
            "title" => $title,
            "text" => $text,
            "url_showcomments" => INDEX_URL . "?tid=$tid#comments",
            "url_addcomment" => INDEX_URL . "?reply=$tid#commentform",
            "num_comments" => ($topic_page ? NULL : $num_replies)
        ));

    $out .= skinvoodoo("topic", "", array(
        "date" => $date,
        "title" => $title,
        "text" => $text,
        "url_showcomments" => INDEX_URL . "?tid=$tid#comments",
        "url_addcomment" => INDEX_URL . "?reply=$tid#commentform",
        "num_comments" => ($topic_page ? NULL : $num_replies)
    ));

    if(!$bare_minimum)
        $out .= skinvoodoo("topic", "topicfooter", array(
            "date" => $date,
            "title" => $title,
            "text" => $text,
            "url_showcomments" => INDEX_URL . "?tid=$tid#comments",
            "url_addcomment" => INDEX_URL . "?reply=$tid#commentform",
            "num_comments" => ($topic_page ? NULL : $num_replies)
        ));

    return $out;

} // end of display_topic()

function display_post($post, $highlight = FALSE)
{
    $pid = $post['pid'];
    $tid = $post['tid'];
    $name = $post['name'];
    $tripcode = $post['tripcode'];
    $timestamp = $post['timestamp'];
    $link = $post['link'];
    $text = $post['text'];

    $text = preg_replace("/@([0-9]+):/", "<span class=\"postref\"><a href=\"?tid=$tid&amp;pid=$1#pid$1\">@$1:</a></span>", $text);

    if(!empty($link))
        $name = "<a href=\"$link\" target=\"_blank\">$name</a>";

    if($highlight)
        $special_anchor = "previewcomment";

    return skinvoodoo("post", "", array(
        "tid" => $tid,
        "pid" => $pid,
        "highlight" => $highlight,
        "name" => $name,
        "tripcode" => $tripcode,
        "date" => date(DATE_FORMAT, $timestamp),
        "url_post" => INDEX_URL . "?tid=$tid&amp;pid=$pid#pid$pid",
        "url_reply" => INDEX_URL . "?reply=$tid&amp;ref=$pid#commentform",
        "text" => textprocess($text),
        "special_anchor" => $special_anchor,
    ));

} // end of display_post()

?>