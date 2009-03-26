<?php

/*
** Voodoo Skin Engine version 2
** from Codewise Manager, adapted for CodewiseBlog Multi-User
**
** by William R. Fraser <wrf@codewise.org> (7/25/2008)
** Copyright (c) 2005-2009 Codewise.org
*/

/*
** NOTES / GOTCHAs:
**
** New to version 2 is the ability to have multiple voodoo statements per tag.
** There is one important exception to this: START and END tags _MUST_ be alone
** in separate tags.
*/
// Though comments are deliniated with C-style /* and */, this doesn't work
/* strictly as in C in that there must be whitespace on either side of the
** symbols.
**
** Due to the ability to have multiple statements per tag, IF conditions must
** now be enclosed in parenthesis if they contain whitespace. It is further
** reccomended that _all_ if conditions be so written. Statements not written
** this way will continue to work via some hackery in the parser, but this
** usage is deprecated.
**
** Beware that a newline immediately following a Voodoo tag will be chopped
** out, just like PHP does to ?> tags. This is in the VOODOO_SUFFIX constant.
*/

/*
** This file is part of Codewise Manager
**
** Codewise Manager is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** Codewise Manager is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with Codewise Manager; if not, write to the Free Software
** Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Definitions for the voodoo tags
// The first six must be escaped for PCRE use. Also escape the "/" char.

// (these are here so we can also support Codewise Blog/Forum skins, which have
// different tags, but work the same way.)

                                    // new version:
define("VOODOO_PREFIX", "<!-- ");   //  "\\[% "
define("VOODOO_SUFFIX", " -->");    // " %\\]\n?"
define("VOODOO_VAR_PREFIX", "");
define("VOODOO_VAR_SUFFIX", "");
define("VOODOO_START",  ":cwb_start:"); // "START"
define("VOODOO_END",    ":cwb_end:");   // "END"
define("VOODOO_IF",     "#cwb_if#");    // "IF"
define("VOODOO_ELSE",   "#cwb_else#");  // "ELSE"
define("VOODOO_ENDIF",  "#cwb_endif#"); // "ENDIF"
define("VOODOO_COMMENT_START", "!cwb!"); // "/*"
define("VOODOO_COMMENT_END", "!cwb!");  // "*/"
define("VOODOO_CALL",   "*cwb_call*");  // "CALL"
define("VOODOO_FOREACH", "FOREACH"); // (doesn't exist in CWB/CWF)
define("VOODOO_ENDFOR", "ENDFOR");  // (doesn't exist in CWB/CWF)
define("VOODOO_BODY", "#CWB_BODY#"); // (unique to CWB)
define("VOODOO_CP_BODY", "#CWB_CP_BODY#"); // (unique to CWB)

// Constants used by the tokenizer.

define("VTOKEN_TEXT",    "VTOKEN_TEXT");
define("VTOKEN_VAR",     "VTOKEN_VAR");
define("VTOKEN_START",   "VTOKEN_START"); // not used
define("VTOKEN_END",     "VTOKEN_END"); // not used
define("VTOKEN_IF",      "VTOKEN_IF");
define("VTOKEN_ELSE",    "VTOKEN_ELSE");
define("VTOKEN_ENDIF",   "VTOKEN_ENDIF");
define("VTOKEN_COMMENT", "VTOKEN_COMMENT"); // not used
define("VTOKEN_CALL",    "VTOKEN_CALL");
define("VTOKEN_FOREACH", "VTOKEN_FOREACH");
define("VTOKEN_ENDFOR",  "VTOKEN_ENDFOR");
define("VTOKEN_BODY",    "VTOKEN_BODY");    // unique to CWB
define("VTOKEN_CPBODY",  "VTOKEN_CPBODY");  // unique to CWB

// Definitions for special system (%{foo}-style) variables
// the contents of these are eval()ed

