<?php

/*
** CodewiseBlog Multi-User
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2005-2008 Codewise.org
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
define("SETTINGS_FILE", "settings.php");

// Unique ID for this request
define("UNIQ", md5(uniqid(mt_rand(), true)));

require(SETTINGS_FILE);

chdir(FSPATH);

// define version strings
require("version.php");

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
require_once "skinvoodoo2.php";
require_once "main_functions.php";
require_once "misc.php";
require_once "postcalendar.php";
require_once "sidebar.php";
require_once "topic.php";
require_once "shoutbox.php";
require_once "stats.php";
require_once "reply.php";
require_once "subscribe.php";
require_once "controlpanel.php";
require_once "imageverify.php";
require_once "antispam.php";
require_once "parseurl.php";

require_once "l1_mysql.php";
$db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS);

// all functions are now defined, so init safe_eval
require_once "safe_eval.php";

// custom error handler to mail the admin as well as print any errors
$db->error_callback = $db->warning_callback = "mail_db_error";

$db->database(SQL_DB);

/*
** Who are we running for?
*/

// first of all, on some servers, mod_rewrite doesn't set the request uri
// correctly. Fix!
if (isset($_SERVER['REDIRECT_URL']))
	$_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'];

$q = $db->issue_query("SELECT blogid,name,custom_url,status FROM blogs");
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
    $who = preg_replace("/^$preg_path(rdf\.php\/)?/", "", $_SERVER['REQUEST_URI']);
    $who = preg_replace("/\\?.*$/", "", $who);
    $who = preg_replace("/\\/.*$/", "", $who);
}

/*
** Keep https:// if we're using it
*/
if($_SERVER['HTTPS'] == "on")
{
    define("HTTP", "https://");
} else {
    define("HTTP", "http://");
}

if($who == "")
{
    /*
    ** No user
    */
    define("BLOGID", 1);
    define("BLOGNAME", "");
    define("SKINID", DEFAULT_SKINID);
    if(DEFAULT_SUBDOMAIN == "")
        define("INDEX_URL", HTTP . BASE_DOMAIN . INSTALLED_PATH);
    else
        define("INDEX_URL", HTTP . DEFAULT_SUBDOMAIN . "." . BASE_DOMAIN . INSTALLED_PATH);
} elseif(!isset($blogdata[$who])) {

    /*
    ** Check to see if the request is a custom url
    ** This is needed when non-proxying RewriteRule directives are used
    */
    $path = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'].'?', '?'));
    $q = $db->issue_query("SELECT name FROM blogs WHERE custom_url = ".$db->prepare_value(HTTP.$_SERVER['HTTP_HOST'].$path));

    if($db->num_rows[$q] > 0)
    {
        $who = $db->fetch_var($q);
    } else {
        /*
        ** Bogus user
        */
        die( "<html><head><title>".SITE_TITLE." :: Invalid User</title><link rel=\"stylesheet\" href=\"stylesheet.php?id=1\" /></head>"
            . "<body><b>Invalid User \"$who\"</b><br /><br /><a href=\"" . HTTP . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "\">...go back</a></body></html>" );
    }
}

if($blogdata[$who]['status'] == "closed")
    $_GET['skinid'] = CLOSED_SKINID;

define("BLOGID", $blogdata[$who]['blogid']);
define("BLOGNAME", $who);

if(isset($_GET['skinid'])
    && $db->num_rows[ $db->issue_query("SELECT skinid FROM skins WHERE skinid = ".$db->prepare_value($_GET['skinid'])) ] > 0)
{
    define("SKINID", $db->prepare_value($_GET['skinid'], FALSE));
} else if (array_psearch($_GET, "/^controlpanel:?/") !== FALSE) {
    // always use the CP Skin when accessing the CP.
    define("SKINID", CONTROLPANEL_SKINID);
} else {
    define("SKINID", $db->fetch_var($db->issue_query("SELECT skinid FROM blogs WHERE blogid = '".BLOGID."'")));
}

if($blogdata[$who]['custom_url'] != NULL && CUSTOM_URL_ENABLED)
{
    define("INDEX_URL", $blogdata[$who]['custom_url']);
} elseif(SUBDOMAIN_MODE) {
    define("INDEX_URL", HTTP . BLOGNAME . "." . BASE_DOMAIN . INSTALLED_PATH);
} else {
    define("INDEX_URL", HTTP . BASE_DOMAIN . INSTALLED_PATH . BLOGNAME . "/");
}

/*
** Set up the $BLOGINFO global var with some useful stuff
*/

