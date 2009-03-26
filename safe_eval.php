<?php

/*
 * Safe Eval Functions
 * by William R. Fraser <wrf@codewise.org> 3/16/2009
 * Copyright (c) 2009 Codewise.org
 */

//if (!function_exists("parsekit_compile_string")) {
//    message("no_parsekit");
//    exit;
//}

/*
 * THE RULES:
 * - no variable assignment allowed
 * - no access to the database
 * - no access to the server environment & filesystem
 */

$safe_eval_cache = array();

list($safe_eval_blacklist_funcs, $safe_eval_whitelist_vars) = safe_eval_init();

function safe_eval_init()
{
    $allowed_vars = array("_GET", "_POST", "_SERVER", "_REQUEST", "_FILES", 
        "_COOKIE");
    $blacklist = array("++", "--", "=", "+=", "-=", "*=", "/=", ".=", "%=",
        "&=", "|=", "^-", "<<=", ">>=", "include", "require", "if", "else",
        "while", "for", "switch", "exit", "break", "print", "echo");
    $whitelist = array("vdump", "count", "isset", "substr", "str_replace", "htmlentities", "html_entity_decode");

    $funcs = get_defined_functions();
    $funcs = array_merge($funcs['internal'], $funcs['user']);

    foreach ($whitelist as $entry) {
        unset($funcs[array_search($entry, $funcs)]);
    }

    $blacklist = array_merge($blacklist, $funcs);

    return array($blacklist, $whitelist);
}

function safe_eval($code, $environment = array())
{
    global $safe_eval_cache, $safe_eval_blacklist_funcs, $safe_eval_whitelist_vars;

    $stripped_code = safe_eval_strip_code($code);

    foreach ($safe_eval_blacklist_funcs as $bad) {
        if (($where = strpos($stripped_code, " $bad ")) !== false) {
            echo "code has blacklisted construct $bad at character $where\n";
            echo "<pre>".htmlspecialchars($code)."</pre>";
            //message("safe_eval_failed", array("bad" => $bad,
            //        "code" => $code));
            exit;
        }
    }

    $allowed_vars = array_merge($safe_eval_whitelist_vars, array_keys($environment));

    foreach ($GLOBALS as $bad => $value) {
        if (in_array($bad, $allowed_vars))
            continue;
        if (($where = strpos($stripped_code, " \$bad ")) !== false) {
            echo "code has blacklisted variable $bad at character $where";
            echo "<pre>".htmlspecialchars($code)."</pre>";
            //message("safe_eval_failed", array("bad" => "$bad",
            //    "code" => $code));
            exit;
        }
    }

    $safe_eval_cache[$code] = true;

    // import the environment
    foreach ($environment as $name => $value) {
        $$name = $value;
    }

    return eval($code);
}

/*
 * This elaborate state machine's purpose is to strip out all non-symbolic
 * text in the given piece of code so the safe_eval function can search it for
 * badness.
 *
 * It returns a string with all constructs/functions/variables separated by
 * whitespace. Variables start with '$'.
 *
 * Example: "foreach (glob("plugins/*.php{$var['hax']} $foo" as $plugin) {"
 * becomes: " foreach  glob  $db    $var $plugin  "
 */
function safe_eval_strip_code($code)
{
    $stripped_code = "";
    $state = array("not in str");
    for ($i = 0; $i < strlen($code); $i++) {
        switch ($state[count($state) - 1]) {
        case "not in str":
        case "dq braced var":
            switch ($code[$i]) {
            case "\\":
                $i++;
                break;
            case "\"":
                array_push($state, "dqstr");
                $stripped_code .= " ";
                break;
            case "'":
                array_push($state, "sqstr");
                $stripped_code .= " ";
                break;
            case "+":
                if ($code[$i+1] == "+") {
                    $i++;
                    $stripped_code .= " ++ ";
                } else
                    $stripped_code .= " ";
                break;
            case "-":
                if ($code[$i+1] == "-") {
                    $i++;
                    $stripped_code .= " -- ";
                } else
                    $stripped_code .= " ";
                break;
            case "=":
                $operator = "=";
                if ($code[$i+1] != "=") {
                    // continue to the end of the operator and then work back
                    // they all end with '='
                    for ($j = $i - 1; $j >= 0; $j--) {
                        if (preg_match("#[<>^!=+/*%&-]#", $code[$j])) {
                            $operator = $code[$j].$operator;
                        } else {
                            $stripped_code .= " $operator ";
                            break;
                        }
                    }
                }
                break;
            case "}":
                if ($state[count($state) - 1] == "dq braced var")
                    array_pop($state);
            case "{":
            case "?":
            case ":":
            case "(":
            case ")":
            case "[":
            case "]":
            case "|":
            case "&":
            case "!":
            case "~":
            case "^":
            case "<":
            case ">":
            case "%":
            case ".":
            case ";":
            case "\n":
            case "\t":
                $stripped_code .= " ";
                break;
            default:
                if (!preg_match("/[0-9]/", $code[$i]))
                    $stripped_code .= $code[$i];
                break;
            }
            break;
        case "dqstr":
            switch ($code[$i]) {
            case "\\":
                $i++;
                break;
            case "$":
                array_push($state, "dq var");
                $stripped_code .= "$";
                break;
            case "{":
                if ($code[++$i] == "$") {
                    array_push($state, "dq braced var");
                    $stripped_code .= "$";
                }
                break;
            case "\"":
                array_pop($state);
                break;
            }
            break;
        case "sqstr":
            switch ($code[$i]) {
            case "\\":
                $i++;
                break;
            case "'":
                array_pop($state);
                break;
            }
            break;
        case "dq var":
            if ($code[$i-1] == "$" ? !preg_match("/[a-zA-Z_\x7f-\xff]/", $code[$i]) : !preg_match("/[a-zA-Z0-9_\x7f-\xff]/", $code[$i])) {
                array_pop($state);
                $stripped_code .= " ";
            } else {
                $stripped_code .= $code[$i];
            }
            break;
        }
    }

    return " $stripped_code ";
}

?>
