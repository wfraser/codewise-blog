<?php

define("CWBVERSIONTAG", "2.1.0");

$range = CWBVERSIONTAG."..HEAD";
$rev = trim(`git rev-list $range 2>/dev/null | wc -l`);

if ($rev == 0) {
	define("CWBVERSION", "DEV-".CWBVERSIONTAG);
} else {
	define("CWBVERSION", "DEV-".CWBVERSIONTAG.".$rev");
}

define("CWBTYPE", "Mainline");

?>
