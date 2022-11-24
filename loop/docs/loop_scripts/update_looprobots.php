<?php
require '../includes/WebStart.php';
require_once "../LoopSettings.php";
require_once "functions_lib.php";

$loop = mb_strtolower( trim( $_REQUEST['loop'] ) );
$token = $_REQUEST['token'];

if ( !check_token( $token, $loop ) ) {
	echo "Wrong token!";
	die;
}
global $IP;
$robots_text = $_REQUEST[ 'robotstext' ];
$robots_folder = "$IP/robots";
$robots_file = "$robots_folder/$loop.txt";

if ( !is_dir( $robots_folder ) ) {
	mkdir( $robots_folder, 0774 );
}

$filehandle = fopen( $robots_file, "w+" );	
fwrite( $filehandle, $robots_text );
fclose( $filehandle );	
dd("$IP/robots/$loop.txt");

echo "\nrobots.txt for ".$loop." written\n";