<?php

define('FSPATH', '/var/www/htdocs/');
define('CUSTOM_URL_ENABLED', TRUE);
define('SUBDOMAIN_MODE', FALSE);
define('BASE_DOMAIN', 'example.com');
define('INSTALLED_PATH', '/');
define('DEFAULT_SUBDOMAIN', 'www');
define('TOPICS_PER_PAGE', '5');
define('POSTS_PER_PAGE', '10');
define('DATE_FORMAT', 'F jS, Y \a\t g:i A');
define('ANONYMOUS_NAME', 'Anonymous');
define('EMAIL', TRUE);
define('SQL_ADMIN_EMAIL', 'nobody@localhost');
define('SQL_HOST', 'localhost');
define('SQL_USER', 'sql_username');
define('SQL_PASS', 'sql_password');
define('SQL_DB', 'sql_database');

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
