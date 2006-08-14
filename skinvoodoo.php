<?php

/*
** "Voodoo" Skin Engine
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

if(file_exists(FSPATH."/TERMS"))
    $terms = ", subject to <a href=\"http://".BASE_DOMAIN.INSTALLED_PATH."TERMS\">terms</a>.";
else
    $terms = "";

define("CWB_COPYRIGHT",
"<a href=\"http://gna.org/projects/codewiseblog\">CodewiseBlog</a> &copy; 2005-2006 "
. "<a href=\"http://www.codewise.org/~netmanw00t/\">Bill R. Fraser</a> / "
. "<a href=\"http://www.codewise.org/\">Codewise.org</a>.<br />"
. "All textual content is the property of its author$terms.<br />"
. "CodewiseBlog is free software under the <a href=\"COPYING\">GNU General Public License</a>"
);

/*
** Skin Voodoo
**
** $skin_section is the section of the Voodoo templates to use
** $subcall if left its default empty string value means to use the main skin
**   subsection and discard the others. If set to another value, the specified
**   subsection is used and the other parts are discarded.
** $args is an associative array in the form name => value of arguments to the
**    skin. These are used in the skin in the form of ${foo} macros.
**
** Returns the fully-processed skin section in all its glory.
*/
function skinvoodoo($skin_section, $subcall = "", $args = array())
{
    global $db, $SKIN_CACHE;

    if(in_array($skin_section, array_keys($SKIN_CACHE)))
    {
        $skin = $SKIN_CACHE[$skin_section];
    } else {

        $q = $db->issue_query("SELECT $skin_section FROM skins WHERE skinid = '".SKINID."'");
        $skin = $db->fetch_var($q);

        // if the user's skin is NULL, use master skin
        if($skin === NULL)
        {
            $q = $db->issue_query("SELECT $skin_section FROM skins WHERE skinid = '00000000000000000000000000000000'");
            $skin = $db->fetch_var($q);
        }

        $SKIN_CACHE[$skin_section] = $skin;
    }

    // DEBUG
    $skin = file_get_contents(FSPATH . "/skin_blueEye/$skin_section.html");

    preg_match_all("/<\\!-- :cwb_start: ([^\s]+) -->\n*?(.*)<\\!-- :cwb_end: \\1 -->\n*?/Us", $skin, $matches, PREG_SET_ORDER);

    if($subcall == "")
    {
        foreach($matches as $match)
            $skin = str_replace($match[0], "", $skin);
    } else {
        $found = FALSE;
        foreach($matches as $match)
        {
            if($match[1] == $subcall)
            {
                $found = TRUE;
                $skin = $match[2];
                break;
            }
        }
        if(!$found)
            return "[[[SKIN ERROR: NO SUCH SECTION '$skin_section::$subcall']]]";
    }

    return voodoo($skin, $args, $skin_section);
}

