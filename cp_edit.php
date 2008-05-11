<?php

/*
** Control Panel :: Edit Post
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

$current = "edit";

// hack to allow direct link to editing a topic
if(isset($_GET['tid']))
{
    $_POST['tid'] = $_GET['tid'];
}

if(empty($_POST))
{
    $q = $db->issue_query("SELECT tid,title,timestamp FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY tid DESC");
    $data = $db->fetch_all($q);

    $html = "";
    foreach($data as $row)
    {
        $html .= "<option value=\"{$row['tid']}\">\"{$row['title']}\" - " . date(DATE_FORMAT, $row['timestamp']) . "</option>\n";
    }

    $body = skinvoodoo(
        "controlpanel_edit", "showselect",
        array(
            "posturl" => INDEX_URL . "?controlpanel:edit",
            "options"  => $html,
        )
    );
} elseif(isset($_POST['delete']) && !isset($_POST['REALLY_FREAKING_SURE'])) {
    $body = skinvoodoo("controlpanel_edit", "delete_ask", array("posturl" => INDEX_URL . "?controlpanel:edit", "tid" => $_POST['tid']));
} elseif(isset($_POST['delete']) && isset($_POST['REALLY_FREAKING_SURE'])) {
    $q1 = $db->issue_query("DELETE FROM topics WHERE tid = " . $db->prepare_value($_POST['tid']) . " AND blogid = '" . BLOGID . "' LIMIT 1");
    $q2 = $db->issue_query("DELETE FROM replies WHERE tid = " . $db->prepare_value($_POST['tid']) . " AND blogid = '" . BLOGID . "'");

    $body = skinvoodoo("controlpanel_edit", "delete_successful", array("num_comments" => $db->num_rows[$q2]));
} elseif(!isset($_POST['do_edit'])) {

    if(isset($_POST['preview']))
    {
        $month = $_POST['month'];
        $date = $_POST['date'];
        $year = $_POST['year'];
        $hour = $_POST['hour'];
        $minute = $_POST['minute'];
        $second = $_POST['second'];
        $ampm = $_POST['ampm'];

        $topic = array(
            "title" => $_POST['title'],
            "text" => $_POST['text'],
        );
    } else {
        $q = $db->issue_query("SELECT title,timestamp,text,extra FROM topics WHERE tid = " . $db->prepare_value($_POST['tid']) . " AND blogid = '" . BLOGID . "'");
        $topic = $db->fetch_row($q);
        $month = date("n", $topic['timestamp']);
        $date = date("j", $topic['timestamp']);
        $year = date("Y", $topic['timestamp']);
        $hour = date("g", $topic['timestamp']);
        $minute = date("i", $topic['timestamp']);
        $second = date("s", $topic['timestamp']);
        $ampm = date("a", $topic['timestamp']);
    }

    $months = array("null", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

    $month_sel = "<select name=\"month\">";
    for($i = 1; $i <= 12; $i++)
    {
        if($i == $month)
            $month_sel .= "<option value=\"$i\" selected=\"selected\">{$months[$i]}</option>";
        else
            $month_sel .= "<option value=\"$i\">{$months[$i]}</option>";
    }
    $month_sel .= "</select>";

    $date_sel = "<select name=\"date\">";
    for($i = 1; $i <= 31; $i++)
    {
        if($i == $date)
            $date_sel .= "<option value=\"$i\" selected=\"selected\">" . ordinal($i) . "</option>";
        else
            $date_sel .= "<option value=\"$i\">" . ordinal($i) . "</option>";
    }
    $date_sel .= "</select>";

    $year_sel = "<input type=\"text\" size=\"4\" value=\"" . $year . "\" name=\"year\" />";

    $hour_sel = "<select name=\"hour\">";
    for($i = 1; $i <= 12; $i++)
    {
        if($i == $hour)
            $hour_sel .= "<option value=\"$i\" selected=\"selected\">$i</option>";
        else
            $hour_sel .= "<option value=\"$i\">$i</option>";
    }
    $hour_sel .= "</select>";

    $minute_sel = "<select name=\"minute\">";
    for($i = 0; $i <= 59; $i++)
    {
        if($i == $minute)
            $minute_sel .= "<option value=\"$i\" selected=\"selected\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
        else
            $minute_sel .= "<option value=\"$i\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
    }
    $minute_sel .= "</select>";

    $second_sel = "<select name=\"second\">";
    for($i = 0; $i <= 59; $i++)
    {
        if($i == $second)
            $second_sel .= "<option value=\"$i\" selected=\"selected\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
        else
            $second_sel .= "<option value=\"$i\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
    }
    $second_sel .= "</select>";

    $ampm_sel = "<select name=\"ampm\">";
    if($ampm == "am")
    {
        $ampm_sel .= "<option value=\"am\" selected=\"selected\">AM</option>";
        $ampm_sel .= "<option value=\"pm\">PM</option>";
    } else {
        $ampm_sel .= "<option value=\"am\">AM</option>";
        $ampm_sel .= "<option value=\"pm\" selected=\"selected\">PM</option>";
    }
    $ampm_sel .= "</select>";

    $preview = skinvoodoo("controlpanel_write", "preview_topic", array("text" => output_topic_text($topic['text'])));

    $body = skinvoodoo(
        "controlpanel_edit", "editform",
        array(
            "posturl" => INDEX_URL . "?controlpanel:edit",
            "title"   => $topic['title'],
            "date"    => date(DATE_FORMAT, $topic['timestamp']),
            "text"    => $topic['text'],
            "extra"   => $topic['extra'],
            "tid"     => $_POST['tid'],
            "month_sel"  => $month_sel,
            "date_sel"   => $date_sel,
            "year_sel"   => $year_sel,
            "hour_sel"   => $hour_sel,
            "minute_sel" => $minute_sel,
            "second_sel" => $second_sel,
            "ampm_sel"   => $ampm_sel,
            "preview" => $preview,
            "quicktags" => HTTP . BASE_DOMAIN . INSTALLED_PATH . "/cwb/quicktags.js",
            "autoresize" => HTTP . BASE_DOMAIN . INSTALLED_PATH . "/cwb/autoresize.js",
            "rows" => $_POST['rows'] ? $_POST['rows'] : 25,
            "cols" => $_POST['cols'] ? $_POST['cols'] : 80,
        )
    );

} else {

    if(!checkdate($_POST['month'], $_POST['date'], $_POST['year']))
    {
        $body = skinvoodoo("error", "error", array("message" => "Invalid date specified."));
    } elseif(
        $_POST['hour'] > 12   ||
        $_POST['hour'] < 1    ||
        $_POST['minute'] > 59 ||
        $_POST['minute'] < 0  ||
        $_POST['second'] > 59 ||
        $_POST['second'] < 0  ||
        ( $_POST['ampm'] != "am" && $_POST['ampm'] != "pm")
    ) {
        $body = skinvoodoo("error", "error", array("message" => "Invalid time specified."));
    } else {

        // int mktime ( [int hour [, int minute [, int second [, int month [, int day [, int year [, int is_dst]]]]]]] )
        $time = mktime(
            // lol 12 hour time makes no sense :P
            $_POST['hour'] + ($_POST['ampm'] == "pm" ? (($_POST['hour'] == 12) ? 0 : 12) : (($_POST['hour'] == 12) ? -12 : 0)),
            $_POST['minute'],
            $_POST['second'],
            $_POST['month'],
            $_POST['date'],
            $_POST['year']
        );

        $data = array
        (
            "blogid" => BLOGID,
            "title" => $_POST['title'], // ToDo: check for uniqueness
            "timestamp" => $time,
            "text" => $_POST['text'],
        );

        $db->update("topics", $data, array("tid" => $_POST['tid']));

        $body = skinvoodoo("controlpanel_edit", "success_redirect", array("topic_url" => INDEX_URL . "?tid={$_POST['tid']}"));

    }

}

?>
