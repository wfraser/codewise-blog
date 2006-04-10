<?php

/*
** Control Panel :: User Registration
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

/* all the checks that need to be performed.
** If any fail, the appropriate $NOTIFY message is set and it returns FALSE,
** causing the if/elseif/else block to go to the else, which is to redisplay
** the registration form (with the $NOTIFY message of course). */
function check_post()
{
    if($_POST['name'] == "")
    {
        $GLOBALS['NOTIFY'] = "Username must not be empty!";
        return FALSE;
    }

    if(strlen($_POST['name']) > 32)
    {
        $GLOBALS['NOTIFY'] = "Username is too long. It must be 32 characters or less.";
        return FALSE;
    }

    if(!preg_match('/^[a-z0-9][a-z0-9-]*$/', $_POST['name'] = strtolower($_POST['name'])))
    {
        $GLOBALS['NOTIFY'] = "Username must contain only lower case letters, numbers, and dashes and cannot start with a dash.";
        return FALSE;
    }

    if($_POST['email'] == "")
    {
        $GLOBALS['NOTIFY'] = "Email address must not be empty!";
        return FALSE;
    }

    if(strlen($_POST['email']) > 64)
    {
        $GLOBALS['NOTIFY'] = "Email address is too long. It must be 64 characters or less.";
        return FALSE;
    }

    if(!preg_match('/^[a-zA-Z0-9-+_.]+@[a-zA-Z0-9-+_.]+$/', $_POST['email']))
    {
        $GLOBALS['NOTIFY'] = "Email address is invalid.";
        return FALSE;
    }

    if($_POST['title'] == "")
    {
        $GLOBALS['NOTIFY'] = "Blog title must not be empty!";
        return FALSE;
    }

    if(strlen($_POST['title']) > 64)
    {
        $GLOBALS['NOTIFY'] = "Blog title is too long. It must be 64 characters or less.";
        return FALSE;
    }

    $_POST['title'] = str_replace(
        array("<",    " ",      "\""),
        array("&lt;", "&nbsp;", "&quot;"),
        $_POST['title']
    );

    if($_POST['birthday'] != "" && !preg_match('/^(0?[1-9]|1[012])\/(0[1-9]|[12][0-9]|3[01])\/(19|20)[0-9]{2}$/', $_POST['birthday']))
    {
        $GLOBALS['NOTIFY'] = "Invalid Birthday. Format is mm/dd/yyyy";
        var_dump($_POST['birthday']);
        return FALSE;
    }

    if($_POST['photo'] != "" && !preg_match('#^http://([a-zA-Z0-9-_]+\.)+[a-z]+/#', $_POST['photo']))
    {
        $GLOBALS['NOTIFY'] = "Photo/Avatar URL is invalid. Only http:// URLs are allowed.";
        return FALSE;
    }

    if($_POST['realname'] == "")
        $_POST['realname'] = NULL;
    if($_POST['birthday'] == "")
        $_POST['birthday'] = NULL;
    if($_POST['location'] == "")
        $_POST['location'] = NULL;
    if($_POST['photo'] == "")
        $_POST['photo'] = NULL;
    if($_POST['homepage'] == "")
        $_POST['homepage'] = NULL;
    if($_POST['interests'] == "")
        $_POST['interests'] = NULL;
    if($_POST['links'] == "")
        $_POST['links'] = NULL;

    return TRUE;
}

// The existance of the TERMS file is what allows registrations
if(!file_exists(FSPATH . "/TERMS"))
{
    die("Open registration has been disabled.");
}

// confirming via emailed link
if(isset($_GET['confirm']) && isset($_GET['username']) && isset($_GET['code']))
{
    $q = $db->issue_query("SELECT password FROM blogs WHERE name = ".$db->prepare_value($_GET['username']));
    $hash = $db->fetch_var($q);

    if($hash === $_GET['code'])
    {
        $db->update("blogs", array("status" => "active"), array("name" => $_GET['username']));

        if(SUBDOMAIN_MODE)
            $user_url = "http://" . $_GET['username'] . "." . BASE_DOMAIN . INSTALLED_PATH;
        else
            $user_url = "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . $_GET['username'];

        $body = skinvoodoo("register", "change_password_form", array(
            "posturl" => INDEX_URL . "?register&amp;change_password",
            "username" => $_GET['username'],
            "code" => $_GET['code'],
        ));
    } else {
        $body = skinvoodoo("register", "confirm_failure");
    }
// final step, setting the password
} elseif($_POST && isset($_GET['change_password'])) {
    if($_POST['password1'] != $_POST['password2'])
    {
        $GLOBALS['NOTIFY'] = "Passwords do not match.";
        $body = skinvoodoo("register", "change_password_form", array("posturl" => INDEX_URL . "?register&amp;change_password&amp;code=".$_POST['code']));
    } elseif($_POST['code'] !== $db->fetch_var( $db->issue_query("SELECT password FROM blogs WHERE name = ".$db->prepare_value($_POST['username'])) )) {
        $GLOBALS['NOTIFY'] = "Wrong authentication code.";
    } else {
        $db->update("blogs", array("password" => md5($_POST['password1'])), array("name" => $_POST['username']));

        if(SUBDOMAIN_MODE)
            $user_url = "http://" . $_POST['username'] . "." . BASE_DOMAIN . INSTALLED_PATH;
        else
            $user_url = "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . $_POST['username'];

        $body = skinvoodoo("register", "password_changed", array("user_url" => $user_url));
    }
// submitting the registration form
} elseif($_POST && check_post()) {

    $password = md5(uniqid(mt_rand(), TRUE));

    $db->insert("blogs", array(
        //"blogid" => 0, // ;et it autoincrement
        "name" => $_POST['name'],
        "email" => $_POST['email'],
        "title" => $_POST['title'],
        "realname" => $_POST['realname'],
        "birthday" => $_POST['birthday'],
        "location" => $_POST['location'],
        "photo" => $_POST['photo'],
        "homepage" => $_POST['homepage'],
        "interests" => $_POST['interests'],
        "links" => $_POST['links'],
        "password" => $password,
        "joindate" => time(),
        "skinid" => "00000000000000000000000000000000",
        "status" => "validating",
    ));

    $confirm_address = "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "?register&confirm&username=" . $_POST['name'] . "&code=" . $password;

    $msg = "Your ".SITE_TITLE." blog account is ready to use.

Go to the following location to confirm your account.
$confirm_address
Your blog will be up and running in no time!

Thanks for registering!
- The Management";

    mail($_POST['email'], "Your ".SITE_TITLE." blog account is ready.", $msg, "From: ".DEFAULT_SUBDOMAIN.BASE_DOMAIN." <nobody@".BASE_DOMAIN.">");

    $body = skinvoodoo("register", "success");

} else {

    $body = skinvoodoo("register", "form", array(
        "posturl"   => INDEX_URL . "?register",
        "terms"     => INDEX_URL . "TERMS",
        "name"      => $_POST['name'],
        "email"     => $_POST['email'],
        "title"     => $_POST['title'],
        "realname"  => $_POST['realname'],
        "birthday"  => $_POST['birthday'],
        "location"  => $_POST['location'],
        "photo"     => $_POST['photo'],
        "homepage"  => $_POST['homepage'],
        "interests" => $_POST['interests'],
        "links"     => $_POST['links'],
    ));

}

$out = skinvoodoo("register", "");

$out = str_replace("<!-- #CWB_BODY# -->", $body, $out);

$out = str_replace("%{".UNIQ."querycount}", querycount(), $out);
$out = str_replace("%{".UNIQ."runtime}", runtime(), $out);

echo $out;

?>
