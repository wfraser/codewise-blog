<?php

$rev = trim(`svn info | grep Revision: | sed 's/Revision: /r/'`);
if ($rev == "" || $rev == "svn: '.' is not a working copy") {
	define("CWBVERSION", "DEV-2.0.0");
} else {
	define("CWBVERSION", "DEV-2.0.0 ($rev)");
}

define("CWBTYPE", "Mainline");

?>
