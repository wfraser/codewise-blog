<?php

/*
** Topic Functions
** for CodewiseBlog Multi-User
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2005-2008 Codewise.org
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

function display_topic($topic, $topic_page = FALSE)
{
    global $db;

    $tid = $topic['tid'];
    $title = $topic['title'];
    $urltitle = string_to_url_goodness($title);
    $date = date(DATE_FORMAT, $topic['timestamp']);
    $text = output_topic_text($topic['text']);

    $q = $db->issue_query("SELECT pid FROM replies WHERE tid = '$tid' AND blogid = '".BLOGID."'");
    $num_replies = $db->num_rows[$q];

    $out = "";

    $args = array(
        "tid" => $tid,
        "date" => $date,
        "title" => $title,
        "urltitle" => $urltitle,
        "text" => $text,
        "url2_showtopic" => INDEX_URL . "article/$urltitle/",
        "url2_showcomments" => INDEX_URL . "article/$urltitle/#comments",
        "url2_addcomment" => INDEX_URL . "article/$urltitle/reply#commentform",
        "url_showtopic" => INDEX_URL . "?tid=$tid",
        "url_showcomments" => INDEX_URL . "?tid=$tid#comments",
        "url_addcomment" => INDEX_URL . "?reply=$tid#commentform",
        "url_edittopic" => INDEX_URL . "?controlpanel:edit&amp;tid=$tid",
        "num_comments" => ($topic_page ? NULL : $num_replies)
    );

    $out .= skinvoodoo("topic", "topicheader", $args);

    $out .= skinvoodoo("topic", "", $args);

    $out .= skinvoodoo("topic", "topicfooter", $args);

    return $out;

} // end of display_topic()

function display_post($post, $highlight = FALSE, $topic_urltitle = "")
{
    $pid = $post['pid'];
    $tid = $post['tid'];
    $name = $post['name'];
    $tripcode = $post['tripcode'];
    $timestamp = $post['timestamp'];
    $link = $post['link'];
    $text = $post['text'];

    $text = preg_replace("/@([0-9]+):/", "<span class=\"postref\"><a href=\"?pid=$1#pid$1\">@$1:</a></span>", $text);

    if(!empty($link))
        $name = "<a href=\"$link\" target=\"_blank\">$name</a>";

    if($highlight)
        $special_anchor = "previewcomment";

    if ($topic_urltitle === "") {
        $url2_post = INDEX_URL . "?tid=$tid&amp;pid=$pid#pid$pid";
        $url2_reply = INDEX_URL . "?reply=$tid&amp;ref=$pid#commentform";
    } else {
        $url2_post = INDEX_URL . "article/$topic_urltitle/$pid#pid$pid";
        $url2_reply = INDEX_URL . "article/$topic_urltitle/reply?ref=$pid#commentform";
    }

    return skinvoodoo("post", "", array(
        "tid" => $tid,
        "pid" => $pid,
        "highlight" => $highlight,
        "name" => $name,
        "tripcode" => $tripcode,
        "date" => date(DATE_FORMAT, $timestamp),
        "url2_post" => $url2_post, 
        "url2_reply" => $url2_reply,
        "url_post" => INDEX_URL . "?tid=$tid&amp;pid=$pid#pid$pid",
        "url_reply" => INDEX_URL . "?reply=$tid&amp;ref=$pid#commentform",
        "url_delreply" => INDEX_URL . "?controlpanel:manage&amp;del=reply:$pid",
        "text" => textprocess($text),
        "special_anchor" => $special_anchor,
    ));

} // end of display_post()

function output_topic_text($text)
{
    $text = str_replace("\r", "", $text);

    $text = preg_replace("/&(?![a-zA-Z]+;)/", "&amp;", $text);

    $tags = "pre|ol|ul";
    $text = preg_replace("#\n\n<($tags)#", "\n<$1", $text);
    $text = preg_replace("#</($tags)>\n\n#", "</$1>", $text);
    $text = str_replace("</noautobr>\n\n", "</noautobr>\n", $text);

    $parts = preg_split("/(<(noautobr|pre)>(.*)<\/\\2>)/Us", $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $text = "";
    for($i = 0; $i < count($parts); $i++)
    {
        if (strpos($parts[$i], "<noautobr>") === 0) {
            $i += 2;
            $text .= textprocess($parts[$i], FALSE);
        }
        else if(strpos($parts[$i], "<pre>") === 0) {
            $text .= textprocess($parts[$i], FALSE);
            $i += 2;
        }
        else {
            $text .= textprocess($parts[$i]);
        }
    }

    return $text;
}

?>
