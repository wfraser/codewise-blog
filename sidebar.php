<?php

/*
** Sidebar Functions
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** (c) 2005 Codewise.org
*/

function welcomeback()
{
    global $BLOGINFO;

    if($_SESSION['beenhere'] === TRUE)
    {
        if($_SESSION['postername'] != "")
        {
            $name = $_SESSION['postername'];
            $contents = skinvoodoo("welcomeback", "name", array("name" => $name));
        } else {
            $contents = skinvoodoo("welcomeback", "noname");
        }
        return skinvoodoo("welcomeback", "", array("contents" => $contents, "url" => INDEX_URL . "?delsession"));
    } else {
        // this'll get displayed on top of everything in the main pane.
        $BLOGINFO['extra'] = "You seem to be new here.<br />Would you like to read the <a href=\"" . INDEX_URL . "?tid=1\">CodewiseBlog introduction</a>?\n";
        $_SESSION['beenhere'] = TRUE;
    }
}

function subscribeform()
{
    return skinvoodoo("subscribeform", "", array("url" => INDEX_URL . "?subscribe"));
}

function loginform()
{
    return skinvoodoo("loginform", "", array("url" => INDEX_URL . "?login"));
}

?>
