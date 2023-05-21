<?php

// MAIN API URL PATH AND KEY CONFIG
define('api_url', $root. "api/");                                            // API SERVER URL WITH SLASH ON END EXAMPLE "api/");
define('api_key',"phptravels");                                              // YOUR API KEY
define('dev',"0");														     // USE 1 FOR DEV AND 0 FOR PRODUCTION

// GOOGLE MAPS KEY
define('gmkey', 'AIzaSyDk_iQ6QWOTHW-TWoXSFLwbcnhaxlcnXXk');

// CURRENCY API KEY  https://currencylayer.com/signup/free
define('currency_key', '6fe22d6838357e898a6cf2c186812a3f');

$active_group = 'default';
$query_builder = TRUE;

$whitelist = array( '127.0.0.1', '::1' );
if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)){ 
$host = "localhost";
} else {
$host = "phptravels-net-87462a2457";
}

$db['default'] = array(
'dsn'	=> '',
'hostname' => "localhost",
'username' => "root",
'password' => "",
'database' => "gondal",
'dbdriver' => 'mysqli',
'dbprefix' => '',
'pconnect' => FALSE,
'db_debug' => (ENVIRONMENT !== 'production'),
'cache_on' => FALSE,
'cachedir' => '',
'char_set' => 'utf8',
'dbcollat' => 'utf8_general_ci',
'swap_pre' => '',
'encrypt' => FALSE,
'compress' => FALSE,
'stricton' => FALSE,
'failover' => array(),
'save_queries' => TRUE
);

