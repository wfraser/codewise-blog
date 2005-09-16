<?php

/*
** User Control Panel
** for CodewiseBlog Multi-User
**
** by Bill Fraser <bill.fraser@gmail.com>
** Copyright (c) 2005 Codewise.org
*/

function cplogin()
{
    global $db;

    $q = $db->issue_query("SELECT password FROM blogs WHERE blogid = '" . BLOGID . "'");
    $hash = $db->fetch_var($q);

    if($_SESSION['controlpanel'] || md5($_POST['password']) == $hash)
    {
        $_SESSION['controlpanel'] = TRUE;
        return "You are now logged in.<br /><a href=\"" . INDEX_URL . "?controlpanel\">Continue to the Control Panel</a>";
    } else {
        $GLOBALS['NOTIFY'] = "Incorrect password.";
        return main_page(1);
    }
}

function controlpanel()
{
    global $db;

    if(!$_SESSION['controlpanel'])
    {
        header("Location: " . INDEX_URL . "?notloggedin");
        return;
    }

    if(isset($_GET['controlpanel:settings']))
    {

        $current = "settings";
        $body = "Settings Page";

    } elseif(isset($_GET['controlpanel:write'])) {

        $current = "write";

        if(empty($_POST))
        {
            $body = skinvoodoo(
                "controlpanel_write", "",
                array(
                    "posturl" => INDEX_URL . "?controlpanel:write",
                    "quicktags" => INDEX_URL . "?quicktags.js",
                    "text" => "",
                    "title" => "Title",
                )
            );
        } else {
            if(isset($_POST['preview']))
            {
                $body = display_topic(
                    array(
                        "tid" => "\" style=\"display:none\"></a>Continue Editing: <a alt=\"",
                        "title" => $_POST['title'],
                        "timestamp" => time(),
                        "text" => $_POST['text'],
                    ),
                    TRUE, TRUE
                );

                $body .= skinvoodoo(
                    "controlpanel_write", "",
                    array(
                        "posturl" => INDEX_URL . "?controlpanel:write",
                        "quicktags" => INDEX_URL . "?quicktags.js",
                        "text" => $_POST['text'],
                        "title" => $_POST['title'],
                    )
                );
            } else {
                print_r($_POST);
            }
        }

    } elseif(isset($_GET['controlpanel:userinfo'])) {
        $current = "userinfo";
        $body = "Userinfo Page";
    } elseif(isset($_GET['controlpanel:skin'])) {
        $current = "skin";
        $body = "Skin Editor Page";
    } else {
        $current = "home";
        $body = "Body goes here...";
    }

    $args = array
    (
        "current" => $current,
        "cpurl"        => INDEX_URL . "?controlpanel",
        "url_settings" => INDEX_URL . "?controlpanel:settings",
        "url_write"    => INDEX_URL . "?controlpanel:write",
        "url_userinfo" => INDEX_URL . "?controlpanel:userinfo",
        "url_skin"     => INDEX_URL . "?controlpanel:skin",
    );

    $main = skinvoodoo("controlpanel", "", $args);

    $out = str_replace("<!-- #CWB_CP_BODY# -->", $body, $main);

    return $out;
}

?>
