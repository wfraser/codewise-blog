<?php

/*
** XML Skin Archive Importer
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
    if(!$_POST)
    {
        $size = str_replace("M", "000000", str_replace("K", "000", ini_get("upload_max_filesize")));
?><html><body><form action="<?=$_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="<?=$size?>" />
<input type="file" name="xmlfile" />
<input type="submit" />
</form></body></html>
<?php
    } else {
        require("settings2.php");
        require("l1_mysql.php");

        $db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

        if(!is_uploaded_file($_FILES['xmlfile']['tmp_name']))
            die("Bogus upload file");

        define("BLOGID", "0");

        $xml = file_get_contents($_FILES['xmlfile']['tmp_name']);

        if(!preg_match("/[a-f0-9]{32}/", $ret = skin_importer_xml($xml)))
        {
            echo "<html><body><h1>Skin import failed</h1>".htmlspecialchars($ret)."</body></html>";
        } else {
            echo "<html><body><h1>Skin import successful</h1>New skin id: $ret</body></html>";
        }
    }
}

/*
** XML Skin Archive Importer
**
** This takes the string contents of a VoodooArchive XML file and imports the
** skin described therein. If $skinid is not specified, a skinid will be
** generated, otherwise the specified skinid is used.
**
** If this returns a 32-char hex string (the new skinid), the import succeeded.
** If not, it returned an error string.
*/
function skin_importer_xml($xml, $skinid = NULL)
{
    global $db;

    if($skinid !== NULL && strlen($skinid) != 32)
        return "Bogus SKINID passed.";
    elseif($db->num_rows[ $db->issue_query("SELECT skinid FROM skins WHERE skinid = ".$db->prepare_value($skinid)) ] > 0)
        return "Duplicate SKINID passed.";

    // generate the list of sections
    $q = $db->issue_query("DESCRIBE skins");
    $desc = $db->fetch_all($q, L1SQL_ASSOC);

    $sections = array();
    foreach($desc as $col)
    {
        array_push($sections, $col['Field']);
    }

    // remove the special sections
    $sections = array_diff($sections, array("skinid", "blogid", "name", "description"));

    $parser = xml_parser_create();
    xml_parser_Set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parse_into_struct($parser, $xml, $struct, $index);

    $start = array_shift($struct);
    $end = array_pop($struct);

    if($start['tag'] != "VoodooArchive" || $start['type'] != "open")
        return "XML does not start with <VoodooArchive> tag";
    if(($version = $start['attributes']['version']) != "1")
        return "Wrong VoodooArchive version (expected '1', saw $version)";
    if($end['tag'] != "VoodooArchive" || $end['type'] != "close")
        return "XML is not closed with a </VoodooArchive> tag";

    $skin = array();

    foreach($struct as $tag)
    {
        $tag['value'] = base64_decode(trim($tag['value']));
        if($tag['type'] == "cdata")
            continue;
        switch($tag['tag'])
        {
case "name":
case "description":
            if(isset($skin[$tag['tag']]))
                return "Duplicate <".$tag['tag']."> tag in XML.";
            if($tag['value'] == "")
                return $tag['tag']." cannot be empty.";
            $skin[$tag['tag']] = $tag['value'];
            break;
case "section":
            $sect = $tag['attributes']['name'];

            // skip sections that we don't have
            if(!in_array($sect, $sections))
                continue;

            if(isset($skin[$sect]))
                return "Duplicate $sect section.";
            if($tag['value'] == "")
                $tag['value'] = NULL;
            $skin[$sect] = $tag['value'];
            break;
default:
            return "Unknown <".$tag['tag']."> tag encountered in XML.";
        }
    }

    // set ownership
    $skin['blogid'] = BLOGID;

    if($skinid === NULL)
    {
        do {
            // generate a new skinid
            $skin['skinid'] = md5(uniqid(mt_rand(), TRUE));
        // make sure the skinid isn't a dupe (unlikely, but possible)
        } while ($db->num_rows[ $db->issue_query("SELECT skinid FROM skins WHERE skinid = '".$skin['skinid']."'") ] > 0);

    } else {
        $skin['skinid'] = $skinid;
    }

    $skin['name'] .= " [import]";

    $db->insert("skins", $skin);

    return $skin['skinid'];
}

?>