<?php

/*
** Posting Calendar Functions
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** (c) 2005 Codewise.org
*/

function postcalendar()
{
    global $db;

/*
    $q = $db->issue_query("SELECT timestamp FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp ASC");
    $data = $db->fetch_column($q);

    if(count($data) == 0)
        return(skinvoodoo("postcalendar", "", array("contents" => "<i>Nothing to show.</i>")));

    $data = array_reverse($data);

    $months = array();
    foreach($data as $timestamp)
    {
        $time = date("mY",$timestamp);
        $months[$time] .= true; // ugly, I know, but simple
    }

    $latestmonth = true;
    foreach($months as $monthspec => $foo) // ignore $foo
    {
        $month = substr($monthspec,0,2);
        $year  = substr($monthspec,2,4);

        switch($month)
        {
            case 1: $name = "January";   break;
            case 2: $name = "February";  break;
            case 3: $name = "March";     break;
            case 4: $name = "April";     break;
            case 5: $name = "May";       break;
            case 6: $name = "June";      break;
            case 7: $name = "July";      break;
            case 8: $name = "August";    break;
            case 9: $name = "September"; break;
            case 10: $name = "October";  break;
            case 11: $name = "November"; break;
            case 12: $name = "December"; break;
        }
*/

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

        $out .= skinvoodoo("postcalendar", "monthrow", array("url" => INDEX_URL . "?year=$year&amp;month=$month", "monthname" => $name, "year" => $year));

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

            while(count($rows) > TOPICS_PER_PAGE)
                array_pop($rows);

            $currentmonth = "";

            foreach($rows as $row)
            {
                $tid = $row['tid'];
                $title = $row['title'];

                if($row['extra'] == "HIDE")
                    continue;

                if(strlen($title) > 20)
                    $title = substr($title, 0, 20) . "...";

                $currentmonth .= skinvoodoo("postcalendar", "topiclink", array("url" => INDEX_URL . "?tid=$tid", "title" => $title));
            }

            if($db->num_rows[$q] > TOPICS_PER_PAGE)
                $currentmonth .= skinvoodoo("postcalendar", "nextpage", array("url" => INDEX_URL . "?year=$year&amp;month=$month&amp;page=2"));

            $out .= skinvoodoo("postcalendar", "latestmonth", array("contents" => $currentmonth));
        }

        $latestmonth = false;

    } // foreach($months as $monthspec => $foo)

    return(skinvoodoo("postcalendar", "", array("contents" => $out)));
}

?>
