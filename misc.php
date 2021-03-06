<?php

/*
** Miscellaneous Functions
** for CodewiseBlog Multi-User
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2004-2009 Codewise.org
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
    unset($_SESSION['controlpanel']);
    unset($_SESSION['blogid']);

    $GLOBALS['NOTIFY'] = "Your information has been cleared.<br />";
    return main_page(1);
}

function textprocess($text, $doautobr = TRUE)
{
    $text = utf8_decode($text);

    preg_match_all("/<php>(.*)<\\/php>/Us", $text, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $text = str_replace($match[0], trim(highlight_string(trim($match[1]), TRUE)), $text);
    }

    if ($doautobr)
        $text = str_replace("\n", "<br />", str_replace("\r", "", $text));

    preg_match_all("/[^\\sa-zA-Z0-9,.\\/<>?;:'\"[\\]{}\\-=_+\\\\|`~!@#$%^&*()]/", $text, $badchars);
    foreach ($badchars[0] as $badchar) {
	$text = str_replace($badchar, htmlentities($badchar), $text);
    }

    /*
    for ($i = 0; $i < strlen($text); $i++) {
        if ($text[$i] > '\x70') {
            $text[$i] = htmlentities($text[$i]);
        }
    }
    */

    return $text;
} // end of textprocess

