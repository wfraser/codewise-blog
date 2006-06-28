<?php

/*
** Control Panel :: Post Manager
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

$current = "manage";

// hack allowing direct links to post deleting
if(isset($_GET['del']))
{
    list($_POST['type'], $_POST['id']) = preg_split('/:/', $_GET['del']);
}

if(isset($_POST['REALLY_FREAKING_SURE']))
{
    switch($_POST['type'])
    {
case "reply":
        $q_check = $db->issue_query("SELECT blogid FROM replies WHERE pid = ".$db->prepare_value($_POST['id']));
        if($db->fetch_var($q_check) !== BLOGID)
        {
            $body = skinvoodoo("error", "error", array('message' => "That post isn't yours to delete."));
            return;
        }
        
        $q_del = $db->issue_query("DELETE FROM replies WHERE pid = ".$db->prepare_value($_POST['id']));
        break;
case "shout":
        $q_check = $db->issue_query("SELECT blogid FROM shoutbox WHERE timestamp = ".$db->prepare_value($_POST['id']));
        if($db->fetch_var($q_check) !== BLOGID)
        {
            $body = skinvoodoo("error", "error", array('message' => "That shout is not yours to delete."));
            return;
        }

        $q_del = $db->issue_query("DELETE FROM shoutbox WHERE timestamp = ".$db->prepare_value($_POST['id']));
        break;
default:
        $body = skinvoodoo('error', 'error', array('message' => 'Invalid post type.'));
        return;
    }

    if($db->num_rows[$q_del] != 1)
    {
        $body = skinvoodoo('error', 'error', array('message' => 'Delete failed... Please contact and administrator'));
        return;
    }

    $body = skinvoodoo('controlpanel_manage', 'success', array(
        "type" => $_POST['type'],
        "id" => $_POST['id'],
    ));
} elseif(isset($_POST['type']) && isset($_POST['id'])) {
    $body = skinvoodoo('controlpanel_manage', 'confirm', array(
        "type" => $_POST['type'],
        "id" => $_POST['id'],
        "posturl" => INDEX_URL . "?controlpanel:manage",
    ));
} else {
    $body = "nothing here yet";
}

?>
