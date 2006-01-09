<?php

/*
** Control Panel :: Skin Editor
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

$current = "skin";

if(isset($_POST['go_baby_go']))
{
    $db->update("skin", array($_POST['section'] => $_POST['section_content']), array("blogid" => BLOGID));
    $GLOBALS['NOTIFY'] = "Skin Updated";
    $_POST['section_sel'] = $_POST['section']; // redisplay the current one
} elseif(isset($_POST['revert'])) {
    $db->update("skin", array($_POST['section'] => NULL), array("blogid" => BLOGID));
    $GLOBALS['NOTIFY'] = "Reverted to Master Skin";
    $_POST['section_sel'] = $_POST['section']; // redisplay the current one
}

$q = $db->issue_query("DESCRIBE skin");
$desc = $db->fetch_all($q, L1SQL_ASSOC);

if(isset($_POST['resize']))
    $_POST['section_sel'] = $_POST['section']; // hack to get the section edit box to redisplay

$sectionlist = "";
foreach($desc as $col)
{
    if($col['Field'] == "blogid")
        continue;
    elseif($col['Field'] == $_POST['section_sel'])
        $sectionlist .= skinvoodoo("controlpanel_skin", "sectionlist_current", array("section" => $col['Field']));
    else
        $sectionlist .= skinvoodoo("controlpanel_skin", "sectionlist_entry",   array("section" => $col['Field']));
}

if(isset($_POST['section_sel']))
{
    $q = $db->issue_query("SELECT ".$db->prepare_value($_POST['section_sel'], FALSE)." FROM skin WHERE blogid = '".BLOGID."'");
    $skin = $db->fetch_var($q);
    if($skin === NULL)
    {
        $using_master = TRUE;
        $q = $db->issue_query("SELECT ".$db->prepare_value($_POST['section_sel'], FALSE)." FROM skin WHERE blogid = '1'");
        $skin = $db->fetch_var($q);
    } else {
        $using_master = FALSE;
    }

    // special case when using the root controlpanel
    if(BLOGID == 1)
        $using_master = TRUE;

    $content = skinvoodoo("controlpanel_skin", "section_edit", array(
        "section_name" => $_POST['section_sel'],
        "using_master" => $using_master,
        "autoresize" => INDEX_URL . "?autoresize.js",
        "rows" => $_POST['rows'] ? $_POST['rows'] : 30,
        "cols" => $_POST['cols'] ? $_POST['cols'] : 80,
        "section_content" => str_replace(
            array("%{",         "\${"       ),
            array("&#x0025;{",  "&#x0024{"  ),
            htmlspecialchars($skin)),
    ));

    $varlist = "<iframe src=\"http://" .  DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "doc/voodoo/localvars.html#". $_POST['section_sel'] . "\" height=\"100%\" width=\"300\" /></iframe>";
}

$body = skinvoodoo("controlpanel_skin", "", array(
    "posturl" => INDEX_URL . "?controlpanel:skin",
    "sectionlist" => $sectionlist,
    "varlist" => $varlist,
    "content" => $content,
    "section_name" => isset($_POST['section_sel']) ? $_POST['section_sel'] : FALSE,
));

?>
