<?php

/*
** Front Page
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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo SITE_TITLE; ?></title>
        <link rel="stylesheet" href="stylesheet.php" />
    </head>
    <body>
<?php if (isset($_GET['delsession'])) : delete_session(); ?>
        <h1>You have been logged out</h1>
        <h5>have a nice day</h5>
        <a href="<?php echo INDEX_URL; ?>">back to the front page</a>
    </body>
</html>
<?php exit; endif; ?>

        <table style="border:none;width:100%;">
            <tr>
                <td style="text-align:center">
                    <a href="<?php echo INDEX_URL; ?>"><span class="main-title"><?php echo SITE_TITLE ?></span></a><br />
                    <i><?php echo SITE_MOTTO; ?></i>
                </td>
		<td style="width:100%;text-align:right;font-size:small">
		    <i><?php echo fortune(); ?></i>
		</td>
            </tr>
        </table>

        <br />

        <table width="100%">
            <tr>
                <td width="1px" style="vertical-align:top">

                    <table class="sidebar" width="100%">
                        <tr>
                            <td class="sidebar">
                                <table style="border:none; text-align:center; width:100%">
                                    <tr><td><b>Blogs on this site:</b></td></tr>
<?php

$q = $db->issue_query("SELECT name,realname,title,custom_url FROM blogs WHERE status = 'active' ORDER BY blogid ASC");
$data = $db->fetch_all($q, L1SQL_ASSOC, "name");

foreach($data as $blogname => $blog)
{
    if($blog['realname'] != NULL)
    {
        $name = $blog['realname'];
        /*
        $parts = explode(" ", $blog['realname']);
        $name = array_shift($parts);
        $name .= " &#8220;" . $blog['name'] . "&#8221; ";
        $name .= implode(" ", $parts);
        */
    } else {
        $name = $blog['name'];
    }

    if(isset($blog['custom_url'])) {
        $link = $blog['custom_url'];
    } elseif(SUBDOMAIN_MODE) {
        $link = "http://$blogname." . BASE_DOMAIN . INSTALLED_PATH;
    } else {
        $link = "http://" . (DEFAULT_SUBDOMAIN == "" ? "" : DEFAULT_SUBDOMAIN . ".") . BASE_DOMAIN . INSTALLED_PATH . $blogname . "/";
    }

    // this is dumb, but helpful
    $blog['title'] = preg_replace('/([^\x09\x0A\x0D\x20-\x7F]|[\x21-\x2F]|[\x3A-\x40]|[\x5B-\x60])/e', '"&#".ord("$0").";"', html_entity_decode($blog['title']));
?>
                                    <tr><td><hr align="center" style="border:1px solid #eee" width="50%" noshade="noshade" /></td></tr>
                                    <tr><td><a href="<?php echo $link; ?>"><?php echo $blog['title']; ?></a><br />
                                            <span style="font-size:smaller">by <?php echo htmlentities($name); ?></span></td></tr>
<?php

} // foreach($data as $blogname => $blog)

?>
                                    <tr><td style="padding:5px"></td></tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="sidebar">
<?php if ($_SESSION['controlpanel'] == 1 && $_SESSION['blogid'] == 1) : ?>
                                <b>Logged in as Admin</b><br />
                                <a href="?controlpanel">go to the Admin CP</a>
                                <br />
                                <a href="?delsession">Log out</a>
<?php else: ?>
                                <b>Admin login:</b><br />

                                <form action="<?php echo INDEX_URL; ?>?login" method="post">
                                <small>password:</small> <input type="password" name="password" style="font-size:smaller" size="10" /><br />
                                <input type="submit" value="enter" style="font-size:smaller" />
                                </form>
<?php endif; ?>
                            </td>
                        </tr>
                    </table>

                </td>
                <td class="blogbody">