$voodoo_function_table = array(
    "fortune"       => "fortune()",
    "postcalendar"  => "postcalendar()",
    "welcomeback"   => "welcomeback()",
    "subscribeform" => "subscribeform()",
    "loginform"     => "loginform()",
    "shoutbox"      => "shoutbox()",
    "statistics"    => "statistics()",
    "querycount"    => "'%{".UNIQ."querycount}'",
    "runtime"       => "'%{".UNIQ."runtime}'",
    "titletag"      => "'%{".UNIQ."titletag}'",
    "copyright"     => "CWB_COPYRIGHT",
    "notify"        => "\$GLOBALS['NOTIFY']",
    "cwb_version"   => "CWBVERSION",
    "cwb_type"      => "CWBTYPE",
    "site_title"    => "SITE_TITLE",
    "site_motto"    => "SITE_MOTTO",
    "imageverify"   => "IMAGEVERIFY",
    "logged_in"     => "LOGGED_IN",
    "admin"         => "ADMIN",
    "realname"      => "str_replace(\" \", \"&nbsp;\", htmlentities(html_entity_decode(\$BLOGINFO['realname'])))",
    "title"         => "str_replace(\" \", \"&nbsp;\", htmlentities(html_entity_decode(\$BLOGINFO['title'])))",
    "CWB_BODY"      => "%{".UNIQ."CWB_BODY}'",
);

/*
** Skin Voodoo
**
** USAGE:
** $skin is the text of a skin section.
** $subcall if left its default value of "main" means to use the main skin
**   subsection and discard the others. If set to another value, the specified
**   subsection is used and the other parts are discarded.
** $args is an associative array in the form name => value of arguments to the
**    skin. These are used in the skin in the form of [% ${foo} %] macros.
**
** This function isolates the proper subsection of the skin to process, and
** hands it over to voodoo() for processing, which then tokenizes and executes
** it.
**
** Returns the fully-processed skin section in all its glory.
*/
function skinvoodoo($section, $subcall = "", $args = array())
{
    global $SKIN_CACHE, $db;

    if (!is_array($SKIN_CACHE))
        $SKIN_CACHE = array();

    if (isset($SKIN_CACHE[$section])) {
        $skin = $SKIN_CACHE[$section];
    } else {
        $q = $db->issue_query("SELECT $section FROM skins WHERE skinid = '".SKINID."'");
        $skin = $db->fetch_var($q);

        if ($skin === NULL) {
            //echo "<h1>getting $section from default</h1>";
            $q = $db->issue_query("SELECT $section FROM skins WHERE skinid = '".CONTROLPANEL_SKINID."'");
            $skin = $db->fetch_var($q);
            //echo htmlspecialchars($skin);
        }

        $SKIN_CACHE[$section] = $skin;
    }

    $full_skin = $skin;
    preg_match_all("/".VOODOO_PREFIX.VOODOO_START." ([^\s]+)".VOODOO_SUFFIX."(.*)".VOODOO_PREFIX.VOODOO_END." \\1".VOODOO_SUFFIX."/Us", $skin, $matches, PREG_SET_ORDER);

    if ($subcall === "") {
        foreach($matches as $match)
            $skin = str_replace($match[0], "", $skin);
    } else {
        $found = FALSE;
        foreach ($matches as $match) {
            if($match[1] == $subcall)
            {
                $found = TRUE;
                $skin = $match[2];
                break;
            }
        }
        if (!$found)
            return "SKIN ERROR: no such section '$section::$subcall'";
    }

    return voodoo($skin, $section, $subcall, $args, $full_skin);
}

/*
** This is the secondary entry point into the Voodoo Skin Engine.
** Given the text of a Voodoo skin SUBSECTION, and the hash of local variables,
** this returns the fully processed section in all its glory.
**
** This SHOULD NOT BE CALLED generally. Use skinvoodoo() with the proper
** arguments instead, because the code beyond here will barf on START and END
** tags, and skinvoodoo() will filter those appropriately.
*/
function voodoo($skin, $section, $subsection, $args)
{
    global $TOKEN_CACHE;

    if (!is_array($TOKEN_CACHE))
        $TOKEN_CACHE = array();

    if (isset($TOKEN_CACHE["$section::$subsection"])) {
        $tokens = $TOKEN_CACHE["$section::$subsection"];
    } else {
        $tokens = voodoo_tokenize($skin, $section, $subsection);
        $TOKEN_CACHE["$section::$subsection"] = $tokens;
    }

    list($output, $i) = voodoo_token_run($tokens, $args, $section, $subsection,
            0, NULL);

    return $output;
}

