<?php

function statistics()
{
    global $db;

    $q = $db->issue_query("SELECT timestamp FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp ASC LIMIT 1");
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

    $time_elapsed = wordwrap("First post was $time_elapsed ago.", 40, "<br />");

    $q = $db->issue_query("SELECT tid FROM topics WHERE blogid = '" . BLOGID . "'");
    $num_topics = $db->num_rows[$q];

    $q = $db->issue_query("SELECT pid FROM replies WHERE blogid = '" . BLOGID . "'");
    $num_posts = $db->num_rows[$q];

    $q = $db->issue_query("SELECT DISTINCT name FROM replies WHERE blogid = '" . BLOGID . "'");
    $num_distinct_replies = $db->num_rows[$q];

    $q = $db->issue_query("SELECT pid FROM replies WHERE tripcode != '' AND tripcode != 'autoinserted comment' AND blogid = '" . BLOGID . "'");
    $num_tripcodes = $db->num_rows[$q];

    $q = $db->issue_query("SELECT timestamp FROM shoutbox WHERE blogid = '" . BLOGID . "'");
    $num_shouts = $db->num_rows[$q];

    return "Blogging since " . date("F jS, Y", $firstblog) . ".<br />\n"
        . "$time_elapsed<br />\n"
        . "$num_topics posts, $num_posts comments in total.<br />\n"
        . "Average 1 blog post every " . number_format(($time_years * 365 + $time_days) / $num_topics, 2) . " days.<br />\n"
        . "Average " . number_format($num_posts / $num_topics, 2) . " comments per post.<br />\n"
        . "Comments under $num_distinct_replies distinct names.<br />\n"
        . round($num_tripcodes / $num_posts * 100) . "% of comments use tripcodes.<br />\n"
        . "$num_shouts shoutbox entries.<br />\n";
}

?>