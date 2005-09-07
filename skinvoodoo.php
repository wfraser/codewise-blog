<?php

/*
** "Voodoo" Skin Engine
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
**    skin. These are used in the skin in the form of %{foo} macros.
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

    $skin = file_get_contents("/srv/www/site/blogs.codewise.org/skin_blueEye/$skin_section.html");

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

    return voodoo($skin, $args, $skin_section);
}

function voodoo($skin, $args = array(), $debug_info = "", $expand = TRUE)
{
    global $BLOGINFO;

    /*
    ** Voodoo Tags
    */

    // basically:
    //
    // if ( .* ( if .* end )* .* ) ( else ( .* ( if .* end )* .* ) )? end

    // This is the regular expression to END ALL REGULAR EXPRESSIONS!!
    preg_match_all("/<\\!-- #cwb_if# (.+) -->\n??(.*(<\\! #cwb_if# .* -->.*<\\!-- #cwb_endif# -->)*.*)(<\\!-- #cwb_else# -->(.*(<\\!-- #cwb_if# .* -->.*<\\!-- #cwb_endif -->)*.*))??<\\!-- #cwb_endif# -->/Us", $skin, $matches, PREG_SET_ORDER);

    foreach($matches as $match)
    {
        $old = $match[0];
        $condition = $match[1];
        $if = $match[2];
        $else = $match[5];

        $result = eval("return " . voodoo($condition, $args, "$debug_info:iftag", FALSE) . ";");

        if($result)
        {
            $skin = str_replace($old, voodoo($if, $args), $skin);
        } else {
            $skin = str_replace($old, voodoo($else, $args), $skin);
        }
    }

    /*
    ** Local Variables
    */

    preg_match_all('/\${([a-zA-Z0-9-_]+)}/', $skin, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $old = $match[0];
        $name = $match[1];

        //if(isset($args[$name]))
        if(in_array($name, array_keys($args)))
        {
            $new = $args[$name];
            if(!$expand)
                $new = "\$args['$name']";
            $skin = str_replace($old, $new, $skin);
        }
    }

    /*
    ** System Variables & Calls
    */

    preg_match_all("/%{([a-zA-Z0-9-_]+)}/", $skin, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $old = $match[0];
        $name = $match[1];

        $function_table = array(
            "special_fortune" => "fortune()",
            "postcalendar" => "postcalendar()",
            "welcomeback" => "welcomeback()",
            "subscribeform" => "subscribeform()",
            "loginform" => "loginform()",
            "shoutbox" => "shoutbox()",
            "statistics" => "statistics()",
            "querycount" => "querycount()",
            "runtime" => "'%{runtime}'", // needs to be done last
            "versionfooter" => "versionfooter()",
            "copyright" => "'CodewiseBlog &copy; <a href=\"http://www.codewise.org/~netmanw00t/\">Bill Fraser</a>.<br />All textual content is the property of its author.'",
            "notify" => "\$GLOBALS['NOTIFY']",
        );

        if(isset($function_table[$name]))
        {
            $new = $function_table[$name];
            if($expand)
                $new = eval("return $new;");
        //} elseif(isset($BLOGINFO[$name])) {
        } elseif(in_array($name, array_keys($BLOGINFO))) {
            if($expand)
                $new = $BLOGINFO[$name];
            else
                $new = "\$BLOGINFO['$name']";
        }
        $skin = str_replace($old, $new, $skin);
    }

    return $skin;
}

?>