/*
** This function "executes" the "program" specified by the tokens in $tokens.
** $args is the hash of local variables
** $skin is the original text of the full skin section prior to tokenizing. This
**   is needed for doing subcalls.
** $position is the index in $tokens to start from
** $end_on is a token type which, when seen, should cause this to return.
**
** Its behavior is recursive to facilitate nesting.
** (note that if $end_on is VTOKEN_ELSE, it will also return on a VTOKEN_ENDIF)
*/
function voodoo_token_run($tokens, $args, $section, $subsection, $position,
        $end_on)
{
    $output = "";

    for ($i = $position; $i < count($tokens); $i++) {

        switch ($tokens[$i][0]) {
        case VTOKEN_TEXT:
            $output .= $tokens[$i][1];
            break;
        case VTOKEN_VAR:
            $output .= voodoo_varsub($tokens[$i][1], $args, FALSE);
            break;
        case VTOKEN_IF:
            $condition = voodoo_varsub($tokens[$i][1], $args, TRUE);
            if (safe_eval("return $condition;", array("args" => $args))) {
                list ($sub, $i) = voodoo_token_run($tokens, $args, $section,
                        $subsection, $i + 1, VTOKEN_ELSE);
                $output .= $sub;
                // search for the ENDIF
                $level = 0;
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j][0] == VTOKEN_IF)
                        $level++;
                    if ($tokens[$j][0] == VTOKEN_ENDIF) {
                        if ($level == 0) {
                            // found it
                            $i = $j;
                            break;
                        } else {
                            $level--;
                        }
                    }
                }
                break;
            } else {
                // search for the ELSE
                $level = 0;
                $no_else = FALSE;
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j][0] == VTOKEN_IF)
                        $level++;
                    if ($tokens[$j][0] == VTOKEN_ELSE && $level == 0) {
                        // found the ELSE
                        $i = $j;
                        break;
                    }
                    if ($tokens[$j][0] == VTOKEN_ENDIF) {
                        if ($level == 0) {
                            // found the ENDIF without finding ELSE
                            $no_else = TRUE;
                            $i = $j;
                            break;
                        }
                        $level--;
                    }
                }
                if (!$no_else) {
                    list($sub, $i) = voodoo_token_run($tokens, $args, $section,
                            $subsection, $i + 1, VTOKEN_ENDIF);
                    $output .= $sub;
                }
            }
            break;
        case VTOKEN_ELSE:
            if ($end_on == VTOKEN_ELSE) {
                return array($output, $i);
            } else {
                user_error("SKIN ERROR: found unexpected ELSE"
                        . " in <b>$section::$subsection</b>", E_USER_WARNING);
            }
            break;
        case VTOKEN_ENDIF:
            if ($end_on == VTOKEN_ENDIF) {
                return array($output, $i);
            } else if ($end_on == VTOKEN_ELSE) {
                // caller is expecting to find the ENDIF at the next position
                return array($output, $i - 1);
            } else {
                user_error("SKIN ERROR: found unexpected ENDIF (token #$i)"
                        . " in <b>$section::$subsection</b>", E_USER_WARNING);
                vdump($tokens);
                user_error("Dying here", E_USER_ERROR);
            }
            break;
        case VTOKEN_COMMENT:
            break;
        case VTOKEN_CALL:
            $call = $tokens[$i][1];
            $given_args = $tokens[$i][2];
            $call_args = array();
            foreach ($given_args as $arg) {
                if ($arg == "@all") {
                    $call_args = array_merge($call_args, $args);
                } else {
                    $call_args[$arg[0]] = voodoo_varsub($arg[1], $args, FALSE);
                }
            }
            // invoke skinvoodoo() to process the called subsection
            $output .= skinvoodoo($section, $call, $call_args);
            break;
        case VTOKEN_FOREACH:
            $var = $tokens[$i][1];
            $index = $tokens[$i][2];
            $count_var = $tokens[$i][3];

            $var = voodoo_varsub($var, $args, TRUE);
            $var = safe_eval("return $var;", array("args" => $args));
            if (!is_array($var))
                $var = preg_split("//", $var, -1, PREG_SPLIT_NO_EMPTY);
            $j = $i; // stops deadlock if array is empty
            $count = 0;
            foreach (array_keys($var) as $key) {
                list ($sub, $j) = voodoo_token_run($tokens,
                        array_merge($args, array(
                            $index => $key, $count_var => $count)),
                        $section, $subsection, $i + 1, VTOKEN_ENDFOR);
                $output .= $sub;
                $count++;
            }
            if ($i == $j) {
                // loop body was never run, find the ENDFOR
                $level = 0;
                for ( ; $j < count($tokens); $j++) {
                    if ($tokens[$j][0] == VTOKEN_FOREACH)
                        $level++;
                    if ($tokens[$j][0] == VTOKEN_ENDFOR) {
                        if ($level == 0)
                            break;
                        else
                            $level--;
                    }
                }
            }
            $i = $j;

            break;
        case VTOKEN_ENDFOR:
            if ($end_on == VTOKEN_ENDFOR) {
                return array($output, $i);
            } else {
                user_error("SKIN ERROR: found unexpected ENDFOR"
                        . " in <b>$section::$subsection</b>", E_USER_WARNING);
            }
            break;
        case VTOKEN_BODY:
            $output .= "<!-- #CWB_BODY# -->";
            break;
        case VTOKEN_CPBODY:
            $output .= "<!-- #CWB_CP_BODY# -->";
            break;
        default:
            user_error("SKIN ERROR: found unknown token "
                    . print_r($tokens[$i], TRUE)
                    . " in <b>$section::$subsection</b>",
                    E_USER_WARNING);
            break;
        }
    }

    return array($output, $i);
}

