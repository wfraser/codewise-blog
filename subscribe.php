<?php

/*
** Subscription Functions
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

function process_subscribe_form()
{
    global $db;

    if($_POST['email'] == "")
        return skinvoodoo("error", "error", array("message" => "You must enter a valid email address."));

    $q = $db->issue_query("SELECT email FROM subscriptions WHERE email = " . $db->prepare_value($_POST['email']) . " AND blogid = '" . BLOGID . "'");
    if($db->num_rows[$q] > 0)
        return skinvoodoo("error", "error", array("message" => "This email address is already subscribed to " . BLOG_TITLE . "."));

    $data = array("blogid" => BLOGID, "email" => $_POST['email'], "password" => `uuidgen`);

    $db->insert("subscriptions", $data);

    if(EMAIL)
        mail( ADMIN_EMAIL, "New Blog Subscriber", $_POST['email'] . " has subscribed to your blog.", "From: " . BASE_URL . " <nobody@" . BASE_URL . ">");

    return skinvoodoo("error", "notify", array("message" => "You have been successfully subscribed to " . BLOG_TITLE . ".<br />"
        . "You will be notified of future updates at the provided email address.<br />"
        . "Each email will contain a link which can be used to unsubscribe at any time.<br />"
        . "<b>Thanks!</b>"));

} // end of process_subscribe_form()

function do_unsubscribe()
{
    global $db;

    if($_GET['sure'] != 1)
    {
        $email = $_GET['email'];
        $password = $_GET['password'];

        return skinvoodoo("error", "notify", array("message" =>
              "Unsubscribing " . strip_tags($email) . ".<br />"
            . "<b>Are you sure??</b>"
            . "<form action=\"" . INDEX_URL . "?unsubscribe&amp;sure=1\" method=\"post\" />"
            . "<input type=\"hidden\" name=\"email\" value=\"" . strip_tags($email) . "\" />"
            . "<input type=\"hidden\" name=\"password\" value=\"" . strip_tags($password) . "\" />"
            . "<input type=\"submit\" value=\"YES\" />"
            . "</form>"));
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    $q = $db->issue_query("DELETE FROM subscriptions WHERE email = " . $db->prepare_value($email) . " AND password = " . $db->prepare_value($password) . " AND blogid = '" . BLOGID . "' LIMIT 1");

    if(EMAIL)
        mail( ADMIN_EMAIL, "Blog Subscription Lost", $_POST['email'] . " has unsubscribed from your blog.", "From: " . BASE_URL . " <nobody@" . BASE_URL . ">");

    if($db->num_rows[$q] == 0)
        return skinvoodoo("error", "error", array("message" =>
              "Sorry, I was unable to unsubscribe you.<br />"
            . "You either specified an incorrect email or an incorrect password, or perhaps both.<br />"));
    else
        return skinvoodoo("error", "notify", array("message" =>
            "You have been successfully unsubscribed from " . BLOG_TITLE . " and will no longer recieve notification of future updates."));

} // end of do_unsubscribe()

?>