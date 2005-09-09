<?php

function main_page($page)
{
    global $db;

    $out = "";

    $q = $db->issue_query("SELECT * FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp DESC");

    if($db->num_rows[$q] == 0 || $db->num_rows[$q] < (TOPICS_PER_PAGE * ($page - 1)))
    {
        return "Nothing to show.";
    }

    // skip over topics to get to the appropriate page
    if($page > 1)
        $db->fetch_row($q, (TOPICS_PER_PAGE * ($page - 1)) - 1, L1SQL_NUM);

    for($i = 0; $i < TOPICS_PER_PAGE || isset($_GET['all_one_page']); $i++)
    {
        $topic = $db->fetch_row($q, 0, L1SQL_ASSOC);
        if($topic === false)
            break;

        if($topic['extra'] == "HIDE")
            continue;

        $out .= display_topic($topic);
    }

    for($i = 1; $i <= ceil($db->num_rows[$q] / TOPICS_PER_PAGE); $i++)
    {
        if($i == $page && !isset($_GET['all_one_page']))
            $out .= skinvoodoo("pagelink", "currpage", array("pageno" => $i));
        elseif($i == 1)
            $out .= skinvoodoo("pagelink", "pagelink", array("url" => INDEX_URL, "pageno" => 1));
        else
            $out .= skinvoodoo("pagelink", "pagelink", array("url" => INDEX_URL . "?page=$i", "pageno" => $i));
    }
    if(isset($_GET['all_one_page']))
        $out .= skinvoodoo("pagelink", "allonepage_current");
    else
        $out .= skinvoodoo("pagelink", "allonepage_link", array("url" => INDEX_URL . "?all_one_page"));

    return $out;

} // end of main_page()

function show_month($month, $year, $page)
{
    global $db;

    $out = "";

    $time1 = mktime(0,0,0, $month, 1, $year);
    $day = date("t", $time1);
    $time2 = mktime(23,59,59, $month, $day, $year);

    $q = $db->issue_query("SELECT * FROM topics WHERE timestamp >= '$time1' AND timestamp <= '$time2' AND blogid = '" . BLOGID . "' ORDER BY timestamp DESC");

    if($db->num_rows[$q] == 0 || $db->num_rows[$q] < (TOPICS_PER_PAGE * ($page - 1)))
    {
        return "Nothing to show";
    }

    // skip over topics to get to the appropriate page
    if($page > 1)
        $db->fetch_row($q, (TOPICS_PER_PAGE * ($page - 1)) - 1, L1SQL_NUM);

    for($i = 0; $i < TOPICS_PER_PAGE || isset($_GET['all_one_page']); $i++)
    {
        $topic = $db->fetch_row($q, 0, L1SQL_ASSOC);
        if($topic === false)
            break;

        $out .= display_topic($topic);
    }

    for($i = 1; $i <= ceil($db->num_rows[$q] / TOPICS_PER_PAGE); $i++)
    {
        if($i == $page && !isset($_GET['all_one_page']))
            $out .= skinvoodoo("pagelink", "currpage", array("pageno" => $i));
        else
            $out .= skinvoodoo("pagelink", "pagelink", array("url" => INDEX_URL . "?month=$month&amp;year=$year&amp;page=$i", "pageno" => $i));
    }
    if(isset($_GET['all_one_page']))
        $out .= skinvoodoo("pagelink", "allonepage_current");
    else
        $out .= skinvoodoo("pagelink", "allonepage_link", array("url" => INDEX_URL . "?month=$month&amp;year=$year&amp;all_one_page"));

    return $out;

} // end of show_month()

function show_topic($tid, $page)
{
    global $db;

    $out = "";

    $q = $db->issue_query("SELECT * FROM topics WHERE tid = '$tid' AND blogid = '" . BLOGID . "'");

    if($db->num_rows[$q] == 0)
    {
        return "no such topic";
    }

    $topic = $db->fetch_row($q, 0, L1SQL_ASSOC);

    $out .= display_topic($topic, TRUE);
    $out .= skinvoodoo("topic", "start_comments");

    $q = $db->issue_query("SELECT * FROM replies WHERE tid = '$tid' AND blogid = '" . BLOGID . "' ORDER BY timestamp ASC");

    if($db->num_rows[$q] == 0 || $db->num_rows[$q] < (POSTS_PER_PAGE * ($page - 1)))
    {
        $out .= "no comments to show";
        return $out ;
    }

    // skip over posts to get to the appropriate page
    if($page > 1)
        $db->fetch_row($q, (POSTS_PER_PAGE * ($page - 1)) - 1, L1SQL_NUM);

    for($i = 0; $i < POSTS_PER_PAGE || isset($_GET['all_one_page']); $i++)
    {
        $post = $db->fetch_row($q, 0, L1SQL_ASSOC);

        if($post === FALSE)
            break;

        if($post['pid'] == $_GET['pid'])
            $out .= display_post($post, TRUE);
        else
            $out .= display_post($post);
    }

    for($i = 1; $i <= ceil($db->num_rows[$q] / POSTS_PER_PAGE); $i++)
    {
        if($i == $page && !isset($_GET['all_one_page']))
            $out .= skinvoodoo("pagelink", "currpage", array("pageno" => $i));
        else
            $out .= skinvoodoo("pagelink", "pagelink", array("url" => INDEX_URL . "?tid=$tid&amp;page=$i", "pageno" => $i));
    }
    if(isset($_GET['all_one_page']))
        $out .= skinvoodoo("pagelink", "allonepage_current");
    else
        $out .= skinvoodoo("pagelink", "allonepage_link", array("url" => INDEX_URL . "?tid=$tid&amp;all_one_page"));

    $out .= skinvoodoo("topic", "topicfooter_addcomment", array("url_addcomment" => INDEX_URL . "?reply=$tid#commentform"));

    return $out;

} // end of show_topic()

?>
