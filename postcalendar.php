<?php

/*
** Posting Calendar Functions
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

function postcalendar()
{
    global $db;

    $q = $db->issue_query("SELECT DISTINCT FROM_UNIXTIME(timestamp, '%Y') AS year, FROM_UNIXTIME(timestamp, '%m') AS month, FROM_UNIXTIME(timestamp, '%M') AS name FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp DESC");
    $data = $db->fetch_all($q, L1SQL_ASSOC);

    if(count($data) == 0)
        return(skinvoodoo("postcalendar", "", array("contents" => "<i>Nothing to show.</i>")));

    $latestmonth = true;
    foreach($data as $row)
    {
        $year = $row['year'];
        $month = $row['month'];
        $name = $row['name'];

        $out .= skinvoodoo("postcalendar", "monthrow", array(
            "url" => INDEX_URL . "?year=$year&amp;month=$month",
            "url2" => INDEX_URL . "archive/$year/$month/",
            "monthname" => $name,
            "year" => $year
        ));

        /*
        ** For the most recent month, display a list of the 5 most recent titles as
        ** links. If a month and year were set in the query, however, show the 5
        ** most recent titles in that month instead.
        **                                                            | <- if the month and year were set in the query ----> | otherwise, just the first
        */
        if( (is_numeric($_GET['month']) && is_numeric($_GET['year'])) ? ($_GET['month'] == $month && $_GET['year'] == $year) : $latestmonth)
        {
            $time1 = mktime(0,0,0, $month, 1, $year);
            $day = date("t", $time1);
            $time2 = mktime(23,59,59, $month, $day, $year);

            $q = $db->issue_query("SELECT title,tid,extra FROM topics WHERE timestamp >= " . $db->prepare_value($time1) . " AND timestamp <= " . $db->prepare_value($time2) . " AND blogid = '" . BLOGID . "' ORDER BY timestamp DESC");
            $rows = $db->fetch_all($q);

            if($_GET['page'] > 1)
            {
                for($i=0; $i< TOPICS_PER_PAGE * ($_GET['page'] - 1); $i++)
                {
                    array_shift($rows);
                }
            }

            while(count($rows) > TOPICS_PER_PAGE)
                array_pop($rows);

            $currentmonth = "";

            foreach($rows as $row)
            {
                $tid = $row['tid'];
                $title = $row['title'];
                $urltitle = string_to_url_goodness($title);

                if($row['extra'] == "HIDE")
                    continue;

                if(strlen($title) > 50)
                    $title = substr($title, 0, 47) . "...";

                $currentmonth .= skinvoodoo("postcalendar", "topiclink", array(
                    "url" => INDEX_URL . "?tid=$tid",
                    "url2" => INDEX_URL . "article/$urltitle",
                    "title" => $title
                ));
            }

            if($db->num_rows[$q] - (($_GET['page'] - 1) * TOPICS_PER_PAGE) > TOPICS_PER_PAGE)
                $currentmonth .= skinvoodoo("postcalendar", "nextpage", array(
                    "url" => INDEX_URL . "?year=$year&amp;month=$month&amp;page=2",
                    "url2" => INDEX_URL . "archive/$year/$month/?page=2",
                ));

            $out .= skinvoodoo("postcalendar", "latestmonth", array("contents" => $currentmonth));
        }

        $latestmonth = false;

    }

    return(skinvoodoo("postcalendar", "", array("contents" => $out)));
}

?>
