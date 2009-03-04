<?php

/*
** User Control Panel
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

function cplogin()
{
    global $db;

    $q = $db->issue_query("SELECT password FROM blogs WHERE blogid = '1'");
    $root_hash = $db->fetch_var($q);

    $q = $db->issue_query("SELECT password FROM blogs WHERE blogid = '" . BLOGID . "'");
    $hash = $db->fetch_var($q);

    if($_SESSION['controlpanel'] == 1 || md5($_POST['password']) == $root_hash)
    {
        $_SESSION['controlpanel'] = 1;
        $_SESSION['blogid'] = BLOGID;
        define('LOGGED_IN', TRUE);
        define('ADMIN', TRUE);
        return "You are now logged in as Admin. Beware that changes you make are potentially dangerous!<br />"
            . "<a href=\"" . INDEX_URL . "?controlpanel\">Continue to the Control Panel</a><br />"
            . "<a href=\"" . INDEX_URL . "\">Back to your blog</a>";
    } elseif($_SESSION['controlpanel'] == BLOGID || md5($_POST['password']) == $hash) {
        $_SESSION['controlpanel'] = BLOGID;
        define('LOGGED_IN', TRUE);
        define('ADMIN', FALSE);
        return "You are now logged in.<br />"
            . "<a href=\"" . INDEX_URL . "?controlpanel\">Continue to the Control Panel</a><br />"
            . "<a href=\"" . INDEX_URL . "\">Back to your blog</a>";
    } else {
        $GLOBALS['NOTIFY'] = "Incorrect password.";
        define('LOGGED_IN', FALSE);
        define('ADMIN', FALSE);
        return main_page(1);
    }
}

function controlpanel()
{
    global $db, $BLOGINFO;

    $TITLE = $BLOGINFO['title'] . " :: Control Panel";

    if($_SESSION['controlpanel'] != BLOGID && $_SESSION['controlpanel'] != 1)
    {
        header("Location: " . INDEX_URL . "?notloggedin");
        return;
    }

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
        require("cp_skin_multi.php");
    } elseif(isset($_GET['controlpanel:adduser'])) {
        require("cp_adduser.php");
    } elseif(isset($_GET['controlpanel:manage'])) {
        require("cp_manage.php");
    } else {
        $current = "home";
        //$body = "<div align=\"center\">Welcome to the CodewiseBlog control panel.<br />"
        $body = skinvoodoo("controlpanel", "welcome", array())
          . skinvoodoo("controlpanel", "versionfooter", array()) . "</div>";
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
        "url_adduser"  => INDEX_URL . "?controlpanel:adduser",
        "url_manage"   => INDEX_URL . "?controlpanel:manage",
    );

    $out = skinvoodoo("controlpanel", "", $args);

    $out = str_replace("<!-- #CWB_CP_BODY# -->", $body, $out);
    $out = str_replace("%{".UNIQ."titletag}", $BLOGINFO['title'] . " :: Control Panel", $out);
    $out = str_replace("%{".UNIQ."querycount}", querycount(), $out);
    $out = str_replace("%{".UNIQ."runtime}", runtime(), $out);

    return $out;
}

?>
