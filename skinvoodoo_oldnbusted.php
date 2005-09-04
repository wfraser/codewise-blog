<?php

/*
** "Voodoo" Skin Engine
** "Old & Busted" version.
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** (c) 2005 Codewise.org
*/

/*
** Skin Voodoo
**
** $skin_section is the column in the skin database to use
** $subcall if left its default empty string value means to use the main skin
**   subsection and discard the others. If set to another value, the specified
**   subsection is used and the other parts are discarded.
** $args is an associative array in the form name => value of arguments to the
**    skin. These are used in the skin in the form of <!-- #arg_foo# -->
**    macros.
**
** Returns the fully-processed skin section in all its glory.
*/
function skinvoodoo($skin_section, $subcall = "", $args = array())
{
    global $db;

    /* No DB for now. When come back bring pie.
    $sections = array("main");
    if(!in_array($skin_section, $sections))
        return "";

    $q = $db->issue_query("SELECT $skin_section FROM skin WHERE blogid = '" . BLOGID . "'");
    $skin = $db->fetch_var($q);

    // if the user's skin is NULL, use the master skin
    if($skin == null)
    {
        $q = $db->issue_query("SELECT $skin_section FROM skin WHERE blogid = '0'");
        $skin = $db->fetch_var($q);
    }
    */

    $skin = file_get_contents("/srv/www/site/blogs.codewise.org/skin_blueEye_old/$skin_section.html");

    preg_match_all("/<\\!-- :cwb_start: ([^\s]+) -->(.*)<\\!-- :cwb_end: \\1 -->/Us", $skin, $matches, PREG_SET_ORDER);

    if($subcall == "")
    {
        foreach($matches as $match)
            $skin = str_replace($match[0], "", $skin);
    } else {
        foreach($matches as $match)
            if($match[1] == $subcall)
            {
                $skin = $match[2];
                break;
            }
    }

    preg_match_all("/<\\!-- (#(cwb|arg).*) -->/Us", $skin, $matches, PREG_SET_ORDER);

    foreach($matches as $match)
    {
        $old = $match[0];
        $new = voodoo($match[1], $args);
        $skin = str_replace($old, $new, $skin);
    }

    return $skin;
}

function voodoo($code, $args = array())
{
    global $BLOGINFO;

    $borked = FALSE;

    $raw = preg_split("/(\s)/", $code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    $pieces = array();
    $untrimmed_pieces = array();
    for($i = 0; $i < count($raw); $i++)
    {
        if(trim($raw[$i]))
        {
            $pieces[] = trim($untrimmed_pieces[] = $raw[$i]);
        } else {
            $pieces[] = $raw[$i + 1];
            $untrimmed_pieces[] = $raw[$i] . $raw[$i + 1];
            $i++;
        }
    }

    $i = 0;
    $word = $pieces[$i];

    switch($word)
    {
case "#cwb_if#":
        $condition = "";
        while($pieces[$i + 1] != "?")
            $condition .= $untrimmed_pieces[++$i];

        $i++;
        $content = "";
        while($pieces[$i + 1] != ":")
            $content .= $untrimmed_pieces[++$i];

        $i++;
        $else = "";
        while(isset($pieces[$i + 1]))
            $else .= $untrimmed_pieces[++$i];

        $temp = macro_translate($condition);
        if($temp === FALSE) // no matches,
            return($code);  // bail out!

        if(eval($temp))
        {
            return(voodoo(trim($content), $args));
        } else {
            return(voodoo(trim($else), $args));
        }

case "#cwb_tag#":
        $vars = array();
        while(preg_match("/^#(cwb|arg)[^#\s]+#$/", $pieces[$i + 1]))
            $vars[] = $pieces[++$i];

        $tag = "";
        while(isset($pieces[$i + 1]))
            $tag .= $untrimmed_pieces[++$i];
        $tag = substr($tag, 1);

        for($j = 1; $j < count($vars) + 1; $j++)
            $tag = preg_replace('/\$' . $j . '(?![0-9])/', voodoo($vars[$j - 1], $args), $tag);

        return($tag);

default:
        $temp = macro_translate($word);
        if($temp === FALSE) // no matches,
            return($code);  // bail out!

        return(eval($temp));
    }
}

function macro_translate($text)
{
    global $BLOGINFO;

    $function_table = array(
        "special_fortune" => "fortune()",
        "postcalendar" => "postcalendar()",
        "welcomeback" => "welcomeback()",
        "subscribeform" => "subscribeform()",
        "adminloginform" => "adminloginform()",
        "shoutbox" => "shoutbox()",
        "statistics" => "statistics()",
        "querycount" => "querycount()",
        "runtime" => "runtime()",
        "versionfooter" => "versionfooter()",
        "copyright" => "'CodewiseBlog &copy; Bill Fraser.<br />All textual content is the property of its author.'",
    );

    preg_match_all("/#(cwb|arg)_([^#\s]+)#/Us", $text, $matches, PREG_SET_ORDER);

    foreach($matches as $match)
    {
        $name = $match[2];
        $class = $match[1];

        if($class == "cwb")
        {
            if(isset($BLOGINFO[$name]))
            {
                $text = str_replace("#cwb_$name#", "\$BLOGINFO['$name']", $text);
            } elseif(isset($function_table[$match[2]])) {
                $text = str_replace("#cwb_$name#", $function_table[$name], $text);
            } else {
                $text = str_replace("#cwb_$name#", "null", $text);
            }
        } elseif($class == "arg") {
            $text = str_replace("#arg_$name#", "\$args['$name']", $text);
        }
    }

    // instruct caller that there were no matches
    if(count($matches) == 0)
        return(FALSE);

    return("return $text;");
}

?>
