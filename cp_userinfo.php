<?php

/*
** Control Panel :: User Info
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

$current = "userinfo";

if($_POST)
{
    if(isset($_POST['chpasswd']))
    {
        if($_POST['password1'] != $_POST['password2'])
        {
            $GLOBALS['NOTIFY'] = "Passwords do not match";
        } else {
            $data = array(
                "password" => md5($_POST['password1']),
            );

            $db->update("blogs", $data, array("blogid" => BLOGID));

            $GLOBALS['NOTIFY'] = "Password updated successfully.";
        }
    } elseif($_POST['email'] == "") {
        $GLOBALS['NOTIFY'] = "Email address must not be empty";
    } elseif($_POST['title'] == "") {
        $GLOBALS['NOTIFY'] = "Site Title must not be empty";
    } else {

        $data = array(
            "email" => $_POST['email'],
            "realname" => $_POST['realname'] == "" ? NULL : htmlspecialchars($_POST['realname']),
            "birthday" => $_POST['birthday'] == "" ? NULL : $_POST['birthday'],
            "location" => $_POST['location'] == "" ? NULL : htmlspecialchars($_POST['location']),
            "interests" => $_POST['interests'] == "" ? NULL : $_POST['interests'],
            "links" => $_POST['links'] == "" ? NULL : $_POST['links'],
            "photo" => $_POST['photo'] == "" ? NULL : htmlspecialchars($_POST['photo']),
            "homepage" => $_POST['homepage'] == "" ? NULL : htmlspecialchars($_POST['homepage']),
            "title" => str_replace(" ", "&nbsp;", htmlspecialchars($_POST['title'])),
            "custom_url" => $_POST['custom_url'] == "" ? NULL : $_POST['custom_url'],
        );

        $db->update("blogs", $data, array("blogid" => BLOGID));

        $GLOBALS['NOTIFY'] = "User info successfully changed";
    }
}

$q = $db->issue_query("SELECT email,realname,birthday,location,interests,links,photo,homepage,title,custom_url FROM blogs WHERE blogid = '" . BLOGID . "'");
$userinfo = $db->fetch_row($q, 0, L1SQL_ASSOC);

$data = array(
    "posturl" => INDEX_URL . "?controlpanel:userinfo",
);

$body = skinvoodoo("controlpanel_userinfo", "", array_merge($userinfo, $data));

?>