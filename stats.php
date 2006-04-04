<?php

/*
** Stats Functions
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

function statistics()
{
    global $db;

    //$q = $db->issue_query("SELECT timestamp FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp ASC LIMIT 1");
    $q = $db->issue_query("SELECT joindate FROM blogs WHERE blogid = '" . BLOGID . "'");
    $joindate = $db->fetch_var($q);

    $q = $db->issue_query("SELECT timestamp FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp ASC LIMIT 1");
    if($db->num_rows[$q] == 0)
        $firstblog = 0;
    else
        $firstblog = $db->fetch_var($q);

    $time_secs = time() - $firstblog;
    $time_minutes = floor($time_secs / 60);
    $time_secs %= 60;
    $time_hours = floor($time_minutes / 60);
    $time_minutes %= 60;
    $time_days = floor($time_hours / 24);
    $time_hours %= 24;
    $time_years = floor($time_days / 365);
    $time_days %= 365;

    $time_elapsed = "";
    if($time_years)
    {
        if($time_years > 1)
            $time_elapsed .= "$time_years years, ";
        else
            $time_elapsed .= "1 year, ";
    }
    if($time_days)
    {
        if($time_days > 1)
            $time_elapsed .= "$time_days days, ";
        else
            $time_elapsed .= "1 day, ";
    }
    if($time_hours)
    {
        if($time_hours > 1)
            $time_elapsed .= "$time_hours hours, ";
        else
            $time_elapsed .= "1 hour, ";
    }
    if($time_minutes)
    {
        if($time_minutes > 1)
            $time_elapsed .= "$time_minutes minutes, ";
        else
            $time_elapsed .= "1 minute, ";
    }
    if($time_secs)
    {
        if($time_secs > 1)
            $time_elapsed .= "$time_secs seconds, ";
        else
            $time_elapsed .= "1 second, ";
    }

    $time_elapsed = substr($time_elapsed, 0, -2);

    $pos_last_comma = strrpos($time_elapsed, ",");
    $time_elapsed = substr($time_elapsed, 0, $pos_last_comma) . " and" . substr($time_elapsed, $pos_last_comma + 1);


    if($firstblog == 0)
        $time_elapsed = "";
    else
        $time_elapsed = wordwrap("First post was $time_elapsed ago.", 40, "<br />") . "<br />\n";

    $q = $db->issue_query("SELECT tid FROM topics WHERE blogid = '" . BLOGID . "'");
    $num_topics = $db->num_rows[$q];

    $q = $db->issue_query("SELECT pid FROM replies WHERE blogid = '" . BLOGID . "'");
    $num_posts = $db->num_rows[$q];

    $q = $db->issue_query("SELECT DISTINCT name FROM replies WHERE blogid = '" . BLOGID . "'");
    $num_distinct_replies = $db->num_rows[$q];

    /* For comments that are inserted by a script, the tripcode is set to
    ** 'autoinserted comment', an impossible tripcode since the tripcode cannot
    ** contain spaces. We won't count them in the stats here. */
    $q = $db->issue_query("SELECT pid FROM replies WHERE tripcode != '' AND tripcode != 'autoinserted comment' AND blogid = '" . BLOGID . "'");
    $num_tripcodes = $db->num_rows[$q];

    $q = $db->issue_query("SELECT timestamp FROM shoutbox WHERE blogid = '" . BLOGID . "'");
    $num_shouts = $db->num_rows[$q];

    return "Blogging since " . date("F jS, Y", $joindate) . ".<br />\n"
        . "$time_elapsed"
        . "$num_topics posts, $num_posts comments in total.<br />\n"
        . "Average 1 blog post every " . @number_format(($time_years * 365 + $time_days) / $num_topics, 2) . " days.<br />\n"
        . "Average " . @number_format($num_posts / $num_topics, 2) . " comments per post.<br />\n"
        . "Comments under $num_distinct_replies distinct names.<br />\n"
        . @round($num_tripcodes / $num_posts * 100) . "% of comments use tripcodes.<br />\n"
        . "$num_shouts shoutbox entries.<br />\n";
}

?>