<?php

/*
** Sidebar Functions
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
