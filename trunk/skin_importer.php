<html>
<head>
<title>Skin Importer</title>
</head>
<body>
<?php

require("settings2.php");
require("l1_mysql.php");

if(!isset($_GET['skin_dir']))
    die("Set ?skin_dir=something to import the skin files in that directory into the database.</body></html>");
elseif(!is_dir(FSPATH . $_GET['skin_dir']))
    die("No such directory as the one described in ?skin_dir</body></html>");

?>
<table>
<?php

// must be root or some other user with ALTER privilages
$db = new L1_MySQL(SQL_HOST, "root", "[root password]", SQL_DB);

$current = $db->fetch_row($db->issue_query("SELECT * FROM skin WHERE blogid = '1'"), 0, L1SQL_ASSOC);

$dir = opendir(FSPATH . $_GET['skin_dir']);

$q = $db->issue_query("DESCRIBE skin");
$desc = $db->fetch_all($q, L1SQL_ASSOC);
$cols = array();
foreach($desc as $col)
    $cols[] = $col['Field'];
array_shift($cols); // remove the 'blogid' one

while($file = readdir($dir))
{
    if(substr($file, 0, 1) == "." || substr($file, -1, 1) == "~" || is_dir(FSPATH . "skin_blueEye/" . $file))
        continue;
    $cont = file_get_contents(FSPATH . "skin_blueEye/" . $file);
    $section = preg_replace("/\\.html$/", "", $file);
    if(preg_match("/\\.css$/", $file))
        $section = "css";

    if(!in_array($section, $cols))
    {
        $db->issue_query("ALTER TABLE skin ADD ".$db->prepare_value($section,FALSE)." TEXT");
        echo "<tr><td>$section</td><td>added</td></tr>\n";
    }

    $db->update("skin", array($section => $cont), array("blogid" => 1));

    if($cont != $current[$section])
        echo "<tr><td>$section</td><td>changed</td></tr>\n";
    else
        echo "<tr><td>$section</td><td>unchanged</td></tr>\n";
}

?>
</table>
</body>
</html>