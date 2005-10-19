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

    if($_SESSION['controlpanel'] == BLOGID || md5($_POST['password']) == $hash)
    {
        $_SESSION['controlpanel'] = BLOGID;
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

    if(BLOGID == 1) // we are root
        $GLOBALS['EXTRA'] = "You are logged in as Admin. Beware that changes you make are potentially dangerous!\n\n";

    if(isset($_GET['controlpanel:settings']))
    {
        $current = "settings";

        if(BLOGID !== 1)
        {
            $body = skinvoodoo("error","error",array("message"=>"You do not have permission to access this area of the control panel."));
        } elseif(empty($_POST)) {
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
                    "main_url" => "http://" . (DEFAULT_SUBDOMAIN == "" ? "" : DEFAULT_SUBDOMAIN . "." ) . BASE_DOMAIN . INSTALLED_PATH,
                    "subdomain_mode" => SUBDOMAIN_MODE,
                    "base_domain" => BASE_DOMAIN,
                    "installed_path" => INSTALLED_PATH,
                    "default_subdomain" => DEFAULT_SUBDOMAIN,
                    "topics_per_page" => TOPICS_PER_PAGE,
                    "posts_per_page" => POSTS_PER_PAGE,
                    "date_format" => DATE_FORMAT,
                    "anonymous_name" => ANONYMOUS_NAME,
                    "email" => EMAIL,
                    "sql_admin_email" => SQL_ADMIN_EMAIL,
                    "sql_host" => SQL_HOST,
                    "sql_user" => SQL_USER,
                    "sql_pass" => SQL_PASS,
                    "sql_db" => SQL_DB,
                    "allowed_tags" => $allowed_tags,
                )
            );
        } else {

            $file = file_get_contents("settings.php");

            $applied = array();

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)FSPATH)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(FSPATH)) . ")\\3\\s*\\);/is",
                "define('FSPATH', '" . $_POST['fspath'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "FSPATH";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)SUBDOMAIN_MODE)\\1,\\s*" . (SUBDOMAIN_MODE ? "TRUE" : "FALSE") . "\\s*\\);/is",
                "define('SUBDOMAIN_MODE', " . $_POST['subdomain_mode'] . ");",
                $file);
            if($file != $filenew)
                $applied[] = "SUBDOMAIN_MODE";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)BASE_DOMAIN)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(BASE_DOMAIN)) . ")\\3\\s*\\);/is",
                "define('BASE_DOMAIN', '" . $_POST['base_domain'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "BASE_DOMAIN";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)INSTALLED_PATH)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(INSTALLED_PATH)) . ")\\3\\s*\\);/is",
                "define('INSTALLED_PATH', '" . $_POST['installed_path'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "INSTALLED_PATH";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)DEFAULT_SUBDOMAIN)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(DEFAULT_SUBDOMAIN)) . ")\\3\\s*\\);/is",
                "define('DEFAULT_SUBDOMAIN', '" . $_POST['default_subdomain'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "DEFAULT_SUBDOMAIN";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)TOPICS_PER_PAGE)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(TOPICS_PER_PAGE)) . ")\\3\\s*\\);/is",
                "define('TOPICS_PER_PAGE', '" . $_POST['topics_per_page'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "TOPICS_PER_PAGE";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)POSTS_PER_PAGE)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(POSTS_PER_PAGE)) . ")\\3\\s*\\);/is",
                "define('POSTS_PER_PAGE', '" . $_POST['posts_per_page'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "POSTS_PER_PAGE";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)DATE_FORMAT)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(DATE_FORMAT)) . ")\\3\\s*\\);/is",
                "define('DATE_FORMAT', '" . $_POST['date_format'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "DATE_FORMAT";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)ANONYMOUS_NAME)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(ANONYMOUS_NAME)) . ")\\3\\s*\\);/is",
                "define('ANONYMOUS_NAME', '" . $_POST['anonymous_name'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "ANONYMOUS_NAME";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)EMAIL)\\1,\\s*" . (EMAIL ? "TRUE" : "FALSE") . "\\s*\\);/is",
                "define('EMAIL', " . $_POST['email'] . ");",
                $file);
            if($file != $filenew)
                $applied[] = "EMAIL";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_ADMIN_EMAIL)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_ADMIN_EMAIL)) . ")\\3\\s*\\);/is",
                "define('SQL_ADMIN_EMAIL', '" . $_POST['sql_admin_email'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "SQL_ADMIN_EMAIL";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_HOST)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_HOST)) . ")\\3\\s*\\);/is",
                "define('SQL_HOST', '" . $_POST['sql_host'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "SQL_HOST";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_USER)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_USER)) . ")\\3\\s*\\);/is",
                "define('SQL_USER', '" . $_POST['sql_user'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "SQL_USER";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_PASS)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_PASS)) . ")\\3\\s*\\);/is",
                "define('SQL_PASS', '" . $_POST['sql_pass'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "SQL_PASS";
            $file = $filenew;

            $filenew = preg_replace(
                "/(?<=\\s)define\\(\\s*(['\"])((?-i)SQL_DB)\\1,\\s*(['\"])((?-i)" . str_replace("/", "\\/", quotemeta(SQL_DB)) . ")\\3\\s*\\);/is",
                "define('SQL_DB', '" . $_POST['sql_db'] . "');",
                $file);
            if($file != $filenew)
                $applied[] = "SQL_DB";
            $file = $filenew;

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

            $filenew = preg_replace("/\\\$ALLOWED_TAGS = array\n\\(.*\\);/s", $new_php_array, $file);
            if($file != $filenew)
                $applied[] = "ALLOWED_TAGS";
            $file = $filenew;

            if(count($applied))
            {
                $message = "";
                foreach($applied as $configvar)
                    $message .= "changed value of $configvar<br />";
            } else {
                $message = "No config values changed.";
            }

            $body = skinvoodoo("error", "notify", array("message" => $message)) . "<a href=\"" . INDEX_URL . "?controlpanel:settings\">Back to Settings</a>";

            file_put_contents("settings2.php", $file);
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
        } elseif(!isset($_POST['do_edit'])) {

            if(isset($_POST['preview']))
            {
                $month = $_POST['month'];
                $date = $_POST['date'];
                $year = $_POST['year'];
                $hour = $_POST['hour'];
                $minute = $_POST['minute'];
                $second = $_POST['second'];
                $ampm = $_POST['ampm'];

                $topic = array(
                    "title" => $_POST['title'],
                    "text" => $_POST['text'],
                );
            } else {
                $q = $db->issue_query("SELECT title,timestamp,text,extra FROM topics WHERE tid = " . $db->prepare_value($_POST['tid']) . " AND blogid = '" . BLOGID . "'");
                $topic = $db->fetch_row($q);
                $month = date("n", $topic['timestamp']);
                $date = date("j", $topic['timestamp']);
                $year = date("Y", $topic['timestamp']);
                $hour = date("g", $topic['timestamp']);
                $minute = date("i", $topic['timestamp']);
                $second = date("s", $topic['timestamp']);
                $ampm = date("a", $topic['timestamp']);
            }

            $months = array("null", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

            $month_sel = "<select name=\"month\">";
            for($i = 1; $i <= 12; $i++)
            {
                if($i == $month)
                    $month_sel .= "<option value=\"$i\" selected=\"selected\">{$months[$i]}</option>";
                else
                    $month_sel .= "<option value=\"$i\">{$months[$i]}</option>";
            }
            $month_sel .= "</select>";

            $date_sel = "<select name=\"date\">";
            for($i = 1; $i <= 31; $i++)
            {
                if($i == $date)
                    $date_sel .= "<option value=\"$i\" selected=\"selected\">" . ordinal($i) . "</option>";
                else
                    $date_sel .= "<option value=\"$i\">" . ordinal($i) . "</option>";
            }
            $date_sel .= "</select>";

            $year_sel = "<input type=\"text\" size=\"4\" value=\"" . $year . "\" name=\"year\" />";

            $hour_sel = "<select name=\"hour\">";
            for($i = 1; $i <= 12; $i++)
            {
                if($i == $hour)
                    $hour_sel .= "<option value=\"$i\" selected=\"selected\">$i</option>";
                else
                    $hour_sel .= "<option value=\"$i\">$i</option>";
            }
            $hour_sel .= "</select>";

            $minute_sel = "<select name=\"minute\">";
            for($i = 0; $i <= 59; $i++)
            {
                if($i == $minute)
                    $minute_sel .= "<option value=\"$i\" selected=\"selected\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
                else
                    $minute_sel .= "<option value=\"$i\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
            }
            $minute_sel .= "</select>";

            $second_sel = "<select name=\"second\">";
            for($i = 0; $i <= 59; $i++)
            {
                if($i == $second)
                    $second_sel .= "<option value=\"$i\" selected=\"selected\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
                else
                    $second_sel .= "<option value=\"$i\">" . str_pad($i, 2, "0", STR_PAD_LEFT) . "</option>";
            }
            $second_sel .= "</select>";

            $ampm_sel = "<select name=\"ampm\">";
            if($ampm == "am")
            {
                $ampm_sel .= "<option value=\"am\" selected=\"selected\">AM</option>";
                $ampm_sel .= "<option value=\"pm\">PM</option>";
            } else {
                $ampm_sel .= "<option value=\"am\">AM</option>";
                $ampm_sel .= "<option value=\"pm\" selected=\"selected\">PM</option>";
            }
            $ampm_sel .= "</select>";

            $preview = display_topic(
                array(
                    "tid" => "\" style=\"display:none\"></a>Continue Editing: <a alt=\"",
                    "title" => $topic['title'],
                    "timestamp" => time(),
                    "text" => $topic['text'],
                ),
                TRUE, TRUE
            );

            $body = skinvoodoo(
                "controlpanel_edit", "editform",
                array(
                    "posturl" => INDEX_URL . "?controlpanel:edit",
                    "title"   => $topic['title'],
                    "date"    => date(DATE_FORMAT, $topic['timestamp']),
                    "text"    => $topic['text'],
                    "extra"   => $topic['extra'],
                    "tid"     => $_POST['tid'],
                    "month_sel"  => $month_sel,
                    "date_sel"   => $date_sel,
                    "year_sel"   => $year_sel,
                    "hour_sel"   => $hour_sel,
                    "minute_sel" => $minute_sel,
                    "second_sel" => $second_sel,
                    "ampm_sel"   => $ampm_sel,
                    "preview" => $preview,
                    "quicktags" => INDEX_URL . "?quicktags.js",
                )
            );

        } else {

            if(!checkdate($_POST['month'], $_POST['date'], $_POST['year']))
            {
                $body = skinvoodoo("error", "error", array("message" => "Invalid date specified."));
            } elseif(
                $_POST['hour'] > 12   ||
                $_POST['hour'] < 1    ||
                $_POST['minute'] > 59 ||
                $_POST['minute'] < 0  ||
                $_POST['second'] > 59 ||
                $_POST['second'] < 0  ||
                ( $_POST['ampm'] != "am" && $_POST['ampm'] != "pm")
            ) {
                $body = skinvoodoo("error", "error", array("message" => "Invalid time specified."));
            } else {

                // int mktime ( [int hour [, int minute [, int second [, int month [, int day [, int year [, int is_dst]]]]]]] )
                $time = mktime(
                    $_POST['hour'] + ($_POST['ampm'] == "pm" ? 12 : 0),
                    $_POST['minute'],
                    $_POST['second'],
                    $_POST['month'],
                    $_POST['date'],
                    $_POST['year']
                );

                $data = array
                (
                    "blogid" => BLOGID,
                    "title" => $_POST['title'], // ToDo: check for uniqueness
                    "timestamp" => $time,
                    "text" => $_POST['text'],
                );

                $db->update("topics", $data, array("tid" => $_POST['tid']));

                $body = skinvoodoo("controlpanel_edit", "success_redirect", array("topic_url" => INDEX_URL . "?tid={$_POST['tid']}"));

            }

        }
    } elseif(isset($_GET['controlpanel:userinfo'])) {
        $current = "userinfo";
        $body = "Userinfo Page";
    } elseif(isset($_GET['controlpanel:skin'])) {
        $current = "skin";

        if($_POST)
        {
            $body = $_POST['section'];
        } else {
            //$body = skinvoodoo("controlpanel_skin", "sectionsel",
        }
    } else {
        $current = "home";
        $body = "Body goes here...";
    }

    $args = array
    (
        "root"    => (BLOGID == 1 ? TRUE : FALSE),
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