/*
** Do not call this function directly.
**
** This function does all the processing of Voodoo tags with the exception of
** subsection call tags (<!-- :cwb_start ... -->), which are handled by
** skinvoodoo().
**
** Order of processing is as follows:
** 1) If/Else/End (and variables in the If tag's condition - see note below)
** 2) Local subsection calls (and variables in the environment assignment - see
**      note below)
** 3) Local variables (${foo})
** 4) Global variables (%{foo})
**
** $skin is the Voodoo template code, sans subsection tags.
** $args is an associative array containing the names and values of all local
**   variables.
** $skin_section is the name of the section in the Voodoo templates the code
**   comes from (used when evaluating local subsection calls).
** $expand is set to TRUE to replace variables with their values. It can be set
**   to FALSE to return global variables as their PHP code equivalents (for use
**   when evaluating the value of If tag conditions).
**
** Note:
**   Global and local variables may be used as the condition in an If tag and in
**   the variable assignments of call tags. If used here, they will be evaluated
**   as their respective parent tags are evaluated, and not at later stages like
**   if they were in the normal skin body.
**
*/
function voodoo($skin, $args = array(), $skin_section = "", $expand = TRUE)
{
    global $BLOGINFO;

    /*
    ** Comments
    **
    ** e.g. <!-- !cwb! comment text here !cwb! -->
    */

    preg_match_all("/<\\!-- \\!cwb\\! .* \\!cwb\\! -->\n?/", $skin, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $skin = str_replace($match[0], "", $skin);
    }

    /*
    ** <!-- #cwb_(if|else|endif)# --> Tags
    */

    $ifcapture = "<\\!-- #cwb_if# (?P<condition>(?:.(?!-->))+) -->";
    $if = "<\\!-- #cwb_if# ((?:.(?!--))+) -->\n*";
    $else = "<\\!-- #cwb_else# -->\n*";
    $end = "<\\!-- #cwb_endif# -->\n*";
    $pattern = "/$ifcapture(?P<true>(?>.(?!$if))*?)($else(?P<false>(?>.(?!$if))*?))?$end/s";

    /*
    ** Here, we work on the innermost set of tags first.
    ** The regex only matches sets of tags that have no #cwb_if# tags inside.
    ** We keep re-evaluating the regex until there are no tags left.
    ** You don't want to know how long it took to work this out... :P
    */

    preg_match_all($pattern, $skin, $matches, PREG_SET_ORDER);
    do {
        foreach($matches as $match)
        {
            $old = $match[0];
            $condition = $match["condition"];
            $true = $match["true"];
            $false = $match["false"];

            $result = eval("return " . voodoo($condition, $args, $skin_section, FALSE) . ";");

            if($result)
            {
                $skin = str_replace($old, voodoo($true, $args, $skin_section), $skin);
            } else {
                $skin = str_replace($old, voodoo($false, $args, $skin_section), $skin);
            }
        }

        preg_match_all($pattern, $skin, $matches, PREG_SET_ORDER);
    } while(count($matches) > 0);

    /*
    ** Local Calls
    **
    ** e,g, <!-- *cwb_call* methodname @all var1="value1" var2="value2" -->
    **
    ** If the first variable listed is "@all", the current section's environment
    ** is copied to the new call and subsequent variables listed override this
    ** environment.
    */

    preg_match_all("/<\\!-- \\*cwb_call\\* ([a-zA-Z0-9-_]+?)(( @all| [a-zA-Z0-9-_]+=\"[^\"]*?\")*?) -->/Us", $skin, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $old = $match[0];
        $call = $match[1];
        $call_arg_list = $match[2];

        if(strpos($match[2], " @all") === 0)
        {
            $call_args = $args;
            $call_arg_list = preg_replace("/^ @all/", "", $match[2]);
        } else {
            $call_args = array();
        }

        preg_match_all("/ ([a-zA-Z0-9-_]+)=\"([^\"]*)\"/s", $call_arg_list, $arg_matches, PREG_SET_ORDER);
        foreach($arg_matches as $match)
            $call_args[$match[1]] = voodoo($match[2], $args, $skin_section);

        $new = skinvoodoo($skin_section, $call, $call_args);
        $skin = str_replace($old, $new, $skin);
    }

    /*
    ** Local Variables
    **
    ** e.g. ${variable}
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
    ** Global Variables & Calls
    **
    ** e.g. %{variable}
    */

    preg_match_all("/%{([a-zA-Z0-9-_]+)}/", $skin, $matches, PREG_SET_ORDER);
    foreach($matches as $match)
    {
        $old = $match[0];
        $name = $match[1];

        $function_table = array(
            "fortune" => "fortune()",
            "postcalendar" => "postcalendar()",
            "welcomeback" => "welcomeback()",
            "subscribeform" => "subscribeform()",
            "loginform" => "loginform()",
            "shoutbox" => "shoutbox()",
            "statistics" => "statistics()",
            "querycount" => "'%{".UNIQ."querycount}'",// <--,
            "runtime" => "'%{".UNIQ ."runtime}'", // <------|- these will be replaced at the very end of execution
            "titletag" => "'%{".UNIQ."titletag}'", // <-----'
            "copyright" => "CWB_COPYRIGHT",
            "notify" => "\$GLOBALS['NOTIFY']",
            "cwb_version" => "CWBVERSION",
            "cwb_type" => "CWBTYPE",
            "site_title" => "SITE_TITLE",
            "site_motto" => "SITE_MOTTO",
            "imageverify" => "IMAGEVERIFY",
            "logged_in" => "LOGGED_IN",
            "admin" => "ADMIN",
        );

        $new = "";
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