function in_text_filter($text, $text_filter_msg = "")
{
    global $ALLOWED_TAGS;

    $new_text = $text;
    $num_starts = array();
    $num_ends = array();

    // remove illegal opening tags and, if possible, illegal open-close tag pairs as well
    preg_match_all("/(<([A-Za-z][A-Za-z0-9-_:]*)([^>]*)>)/s", $new_text, $matches, PREG_SET_ORDER /*| PREG_OFFSET_CAPTURE*/);
    foreach($matches as $match)
    {
        $full_tag = $match[1];
        $tag_attribs = $match[3];
        $tag_name = $match[2];

        // get all the attributes and remove all but the allowed ones
        preg_match_all("/([A-Za-z][A-Za-z0-9-_:]*)=((['\"])(.*)\\2|\S+?)/Us", $tag_attribs, $attrib_matches, PREG_SET_ORDER);

        foreach($attrib_matches as $attrib_match)
        {
            $full_attrib = $attrib_match[0];
            $attrib_name = $attrib_match[1];
            if(preg_match("/^['\"]$/", $attrib_match[2]))
                $attrib_cont = $attrib_match[3];
            else
                $attrib_cont = $attrib_match[2];

            if(!@in_array(strtolower($attrib_name), $ALLOWED_TAGS[strtolower($tag_name)]))
            {
                $new_text = str_replace($full_tag, str_replace($full_attrib, "", $full_tag), $new_text);
                $text_filter_msg .= "Removed illegal <code>&lt;$tag_name&gt;</code> attribute <code>$attrib_name</code><br />";
            }
        }

        if(!in_array(strtolower($tag_name), array_keys($ALLOWED_TAGS)))
        {
            $new_text = str_replace($full_tag, "", $new_text);
            $text_filter_msg .= "Removed illegal <code>&lt;$tag_name&gt;</code><br />";
            if(substr($full_tag, strlen($full_tag) - 2) != "/>")
            {
                $temp = preg_replace("/<\/" . $tag_name . "[^>]*>/i", "", $new_text, 1);
                if($temp != $new_text)
                    $text_filter_msg .= "Removed accompanying illegal closing <code>&lt;$tag_name&gt;</code><br />";
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

        if(!in_array(strtolower($tag_name), array_keys($ALLOWED_TAGS)))
        {
            $new_text = str_replace($full_tag, "", $new_text);
            $text_filter_msg .= "Removed illegal closing <code>&lt;$tag_name&gt;</code><br />";
            continue;
        }

        /*
        ** check to make sure that there are only as many closing tags as there
        ** are opening tags for each tag type
        */
        $num_ends[strtolower($tag_name)]++;
        if($num_ends[strtolower($tag_name)] > $num_starts[strtolower($tag_name)])
        {
            $new_text = str_replace($full_tag, "", $new_text);
            $num_ends[strtolower($tag_name)]--;
            $text_filter_msg .= "Removed extra closing <code>&lt;$tag_name&gt;</code><br />";
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
            $text_filter_msg .= "Removed extra opening <code>&lt;$tag_name&gt;</code><br />";
        }
        // another idea: simply append a closing tag
        //    $new_text .= "</$tag_name>";
    }

    $new_text = preg_replace("/&(?![a-zA-Z]+;)/", "&amp;", $new_text);

    if($new_text == $text)
    {
        if($text_filter_msg === "")
            return($text);
        else
            return(array($text, $text_filter_msg));
    } else {
        return(in_text_filter($new_text, $text_filter_msg));
    }
} // end of in_text_filter

function tripcode($input)
{
    if($input === null || $input === "" || $input === false)
        return(null);

    $md5 = md5($input.TRIPCODE_SALT);

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
        $okay = mail(SQL_ADMIN_EMAIL, "CodewiseBlog Error Notice", $message, "From: ".BASE_DOMAIN." <nobody@".BASE_DOMAIN.">");

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
        <link rel="stylesheet" href="skin_blueEye/stylesheet.css" />
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

function ordinal($n)
{
    if(substr($n, -2, 1) == "1")    // teens always end in 'th'
        return "${n}th";
    if(substr($n, -1, 1) == "1")    // ...1st
        return "{$n}st";
    elseif(substr($n, -1, 1) == "2")    // ...2nd
        return "{$n}nd";
    elseif(substr($n, -1, 1) == "3")    // ...3rd
        return "{$n}rd";
    else                            // ...th
        return "{$n}th";
}

/*
** This function will clip text as close to (without going over) the specified
** character limit as possible without messing up html or breaking words. IT
** MAY, HOWEVER, LEAVE TAGS OPEN, so be careful.
*/
function text_clip($text, $limit = 500, $append = " ...")
{
    if(strlen($text) <= $limit)
    {
        return($text);
    } else {
        $content = "";
        $tags = array(); // stack for holding tags that are open at time of clip
        $parts = preg_split("/(<[^>]+>)/",$text,-1,PREG_SPLIT_DELIM_CAPTURE);
        foreach($parts as $part)
        {
            if(strlen($content) + strlen($part) <= $limit - strlen($append))
            {
                if(substr($part,0,2) == "</") // html end tag
                    array_pop($tags); // pop the tag
                else if (substr($part,0,1) == "<") // html start tag
                    array_push($tags, substr($part,1,-1)); // add tag to the stack
                $content .= $part;
                continue;
            } else {
                if(substr($part,0,1) == "<") // html tag part
                {
                    $content .= $append;
                    for($i = count($tags) - 1; $i >= 0; $i--)
                        $content .= "</" . $tags[$i] . ">";
                    break;
                } else { // text part
                    $words = preg_split("/( )/",$part,-1,PREG_SPLIT_DELIM_CAPTURE);
                    foreach($words as $word)
                    {
                        if(strlen($content) + strlen($word) <= $limit - strlen($append))
                        {
                            $content .= $word;
                            continue;
                        } else {
                            $content .= $append;
                            break 2;
                        }
                    }
                }
            }
        }
    }

    return($content);
}

function uuidgen()
{
    return md5($_SERVER['SERVER_ADDR'] . getmypid() . uniqid(mt_rand(), true));
}

function array_psearch($array, $preg)
{
    foreach ($array as $key => $value) {
        if (preg_match($preg, $value)) {
            return array($key, $value);
        }
    }
    return FALSE;
}

function vdump($var)
{
    echo "<pre>";
    ob_start();
    var_dump($var);
    echo htmlspecialchars(ob_get_clean());
    echo "</pre>";
}

?>
