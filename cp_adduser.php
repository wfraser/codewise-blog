<?php

/*
** Control Panel :: Add User
** for CodewiseBlog Multi-User
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2005-2008 Codewise.org
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

$current = "adduser";

$blogid = $db->fetch_var($db->issue_query("SELECT blogid FROM blogs ORDER BY blogid DESC LIMIT 1"));
$blogid++;

if(BLOGID != 1)
{
    $body = skinvoodoo("error","error",array("message"=>"You do not have permission to access this area of the control panel."));
} elseif(empty($_POST)) {

    $body = skinvoodoo(
        "controlpanel_adduser", "",
        array(
            "posturl" => INDEX_URL . "?controlpanel:adduser",
            "blogid"  => $blogid,
        )
    );

} else {

    $db->insert("blogs", array(
        "blogid" => $blogid,
        "name" => $_POST['name'],
        "email" => $_POST['email'],
        "title" => $_POST['title'],
        "password" => md5($_POST['password']),
        "joindate" => time(),
        "skinid" => DEFAULT_SKINID,
    ));

    $db->insert("skin", array("blogid" => $blogid));

    if(SUBDOMAIN_MODE)
        $newuser = "http://" . $_POST['name'] . "." . BASE_DOMAIN . INSTALLED_PATH;
    else
        $newuser = "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . $_POST['name'];

    $body = "User created. Their page is located <a href=\"" . $newuser . "\">here</a>";

}
