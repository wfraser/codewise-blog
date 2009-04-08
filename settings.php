<?php

/*
** CodewiseBlog Settings File
*/

// if settings.local.php exists, its settings are used instead of this file.
if (file_exists('settings.local.php')) {
    require_once 'settings.local.php';
    return;
}

// Where CodewiseBlog is located in the filesystem. Do not append a trailing
// slash.
define('FSPATH', '/var/www/htdocs/blogs');

// If Subdomain Mode is enabled, each user will get their own subdomain the
// same as their username. This works well with wildcard DNS.
// If Subdomain Mode is disabled, the username is appended to the URL.
define('SUBDOMAIN_MODE', FALSE);

// The domain name CodewiseBlog is installed at
define('BASE_DOMAIN', 'www.example.com');

// If Custom URLs are enabled, selected users can use URL redirection to have
// their blog appear somewhere else. Custom URL Mode changes the links to
// use their Custom URL.
define('CUSTOM_URL_ENABLED', TRUE);

// The path CodewiseBlog is located at in the URL. Do not append a trailing
// slash.
define('INSTALLED_PATH', '/blogs');

// If Subdomain Mode is enabled, this is the subdomain used when not displaying
// a user's blog. It may be set to the empty string to use the Base Domain.
// If Subdomain Mode is disabled, this is ignored and Base Domain is always
// used.
define('DEFAULT_SUBDOMAIN', '');

// How many blog entries to display per page?
define('TOPICS_PER_PAGE', '5');

// How many replies to display per page?
define('POSTS_PER_PAGE', '10');

// How many shoutbox posts to display?
define('SHOUTS_PER_PAGE', '10');

// Date format. See http://www.php.net/date for info.
define('DATE_FORMAT', 'F jS, Y \a\t g:i A');

// Anonymous users get this name.
define('ANONYMOUS_NAME', 'Anonymous');

// Tripcodes have this text appended to them before hashing. This makes sure
// nobody can bruteforce the resultant hash to find tripcodes. Keep this
// text secret!
define('TRIPCODE_SALT', 'playwithfire');

// Do we send out emails? (Disable this for testing.)
define('EMAIL', TRUE);

// Who do we email when something goes wrong accessing the database?
define('SQL_ADMIN_EMAIL', 'bill.fraser@gmail.com');

// SQL server access settings
define('SQL_HOST', 'localhost');
define('SQL_USER', 'codewiseblog');
define('SQL_PASS', '#!joltColaINaCan');
define('SQL_DB', 'codewiseblog');

// The name of the site. Used in lots of places.
define('SITE_TITLE', 'Codewise Blogs');

// Sub-title of the site.
define('SITE_MOTTO', 'A better place to write.');

// Make users type CAPTCHAs when replying to blog posts? (Reccomended)
define('IMAGEVERIFY', TRUE);

// The skin used for the Control Panel
define('CONTROLPANEL_SKINID', '00000000000000000000000000000000');

// Skin used for closed accounts
define('CLOSED_SKINID',  '00000000000000000000000000000002');

// Skin used for user blogs by default
define('DEFAULT_SKINID', '00000000000000000000000000000003');

// If the file 'TERMS' exists in the CodewiseBlog main folder, a link to it is
// displayed in the footer of each page.
if(file_exists(FSPATH."/TERMS")) {
    if (SUBDOMAIN_MODE && DEFAULT_SUBDOMAIN != "")
        $terms = ", subject to <a href=\"http://".DEFAULT_SUBDOMAIN.".".BASE_DOMAIN.INSTALLED_PATH."TERMS\">terms</a>";
    else
        $terms = ", subject to <a href=\"http://".BASE_DOMAIN.INSTALLED_PATH."TERMS\">terms</a>";
} else {
    $terms = "";
}

// The copright footer. Do not remove the attribution to CodewiseBlog. Doing so
// violates the terms of your license. You may, however, append additional text
// to this string.
define("CWB_COPYRIGHT",
"<a href=\"http://gna.org/projects/codewiseblog\">CodewiseBlog</a> &copy; 2004-2009 "
. "<a href=\"http://www.codewise.org/~wrf/\">William R. Fraser</a> / "
. "<a href=\"http://www.codewise.org/\">Codewise.org</a>.<br />"
. "All textual content is the property of its author$terms.<br />"
. "CodewiseBlog is free software under the <a href=\"COPYING\">GNU General Public License</a>"
);

unset($terms);

// This defines which HTML tags are allowed in replies. The array after each
// tag defines which HTML attributes are allowed. All others are stripped out.
$ALLOWED_TAGS = array
(
    'b' => array(),
    'i' => array(),
    'u' => array(),
    'p' => array(),
    'br' => array(),
    'a' => array('href', 'name'),
    'ol' => array(),
    'ul' => array(),
    'li' => array(),
    'em' => array(),
    'strong' => array(),
    'strike' => array(),
    'font' => array('color', 'size'),
    'tt' => array(),
    'blockquote' => array(),
    'sub' => array(),
    'sup' => array(),
);

?>
