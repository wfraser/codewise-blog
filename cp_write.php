<?php

/*
** Control Panel :: Write Post
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

$current = "write";

if(empty($_POST) || isset($_POST['resize']))
{
    $body = skinvoodoo(
        "controlpanel_write", "",
        array(
            "posturl" => INDEX_URL . "?controlpanel:write",
            "quicktags" => HTTP.BASE_DOMAIN.INSTALLED_PATH."cwb/quicktags.js",
            "autoresize" => HTTP.BASE_DOMAIN.INSTALLED_PATH."cwb/autoresize.js",
            "rows" => $_POST['rows'] ? $_POST['rows'] : 25,
            "cols" => $_POST['cols'] ? $_POST['cols'] : 80,
            "text" => $_POST['text'] ? $_POST['text'] : "",
            "title" => "Title",
        )
    );
} else {
    if(isset($_POST['preview']))
    {
        $body = skinvoodoo("controlpanel_write", "preview_topic", array("text" => output_topic_text($_POST['text'])));

        $body .= skinvoodoo(
            "controlpanel_write", "",
            array(
                "posturl" => INDEX_URL . "?controlpanel:write",
                "quicktags" => HTTP.BASE_DOMAIN.INSTALLED_PATH."cwb/quicktags.js",
                "autoresize" => HTTP.BASE_DOMAIN.INSTALLED_PATH."cwb/autoresize.js",
                "rows" => $_POST['rows'] ? $_POST['rows'] : 25,
                "cols" => $_POST['cols'] ? $_POST['cols'] : 80,
                "text" => htmlspecialchars($_POST['text']),
                "title" => $_POST['title'],
            )
        );

    } else {

        $q = $db->issue_query("SELECT tid,timestamp FROM topics WHERE title = " . $db->prepare_value($_POST['title']) . " AND blogid = '".BLOGID."'");
        if($db->num_rows[$q] > 0)
        {
            list($row,$timestamp) = $db->fetch_row($q, 0, L1SQL_NUM);
            $GLOBALS['NOTIFY'] = "A post with that title already exists: <a href=\"" . INDEX_URL . "?tid=$tid\">" . $_POST['title'] . "</a>"
                . " (posted " . date(DATE_FORMAT, $timestamp) . ")";

            $body = skinvoodoo(
                "controlpanel_write", "",
                array(
                    "posturl" => INDEX_URL . "?controlpanel:write",
                    "quicktags" => HTTP.BASE_DOMAIN.INSTALLED_PATH."cwb/quicktags.js",
                    "autoresize" => HTTP.BASE_DOMAIN.INSTALLED_PATH."cwb/autoresize.js",
                    "rows" => $_POST['rows'] ? $_POST['rows'] : 25,
                    "cols" => $_POST['cols'] ? $_POST['cols'] : 80,
                    "text" => $_POST['text'],
                    "title" => $_POST['title'],
                )
            );
        } else {
            // tid blogid title timestamp text extra
            $data = array
            (
                "blogid" => BLOGID,
                "title" => $_POST['title'],
                "timestamp" => time(),
                "text" => $_POST['text'],
            );

            $db->insert("topics", $data);

            $tid = $db->fetch_var( $db->issue_query("SELECT tid FROM topics WHERE timestamp = " . $data['timestamp']) );

            if(EMAIL)
            {
                $q = $db->issue_query("SELECT * FROM subscriptions WHERE blogid = '" . BLOGID . "'");
                $rows = $db->fetch_all($q);

                foreach($rows as $row)
                {
                    $email = $row['email'];
                    $password = $row['password'];

                    $message =
"Hello,

This is the " . html_entity_decode($BLOGINFO['title']) . " subscription service letting you know that there has been a new blog entry posted, entitled \"" . $_POST['title'] . "\"
You can view it here: " . INDEX_URL . "?tid=$tid

Thanks for reading!

--
you may unsubscribe from these mailings by visiting this url:
" . INDEX_URL . "?unsubscribe&email=$email&password=$password
";

                    mail($email, html_entity_decode($BLOGINFO['title']) . " Subscription", $message, "From: ".DEFAULT_SUBDOMAIN.BASE_DOMAIN." <nobody@".BASE_DOMAIN.">");
                }
            }

            if($_POST['weblogs.com'] == "checked")
            {
                global $BLOGINFO;

                file_get_contents("http://rpc.weblogs.com/pingSiteForm?name=".$BLOGINFO['title']."&url=".INDEX_URL.".&changesURL=".INDEX_URL."/rdf.php");
/*
                require(FSPATH . "/xmlrpc.inc");
                $client = new xmlrpc_client("/RPC2", "rpc.weblogs.com", 80);
                $msg = new xmlrpcmsg(
                    "weblogUpdates.extendedPing", array(
                        new xmlrpcval($BLOGINFO['title'], "string"),
                        new xmlrpcval(INDEX_URL, "string"),
                        new xmlrpcval(INDEX_URL, "string"),
                        new xmlrpcval(INDEX_URL . "/rdf.php", "string")
                ));
                $resp = $client->send($msg, 10);
                if($resp->faultCode != 0)
                {
                    $GLOBALS['NOTIFY'] = "weblogs.com ping failed";
                } else {
                    $rmsg = $resp->value();
                    //oo
                }
*/
            }

            $body = skinvoodoo("controlpanel_write", "success_redirect", array("topic_url" => INDEX_URL . "?tid=$tid"));
        }

    }
}

?>
