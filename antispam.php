<?php

/*
** Anti-Spam Routines
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2006 Codewise.org
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

function antispam_shoutbox($database_row, $client_ip)
{
    global $db;

    /*
    ** Don't let someone repost within a minute
    */

    //$q = $db->issue_query("SELECT NOW() - FROM_UNIXTIME(timestamp) FROM shoutbox WHERE extra = ".$db->prepare_value($database_row['extra'])." ORDER BY timestamp DESC LIMIT 1");

    $q = $db->issue_query("SELECT NOW() - FROM_UNIXTIME(timestamp) FROM shoutbox WHERE extra LIKE 'ip: ".$db->prepare_value($client_ip, FALSE)."\n%' ORDER BY timestamp DESC LIMIT 1");

    $timediff = $db->fetch_var($q);
    if($timediff < 60)
    {
        return skinvoodoo("error", "error", array("message" => "You shouted too recently. Wait a minute and try again."));
    }

    return NULL;
}

?>
