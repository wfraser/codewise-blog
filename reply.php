<?php

function show_reply_form($tid, $preview_data = "", $text = "", $text_filter_msg = "")
{
    global $db, $ALLOWED_TAGS;

    $q = $db->issue_query("SELECT * FROM topics WHERE tid = '$tid' AND blogid = '" . BLOGID . "'");

    if($db->num_rows[$q] == 0)
    {
        echo "no such topic";
        return;
    }

    $topic = $db->fetch_row($q, 0, L1SQL_ASSOC);

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

    return $out . skinvoodoo("replyform", "", array(
        "form_url" => INDEX_URL . "?do_reply=$tid#previewcomment",
        "name" => $_SESSION['postername'],
        "tripcode" => $_SESSION['tripcode'],
        "tripcode_help_link" => INDEX_URL . "?tid=1#tripcodes", //oo
        "link" => ($_SESSION['posterlink'] ? $_SESSION['posterlink'] : "http://"),
        "text" => htmlspecialchars($text),
        "allowed_tags" => $tags,
        "text_filter_msg" => $text_filter_msg === "" ? "" : $text_filter_msg,
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

    $_SESSION['postername'] = $name;
    $_SESSION['posterlink'] = $link;
    $_SESSION['tripcode']   = $tripcode;

    if(empty($name))
        $name = ANONYMOUS_NAME;

    $data = array
    (
        "blogid" => BLOGID,
        "tid" => $tid,
        "name" => $name,
        "tripcode" => tripcode($tripcode),
        "timestamp" => $timestamp,
        "link" => $link,
        "text" => $text,
        "extra" => "ip: " . $_SERVER['REMOTE_ADDR'] . "\nuseragent: " . $_SERVER['HTTP_USER_AGENT'] . "\n",
    );

    if(empty($text))
        return skinvoodoo("error", "error", array("message" => "Your comment cannot be empty.<br />Please go back and fix this."));

    if($_POST['preview'] == "preview" || $text_filter_msg)
    {
            return show_reply_form($data['tid'], $data, $_POST['text'], $text_filter_msg);
    }

    $db->insert("replies", $data);
    $q = $db->issue_query("SELECT pid FROM replies WHERE timestamp = " . $db->prepare_value($timestamp) . " AND blogid = '" . BLOGID . "'");
    $pid = $db->fetch_var($q);

    $topic_title = $db->fetch_var($db->issue_query("SELECT title FROM topics WHERE tid = " . $db->prepare_value($data['tid']) . " AND blogid = '" . BLOGID . "'"));

    if(EMAIL)
    {
        $message = $data['name'] . " has posted a comment on \"$topic_title\":\n" . INDEX_URL . "?tid={$data['tid']}&pid=$pid";
        mail( ADMIN_EMAIL, "New CodewiseBlog Comment", $message, "From: blog.codewise.org <nobody@codewise.org>");
    }

    return skinvoodoo("error", "notify", array("message" => "Your comment has been successfully recorded.<br />"
        . "<a href=\"" . INDEX_URL . "?tid=$tid#pid$pid\">Click here</a> to go to your comment."));

} // end of process_reply_form()

?>