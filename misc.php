<?php

/*
** Miscellaneous Functions
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** (c) 2005 Codewise.org
*/

function fortune()
{
    ob_start(); passthru("fortune -s"); return(nl2br(ob_get_clean()));
}

function querycount()
{
    global $db;

    return count($db->queries);
}

function runtime()
{
    global $starttime;

    list($usec,$sec) = explode(" ",microtime());
    $endtime = (string) $sec + $usec;

    return number_format(($endtime - $starttime) * 1e3, 0);
}

function versionfooter()
{
    //return id_footer("CodewiseBlog version <a href=\"changelog.php\" style=\"color:#aaa;text-decoration:underline\" title=\"" . iso8601_date(filemtime("index.php")) . "\">" . CWBVERSION . "</a>");

    $sig = trim($_SERVER['SERVER_SIGNATURE']);
    $sig = str_replace("<address>", "<br /><i>", str_replace("</address>", "</i><br />", $sig));

    return skinvoodoo("main", "versionfooter", array("mtime" => iso8601_date(filemtime("cwbmulti.php")), "version" => CWBVERSION, "sig" => $sig, "hostname" => "delta-zero"));
}

/*
** Return the ISO8601 date ( 2004-02-12T15:19:21+00:00 ) of a UNIX timestamp
*/
function iso8601_date($time)
{
    $zone = wordwrap(date('O',$time),3,":",2);
    $date = date('Y-m-d\TH:i:s',$time).$zone;
    return $date;
}

function delete_session()
{
    unset($_SESSION['postername']);
    unset($_SESSION['posterlink']);
    unset($_SESSION['tripcode']);
    unset($_SESSION['beenhere']);
    unset($_SESSION['admin']);

    return <<<EOF
<div class="notify">
    Your information has been fogotten.<br />
    <a href="<?php echo INDEX_URL; ?>">Back to the front page.</a>
</div>
EOF;
}

function textprocess($text)
{
    $text = nl2br($text) . "\n";

    $text = preg_replace("/(f)uck/i", "\\1%&#", $text);
    $text = preg_replace("/(s)hit/i", "\\1%&#", $text);

    return $text;
} // end of textprocess

function in_text_filter($text)
{
    global $ALLOWED_TAGS;

    $new_text = $text;

    $num_starts = array();
    $num_ends = array();

    // remove illegal opening tags and, if possible, illegal open-close tag pairs as well
    preg_match_all("/(<([A-Za-z][A-Za-z0-9-_:]*)[^>]*>)/s", $new_text, $matches, PREG_SET_ORDER /*| PREG_OFFSET_CAPTURE*/);
    foreach($matches as $match)
    {
        $full_tag = $match[1];
        $tag_name = $match[2];

        if(!in_array(strtolower($tag_name), $ALLOWED_TAGS))
        {
            $new_text = str_replace($full_tag, "", $new_text);
            echo "<font color=\"red\">killed illegal &lt;$tag_name&gt;</font><br />";
            if(substr($full_tag, strlen($full_tag) - 2) != "/>")
            {
                $temp = preg_replace("/<\/" . $tag_name . "[^>]*>/i", "", $new_text, 1);
                if($temp != $new_text)
                    echo "<font color=\"red\">killed accompanying illegal closing &lt;$tag_name&gt;</font><br />";
                $new_text = $temp;
            }
        } else {
            $num_starts[strtolower($tag_name)]++;
        }
    }

    // remove stray closing tags
    preg_match_all("/<\/([A-Za-z][A-Za-z0-9-_:]*)[^>]*>/s", $new_text, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $full_tag = $match[0];
        $tag_name = $match[1];

        if(!in_array(strtolower($tag_name), $ALLOWED_TAGS))
        {
            $new_text = str_replace($full_tag, "", $new_text);
            echo "<font color=\"red\">killed illegal closing &lt;$tag_name&gt;</font><br />";
            continue;
        }

        /*
        ** Basically, check to make sure that there are only as many closing
        ** tags as there are opening tags for each tag type
        */
        $num_ends[strtolower($tag_name)]++;
        if($num_ends[strtolower($tag_name)] > $num_starts[strtolower($tag_name)])
        {
            $new_text = str_replace($full_tag, "", $new_text);
            $num_ends[strtolower($tag_name)]--;
            echo "<font color=\"red\">killed extra closing &lt;$tag_name&gt;</font><br />";
        }
    }

    // one last pass to catch unclosed opening tags
    preg_match_all("/<([A-Za-z][A-Za-z0-9-_:]*)[^>]*>/s", $new_text, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $full_tag = $match[0];
        $tag_name = $match[1];

        if($num_starts[strtolower($tag_name)] > $num_ends[strtolower($tag_name)])
        {
            $new_text = str_replace($full_tag, "", $new_text);
            echo "<font color=\"red\">killed extra opening &lt;$tag_name&gt;</font><br />";
        }
        // another idea: simply append a closing tag
        //    $new_text .= "</$tag_name>";
    }

    if($new_text == $text)
        return($text);
    else
        return(in_text_filter($new_text));
} // end of in_text_filter