/*
** Given the text of a voodoo skin section, parses and tokenizes it.
** The idea is that no regular expressions or string parsing of any kind should
** be needed outside this function. All other code should be able to use the
** tokenized form instead.
*/
function voodoo_tokenize($skin, $section, $subsection)
{
    $parts = preg_split("/(".VOODOO_PREFIX.".*?".VOODOO_SUFFIX.")/s", $skin, -1,
        PREG_SPLIT_DELIM_CAPTURE);

    $tokens = array();
    foreach ($parts as $part) {
        if (preg_match("/^".VOODOO_PREFIX."(.+?)".VOODOO_SUFFIX."$/s",
                $part, $match) == 1) {
            // got a voodoo tag

            // process the contents of the voodoo tag
            $tokens = array_merge($tokens, voodoo_tag_tokenize($match[1],
                    $section, $subsection));
        } else if (VOODOO_VAR_PREFIX != VOODOO_PREFIX
                || VOODOO_VAR_SUFFIX != VOODOO_SUFFIX) {
            // special case where vars can appear outside of voodoo tags
            // (like in CWB and CWF, where VOODOO_VAR_PREFIX/SUFFIX is nothing)

            // look for vars in the text
            $var_pattern = "[%$]{(?>[a-zA-Z0-9_-]+)(?:\\[(?:[0-9]+|'[^']+'|(?R))\\])*}";
            $further_parts = preg_split("/(".VOODOO_VAR_PREFIX.$var_pattern.VOODOO_VAR_SUFFIX.")/",
                    $part, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($further_parts as $fpart) {
                if (preg_match("/^".VOODOO_VAR_PREFIX."([%\\$]{.*?})".VOODOO_VAR_SUFFIX."$/",
                        $fpart, $fmatch) == 1) {
                    // found a var
                    $tokens[] = array(VTOKEN_VAR, $fmatch[1]);
                } else {
                    // nope, just text
                    $tokens[] = array(VTOKEN_TEXT, $fpart);
                }
            }
        } else {
            // just text.
            $tokens[] = array(VTOKEN_TEXT, $part);
        }
    }

    return $tokens;
}

/*
** This function scans the argument for valid Voodoo statements and makes tokens
** for each one.
** After voodoo_tokenize() has found all the voodoo tags in its input, it passes
** the contents of each tag to this function to complete the tokenizing.
** Each tag can have one or more valid statements in it.
*/
function voodoo_tag_tokenize($code, $section, $subsection)
{
    $tokens = array();

    // each voodoo tag can contain many voodoo statements, so we just loop while
    // adding tokens for each valid one.
    while ($code != "") {
        list($word, $code) = voodoo_get_word($code);

        // look for voodoo statements
        switch ($word) {
        case VOODOO_START:
            // bug. only skinvoodoo() should see these
            user_error("SKIN BUG: voodoo_tag_tokenize() saw a START tag"
                    ." in <b>$section::$subsection</b>", E_USER_WARNING);
            $code = voodoo_simple_skip_word($code);
            break;
        case VOODOO_END:
            user_error("SKIN BUG: voodoo_tag_tokenize() saw an END tag"
                    ." in <b>$section::$subsection</b>", E_USER_WARNING);
            $code = voodoo_simple_skip_word($code);
            break;
        case VOODOO_IF:
            list ($condition, $code) = voodoo_get_word($code);
            $tokens[] = array(VTOKEN_IF, $condition);
            break;
        case VOODOO_ELSE:
            $tokens[] = array(VTOKEN_ELSE);
            break;
        case VOODOO_ENDIF:
            $tokens[] = array(VTOKEN_ENDIF);
            break;
        case VOODOO_COMMENT_START:
            while ($code != "") {
                list ($word, $code) = voodoo_get_word($code);
                if ($word === VOODOO_COMMENT_END)
                    break 2;
            }
            user_error("SKIN WARNING: voodoo comment didn't end with \""
                    .VOODOO_COMMENT_END."\" in <b>$section::$subsection</b>",
                    E_USER_WARNING);
            break;
        case VOODOO_COMMENT_END:
            user_error("SKIN WARNING: unexpected \"".VOODOO_COMMENT_END
                    ."\" in <b>$section::$subsection</b>", E_USER_WARNING);
            break;
        case VOODOO_CALL:
            // get the name of the subcall to invoke
            list ($call, $code) = voodoo_get_word($code);

            $args = array();
            while ($code != "") {
                // get each subcall argument assignment
                list ($word, $code) = voodoo_get_word($code);
                if (strpos($word, "=") === FALSE && $word != "@all") {
                    // if the argument is invalid, it means we're done.
                    // put it back in $code and break
                    $code = "$word $code";
                    break;
                } else {
                    if ($word == "@all")
                        $args[] = "@all";
                    else {
                        preg_match("/^([a-zA-Z0-9-_]+)=\"(.*)\"$/s", $word, $match);
                        $args[] = array($match[1], $match[2]);
                    }
                }
            }
            $tokens[] = array(VTOKEN_CALL, $call, $args);
            break;
        case VOODOO_FOREACH:
            // get the array variable to iterate over
            list ($variable, $code) = voodoo_get_word($code);

            // get the name for the new index variable
            list ($new_index, $code) = voodoo_get_word($code);

            // look for a third, optional, argument
            list ($count_var, $code) = voodoo_get_word($code);

            // check validity of 2nd argument
            if (preg_match("/^\\((.*)\\)$/", $new_index, $match))
                $new_index = $match[1];
            else
                user_error("SKIN WARNING: FOREACH index must be in parens"
                        ." in <b>$section::$subsection</b>", E_USER_WARNING);

            // check validity of 3rd argument
            if (preg_match("/^\\((.*)\\)$/", $count_var, $match)) {
                $count_var = $match[1];
            } else {
                // if invalid, no worries, just put it back in $code
                $count_var = NULL;
                if ($count_var != "")
                    $code = $count_var . " " . $code;
            }
            $tokens[] = array(VTOKEN_FOREACH, $variable, $new_index, $count_var);
            break;
        case VOODOO_ENDFOR:
            $tokens[] = array(VTOKEN_ENDFOR);
            break;
        case VOODOO_BODY:
            $tokens[] = array(VTOKEN_BODY);
            break;
        case VOODOO_CP_BODY:
            $tokens[] = array(VTOKEN_CPBODY);
        default:
            if (preg_match("/^[%$]{.*?}$/", $word)) {
                $tokens[] = array(VTOKEN_VAR, $word);
            } else if ($tokens[count($tokens) - 1][0] == VTOKEN_IF) {
                // silly hack to support old skins where the condition didn't
                // have to be in parens and only one statement could appear per
                // tag. Basically, if the last token was an IF statement and we
                // run into an unknown word, tack it on to the end of that IF
                // statement's condition.
                $tokens[count($tokens) - 1][1] .= " " . $word;
            } else {
                // an unfortunate side-effect of Voodoo tags being HTML comments
                // is that all HTML comments will end up getting parsed as
                // Voodoo tags. We need to let unrecognized garbage through.

                /*
                user_error("SKIN WARNING: don't know what \"$word\" means"
                        ." inside voodoo tag in <b>$section::$subsection</b>",
                        E_USER_WARNING);
                */
            }
            break;
        }
    }

    return $tokens;
}

/*
** Gets one word from the given code, and returns an array with the word, and
** the rest of the code. Words are delimited by spaces, and extra spaces are
** killed. Words can be grouped by parentheses, and if they can contain double-
** quoted sequences of anything. Either may be backslash-escaped.
**
** Protip: use list($word, code) = voodoo_get_word($code);
*/
function voodoo_get_word($code)
{
    $word = "";
    $in = array();  // what kind of grouping char are we in between?
    for ($i = 0; $i < strlen($code); ++$i) {
        switch ($code[$i]) {
        case " ":
        case "\t":
        case "\n":
        case "\r":
            if ($word == "")
                continue; // skip over extra spaces at the beginning
            if (count($in) == 0)
                return array($word, substr($code, $i + 1)); // all done!
            $word .= " ";
            break;
        case "\\":
            // found a backslash. Add this char AND the next one, so we skip the
            // checks on the next char.
            if ($i == strlen($code) - 1) {  // don't overrun
                $word .= "\\";
                break;
            } else
                $word .= $code[$i+1];
            $i++;
            break;
        case "(":
            $word .= "(";
            if ($code[0] == "(")    // only do paren matching if the string
                $in[] = "(";        // started with a paren.
            break;
        case ")":
            $word .= ")";
            if ($code[0] == "(" && $in[count($in)-1] == "(")
                array_pop($in);
            break;
        case "\"":
            $word .= "\"";
            if ($in[count($in)-1] != "\"") {
                $in[] = "\"";
            } else {
                array_pop($in);
            }
            break;
        default:
            $word .= $code[$i];
            break;
        }
    }

    // we ran out of chars
    return array($word, "");
}

/*
** Skips over a simple space-delimited word in the argument and returns what's
** left over.
*/
function voodoo_simple_skip_word($code)
{
    $pos = strpos($code, " ");
    $pos = ($pos === FALSE) ? strlen($code) : $pos + 1;
    return substr($code, $pos);
}

/*
** Finds all valid voodoo variables in the $skin arg and replaces them.
** $args is the hash of local args
** If $symbolic is set to TRUE, the return value is in terms of $args, suitable
** for eval() by the caller (assuming the caller has a valid $args variable).
** If $symbolic is set to FALSE, the substitutions will happen here.
*/
function voodoo_varsub($skin, $args, $symbolic) {
    global $voodoo_function_table, $BLOGINFO;

    // recursive pattern for all valid var expressions
    $pattern1 = "/([%$]){((?>[a-zA-Z0-9_-]+))((?:\\[([0-9]+|'[^']+'|(?R))\\])*)}/";

    // pattern to match all the array index parts
    $pattern2 = "/\\[(((?>[^\\]\\[]+)|(?R))*)\\]/";

    preg_match_all($pattern1, $skin, $matches, PREG_SET_ORDER);
    foreach ($matches as $match)
    {
        $old = $match[0];
        $type = $match[1];
        $name = $match[2];
        $index_parts = $match[3];
        $new = FALSE;
        $trusted = FALSE;

        if ($type == "$" && in_array($name, array_keys($args))) {
            $new = "\$args['$name']";
        } else if (in_array($name, array_keys($voodoo_function_table))) {
            $trusted = TRUE;
            $new = $voodoo_function_table[$name];
        } else if (in_array($name, array_keys($BLOGINFO))) {
            $trusted = TRUE;
            $new = "\$BLOGINFO['$name']";
        }

        if ($new) {
            if ($index_parts != "") {
                preg_match_all($pattern2, $index_parts, $imatches, PREG_SET_ORDER);
                foreach ($imatches as $imatch)
                    $new .= "[".voodoo_varsub($imatch[1], $args, TRUE)."]";
            }

            if (!$symbolic) {
                //$new = "'".preg_replace("/(?<!\\\\)'/", "\\'", eval("return $new;"))."'";
                if ($trusted)
                    $new = eval("return $new;");
                else
                    $new = @safe_eval("return $new;", array("args" => $args));
            }

            $skin = str_replace($old, $new, $skin);
        } else if ($symbolic) {
            $skin = str_replace($old, "FALSE", $skin);
        } else {
            // if var is invalid and we're not in symbolic mode, don't replace
        }
    }

    return $skin;
}

// vim: sts=4 expandtab

?>
