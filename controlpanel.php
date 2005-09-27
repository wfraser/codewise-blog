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

        if(empty($_POST))
        {
            global $ALLOWED_TAGS;

            $allowed_tags = "";
            foreach($ALLOWED_TAGS as $name => $attribs)
                $allowed_tags .= "<$name>: " . implode(", ", $attribs) . "\n";

            $body = skinvoodoo(
                "controlpanel_settings", "",
                array(
                    "posturl" => INDEX_URL . "?controlpanel:settings",
                    "fspath"  => FSPATH,
                    "sample_url" => SUBDOMAIN_MODE ? "http://username." . BASE_DOMAIN . INSTALLED_PATH : "http://" . DEFAULT_SUBDOMAIN . BASE_DOMAIN . INSTALLED_PATH . "/username",
                    "subdomain_mode" => SUBDOMAIN_MODE,
                    "base_domain" => BASE_DOMAIN,
                    "installed_path" => INSTALLED_PATH,
                    "default_subdomain" => DEFAULT_SUBDOMAIN,
                    "topics_per_page" => TOPICS_PER_PAGE,
                    "posts_per_page" => POSTS_PER_PAGE,
                    "date_format" => DATE_FORMAT,
                    "anonymous_name" => ANONYMOUS_NAME,
                    "allowed_tags" => $allowed_tags,
                )
            );
        } else {

            $file = file_get_contents("settings.php");

            $file = str_replace("define('FSPATH', " . FSPATH . ");\n", "define('FSPATH', " . $_POST['fspath'] . ");\n", $file);
            $file = str_replace("define('SUBDOMAIN_MODE', '" . SUBDOMAIN_MODE . "');\n", "define('SUBDOMAIN_MODE', '" . $_POST['subdomain_mode'] . "');\n", $file);

            //oo stuff

            $new_php_array = "\$ALLOWED_TAGS = array\n(\n";
            $lines = explode("\n", $_POST['allowed_tags']);
            foreach($lines as $line)
            {
                $tagname = substr($line, 1, strpos($line, ":") - 2);

                $attribs = substr($line, strpos($line, ":") + 2);
                $attribs_array = explode(", ", $attribs);

                foreach($attribs_array as $i => $text)
                    $attribs_array[$i] = trim($text);

                if($tagname == "")
                    continue;
                elseif(count($attribs_array) == 0 || empty($attribs_array[0]))
                    $new_php_array .= "    '$tagname' => array(),\n";
                else
                    $new_php_array .= "    '$tagname' => array('" . implode("', '", $attribs_array) . "'),\n";
            }
            $new_php_array .= ");";

            $file = preg_replace("/\\\$ALLOWED_TAGS = array\n\\(.*\\);/s", $new_php_array, $file);

            echo $file;
        }

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

                // tid blogid title timestamp text extra
                $data = array
                (
                    "blogid" => BLOGID,
                    "title" => $_POST['title'], // ToDo: check for uniqueness
                    "timestamp" => time(),
                    "text" => $_POST['text'],
                );

                $db->insert("topics", $data);

                $tid = $db->fetch_var( $db->issue_query("SELECT tid FROM topics WHERE timestamp = " . $data['timestamp']) );

                $body = skinvoodoo("controlpanel_write", "success_redirect", array("topic_url" => INDEX_URL . "?tid=$tid"));

            }
        }
    } elseif(isset($_GET['controlpanel:edit'])) {
        $current = "edit";

        if(empty($_POST))
        {
            $q = $db->issue_query("SELECT tid,title,timestamp FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY tid DESC");
            $data = $db->fetch_all($q);

            $html = "";
            foreach($data as $row)
            {
                $html .= "<option value=\"{$row['tid']}\">\"{$row['title']}\" - " . date(DATE_FORMAT, $row['timestamp']) . "</option>\n";
            }

            $body = skinvoodoo(
                "controlpanel_edit", "showselect",
                array(
                    "posturl" => INDEX_URL . "?controlpanel:edit",
                    "options"  => $html,
                )
            );
        } elseif(!isset($_GET['do_edit'])) {

            $q = $db->issue_query("SELECT title,timestamp,text,extra FROM topics WHERE tid = " . $db->prepare_value($_POST['tid']) . " AND blogid = '" . BLOGID . "'");
            $topic = $db->fetch_row($q);

            $months = array("null", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

            $month_sel = "<select name=\"month\">\n";
            for($i = 0; $i <= 12; $i++)
            {
                if($i == date("n", $topic['timestamp']))
                    $month_sel .= "<option value=\"$i\" selected=\"selected\">{$months[$i]}</option>\n";
                else
                    $month_sel .= "<option value=\"$i\">{$months[$i]}</option>\n";
            }
            $month_sel .= "</select>\n";

            $date_sel = "<select name=\"date\">\n";
            //oo

            $body = skinvoodoo(
                "controlpanel_edit", "editform",
                array(
                    "posturl" => INDEX_URL . "?controlpanel:edit&amp;do_edit",
                    "title"   => $topic['title'],
                    "date"    => date(DATE_FORMAT, $topic['timestamp']),
                    "text"    => $topic['text'],
                    "extra"   => $topic['extra'],
                    "tid"     => $_POST['tid'],
                    "month_sel" => $month_sel,
                )
            );

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
        "url_edit"     => INDEX_URL . "?controlpanel:edit",
        "url_userinfo" => INDEX_URL . "?controlpanel:userinfo",
        "url_skin"     => INDEX_URL . "?controlpanel:skin",
    );

    $main = skinvoodoo("controlpanel", "", $args);

    $out = str_replace("<!-- #CWB_CP_BODY# -->", $body, $main);

    return $out;
}

?>