function tripcode($input)
{
    if($input === null || $input === "" || $input === false)
        return(null);

    /*
    ** since this algorithm could be easily brute-forced, we'll obfuscate it in
    ** a non-obvious and server-dependent way.
    */
    //$input .= "%".$_ENV['HOST']."%";
    $md5 = md5($input);

    $values = array();
    $chars = preg_split('//', $md5, -1, PREG_SPLIT_NO_EMPTY);
    foreach($chars as $char)
    {
        if(preg_match("/[0-9]/", $char))
        {
            array_push($values, $char);
        } else {
            switch($char)
            {
case "a":       $val=10; break;
case "b":       $val=11; break;
case "c":       $val=12; break;
case "d":       $val=13; break;
case "e":       $val=14; break;
case "f":       $val=15; break;
            }
            array_push($values, $val);
        }
    }

    $binary = "";
    for($i = 0; $i <= 30; $i += 2)
    {
        $a = $values[$i];
        $b = $values[$i + 1];

        $c = ($a << 4) + $b;

        $binary .= chr($c);
    }

    $final = base64_encode($binary);

    return substr($final, 0, -2);
} // end of tripcode()

function mail_db_error($error)
{
    $message = "CodewiseBlog encountered the following database error at " . date(DATE_FORMAT, time()) . ":\n\n" . $error;
    $message .= "\n\n\$_SERVER:\n" . var_export($_SERVER, true) . "\n\n\$_GET:\n" . var_export($_GET, true) . "\n\n\$_POST:\n" . var_export($_POST, true);

    if(EMAIL)
        $okay = mail(SQL_ADMIN_EMAIL, "CodewiseBlog Error Notice", $message, "From: blog.codewise.org <nobody@codewise.org>");

    preg_match("/\A(.+)<br \/>\n(.+)<br \/>\n(.*<br \/>\n)*(.*)\Z/", $error, $matches);
    $errortype = $matches[1];
    $mysqlout = $matches[2];
    $fatal = ($matches[4] == "<b>FATAL</b>");
    if($fatal):
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title>Database Error</title>
        <link rel="stylesheet" href="http://www.codewise.org/blueEye.css" />
    </head>
    <body>
<?php
    endif;
?>
        <h1>Database Error</h1>
        <b><?php echo $errortype;?> - <?php echo $mysqlout; ?></b><br />
        <?php if($fatal) echo "<b>FATAL</b><br />\n"; ?>
        <br />
        <?php if($okay) echo "The admin has been notified.\n"; else echo "Unable to email the admin.\n"; ?>
<?php if($fatal): ?>
    </body>
</html>
<?php
    else:
        echo "<br /><br />";
    endif;
} // end of mail_db_error()

?>
