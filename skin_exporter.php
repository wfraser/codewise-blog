<?php

require("settings.php");
require("l1_mysql.php");

ini_set("session.name", "codewiseblog");
session_start();

?>
<html>
<head>
<title>Skin Exporter</title>
</head>
<body>
<?php

if ($_SESSION['controlpanel'] != 1 || $_SESSION['blogid'] != 1) {
    echo "You need to be logged in to the Admin Control Panel to use this script. Go back to the front page and log in.</body></html>";
    exit;
}

if(!isset($_GET['skin_dir']))
    die("Set ?skin_dir=something to export the skin sections in the database to files in that directory.</body></html>");
elseif(!is_dir(FSPATH . "/" . $_GET['skin_dir']))
    die("No such directory as the one described in ?skin_dir</body></html>");

if(!isset($_GET['skinid']))
    die("Set ?skinid=something to export that skin ID. Value will be left-padded with zeroes to 32 chars.</body></html>");

?>
<table>
<?php

$skinid = str_pad($_GET['skinid'], 32, "0", STR_PAD_LEFT);

$db = new L1_MySQL(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);

$q = $db->issue_query("SELECT * FROM skins WHERE skinid = ".$skinid);

$skin = $db->fetch_row($q, 0, L1SQL_ASSOC);

foreach ($skin as $section => $data) {
    if ($data == NULL)
        $data = $db->fetch_var($db->issue_query("SELECT $section FROM skins WHERE skinid = '00000000000000000000000000000000'"));

    if ($section == "description")
        $section .= ".txt";
    else if ($section == "name" || $section == "skinid" || $section == "blogid")
        continue;
    else if ($section == "css")
        $section = "stylesheet.css";
    else
        $section .= ".html";

    file_put_contents(FSPATH . "/" . $_GET['skin_dir'] . "/" . $section,
            $data);

    echo "wrote $section<br />\n";
}

?>
</table>
</body>
</html>
