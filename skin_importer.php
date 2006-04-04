<?php

require("settings2.php");
require("l1_mysql.php");

?>
<html>
<head>
<title>Skin Importer</title>
</head>
<body>
<?php

if(!isset($_GET['skin_dir']))
    die("Set ?skin_dir=something to import the skin files in that directory into the database.</body></html>");
elseif(!is_dir(FSPATH . $_GET['skin_dir']))
    die("No such directory as the one described in ?skin_dir</body></html>");

if(!isset($_GET['skinid']))
    die("Set ?skinid=something to import the skin to that skin ID. Value will be left-padded with zeroes to 32 chars.</body></html>");

?>
<table>
<?php

$skinid = str_pad($_GET['skinid'], 32, "0", STR_PAD_LEFT);

$description = file(FSPATH . $_GET['skin_dir'] . "/DESCRIPTION");
$name = rtrim($description[0]);
$description = implode("", $description);

$db = new L1_MySQL(SQL_HOST, "root", "czv101754", SQL_DB);

$db->update("skins", array("name" => $name, "description" => $description), array("skinid" => $skinid));

//$current = $db->fetch_row($db->issue_query("SELECT * FROM skin WHERE blogid = '1'"), 0, L1SQL_ASSOC);
$current = $db->fetch_row($db->issue_query("SELECT * FROM skins WHERE skinid = ".$db->prepare_value($skinid)), 0, L1SQL_ASSOC);

//$q = $db->issue_query("DESCRIBE skin");
$q = $db->issue_query("DESCRIBE skins");
$desc = $db->fetch_all($q, L1SQL_ASSOC);
$cols = array();
foreach($desc as $col)
    $cols[] = $col['Field'];
array_shift($cols); // remove the 'skinid' one
array_shift($cols); // remove the 'blogid' one
array_shift($cols); // remove the 'name' one
array_shift($cols); // remove the 'description' one

$dir = opendir(FSPATH . $_GET['skin_dir']);
while($file = readdir($dir))
{
    if(substr($file, 0, 1) == "." || substr($file, -1, 1) == "~" || is_dir(FSPATH . $_GET['skin_dir'] . "/" . $file))
        continue;
    $cont = file_get_contents(FSPATH . $_GET['skin_dir'] . "/" . $file);
    if(preg_match("/\\.css$/", $file))
    {
        $section = "css";
    } elseif($file == "DESCRIPTION") {
        continue;
    } else {
        $section = preg_replace("/\\.html$/", "", $file);
    }

    if(!in_array($section, $cols))
    {
        //$db->issue_query("ALTER TABLE skin ADD ".$db->prepare_value($section,FALSE)." TEXT");
        $db->issue_query("ALTER TABLE skins ADD ".$db->prepare_value($section,FALSE)." TEXT");
        echo "<tr><td>$section</td><td>added</td></tr>\n";
    }

    //$db->update("skin", array($section => $cont), array("blogid" => 1));
    $db->update("skins", array($section => $cont), array("skinid" => $skinid));

    if($cont != $current[$section])
        echo "<tr><td>$section</td><td>changed</td></tr>\n";
    else
        echo "<tr><td>$section</td><td>unchanged</td></tr>\n";
}

?>
</table>
</body>
</html>