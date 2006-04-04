<?php

/*
** XML Skin Archive Exporter
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

if(!defined("FSPATH")) // if called directly
{
    header("Content-Type: text/xml");

    require("settings2.php");
    require("l1_mysql.php");
    require("file_put_contents.php");

    $db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

    if(!isset($_GET['skinid']))
        $skinid = "00000000000000000000000000000000";
    else
        $skinid = $db->prepare_value(str_pad($_GET['skinid'], 32, "0", STR_PAD_LEFT), FALSE);

    echo skin_exporter_xml($skinid, TRUE);
}

/*
** XML Skin Archive Exporter
**
** This generates a simple XML file where all skin sections are base64 encoded
** and represented as <section> elements of a <VoodooArchive> root element.
** The name and description sections are special, and have their own tags.
*/
function skin_exporter_xml($skinid, $full_export = TRUE)
{
    global $db;

    $skin = $db->fetch_row( $db->issue_query("SELECT * FROM skins WHERE skinid = ".$db->prepare_value($skinid)), 0, L1SQL_ASSOC);

    $xml = "<?xml version=\"1.0\"?>
<VoodooArchive version=\"1\">
";

    foreach($skin as $name => $data)
    {
        $data = base64_encode($data);

        if(!$full_export)
        {
            if(strpos($name, "controlpanel") === 0)
                continue;
            elseif($name == "register")
                continue;
        }

        if($name == "skinid" || $name == "blogid")
            continue;
        elseif($name == "name")
            $xml .= " <name>$data</name>\n";
        elseif($name == "description")
            $xml .= " <description>$data</description>\n";
        else
            $xml .= " <section name=\"$name\">$data</section>\n";
    }

    $xml .= "</VoodooArchive>\n";

    return $xml;
}

?>
