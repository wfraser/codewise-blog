<?php

/*
** CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2005 Codewise.org
*/

/*
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

// start execution timer
list($usec,$sec) = explode(" ",microtime());
$starttime = (string) $sec + $usec;
unset($sec, $usec);

// define version string
define("CWBVERSION","1.0.0-BETA-r2");
define("CWBTYPE", "Multi-User");
define("SETTINGS_FILE", "settings.php");

// Unique ID for this request
define("UNIQ", md5(uniqid(mt_rand(), true)));

require(SETTINGS_FILE);

chdir(FSPATH);

/*
** Set up environment
*/

// deal with errors properly
ini_set("track_errors", true);
error_reporting(E_ALL ^ E_NOTICE);

// fire up a session
ini_set("session.name", "codewiseblog");
ini_set("session.cookie_lifetime", 60*60*24*365);
//ini_set("session.cookie_domain", BASE_DOMAIN);
session_start();

// clean out this crap - it's never used
unset($HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_ENV_VARS, $HTTP_POST_FILES, $HTTP_SESSION_VARS);

// cache of skin data
$SKIN_CACHE = array();

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
$db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS);

// custom error handler to mail the admin as well as print any errors
$db->error_callback = $db->warning_callback = "mail_db_error";

$db->database(SQL_DB);

/*
** Who are we running for?
*/

$q = $db->issue_query("SELECT blogid,name,custom_url FROM blogs");
$blogdata = $db->fetch_all($q, L1SQL_ASSOC, "name");

// set ?subdomain_mode=0 to pass the username by path anyways.
// Useful when using mod_rewrite
if(isset($_GET['subdomain_mode']) ? $_GET['subdomain_mode'] : SUBDOMAIN_MODE)
{
    $who = preg_replace("/\." . quotemeta(BASE_DOMAIN) . "$/", "", $_SERVER['HTTP_HOST']);
    if($who == DEFAULT_SUBDOMAIN || $who == BASE_DOMAIN)
        $who = "";
} else {
    $preg_path = str_replace("/", "\\/", quotemeta(INSTALLED_PATH));
    $who = preg_replace("/^$preg_path(.*\\/)*/", "", $_SERVER['REQUEST_URI']);
    $who = preg_replace("/\\?.*$/", "", $who);
}

if($who == "")
{
    define("BLOGID", 1);
    define("BLOGNAME", "");
    if(DEFAULT_SUBDOMAIN == "")
        define("INDEX_URL", "http://" . BASE_DOMAIN . INSTALLED_PATH);
    else
        define("INDEX_URL", "http://" . DEFAULT_SUBDOMAIN . "." . BASE_DOMAIN . INSTALLED_PATH);
} elseif(!isset($blogdata[$who])) {
    die( "<html><head><title>CodewiseBlog :: Invalid User</title><link rel=\"stylesheet\" href=\"stylesheet.php?id=1\" /></head>"
       . "<body><b>Invalid User \"$who\"</b><br /><br /><a href=\"http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "\">...go back</a></body></html>" );
} else {
    define("BLOGID", $blogdata[$who]['blogid']);
    define("BLOGNAME", $who);

    if($blogdata[$who]['custom_url'] != NULL && CUSTOM_URL_ENABLED)
    {
        define("INDEX_URL", $blogdata[$who]['custom_url']);
    } elseif(SUBDOMAIN_MODE) {
        define("INDEX_URL", "http://" . BLOGNAME . "." . BASE_DOMAIN . INSTALLED_PATH);
    } else {
        if(DEFAULT_SUBDOMAIN == "")
            define("INDEX_URL", "http://" . BASE_DOMAIN . INSTALLED_PATH . BLOGNAME);
        else
            define("INDEX_URL", "http://" . DEFAULT_SUBDOMAIN . "." . BASE_DOMAIN . INSTALLED_PATH . BLOGNAME);
    }
}

/*
** Set up the $BLOGINFO global var with some useful stuff
*/

$q = $db->issue_query("SELECT blogid,name,email,realname,birthday,location,interests,links,photo,homepage,title FROM blogs WHERE blogid = '" . BLOGID . "'");
$BLOGINFO = $db->fetch_row($q, 0, L1SQL_ASSOC);

define("ADMIN_EMAIL", $BLOGINFO['email']);


if($BLOGINFO['birthday'])
{
    list($month,$day,$year) = explode("/", $BLOGINFO['birthday']);
    $BLOGINFO['birthday_month'] = $month;
    $BLOGINFO['birthday_day'] = $day;
    $BLOGINFO['birthday_year'] = $year;
    $BLOGINFO['age'] = ($month >= date("m") && $day > date("d")) ? date("Y") - $year - 1 : date("Y") - $year;
} else {
    $BLOGINFO['age'] = $BLOGINFO['birthday_month'] = $BLOGINFO['birthday_day'] = $BLOGINFO['birthday_year'] = "";
}

$BLOGINFO['index_url'] = INDEX_URL;
$BLOGINFO['ucp_url'] = INDEX_URL . "?controlpanel";
if(!SUBDOMAIN_MODE) $BLOGINFO['rdf_url'] = "rdf.php/" . BLOGNAME;
else                $BLOGINFO['rdf_url'] = "rdf.php";
$BLOGINFO['css_url'] = "stylesheet.php?id=" . BLOGID;

$BLOGINFO['interests'] = nl2br($BLOGINFO['interests']);
$BLOGINFO['links'] = nl2br($BLOGINFO['links']);
$BLOGINFO['version'] = CWBVERSION;
$BLOGINFO['anonymous_name'] = ANONYMOUS_NAME;

if(!defined("NO_ACTION"))
{
    /*
    ** Let's light this candle!
    */

    // control panel
    foreach(array_keys($_GET) as $key)
    {
        if(preg_match("/^controlpanel:?/", $key))
        {
            $out = controlpanel();
            die(str_replace("%{runtime}", runtime(), $out));
        }
    }

    // special front page
    if(BLOGID == 1 && !isset($_GET['login'])) // allow admin controlpanel login from front page
    {
        ob_start();
        require("front_page.php");
        $main = ob_get_clean();
        die($main);
    }

    // QuickTags for controlpanel:write page
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

    $out = skinvoodoo("main");

    $out = str_replace("<!-- #CWB_BODY# -->", $body, $out);

    if($TITLE == "")
        $TITLE = $BLOGINFO['title'];
    $out = str_replace("%{".UNIQ."titletag}", $TITLE, $out);

    $out = str_replace("%{".UNIQ."runtime}", runtime(), $out);

    echo $out;
}

// and we're out. :)

?>