$q = $db->issue_query("SELECT blogid,name,email,realname,birthday,location,interests,links,photo,homepage,title FROM blogs WHERE blogid = '" . BLOGID . "'");
$BLOGINFO = $db->fetch_row($q, 0, L1SQL_ASSOC);

define("ADMIN_EMAIL", $BLOGINFO['email']);
define("BLOG_TITLE", $BLOGINFO['title']);

if($BLOGINFO['birthday'])
{
    list($month,$day,$year) = explode("/", $BLOGINFO['birthday']);
    $BLOGINFO['birthday_month'] = $month;
    $BLOGINFO['birthday_day'] = $day;
    $BLOGINFO['birthday_year'] = $year;
    $BLOGINFO['age'] = ($month > date("m") || ($month == date("m") && $day > date("d"))) ? date("Y") - $year - 1 : date("Y") - $year;
} else {
    $BLOGINFO['age'] = $BLOGINFO['birthday_month'] = $BLOGINFO['birthday_day'] = $BLOGINFO['birthday_year'] = "";
}

$BLOGINFO['index_url'] = INDEX_URL;
$BLOGINFO['ucp_link'] = INDEX_URL . "?controlpanel";
if(!SUBDOMAIN_MODE) $BLOGINFO['rdf_url'] = HTTP.DEFAULT_SUBDOMAIN.BASE_DOMAIN.INSTALLED_PATH
						."rdf.php/".BLOGNAME;
else                $BLOGINFO['rdf_url'] = HTTP.DEFAULT_SUBDOMAIN.BASE_DOMAIN.INSTALLED_PATH
						.BLOGNAME."rdf.php";

$BLOGINFO['css_url'] = HTTP.BASE_DOMAIN.INSTALLED_PATH."stylesheet.php?id=" . SKINID;

$BLOGINFO['interests'] = nl2br($BLOGINFO['interests']);
$BLOGINFO['links'] = nl2br($BLOGINFO['links']);
$BLOGINFO['version'] = CWBVERSION;
$BLOGINFO['anonymous_name'] = ANONYMOUS_NAME;

$BLOGINFO['multiuser_root'] = HTTP . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH;

if(isset($_GET['login']))
{
    // don't define them yet - we'll define them in controlpanel.php:cplogin()
} elseif(isset($_GET['delsession'])) {
    define('LOGGED_IN', FALSE);
    define('ADMIN', 'FALSE');
} elseif($_SESSION['controlpanel'] === 1) {
    define('LOGGED_IN', TRUE);
    define('ADMIN', TRUE);
} elseif($_SESSION['controlpanel'] == BLOGID) {
    define('LOGGED_IN', TRUE);
    define('ADMIN', FALSE);
} else {
    define('LOGGED_IN', FALSE);
    define('ADMIN', FALSE);
}

if(!defined("NO_ACTION"))
{
    /*
    ** Let's light this candle!
    */

    // new URL scheme hax
    path_parse_url();

    // control panel
    foreach(array_keys($_GET) as $key)
    {
        if(preg_match("/^controlpanel:?/", $key))
        {
            echo controlpanel();
            exit;
        }
    }

    if(isset($_GET['register']))
    {
        require("register.php");
        exit;
    }

    // special front page
    if(BLOGID == 1 && !isset($_GET['login'])) // allow admin controlpanel login from front page
    {
        require("front_page.php");
        exit;
    }

    if(isset($_GET['util_js']))
    {
        header("Content-Type: text/javascript");
        readfile("cwb/util.js");
        exit;
    }

    if(!is_numeric($_GET['page']))
        $_GET['page'] = 1;

    if(is_numeric($_GET['tid']))
    {
        $body = show_topic($_GET['tid'], $_GET['page']);
    } elseif(is_numeric($_GET['month']) && is_numeric($_GET['year'])) {
        $body = show_month((int) $_GET['month'], (int) $_GET['year'], $_GET['page']);
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

/*
    if($blogdata[$who]['status'] == "closed")
    {
        $q = $db->issue_query("SELECT tid FROM topics WHERE blogid = '".BLOGID."' ORDER BY tid DESC LIMIT 1");
        $farewell_tid = $db->fetch_var($q);
        $out = str_replace("<!-- #CWB_BODY# -->", show_topic($farewell_tid,0), $out);
    }
*/

    $out = str_replace("<!-- #CWB_BODY# -->", $body, $out);

    $db->disconnect();

    if($TITLE == "")
        $TITLE = $BLOGINFO['title'];
    $out = str_replace("%{".UNIQ."titletag}", $TITLE, $out);
    $out = str_replace("%{".UNIQ."querycount}", querycount(), $out);
    $out = str_replace("%{".UNIQ."runtime}", runtime(), $out);

    echo $out;
}

// and we're out. :)

?>
