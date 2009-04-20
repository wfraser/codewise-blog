<?php

$rev = trim(`git rev-list HEAD 2>/dev/null | wc -l`);

if ($rev == 0) {
	define("CWBVERSION", "DEV-2.0.0");
} else {
	define("CWBVERSION", "DEV-2.0.0 rev $rev");
}

define("CWBTYPE", "Mainline");

?>
