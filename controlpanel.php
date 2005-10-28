<?php

/*
** User Control Panel
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

function cplogin()
{
    global $db;

    $q = $db->issue_query("SELECT password FROM blogs WHERE blogid = '" . BLOGID . "'");
    $hash = $db->fetch_var($q);

    if($_SESSION['controlpanel'] == BLOGID || md5($_POST['password']) == $hash)
    {
        $_SESSION['controlpanel'] = BLOGID;
        return "You are now logged in.<br /><a href=\"" . INDEX_URL . "?controlpanel\">Continue to the Control Panel</a>";
    } else {
        $GLOBALS['NOTIFY'] = "Incorrect password.";
        return main_page(1);
    }
}

function controlpanel()
{
    global $db, $BLOGINFO;

    $TITLE = $BLOGINFO['title'] . " :: Control Panel";

    if(!$_SESSION['controlpanel'])
    {
        header("Location: " . INDEX_URL . "?notloggedin");
        return;
    }

    if(BLOGID == 1) // we are root
        $GLOBALS['EXTRA'] = "You are logged in as Admin. Beware that changes you make are potentially dangerous!\n\n";

    if(isset($_GET['controlpanel:settings']))
    {
        require("cp_settings.php");
    } elseif(isset($_GET['controlpanel:write'])) {
        require("cp_write.php");
    } elseif(isset($_GET['controlpanel:edit'])) {
        require("cp_edit.php");
    } elseif(isset($_GET['controlpanel:userinfo'])) {
        require("cp_userinfo.php");
    } elseif(isset($_GET['controlpanel:skin'])) {
        require("cp_skin.php");
    } else {
        $current = "home";
        $body = "Welcome to the CodewiseBlog control panel.<br />Powered by CodewiseBlog ".CWBVERSION;
    }

    $args = array
    (
        "root"    => (BLOGID == 1 ? TRUE : FALSE),
        "current" => $current,
        "cpurl"        => INDEX_URL . "?controlpanel",
        "url_settings" => INDEX_URL . "?controlpanel:settings",
        "url_write"    => INDEX_URL . "?controlpanel:write",
        "url_edit"     => INDEX_URL . "?controlpanel:edit",
        "url_userinfo" => INDEX_URL . "?controlpanel:userinfo",
        "url_skin"     => INDEX_URL . "?controlpanel:skin",
    );

    $out = skinvoodoo("controlpanel", "", $args);

    $out = str_replace("<!-- #CWB_CP_BODY# -->", $body, $out);
    $out = str_replace("%{".UNIQ."titletag}", $BLOGINFO['title'] . " :: Control Panel", $out);
    $out = str_replace("%{".UNIQ."runtime}", runtime(), $out);

    return $out;
}

?>
