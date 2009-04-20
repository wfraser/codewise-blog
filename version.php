<?php

/*
 * Codewise Blog Version Scheme:
 *
 * a.b.c.d
 *
 * a: major version
 *        incremented with major project changes
 * b: minor version
 *        incremented with feature changes
 * c: patch version
 *        incremented with each release
 * d: revision
 *        number of Git commits since release
 *        only shown on development versions
 */

// this is the current release, and also the Git tag
define("CWBVERSIONTAG", "2.1.0");

// figure out how many commits since release
$range = CWBVERSIONTAG."..HEAD";
$rev = trim(`git rev-list $range 2>/dev/null | wc -l`);

if ($rev == 0) {
	define("CWBVERSION", CWBVERSIONTAG);
} else {
	define("CWBVERSION", CWBVERSIONTAG.".$rev-DEV");
}

// if you fork the project, CHANGE THIS!
define("CWBTYPE", "Mainline");

?>
