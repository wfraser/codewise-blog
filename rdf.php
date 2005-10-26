<?php

/*
** RDF Feed Generator
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2005 Codewise.org
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

header("Content-Type: text/xml");

echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";

require("settings.php");

chdir(FSPATH);

/*
** Set up environment
*/

// deal with errors properly
ini_set("track_errors", true);
error_reporting(E_ALL ^ E_NOTICE);

// clean out this crap - it's never used
unset($HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_ENV_VARS, $HTTP_POST_FILES, $HTTP_SESSION_VARS);

require("misc.php");

require("l1_mysql.php");
$db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS);

// custom error handler to mail the admin as well as print any errors
$db->error_callback = $db->warning_callback = "mail_db_error";

$db->database(SQL_DB);

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
    die( "CodewiseBlog :: Invalid User \"$who\"" );
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

?>

<rdf:RDF
        xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
        xmlns="http://purl.org/rss/1.0/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/"
        xmlns:syn="http://purl.org/rss/1.0/modules/syndication/"
>

    <channel rdf:about="<?php echo INDEX_URL; ?>">
        <title><?php echo htmlspecialchars($BLOGINFO['title']); ?></title>
        <link><?php echo INDEX_URL; ?></link>
        <description><?php echo htmlspecialchars($BLOGINFO['title']); ?> :: by <?php echo htmlspecialchars($BLOGINFO['realname'] == NULL ? BLOGNAME : $BLOGINFO['realname']); ?></description>
        <dc:language>en-us</dc:language>
        <dc:rights>Copyright <?php echo $copyright_years; ?> - <?php echo htmlspecialchars($BLOGINFO['realname'] == NULL ? BLOGNAME : $BLOGINFO['realname']); ?></dc:rights>
        <dc:date>2005-10-16T02:00:01Z</dc:date>
        <dc:creator><?php echo htmlspecialchars($BLOGINFO['realname'] == NULL ? BLOGNAME : $BLOGINFO['realname']); ?></dc:creator>
        <items>
            <rdf:Seq>

<?php

if(isset($_GET['all_one_page']))
    $limit = "";
else
    $limit = "LIMIT 10"; // rdf spec says it must be 10
$q = $db->issue_query("SELECT tid,title,text FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp $limit");
$data = $db->fetch_all($q);

foreach($data as $row)
{
    echo "                <rdf:li rdf:resource=\"" . INDEX_URL . "?tid=" . $row['tid'] . "\" />\n";
}

?>
            </rdf:Seq>
        </items>
    </channel>

<?php foreach($data as $row)
{
    echo "    <item rdf:about=\"" . INDEX_URL . "?tid=" . $row['tid'] . "\">
        <title>" . $row['title'] . "</title>
        <link>" . INDEX_URL . "?tid=" . $row['tid'] . "</link>
        <description>" . htmlspecialchars(textprocess($row['text'])) . "</description>
        <dc:date>" . iso8601_date($row['timestamp']) . "</dc:date>
    </item>";
}

?>

</rdf:RDF>