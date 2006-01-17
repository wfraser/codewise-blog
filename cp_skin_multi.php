<?php

/*
** Control Panel :: The Great Multi-Skin Editor (Behold!)
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

if(isset($_POST['skinid']))
{
    // check to make sure the skin exists
    $q = $db->issue_query("SELECT skinid FROM skins WHERE skinid = ".$db->prepare_value($_POST['skinid']));
    if($db->num_rows[$q] != 1)
    {
        $body = skinvoodoo("error", "error", array("message" => "No such Skin ID."));
        return;
    }

    if(isset($_POST['delete'])) {

        // for users, don't actually delete, just disown (to blogid 0). Only root can actually delete.
        if(BLOGID != 1)
        {
            $name = $db->fetch_var($db->issue_query("SELECT name FROM skins WHERE skinid = ".$db->prepare_value($_POST['skinid'])));
            $db->update("skins", array("blogid" => 0, "name" => $name . " (deleted by ".BLOGID.")"), array("skinid" => $_POST['skinid']));
            $body = "<p>Your skin has been deleted. If this is in error, copy down the Skin ID below and contact an administrator."
                . "They can recover your skin.<br /><br />Skin ID: <b>{$_POST['skinid']}</b>";
        } else {
            $db->issue_query("DELETE FROM skins WHERE skinid = ".$db->prepare_value($_POST['skinid']));
            $body = skinvoodoo("error", "notify", array("message" => "Skin {$_POST['skinid']} deleted."));
        }

        // if it's the current skin being deleted, switch to master
        if(SKINID == $_POST['skinid'])
            $db->update("blogs", array("skinid" => "00000000000000000000000000000000"), array("blogid" => BLOGID));

    } else {

        $owner = $db->fetch_var($db->issue_query("SELECT blogid FROM skins WHERE skinid = ".$db->prepare_value($_POST['skinid'])));

        /*
           IF:
                not owner
            and not root
            and not just USEing a builtin skin

            or  copying

                copy skin
        */
        if($owner != BLOGID
        && BLOGID != 1
        && !(isset($_POST['use']) && $owner == 1 )
        || isset($_POST['copy']))
        {
            // pull skin from DB
            $q = $db->issue_query("SELECT * FROM skins WHERE skinid = ".$db->prepare_value($_POST['skinid']));
            $skin = $db->fetch_row($q, 0, L1SQL_ASSOC);

            // generate a new skinid and change ownership
            $skin['skinid'] = md5(uniqid(mt_rand(), TRUE));
            $skin['blogid'] = BLOGID;

            // make sure the skinid isn't a dupe (unlikely, but possible)
            while($db->num_rows[ $db->issue_query("SELECT skinid FROM skins WHERE skinid = '".$skin['skinid']."'") ] > 0)
                $skin['skinid'] = md5(uniqid(mt_rand(), TRUE));

            // copying the master skin is a special case
            if($_POST['skinid'] == "00000000000000000000000000000000")
            {
                $new_skin = array();
                foreach($skin as $section => $contents)
                {
                    switch($section)
                    {
                        /* Since the master is owned by root, the check below
                        ** to eliminate duplicates doesn't work. This gets
                        ** around that. */
case "name":            $contents .= " [copy]";
                        break;

                        // these fields stay the same
case "skinid":
case "blogid":
case "description":     break;

                        /* All the sections should be NULL so that the editor
                        ** properly indicates that you're using the master skin
                        ** for all sections, instead of just copying the text
                        ** over, which would make the editor think every section
                        ** was changed. */
default:                $contents = NULL;
                    }

                    $new_skin[$section] = $contents;
                }
                $skin = $new_skin;
            } elseif($owner == 1) {
                /* copying builtin skins don't need all the above modifications,
                ** just appending [copy] to the end is enough */
                $skin['name'] .= " [copy]";
            }

            // make sure no two skins with the same owner have the same name
            while( $db->num_rows[ $ff = $db->issue_query("SELECT * FROM skins WHERE blogid = '".BLOGID."' AND name = ".$db->prepare_value($skin['name'])) ] > 0)
            {
                $skin['name'] .= " [copy]";
            }

            // add skin to DB
            $db->insert("skins", $skin);

            // Edit the copy
            $_POST['skinid'] = $skin['skinid'];

            // if the user did not explicitly request this, tell them about the copy procedure
            if(!isset($_POST['copy']))
                $GLOBALS['NOTIFY'] .= "You cannot edit someone else's skin, so a copy has been made.<br />";
        }

        if(isset($_POST['save_skin']))
        {
            // description changes the title and possibly the owner too
            if($_POST['section'] == "description")
            {
                $db->update("skins", array("name" => $_POST['skin_name']), array("skinid" => $_POST['skinid']));

                // root can change the owner too
                if(BLOGID == 1)
                    $db->update("skins", array("blogid" => $_POST['skin_owner']), array("skinid" => $_POST['skinid']));
            }
            $db->update("skins", array($_POST['section'] => $_POST['section_content']), array("skinid" => $_POST['skinid']));

            $GLOBALS['NOTIFY'] .= "Skin saved";
        } elseif(isset($_POST['revert'])) {
            $db->update("skins", array($_POST['section'] => NULL), array("skinid" => $_POST['skinid']));
            $GLOBALS['NOTIFY'] .= "Reverted section to master skin.<br />";
        }

        if(isset($_POST['use']))
        {
            $db->update("blogs", array("skinid" => $_POST['skinid']), array("blogid" => BLOGID));

            $body = skinvoodoo("error", "notify", array("message" => "Now using the selected skin."));
            return;
        }

        // get the section edit box to redisplay after resizing or saving or reverting
        if(isset($_POST['resize']) || isset($_POST['save_skin']) || isset($_POST['revert']))
            $_POST['section_sel'] = $_POST['section'];

        // bring up the description and title by default
        if(!isset($_POST['section_sel']))
            $_POST['section_sel'] = "description";

        // generate the list of sections
        $q = $db->issue_query("DESCRIBE skins");
        $desc = $db->fetch_all($q, L1SQL_ASSOC);

        $sectionlist = "";
        foreach($desc as $col)
        {
            if(BLOGID != 1)
            {
                if(strpos($col['Field'], "controlpanel") === 0
                || $col['Field'] == "register")
                {
                    continue;
                }
            }

            switch($col['Field'])
            {
case "skinid":
case "blogid":
case "name":
                continue;
case $_POST['section_sel']:
                $sectionlist .= skinvoodoo("controlpanel_skin_multi", "sectionlist_current", array("section" => $col['Field']));
                break;
default:
                $sectionlist .= skinvoodoo("controlpanel_skin_multi", "sectionlist_entry",   array("section" => $col['Field']));
                break;
            }
        }

        // get the section from DB
        $q = $db->issue_query("SELECT ".$db->prepare_value($_POST['section_sel'], FALSE)." FROM skins WHERE skinid = ".$db->prepare_value($_POST['skinid']));
        $skin = $db->fetch_var($q);
        if($skin === NULL)
        {
            $using_master = TRUE;
            $q = $db->issue_query("SELECT ".$db->prepare_value($_POST['section_sel'], FALSE)." FROM skins WHERE skinid = '00000000000000000000000000000000'");
            $skin = $db->fetch_var($q);
        } else {
            $using_master = FALSE;
        }

        //if(BLOGID == 1)
        //    $using_master = TRUE;

        // in the master skin, the section is always the master, not when it is NULL like for other skins
        if($_POST['skinid'] == "00000000000000000000000000000000")
            $using_master = TRUE;

        // for the description, show the title edit field as well
        if($_POST['section_sel'] == "description")
        {
            $name = $db->fetch_var($db->issue_query("SELECT name FROM skins WHERE skinid = ".$db->prepare_value($_POST['skinid'])));
            $content = skinvoodoo("controlpanel_skin_multi", "skin_name", array("name" => $name));

            // root can change the owner too
            if(BLOGID == 1)
                $content .= skinvoodoo("controlpanel_skin_multi", "skin_owner", array("owner" => $owner));
        } else {
            $content = "";
        }

        $content .= skinvoodoo("controlpanel_skin_multi", "section_edit", array(
            "section_name" => $_POST['section_sel'],
            "using_master" => $using_master,
            "autoresize" => INDEX_URL . "?autoresize.js",
            "rows" => $_POST['rows'] ? $_POST['rows'] : 30,
            "cols" => $_POST['cols'] ? $_POST['cols'] : 80,
            "skinid" => $_POST['skinid'],
            "section_content" => str_replace(
                array("%{",         "\${"       ),
                array("&#x0025;{",  "&#x0024{"  ),
                htmlspecialchars($skin)),
        ));

        // <iframe> containing the local variable reference, scrolled to the appropriate section
        $varlist = "<iframe src=\"http://" .  DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "doc/voodoo/localvars.html#". $_POST['section_sel'] . "\" height=\"100%\" width=\"300\" /></iframe>";

        $body = skinvoodoo("controlpanel_skin_multi", "", array(
            "posturl" => INDEX_URL . "?controlpanel:skin",
            "sectionlist" => $sectionlist,
            "varlist" => $varlist,
            "content" => $content,
            "skinid"  => $_POST['skinid'],
            "section_name" => isset($_POST['section_sel']) ? $_POST['section_sel'] : FALSE,
        ));

    }

} else {

    if(BLOGID != 1)
    {
        // get the skinid and name of all the builtin skins (those owned by root)
        $q = $db->issue_query("SELECT skinid, name FROM skins WHERE blogid = '1'");
        $root_skins = $db->fetch_all($q, L1SQL_ASSOC);

        // get the skinid and name of all the skins owned by the user
        $q = $db->issue_query("SELECT skinid, name FROM skins WHERE blogid = '".BLOGID."'");
        $user_skins = $db->fetch_all($q, L1SQL_ASSOC);

        // one blank entry
        //$skinids = skinvoodoo("controlpanel_skin_multi", "saved_skinids_separator", array("text" => ""));

        // separator
        $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_separator", array("text" => "Built-In Skins:"));

        foreach($root_skins as $skin)
        {
            if(SKINID == $skin['skinid'])
                $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_current", array("skinid" => $skin['skinid'], "name" => $skin['name']));
            else
                $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_entry", array("skinid" => $skin['skinid'], "name" => $skin['name']));
        }

        // separator
        $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_separator", array("text" => "Your Skins:"));

        foreach($user_skins as $skin)
        {
            if(SKINID == $skin['skinid'])
                $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_current", array("skinid" => $skin['skinid'], "name" => $skin['name']));
            else
                $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_entry", array("skinid" => $skin['skinid'], "name" => $skin['name']));
        }

    } else {
        // get the skins from BLOGID 1 (builtin skins)
        $q = $db->issue_query("SELECT skinid, name FROM skins WHERE blogid = '1'");
        $builtin_skins = $db->fetch_all($q, L1SQL_ASSOC);

        // get the skins from BLOGID 0 (disowned skins)
        $q = $db->issue_query("SELECT skinid, name FROM skins WHERE blogid = '0'");
        $disowned_skins = $db->fetch_all($q, L1SQL_ASSOC);

        // one blank entry
        //$skinids = skinvoodoo("controlpanel_skin_multi", "saved_skinids_separator", array("text" => ""));

        $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_separator", array("text" => "Built-In Skins:"));

        foreach($builtin_skins as $skin)
        {
            $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_entry", array("skinid" => $skin['skinid'], "name" => $skin['name']));
        }

        $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_separator", array("text" => "Disowned Skins:"));

        foreach($disowned_skins as $skin)
        {
            $skinids .= skinvoodoo("controlpanel_skin_multi", "saved_skinids_entry", array("skinid" => $skin['skinid'], "name" => $skin['name']));
        }
    }

    $body = skinvoodoo("controlpanel_skin_multi", "skin_select", array(
        "posturl" => INDEX_URL . "?controlpanel:skin",
        "saved_skinids" => $skinids,
    ));

}

?>
