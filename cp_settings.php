<?php

/*
** Control Panel :: Settings Page
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

$current = "settings";

if(BLOGID != 1)
{
    $body = skinvoodoo("error","error",array("message"=>"You do not have permission to access this area of the control panel."));
} elseif(empty($_POST)) {
    global $ALLOWED_TAGS;

    $allowed_tags = "";
    foreach($ALLOWED_TAGS as $name => $attribs)
        $allowed_tags .= "<$name>: " . implode(", ", $attribs) . "\n";

    $body = skinvoodoo(
        "controlpanel_settings", "",
        array(
            "posturl" => INDEX_URL . "?controlpanel:settings",
            "fspath"  => FSPATH,
            "custom_url_enabled" => CUSTOM_URL_ENABLED,
            "sample_url" => SUBDOMAIN_MODE ? "http://username." . BASE_DOMAIN . INSTALLED_PATH : "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "/username",
            "main_url" => "http://" . (DEFAULT_SUBDOMAIN == "" ? "" : DEFAULT_SUBDOMAIN . "." ) . BASE_DOMAIN . INSTALLED_PATH,
            "subdomain_mode" => SUBDOMAIN_MODE,
            "base_domain" => BASE_DOMAIN,
            "installed_path" => INSTALLED_PATH,
            "default_subdomain" => DEFAULT_SUBDOMAIN,
            "site_title" => SITE_TITLE,
            "site_motto" => SITE_MOTTO,
            "topics_per_page" => TOPICS_PER_PAGE,
            "posts_per_page" => POSTS_PER_PAGE,
            "shouts_per_page" => SHOUTS_PER_PAGE,
            "date_format" => DATE_FORMAT,
            "anonymous_name" => ANONYMOUS_NAME,
            "email" => EMAIL,
            "sql_admin_email" => SQL_ADMIN_EMAIL,
            "sql_host" => SQL_HOST,
            "sql_user" => SQL_USER,
            "sql_pass" => SQL_PASS,
            "sql_db" => SQL_DB,
            "allowed_tags" => $allowed_tags,
        )
    );
} else {

    $file = file_get_contents(SETTINGS_FILE);

    $applied = array();

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)FSPATH)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(FSPATH)) . ")\\3\\s*\\);/is",
        "define('FSPATH', '" . $_POST['fspath'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "FSPATH";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)CUSTOM_URL_ENABLED)\\1,\\s*" . (CUSTOM_URL_ENABLED ? "TRUE" : "FALSE") . "\\s*\\);/is",
        "define('CUSTOM_URL_ENABLED', " . $_POST['custom_url_enabled'] . ");",
        $file);
    if($file != $filenew)
        $applied[] = "CUSTOM_URL_ENABLED";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SUBDOMAIN_MODE)\\1,\\s*" . (SUBDOMAIN_MODE ? "TRUE" : "FALSE") . "\\s*\\);/is",
        "define('SUBDOMAIN_MODE', " . $_POST['subdomain_mode'] . ");",
        $file);
    if($file != $filenew)
        $applied[] = "SUBDOMAIN_MODE";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)BASE_DOMAIN)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(BASE_DOMAIN)) . ")\\3\\s*\\);/is",
        "define('BASE_DOMAIN', '" . $_POST['base_domain'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "BASE_DOMAIN";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)INSTALLED_PATH)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(INSTALLED_PATH)) . ")\\3\\s*\\);/is",
        "define('INSTALLED_PATH', '" . $_POST['installed_path'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "INSTALLED_PATH";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)DEFAULT_SUBDOMAIN)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(DEFAULT_SUBDOMAIN)) . ")\\3\\s*\\);/is",
        "define('DEFAULT_SUBDOMAIN', '" . $_POST['default_subdomain'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "DEFAULT_SUBDOMAIN";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SITE_TITLE)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SITE_TITLE)) . ")\\3\\s*\\);/is",
        "define('SITE_TITLE', '" . $_POST['site_title'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "TOPICS_PER_PAGE";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SITE_MOTTO)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SITE_MOTTO)) . ")\\3\\s*\\);/is",
        "define('SITE_MOTTO', '" . $_POST['site_motto'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "SITE_MOTTO";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)TOPICS_PER_PAGE)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(TOPICS_PER_PAGE)) . ")\\3\\s*\\);/is",
        "define('TOPICS_PER_PAGE', '" . $_POST['topics_per_page'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "TOPICS_PER_PAGE";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)POSTS_PER_PAGE)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(POSTS_PER_PAGE)) . ")\\3\\s*\\);/is",
        "define('POSTS_PER_PAGE', '" . $_POST['posts_per_page'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "POSTS_PER_PAGE";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SHOUTS_PER_PAGE)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SHOUTS_PER_PAGE)) . ")\\3\\s*\\);/is",
        "define('SHOUTS_PER_PAGE', '" . $_POST['shouts_per_page'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "SHOUTS_PER_PAGE";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)DATE_FORMAT)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(DATE_FORMAT)) . ")\\3\\s*\\);/is",
        "define('DATE_FORMAT', '" . $_POST['date_format'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "DATE_FORMAT";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)ANONYMOUS_NAME)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(ANONYMOUS_NAME)) . ")\\3\\s*\\);/is",
        "define('ANONYMOUS_NAME', '" . $_POST['anonymous_name'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "ANONYMOUS_NAME";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)EMAIL)\\1,\\s*" . (EMAIL ? "TRUE" : "FALSE") . "\\s*\\);/is",
        "define('EMAIL', " . $_POST['email'] . ");",
        $file);
    if($file != $filenew)
        $applied[] = "EMAIL";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_ADMIN_EMAIL)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_ADMIN_EMAIL)) . ")\\3\\s*\\);/is",
        "define('SQL_ADMIN_EMAIL', '" . $_POST['sql_admin_email'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "SQL_ADMIN_EMAIL";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_HOST)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_HOST)) . ")\\3\\s*\\);/is",
        "define('SQL_HOST', '" . $_POST['sql_host'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "SQL_HOST";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_USER)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_USER)) . ")\\3\\s*\\);/is",
        "define('SQL_USER', '" . $_POST['sql_user'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "SQL_USER";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_PASS)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_PASS)) . ")\\3\\s*\\);/is",
        "define('SQL_PASS', '" . $_POST['sql_pass'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "SQL_PASS";
    $file = $filenew;

    $filenew = preg_replace(
        "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_DB)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_DB)) . ")\\3\\s*\\);/is",
        "define('SQL_DB', '" . $_POST['sql_db'] . "');",
        $file);
    if($file != $filenew)
        $applied[] = "SQL_DB";
    $file = $filenew;

    $new_php_array = "\$ALLOWED_TAGS = array\n(\n";
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
            $new_php_array .= "    '$tagname' => array(),\n";
        else
            $new_php_array .= "    '$tagname' => array('" . implode("', '", $attribs_array) . "'),\n";
    }
    $new_php_array .= ");";

    $filenew = preg_replace("/\\\$ALLOWED_TAGS = array\n\\(.*\\);/s", $new_php_array, $file);
    if($file != $filenew)
        $applied[] = "ALLOWED_TAGS";
    $file = $filenew;

    if(count($applied))
    {
        $message = "";
        foreach($applied as $configvar)
            $message .= "changed value of $configvar<br />";
    } else {
        $message = "No config values changed.";
    }

    file_put_contents(SETTINGS_FILE, $file);

    $body = skinvoodoo("error", "notify", array("message" => $message)) . "<a href=\"" . INDEX_URL . "?controlpanel:settings\">Back to Settings</a>";
}

?>