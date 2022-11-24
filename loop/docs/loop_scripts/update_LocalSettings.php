<?php
/*
* loop => loop.oncampus.de
* token 
* config_text => content for LocalSettings.php
* overwrite => 'yes' for overwriting existing file
*/

require_once '../includes/WebStart.php';
require_once 'functions_lib.php';

$loop = mb_strtolower( trim( $_REQUEST[ 'loop' ] ) );
$token = $_REQUEST['token'];

if ( ! check_token( $token, $loop ) ) {
	echo "Wrong token!";
	die;
}

$config_text = $_REQUEST[ 'configtext' ];
$overwrite = $_REQUEST[ 'overwrite' ];

write_localSettings( $overwrite, $loop, $config_text );