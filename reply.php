<?php

/*
** Reply Functions
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

function show_reply_form($tid, $preview_data = "", $text = "", $text_filter_msg = "")
{
    global $db, $ALLOWED_TAGS, $BLOGINFO, $TITLE;

    $q = $db->issue_query("SELECT * FROM topics WHERE tid = '$tid' AND blogid = '" . BLOGID . "'");

    if($db->num_rows[$q] == 0)
    {
        echo "no such topic";
        return;
    }

    $topic = $db->fetch_row($q, 0, L1SQL_ASSOC);

    $TITLE = $BLOGINFO['title'] . " :: Commenting on '" . $topic['title'] . "'";

    $out = display_topic($topic);
    //display_main_post($topic, TRUE);

    $q = $db->issue_query("SELECT * FROM replies WHERE tid = '$tid' AND blogid = '" . BLOGID . "' ORDER BY timestamp DESC LIMIT 5");

    if($db->num_rows[$q] > 0)
    {
        $out .= skinvoodoo("topic", "last_comments", array("num" => $db->num_rows[$q]));

        $data = $db->fetch_all($q);
        $data = array_reverse($data);

        foreach($data as $row)
            $out .= display_post($row);
    }

    if($preview_data !== "")
    {
        $preview_data['pid'] = "0";
        $out .= display_post($preview_data, TRUE);
    } else {
        if(is_numeric($_GET['ref']))
            $text = "@{$_GET['ref']}: ";
        else
            $text = "";
    }

    $tags = "";
    foreach($ALLOWED_TAGS as $name => $attribs)
    {
        $tags .= "&lt;$name";
        if(count($attribs))
        {
            foreach($attribs as $attrib)
                $tags .= "&nbsp;$attrib=\"\"";
        }
        $tags .= "&gt; ";
    }

    if(IMAGEVERIFY)
    {
        // image verification id
        $ivid = genivid();
    } else {
        $ivid = NULL;
    }

    return $out . skinvoodoo("replyform", "", array(
        "form_url" => INDEX_URL . "?do_reply=$tid#previewcomment",
        "name" => $_SESSION['postername'],
        "tripcode" => $_SESSION['tripcode'],
        "tripcode_help_link" => INDEX_URL . "?tid=1#tripcodes", //oo
        "link" => ($_SESSION['posterlink'] ? $_SESSION['posterlink'] : "http://"),
        "text" => htmlspecialchars($text),
        "allowed_tags" => $tags,
        "text_filter_msg" => $text_filter_msg === "" ? "" : $text_filter_msg,
        "imageverify" => HTTP.BASE_DOMAIN.INSTALLED_PATH."imageverify.php?id=$ivid",
        "ivid" => $ivid,
        "terms" => (file_exists(FSPATH."/TERMS") ? HTTP.BASE_DOMAIN.INSTALLED_PATH."TERMS" : NULL),
    ));

} // end of show_reply_form()

function process_reply_form($tid)
{
    global $db;

    $name = strip_tags($_POST['name']);
    $tripcode = $_POST['tripcode'];
    $link = htmlentities(strip_tags($_POST['link']));
    $text_filter = in_text_filter($_POST['text']);
    $timestamp = time();

    if(is_array($text_filter))
    {
        $text = $text_filter[0];
        $text_filter_msg = $text_filter[1];
    } else {
        $text = $text_filter;
        $text_filter_msg = "";
    }

    if($link == "http://")
        $link = null;
    elseif(strpos($link, "http://") !== 0)
        $link = "http://" . $link;

    $_SESSION['postername'] = $name;
    $_SESSION['posterlink'] = $link;
    $_SESSION['tripcode']   = $tripcode;

    if(empty($name))
        $name = ANONYMOUS_NAME;

    $ip = $_SERVER['REMOTE_ADDR'];

    // make sure we get the client's IP if we're using mod_rewrite to proxy the request
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ip .= "::".$_SERVER['HTTP_X_FORWARDED_FOR'];

    $data = array
    (
        "blogid" => BLOGID,
        "tid" => $tid,
        "name" => $name,
        "tripcode" => tripcode($tripcode),
        "timestamp" => $timestamp,
        "link" => $link,
        "text" => $text,
        "extra" => "ip: $ip\nuseragent: " . $_SERVER['HTTP_USER_AGENT'] . "\n",
    );

    if($_POST['preview'] == "preview" || $text_filter_msg)
    {
        return show_reply_form($data['tid'], $data, $_POST['text'], $text_filter_msg);
    }

    if(IMAGEVERIFY && md5(strtolower($_POST['imageverify'])) != $_POST['ivid'])
    {
        return show_reply_form($data['tid'], $data, $_POST['text'], "You didn't correctly type the letters in the image.<br />Try again.");
    }

    if(empty($text))
    {
        return show_reply_form($data['tid'], $data, $_POST['text'], "Your comment cannot be empty.<br />Please go back and fix this.");
    }

    $db->insert("replies", $data);
    $q = $db->issue_query("SELECT pid FROM replies WHERE timestamp = " . $db->prepare_value($timestamp) . " AND blogid = '" . BLOGID . "'");
    $pid = $db->fetch_var($q);

    $topic_title = $db->fetch_var($db->issue_query("SELECT title FROM topics WHERE tid = " . $db->prepare_value($data['tid']) . " AND blogid = '" . BLOGID . "'"));

    if(EMAIL)
    {
        $message = $data['name'] . " has posted a comment on \"$topic_title\":\n" . INDEX_URL . "?tid={$data['tid']}&pid=$pid";
        mail( ADMIN_EMAIL, "Blog Comment", $message, "From: ".BASE_DOMAIN." <nobody@".BASE_DOMAIN.">");
    }

    return skinvoodoo("error", "notify", array("message" => "Your comment has been successfully recorded.<br />"
        . "<a href=\"" . INDEX_URL . "?tid=$tid#pid$pid\">Click here</a> to go to your comment."));

} // end of process_reply_form()

?>
