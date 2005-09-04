<?php

/*
** CodewiseBlog Multi-User Front Page
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** (c) 2005 Codewise.org
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title>CodewiseBlog</title>
        <link rel="stylesheet" href="http://www.codewise.org/blueEye.css" />
    </head>
    <body>

        <table style="border:none;width:100%;">
            <tr>
                <td style="text-align:center">
                    <a href="<?php echo INDEX_URL; ?>"><span class="main-title">CodewiseBlog</span></a><br />
                    <i>A better place to blog.</i>
                </td>
                <td style="width:100%;text-align:right;font-size:small">
<?php echo fortune(); ?>
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

$q = $db->issue_query("SELECT * FROM blogs WHERE blogid != '1' ORDER BY blogid ASC");
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
?>
                                    <tr><td><hr align="center" style="border:1px solid #eee" width="50%" noshade="noshade" /></td></tr>
                                    <tr><td><a href="http://<?php echo $blogname; ?>.blogs.codewise.org/"><?php echo $blog['title']; ?></a><br />
                                            <span style="font-size:smaller">by <?php echo $name; ?></span></td></tr>
<?php
}
?>
                                    <tr><td style="padding:5px"></td></tr>
                                </table>
                            </td>
                        </tr>
<?php
/*
                        <tr>
                            <td class="sidebar">
                                <table style="border:none; text-align:center; width:100%">
                                    <tr><td><b>Other blogs using CWB:</b></td></tr>
                                    <tr><td><a href="http://blogs.codewise.org/cwb/notify.php">(get added to this list)</a></td></tr>
                                </table>
                            </td>
                        </tr>
*/
?>
<?php
// no "welcome back" sidebar here
?>
                        <tr>
                            <td class="sidebar">
                                <b>Admin login:</b><br />

                                <form action="<?php echo INDEX_URL; ?>?admin" method="post">
                                <small>password:</small> <input type="password" name="password" style="font-size:smaller" size="10" /><br />
                                <input type="submit" value="enter" style="font-size:smaller" />
                                </form>
                            </td>
                        </tr>
                    </table>

                </td>
                <td class="blogbody">

                    <div style="background-color: yellow"><div style="border: 5px dashed red"><div style="background-color:white; padding:10px">
                        <b style="font-size: xx-large">CodewiseBlog Multi-User</b><br />
                        <br />
                        <b>Under Development</b><br />
                        <br />
                        "It's working sorta! Yayuhh!"<br />
                        &nbsp;&nbsp;&nbsp;&nbsp;-NMW
                    </div></div></div>

                </td>
            </tr>
        </table>
        <br />
        <table style="border:none;width:100%">
            <tr>
                <td style="width:50%;font-size:small">
                    <?php echo querycount(); ?> database queries. Page generated in <?php echo runtime(); ?> milliseconds.<br />

                    <br />
                    <?php echo versionfooter(); ?>
                </td>
                <td style="width:50%;font-size:small;text-align:right;padding-right:1em">
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