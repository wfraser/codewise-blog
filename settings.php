<?php

define("FSPATH", "/srv/www/site/blogs.codewise.org/");

/*
** Subdomain Mode
**
** if enabled, username will come from the hostname:
**     http://username.yourdomain.com/
** instead of the normal:
**     http://www.yourdomain.com/username
*/

define('SUBDOMAIN_MODE', TRUE);
define('BASE_DOMAIN', 'blogs.codewise.org');
define('INSTALLED_PATH', '/radix.cwb');
define('DEFAULT_SUBDOMAIN', '');

/*
** Tweakable Vars
*/

define('TOPICS_PER_PAGE', '5');
define('POSTS_PER_PAGE', '10');
define('DATE_FORMAT', 'F jS, Y \a\t g:i A');

define('ANONYMOUS_NAME', 'Dr. Anonymous');

// Slashdot, in their infinite wisdom, allow: <b> <i> <p> <br> <a> <ol> <ul> <li> <dl> <dt> <dd> <em> <strong> <tt> <blockquote> <div> <ecode>

$ALLOWED_TAGS = array
(
    'b' => array(),
    'i' => array(),
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