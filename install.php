<?php

/*
** Installer Script
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

switch($_GET['stage']) {

default:

?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body>
<h1 class="main-title">CodewiseBlog</h1>
<table>
<tr>
<td>
    <p><b>Welcome to the CodewiseBlog installer</b></p>
    <p>This installer is divided up into multiple stages, described below. You will want to have the neccessary information on hand before you start.</p>
    <ol>
        <li><b>Stage 1:</b>
            <blockquote>
                Filesystem and permissions
            </blockquote>
        </li>
        <li>
            <b>Stage 2:</b>
            <blockquote>
                Database settings
            </blockquote>
        </li>
        <li>
            <b>Stage 3:</b>
            <blockquote>
                CodewiseBlog main settings
            </blockquote>
        </li>
        <li>
            <b>Stage 4:</b>
            <blockquote>
                Set Up A User
            </blockquote>
        </li>
        <li>
            <b>Stage 5:</b>
            <blockquote>
                Extra Webserver Configuration
            </blockquote>
        </li>
    <ol>
</td>
</tr>
<tr>
<td align="center">
    <a href="install.php?stage=1"><span style="font-size:x-large">Start Stage 1</span></a>
</td>
</tr>
</table>
</body>
</html>
<?php

    break;

case 1:

    require("file_put_contents.php");

    if(isset($_POST['submit'])) {
        $file = "<?php\n\ndefine('FSPATH', '".$_POST['fspath']."');\n\n?>\n";

        file_put_contents("settings.php", $file);

        header("Location: install.php?stage=2");
    } else {

        if(isset($_POST['fspath'])) {
            $fspath = preg_replace("%/+$%", "/", $_POST['fspath']);
        } else {
            $fspath = dirname($_SERVER['SCRIPT_FILENAME']) . "/";
        }

        if(!is_dir($fspath)) {
            $perms = "<b>path does not exist</b>";
        } else {
            $writable = is_writable("$fspath/settings.php");
            $writable_install = is_writable("$fspath/install.php");
            $writable_htaccess = is_writable("$fspath/.htaccess");
        }

?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body>
<h1 class="main-title">CodewiseBlog</h1>
<form action="install.php?stage=1" method="post">
<table style="border:none">
<tr>
<td>
    <table>
    <tr>
        <td>Path to CodewiseBlog:</td>
        <td><input type="text" size="50" name="fspath" value="<?=$fspath?>" /></td>
        <td>Autodetected; you probably don't need to change this.</td>
    </tr>
    <tr>
        <td>settings.php is writable?</td>
        <td><?=($writable ? "yes" : "no")?></td>
        <td>settings.php needs to be writable before we continue
    </tr>
    <tr>
        <td>install.php is writable?</td>
        <td><?=($writable_install ? "yes" : "no")?></td>
        <td>install.php needs to be writable or the installer won't be able to disable itself when the install is done and you'll have to do so manually.</td>
    </tr>
    <tr>
        <td>.htaccess is writable?</td>
        <td><?=($writable_htaccess ? "yes" : "no")?></td>
        <td>If you're using Apache, .htaccess needs to be writable or the installer won't be able to finish the installation and you'll have to edit the file manually.</td>
    </tr>
    </table>
</td>
</tr>
<tr>
<td align="center">
    <input type="submit" value="Refresh" />
    <input type="submit" name="submit" value="Continue to Stage 2" <?=($writable ? "" : "disabled=\"disabled\" ")?>/>
</td>
</tr>
</table>
</body>
</html>

<?php

    }

    break;

case 2:

    require("settings.php");
    require("file_put_contents.php");

    chdir(FSPATH);

    if(isset($_POST['submit'])) {

        require("l1_mysql.php");

        $db = new L1_MySQL($_POST['sql_host'], $_POST['sql_user'], $_POST['sql_pass'], $_POST['sql_db']);

        $q = $db->issue_query("SHOW TABLES");
        $tables = $db->fetch_column($q);

        if(!isset($_GET['force']) && count($tables = array_intersect(array("blogs","replies","shoutbox","skin","subscriptions","topics"), $tables)) > 0)
        {
            foreach($_POST as $name => $value)
            {
                $hiddens .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
            }

            $table_list = "<code>" . implode("</code>, <code>", $tables) . "</code>";
?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body>
<h1 class="main-title">CodewiseBlog</h1>
<form action="install.php?stage=2&amp;force" method="post">
<?=$hiddens?>
<div style="font-size:xx-large; color:red">WARNING:</div>
<br />
<b>There appears to be a CodewiseBlog installation already present in the specified database.<br />
Continuing farther will irrevocably destroy this data!</b><br />
<br />
The following tables will be erased: <?=$table_list?><br />
<br />
<input type="submit" value="Continue and Destroy Data?" />
</body>
</html>
<?php
            exit;
        }

        $sql = file_get_contents(FSPATH . "structure.sql");
        foreach(explode(";", $sql) as $statement)
        {
            if(($statement = trim($statement)) != "")
            $db->issue_query($statement);
        }

        $db->insert("skin", array("blogid" => "1"));

        $dir = opendir(FSPATH . "skin_blueEye");
        while($file = readdir($dir)) {
            if(substr($file, 0, 1) == "." || substr($file, -1, 1) == "~" || is_dir(FSPATH."skin_blueEye/$file"))
                continue;
            $cont = file_get_contents(FSPATH."skin_blueEye/$file");
            if(preg_match("/\\.css$/", $file)) {
                $section = "css";
            } else {
                $section = preg_replace("/\\.html$/", "", $file);
            }
            $db->issue_query("ALTER TABLE skin ADD ".$db->prepare_value($section,FALSE)." TEXT");
            $db->update("skin", array($section => $cont), array("blogid" => 1));
        }

        $file = file_get_contents("settings.php");
        $file = substr($file, 0, -4);
        $file .=
"define('SQL_HOST', '{$_POST['sql_host']}');
define('SQL_USER', '{$_POST['sql_user']}');
define('SQL_PASS', '{$_POST['sql_pass']}');
define('SQL_DB', '{$_POST['sql_db']}');
define('SQL_ADMIN_EMAIL', '{$_POST['sql_admin_email']}');

?>
";
        file_put_contents("settings.php", $file);

        header("Location: install.php?stage=3");
    } else {

?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body>
<h1 class="main-title">CodewiseBlog</h1>
<form action="install.php?stage=2" method="post">
<table style="border:none">
<tr>
<td>
    <table>
    <tr>
        <td>MySQL hostname:</td>
        <td><input type="text" size="50" name="sql_host" value="localhost" /></td>
        <td>chances are this should be kept as localhost</td>
    </tr>
    <tr>
        <td>MySQL username:</td>
        <td><input type="text" size="50" name="sql_user" value="username" /></td>
        <td>ask your host for the following settings</td>
    </tr>
    <tr>
        <td>MySQL password:</td>
        <td><input type="text" size="50" name="sql_pass" value="password" /></td>
        <td></td>
    </tr>
    <tr>
        <td>MySQL database:</td>
        <td><input type="text" size="50" name="sql_db" value="codewiseblog" /></td>
        <td><b>WARNING:</b> any existing CodewiseBlog database here will be destroyed</td>
    </tr>
    <tr>
        <td>Admin Email:</td>
        <td><input type="text" size="50" name="sql_admin_email" value="" /></td>
        <td>MySQL errors will be emailed to this address</td>
    </tr>
    </table>
</td>
</tr>
<tr>
<td align="center">
    <input type="submit" name="submit" value="Install Database Tables and Continue to Stage 3" />
</td>
</tr>
</table>
</form>
</body>
</html>

<?php

    }

    break;

case 3:

    require("settings.php");
    require("file_put_contents.php");

    chdir(FSPATH);

    if(isset($_POST['submit'])) {
        $allowed_tags = "\$ALLOWED_TAGS = array\n(\n";
        $lines = explode("\n", $_POST['allowed_tags']);
        foreach($lines as $line)
        {
            $tagname = substr($line, 1, strpos($line, ":") - 2);

            $attribs = substr($line, strpos($line, ":") + 2);
            $attribs_array = explode(", ", $attribs);

            foreach($attribs_array as $i => $text)
                $attribs_array[$i] = trim($text);

            if($tagname == "")
                continue;
            elseif(count($attribs_array) == 0 || empty($attribs_array[0]))
                $allowed_tags .= "    '$tagname' => array(),\n";
            else
                $allowed_tags .= "    '$tagname' => array('" . implode("', '", $attribs_array) . "'),\n";
        }
        $allowed_tags .= ");";

        $file = file_get_contents("settings.php");
        $file = substr($file, 0, -4);
        $file .=
"define('SUBDOMAIN_MODE', {$_POST['subdomain_mode']});
define('BASE_DOMAIN', '{$_POST['base_domain']}');
define('DEFAULT_SUBDOMAIN', '{$_POST['default_subdomain']}');
define('INSTALLED_PATH', '{$_POST['installed_path']}');
define('CUSTOM_URL_ENABLED', {$_POST['custom_url_enabled']});
define('TOPICS_PER_PAGE', '{$_POST['topics_per_page']}');
define('POSTS_PER_PAGE', '{$_POST['posts_per_page']}');
define('SHOUTS_PER_PAGE', '{$_POST['shouts_per_page']}');
define('DATE_FORMAT', '{$_POST['date_format']}');
define('ANONYMOUS_NAME', '{$_POST['anonymous_name']}');

$allowed_tags

?>
";
        file_put_contents("settings.php", $file);

        header("Location: install.php?stage=4");
    } else {

        preg_match("/^(([^.]+)\\.)?([^.]+\\.[^.]+)$/", $_SERVER['HTTP_HOST'], $match);

        $default_subdomain = $match[2];
        $base_domain = $match[3];
        $full_hostname = $match[0];

        $installed_path = dirname($_SERVER['SCRIPT_NAME']) . "/";
?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body onload="loadElements()">
<h1 class="main-title">CodewiseBlog</h1>
<form action="install.php?stage=3" method="post">
<script type="text/javascript" language="JavaScript">
var subdomainMode;
var baseDomain;
var defaultSubdomain;
var installedPath;
var exampleMainUrl;
var exampleUserUrl;
var username = "jrandomuser";

function loadElements()
{
    subdomainMode       = document.getElementById("subdomainMode");
    baseDomain          = document.getElementById("baseDomain");
    defaultSubdomain    = document.getElementById("defaultSubdomain");
    installedPath       = document.getElementById("installedPath");
    exampleMainUrl      = document.getElementById("exampleMainUrl");
    exampleUserUrl      = document.getElementById("exampleUserUrl");

    dataChanged();
}

function dataChanged()
{
    if(defaultSubdomain.value == "") {
        exampleMainUrl.value = "http://" + baseDomain.value + installedPath.value;
    } else {
        exampleMainUrl.value = "http://" + defaultSubdomain.value + "." + baseDomain.value + installedPath.value;
    }

    if(subdomainMode.value == "TRUE") {
        exampleUserUrl.value = "http://" + username + "." + baseDomain.value + installedPath.value;
    } else {
        exampleUserUrl.value = "http://" + baseDomain.value + installedPath.value + username;
    }
}
</script>
<table style="border:none">
<tr>
<td>
    <table>
    <tr>
        <td>Subdomain Mode:</td>
        <td><select name="subdomain_mode" onchange="dataChanged()" id="subdomainMode"><option value="TRUE">Enabled</option><option value="FALSE">Disabled</option></select></td>
    </tr>
    <tr>
        <td>CodewiseBlog Domain:</td>
        <td><input type="text" size="50" name="base_domain" value="<?=$base_domain?>" onchange="dataChanged()" id="baseDomain" /></td>
    </tr>
    <tr>
        <td>Default Subdomain:</td>
        <td><input type="text" size="50" name="default_subdomain" value="<?=$default_subdomain?>" onchange="dataChanged()" id="defaultSubdomain" /></td>
    </tr>
    <tr>
        <td>CodewiseBlog Path:</td>
        <td><input type="text" size="50" name="installed_path" value="<?=$installed_path?>" onchange="dataChanged()" id="installedPath" /></td>
    </tr>
    <tr>
        <td>Main CodewiseBlog URL:</td>
        <td><input type="text" size="50" readonly="readonly" value="" id="exampleMainUrl" /></td>
        <td>This is the URL the CodewiseBlog front page will be located at under the above configuration settings.</td>
    </tr>
    <tr>
        <td>CodewiseBlog User URL:</td>
        <td><input type="text" size="50" readonly="readonly" value="" id="exampleUserUrl" /></td>
        <td>If you have a user named 'jrandomuser', their blog will be located at this URL under the above configuration settings.</td>
    </tr>
    <tr>
        <td>Custom URLs Enabled:</td>
        <td><select name="custom_url_enabled"><option value="TRUE">Enabled</option><option value="FALSE">Disabled</option></select></td>
        <td>If enabled, users can set up redirects to their blog from other websites and have the canonical URL reflect that.</td>
    </tr>
    <tr>
        <td><hr /></td>
        <td><hr /></td>
        <td><hr /></td>
    </tr>
    <tr>
        <td>Blog Posts Per Page:</td>
        <td><input type="text" size="3" name="topics_per_page" value="5" /></td>
        <td>How many posts will be diplayed on one page. It's best to keep this value small</td>
    </tr>
    <tr>
        <td>Comments Per Page:</td>
        <td><input type="text" size="3" name="posts_per_page" value="10" /></td>
        <td>How many comments will be displayed on one page.</td>
    </tr>
    <tr>
        <td>Shoutbox Entries Per Page:</td>
        <td><input type="text" size="3" name="shouts_per_page" value="10" /></td>
        <td>The shoutbox will only show this many shouts at a time.</td>
    </tr>
    <tr>
        <td>Date Format:</td>
        <td><input type="text" size="50" name="date_format" value="F jS, Y \a\t g:i A" /></td>
        <td>See <a href="http://www.php.net/date#AEN25031">this table</a> for help.</td>
    </tr>
    <tr>
        <td>Anonymous Name:</td>
        <td><input type="text" size="50" name="anonymous_name" value="Anonymous" /></td>
        <td>Anonymous comments will be made under this name.</td>
    </tr>
    <tr>
        <td>HTML Tags Allowed In Comments:</td>
        <td><textarea name="allowed_tags" rows="25" cols="50"><b>:
<i>: 
<p>: 
<br>: 
<a>: href, name
<ol>: 
<ul>: 
<li>: 
<em>: 
<strong>: 
<strike>: 
<font>: color, size
<tt>: 
<blockquote>: 
<sub>: 
<sup>: 
</textarea></td>
        <td>
            Each line is a HTML tag and a colon, followed by HTML attributes allowed.<br />
            <br />
            For example, the line
                <blockquote><code>&lt;a>: href. name</code></blockquote>
            allows the text
                <blockquote><code>&lt;a href="http://foo.com/bar" name="blah">text&lt;/a></code></blockquote>
            to appear in a comment, but the text
                <blockquote><code>&lt;a style="font-size:xx-large" href="http://somesite.com/">OMG WTF BBQ&lt;/a></code></blockquote>
            would have the <code>style="font-size:xx-large"</code> part removed because it's not in the list of allowed attributes.<br />
            <br />
            If no attributes are specified, none are allowed.
        </td>
    </table>
</td>
</tr>
<tr>
<td align="center">
    <input type="submit" name="submit" value="Continue to Stage 4" />
</td>
</tr>
</table>
</form>
</body>
</html>
<?php

    }

    break;

case 4:

    require("settings.php");

    chdir(FSPATH);

    if(isset($_POST['submit'])) {

        $empty = array();

        if($_POST['username'] == "")
            $empty[] = "Username";
        if($_POST['title'] == "")
            $empty[] = "Blog Name";
        if($_POST['realname'] == "")
            $empty[] = "Your Name";
        if($_POST['email'] == "")
            $empty[] = "Your Email Address";
        if($_POST['password'] == "")
            $empty[] = "Password";

        if(count($empty) > 0)
        {
            die("<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel=\"stylesheet\" href=\"skin_blueEye/blueEye.css\" />
</head>
<body>
The following required fields were left empty.
You must go back and fill them in before installation can continue.
</body>
</html>");
        }

        if(!preg_match("/^[a-z0-9-]+$/", $_POST['username']))
        {
            die("<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel=\"stylesheet\" href=\"skin_blueEye/blueEye.css\" />
</head>
<body>
Your username may only contain lower-case letters, numbers, and dashes.
</body>
</html>");
        }

        if($_POST['username'] == "root")
        {
            die("<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel=\"stylesheet\" href=\"skin_blueEye/blueEye.css\" />
</head>
<body>
Sorry, the username 'root' is reserved for the CodewiseBlog system.
Go back and change your username to something else.
</body>
</html>");
        }

        require("l1_mysql.php");

        $db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

        $root_blog = array(
            "blogid"     => 1,
            "name"       => "root",
            "email"      => $_POST['email'],
            "realname"   => NULL,
            "birthday"   => NULL,
            "location"   => NULL,
            "interests"  => NULL,
            "links"      => NULL,
            "photo"      => NULL,
            "homepage"   => NULL,
            "title"      => "CodewiseBlog",
            "password"   => md5($_POST['password']),
            "joindate"   => 0,
            "custom_url" => NULL,
        );

        $user_blog = array(
            "blogid"     => 2,
            "name"       => $_POST['username'],
            "email"      => $_POST['email'],
            "realname"   => $_POST['realname'],
            "birthday"   => ($_POST['birthday'] == "" ? NULL : $_POST['birthday']),
            "location"   => ($_POST['location'] == "" ? NULL : $_POST['location']),
            "interests"  => ($_POST['interests'] == "" ? NULL : $_POST['interests']),
            "links"      => ($_POST['links'] == "" ? NULL : $_POST['links']),
            "photo"      => ($_POST['photo'] == "" ? NULL : $_POST['photo']),
            "homepage"   => ($_POST['homepage'] == "" ? NULL : $_POST['homepage']),
            "title"      => $_POST['title'],
            "password"   => md5($_POST['password']),
            "joindate"   => time(),
            "custom_url" => NULL,
        );

        $db->insert("blogs", $root_blog);
        $db->insert("blogs", $user_blog);
        $db->insert("skin", array("blogid" => 2));

        header("Location: install.php?stage=5");

    } else {

?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body>
<h1 class="main-title">CodewiseBlog</h1>
<form action="install.php?stage=4" method="post">
<table style="border:none">
<tr>
<td>
    <table>
    <tr>
        <td>Username:</td>
        <td><input type="text" size="50" name="username" value="" /></td>
        <td>Your username may only contain lower-case letters, numbers, and dashes.</td>
    </tr>
    <tr>
        <td>Blog Name:</td>
        <td><input type="text" size="50" name="title" value="" /></td>
        <td>This will be shown at the top of your blog and will identify it in other various places.</td>
    </tr>
    <tr>
        <td>Your Name:</td>
        <td><input type="text" size="50" name="realname" value="" /></td>
        <td>Your real name.</td>
    </tr>
    <tr>
        <td>Your Email Address:</td>
        <td><input type="text" size="50" name="email" value="" /></td>
        <td>Notification of new comments and other things will be sent to this address.</td>
    </tr>
    <tr>
        <td>Password:</td>
        <td><input type="text" size="50" name="password" value="" /></td>
        <td>This will be your password for the User Control Panel and Admin Control Panel</td>
    </tr>
    <tr>
        <td><hr /></td>
        <td><hr /></td>
        <td><hr /></td>
    </tr>
    <tr>
        <td>The following fields are optional.</td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Homepage:</td>
        <td><input type="text" size="50" name="homepage" value="" /></td>
        <td>A link to your homepage or some other website of yours.</td>
    </tr>
    <tr>
        <td>Location:</td>
        <td><input type="text" size="50" name="location" value="" /></td>
        <td>Some short text describing where you reside.</td>
    </tr>
    <tr>
        <td>Birthday:</td>
        <td><input type="text" size="10" name="birthday" value="" /></td>
        <td>mm/dd/yyyy</td>
    </tr>
    <tr>
        <td>Photo:</td>
        <td><input type="text" size="50" name="photo" value="" /></td>
        <td>URL of a photo of you or some other avatar. The image should be fairly small, not more than 200x200 for best results.</td>
    </tr>
    <tr>
        <td>Links:</td>
        <td><textarea name="links" rows="7" cols="80"></textarea></td>
        <td>Some links to other websites. All HTML permitted.</td>
    </tr>
    <tr>
        <td>Bio / Interests:</td>
        <td><textarea name="interests" rows="7" cols="80"></textarea></td>
        <td>A short biography or description of your interests.</td>
    </tr>
    </table>
</td>
</tr>
<tr>
<td align="center">
    <input type="submit" name="submit" value="Create User and Continue to Stage 5" />
</td>
</tr>
</table>
</form>
</body>
</html>
<?php

    }

    break;

case 5:

    require("settings.php");
    require("file_put_contents.php");

    chdir(FSPATH);

    if(isset($_POST['submit'])) {

        $file = "<?php die(\"<html><body>The installer has been disabled.</body></html>\"); ?>\n" . file_get_contents("install.php");
        file_put_contents("install.php", $file);

        $htaccess = 
"Options +Indexes +FollowSymLinks
RewriteEngine on
RewriteRule !(CHANGELOG|favicon\.ico|rdf\.php(/.*)?|stylesheet\.php|skin_importer\.php|install\.php)$ cwbmulti.php";
        file_put_contents(".htaccess", $htaccess);

?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body>
<h1 class="main-title">CodewiseBlog</h1>
<b>The installation is now complete.</b>
<br /><br />
<a href="<?php echo INSTALLED_PATH; ?>">Welcome to your new CodewiseBlog</a>
</body>
</html>
<?php

    } else {

?>
<html>
<head>
<title>CodewiseBlog Installer</title>
<link rel="stylesheet" href="skin_blueEye/blueEye.css" />
</head>
<body>
<h1 class="main-title">CodewiseBlog</h1>
<table>
<tr>
<td>
    <p><b>The installation is now nearly complete.</b></p>
    <p>All that is left is to configure your server properly so that it works with CodewiseBlog.
        Unfortunately, this installer script cannot perform these steps - you or your host need to do them by hand.
    </p>
    <p>The steps neccessary to complete installation are as follows:
        <ol>
            <li><b>Disable installer</b>
                <blockquote>
                    The installer will disable itself upon submitting this page.
                    It can be re-enabled by editing the install.php file and deleting the first line.
                </blockquote>
            </li>
            <li><b>Webserver Configuration</b>
                <blockquote>
                    Apache: mod_rewrite must be installed and enabled.
                    You must be allowed by your host to use .htaccess files.
                    (by setting the directive '<code>AllowOverride All</code>' in Apache's configuration files)
                </blockquote>
            </li>
            <li><b>Domain Name Configuration</b>
                <blockquote>
                    If you enabled Subdomain Mode in Stage 3, your domain must be set up with a wildcard CNAME to your server.
                    For example, if your domain name is 'example.com', and CodewiseBlog is installed at 'blogs.example.com',
                        the line '<code>*.blogs.example.com. IN CNAME www.example.com.</code>' must be in your zone file.
                </blockquote>
            </li>
        </ol>
    </p>
</td>
</tr>
<tr>
<td align="center">
    <form action="install.php?stage=5" method="post">
    <input type="submit" name="submit" value="Disable Installer and Finish Installation" />
    </form>
</td>
</tr>
</table>
</body>
</html>
<?php

    }

    break;

}

?>
