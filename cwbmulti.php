<?php

/*
** Initializer
** for CodewiseBlog Multi-User
**
** by Bill Fraser <firstname.lastname@gmail.com>
** Copyright (c) 2005 Codewise.org
** http://blogs.codewise.org/
*/

/*
** Change History:
**
** Dev - July 25 to September 12, 2005
**   - developed alongside CodewiseBlog Single-User v1.2.4 to v1.2.9
**
** 1.0.0-ALPHA - September 12, 2005
**   - basic alpha release
**
** 1.0.0-ALPHA-r1 - September 15, 2005
**   - added write page for UCP
*/

// start execution timer
list($usec,$sec) = explode(" ",microtime());
$starttime = (string) $sec + $usec;
unset($sec, $usec);

// define version string
define("CWBVERSION","1.0.0-ALPHA-r2");
define("CWBTYPE", "Multi-User");

require("settings.php");

chdir(FSPATH);

define("EMAIL", TRUE);
define("SQL_ADMIN_EMAIL", "bill.fraser@gmail.com");

/*
** Set up environment
*/

// deal with errors properly
ini_set("track_errors", true);
error_reporting(E_ALL ^ E_NOTICE);

// fire up a session
ini_set("session.name", "codewiseblog");
ini_set("session.cookie_lifetime", 60*60*24*365);
ini_set("session.cookie_domain", BASE_DOMAIN);
session_start();

// clean out this crap - it's never used
unset($HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_ENV_VARS, $HTTP_POST_FILES, $HTTP_SESSION_VARS);

// functions
require("skinvoodoo.php");
require("main_functions.php");
require("misc.php");
require("postcalendar.php");
require("sidebar.php");
require("topic.php");
require("shoutbox.php");
require("stats.php");
require("reply.php");
require("subscribe.php");
require("controlpanel.php");
require("file_put_contents.php"); // from the PHP_Compat project

require("l1_mysql.php");
$db = new L1_MySQL("localhost", "codewiseblog", "!#joltColaINaCan");

// custom error handler to mail the admin as well as print any errors
$db->error_callback = $db->warning_callback = "mail_db_error";

$db->database("codewiseblog");

/*
** Support Apache2 mod_rewrite proxying
*/

if(isset($_SERVER['HTTP_X_FORWARDED_HOST']))
{
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

/*
** Who are we running for?
*/

$q = $db->issue_query("SELECT blogid,name FROM blogs");
$blogdata = $db->fetch_all($q, L1SQL_ASSOC, "name");

if(SUBDOMAIN_MODE)
{
    $who = preg_replace("/\." . quotemeta(BASE_DOMAIN) . "$/", "", $_SERVER['HTTP_HOST']);
    if($who == DEFAULT_SUBDOMAIN || $who == BASE_DOMAIN)
        $who = "";
} else {
    $who = preg_replace("/^" . str_replace("/", "\/", quotemeta(INSTALLED_PATH)) . "/", "", $_SERVER['REQUEST_URI']);
}

//KEEP
//$who = "netmanw00t";

if($who == "")
{
    define("BLOGID", 1);
    define("BLOGNAME", "");
        define("INDEX_URL", "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH);
} elseif(!isset($blogdata[$who])) {
    die( "<html><head><title>CodewiseBlog :: Invalid User</title><link rel=\"stylesheet\" href=\"http://www.codewise.org/blueEye.css\" /></head>"
       . "<body><b>Invalid User \"$who\"</b><br /><br /><a href=\"http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "\">...back to CodewiseBlog</a></body></html>" );
} else {
    define("BLOGID", $blogdata[$who]['blogid']);
    define("BLOGNAME", $who);
    define("ADMIN_EMAIL", $blogdata[$who]['email']);
    if(SUBDOMAIN_MODE)
        define("INDEX_URL", "http://" . BLOGNAME . "." . BASE_DOMAIN . INSTALLED_PATH);
    else
        define("INDEX_URL", "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "/" . BLOGNAME);
}

/*
** Set up the $BLOGINFO global var with some useful stuff
*/

$q = $db->issue_query("SELECT blogid,name,email,realname,birthday,location,interests,links,photo,homepage,title FROM blogs WHERE blogid = '" . BLOGID . "'");
$BLOGINFO = $db->fetch_row($q, 0, L1SQL_ASSOC);

if($BLOGINFO['birthday'])
    $BLOGINFO['age'] = date("Y", time() - $BLOGINFO['birthday']);

$BLOGINFO['index_url'] = INDEX_URL;
$BLOGINFO['ucp_url'] = INDEX_URL . "?controlpanel";

$BLOGINFO['interests'] = nl2br($BLOGINFO['interests']);
$BLOGINFO['links'] = nl2br($BLOGINFO['links']);
$BLOGINFO['version'] = CWBVERSION;
$BLOGINFO['anonymous_name'] = ANONYMOUS_NAME;

if(!defined("NO_ACTION"))
{
    /*
    ** Let's light this candle!
    */

    // special front page
    if(BLOGID == 1)
    {
        ob_start();
        require("front_page.php");
        $main = ob_get_clean();
        die($main);
    }

    foreach(array_keys($_GET) as $key)
    {
        if(preg_match("/^controlpanel:?/", $key))
        {
            $out = controlpanel();
            die(str_replace("%{runtime}", runtime(), $out));
        }
    }

    if(isset($_GET['quicktags_js']))
    {
        header("Content-type: text/javascript");
        die(file_get_contents("cwb/quicktags.js"));
    }

    if(!is_numeric($_GET['page']))
        $_GET['page'] = 1;

    if(is_numeric($_GET['tid']))
    {
        $body = show_topic($_GET['tid'], $_GET['page']);
    } elseif(is_numeric($_GET['month']) && is_numeric($_GET['year'])) {
        $body = show_month($_GET['month'], $_GET['year'], $_GET['page']);
    } elseif(is_numeric($_GET['reply'])) {
        $body = show_reply_form($_GET['reply']);
    } elseif(is_numeric($_GET['do_reply'])) {
        $body = process_reply_form($_GET['do_reply']);
    } elseif(isset($_GET['delsession'])) {
        $body = delete_session();
    } elseif(isset($_GET['subscribe'])) {
        $body = process_subscribe_form();
    } elseif(isset($_GET['unsubscribe'])) {
        $body = do_unsubscribe();
    } elseif(isset($_GET['shoutbox'])) {
        $body = shoutbox_process();
    } elseif(isset($_GET['login'])) {
        $body = cplogin();
    } elseif(isset($_GET['notloggedin'])) {
        $NOTIFY = "You are not logged in to the control panel.";
        $body = main_page(1);
    } else {
        $body = main_page($_GET['page']);
    }

    $main = skinvoodoo("main");

    $out = str_replace("<!-- #CWB_BODY# -->", $body, $main);

    echo str_replace("%{runtime}", runtime(), $out);
}

// and we're out. :)

?>
