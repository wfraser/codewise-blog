<?php

header("Content-type: text/plain");

require("l1_mysql.php");

$db = new L1_MySQL("localhost", "codewiseblog", "!#joltColaINaCan");
$db->database("codewiseblog");

$q = $db->issue_query("SELECT css FROM skin WHERE blogid = " . $db->prepare_value($_GET['id']));

var_export($db->fetch_all($q, L1SQL_NUM));

if($db->num_rows[$q] == 0 || ($text = $db->fetch_var($q)) === NULL)
    readfile("/srv/www/site/www.codewise.org/blueEye.css");

echo $text;

?>
