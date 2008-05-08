<?php

define('FSPATH', '/var/www/site/blogs.codewise.org');
define('SUBDOMAIN_MODE', FALSE);
define('BASE_DOMAIN', 'blogs.codewise.org');
define('CUSTOM_URL_ENABLED', TRUE);
define('INSTALLED_PATH', '/');
define('DEFAULT_SUBDOMAIN', '');
define('TOPICS_PER_PAGE', '5');
define('POSTS_PER_PAGE', '10');
define('SHOUTS_PER_PAGE', '10');
define('DATE_FORMAT', 'F jS, Y \a\t g:i A');
define('ANONYMOUS_NAME', 'Anonymous');
define('EMAIL', TRUE);
define('SQL_ADMIN_EMAIL', 'bill.fraser@gmail.com');
define('SQL_HOST', 'localhost');
define('SQL_USER', 'root');
define('SQL_PASS', 'czv101754');
define('SQL_DB', 'codewiseblog');
define('SITE_TITLE', 'Codewise Blogs');
define('SITE_MOTTO', 'A better place to write.');
define('IMAGEVERIFY', TRUE);
define('CONTROLPANEL_SKINID', '00000000000000000000000000000000');
define('CLOSED_SKINID',  '00000000000000000000000000000002');
define('DEFAULT_SKINID', '00000000000000000000000000000003');

if(file_exists(FSPATH."/TERMS"))
    $terms = ", subject to <a href=\"http://".BASE_DOMAIN.INSTALLED_PATH."TERMS\">terms</a>";
else
    $terms = "";

define("CWB_COPYRIGHT",
"<a href=\"http://gna.org/projects/codewiseblog\">CodewiseBlog</a> &copy; 2004-2008 "
. "<a href=\"http://www.codewise.org/~wrf/\">William R. Fraser</a> / "
. "<a href=\"http://www.codewise.org/\">Codewise.org</a>.<br />"
. "All textual content is the property of its author$terms.<br />"
. "CodewiseBlog is free software under the <a href=\"COPYING\">GNU General Public License</a>"
);

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