<?php

    if(file_exists("TERMS"))
    {

?>
<table style="border:none; width:100%">
<tr>
<td>
    <table style="padding: 0px; border: 1px solid #487393; width: 100%;">
        <tr>
            <td style="padding-left: 3px; width: 1px">
                <a href="?register">
                    <img src="cwb/keys.png" style="border:none" alt="Keys" />
                </a>
            </td>
            <td style="padding: 5px">
                <a href="?register">
                    <span style="text-decoration:underline">Register for a blog on this site.</span>
                </a>
            </td>
        </tr>
    </table>
</td>
</tr>
</table>
<br />
<?php

    }

    $q = $db->issue_query("SELECT blogid FROM blogs WHERE status = 'active'");
    if ($db->num_rows[$q] == 0)
        $activeblogids = "false";
    else
        $activeblogids = "blogid = " . implode($db->fetch_column($q), " OR blogid = ");

    $q = $db->issue_query("SELECT tid,blogid,title,timestamp,text FROM topics WHERE $activeblogids ORDER BY timestamp DESC LIMIT 5");
    $data = $db->fetch_all($q, L1SQL_ASSOC, "");

    foreach($data as $topic)
    {
        $q = $db->issue_query("SELECT name,realname,photo,title,custom_url FROM blogs WHERE blogid = " . $db->prepare_value($topic['blogid']));
        $blog = $db->fetch_row($q);

        $q = $db->issue_query("SELECT COUNT(*) FROM replies WHERE blogid = " . $db->prepare_value($topic['blogid']) . " AND tid = " . $db->prepare_value($topic['tid']));
        $num_replies = $db->fetch_var($q);

        if($blog['photo'] === NULL)
            $blog['photo'] = "";

        if($blog['realname'] !== NULL)
        {
            $name = $blog['realname'];
        } else {
            $name = $blog['name'];
        }

	if ($blog['custom_url']) {
	    $url = preg_replace("/\\/$/", "", $blog['custom_url']);
	} elseif (SUBDOMAIN_MODE) {
            $url = "http://{$blog['name']}." . BASE_DOMAIN . INSTALLED_PATH;
        } else {
            $url = "http://" . (DEFAULT_SUBDOMAIN == "" ? "" : DEFAULT_SUBDOMAIN . ".") . BASE_DOMAIN . INSTALLED_PATH . $blog['name'];
        }

        $filtered_text = in_text_filter($topic['text']);

        if(is_array($filtered_text))
        {
            $filtered_text = $filtered_text[0];
        }

        $text = output_topic_text(text_clip($filtered_text, 1000, " &hellip;"));

        // $topic['url'] = $url . "?tid=" . $topic['tid'];
        $topic['url'] = $url . "/article/" . string_to_url_goodness($topic['title']);
?>

<table style="border:none; width:100%">
<tr>
<td>
    <table style="padding: 0px; border: 1px solid #ddd; width: 100%">
        <tr>
            <td style="padding-left: 3px; width: 1px">
                <img src="<?php echo INDEX_URL; ?>img.php?blogid=<?php echo $topic['blogid']; ?>" alt="<?php echo $blog['name']; ?>" />
            </td>
            <td style="padding: 5px">
                <a href="<?php echo $topic['url']; ?>"><b><?php echo $topic['title']; ?></b></a> :: <?php echo $num_replies; ?> comments
                <br />
                <a href="<?php echo $url; ?>"><?php echo $blog['title']; ?></a> by <?php echo $name; ?>
                <br />
                <?php echo date(DATE_FORMAT, $topic['timestamp']); ?>
            </td>
        </tr>
    </table>
</td>
</tr>
<tr>
<td>
    <div class="topicbody" style="border-bottom: none">
        <?php echo $text; ?>
    </div>
<?php /* this is a cheap (but effective!) way of closing any tags left open by text_clip() */ ?>
    <div class="topicbody" style="border-top: none">
        <a href="<?php echo "$url?tid={$topic['tid']}"; ?>"><b>read the whole post</b></a>
    </div>
</td>
</tr>
</table>
<br />
<?php

    } // foreach($data as $topic)

?>

                </td>
            </tr>
        </table>
        <br />
        <table style="border:none;width:100%">
            <tr>
                <td style="width:50%;font-size:small;vertical-align:top">
                    <?php echo querycount(); ?> database queries. Page generated in <?php echo runtime(); ?> milliseconds.<br />
                    <?php echo skinvoodoo("main", "versionfooter"); ?>
                </td>
                <td style="width:50%;font-size:small;text-align:right;padding-right:1em;vertical-align:top">
                    <?php echo voodoo("%{copyright}", array(), "front_page"); ?>
                </td>
                <td>
                    <table align="center">
                        <tr>
                            <td>
                                <a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0" style="border:none" /></a>
                            </td>

                        </tr>
                        <tr>
                            <td>
                                <a href="http://jigsaw.w3.org/css-validator/check/referer"><img src="http://jigsaw.w3.org/css-validator/images/vcss" alt="Valid CSS" style="border:none" /></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

        </table>
    </body>
</html>
