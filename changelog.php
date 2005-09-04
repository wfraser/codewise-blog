<?php

header("Content-Type: text/plain");

$file = file_get_contents("cwbmulti.php");

$lines = explode("\n", $file);

for($i = 10; $i < count($lines); $i++)
{
    if($lines[$i] == "*/")
        break;

    echo substr($lines[$i], 3) . "\n";
}

?>
