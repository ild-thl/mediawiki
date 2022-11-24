<?php
/*
* Initialize LOOP Settings
* token 
* settings => serialized array
*/

require_once '../includes/WebStart.php';
require_once 'functions_lib.php';

$token = $_REQUEST['token'];

if ( ! check_token( $token, $wgServerName ) ) {
	echo "Wrong token!";
	die;
}

$settings = $_REQUEST['settings'];
if ( empty($settings) ) {
	echo "No Settings!";
	die;
}
$settings = unserialize($settings);

$oldLoopSettings = new LoopSettings();
$oldLoopSettings->loadSettings();

foreach ( $oldLoopSettings as $key => $value ) {
	if ( $value != null ) {
		echo "Settings not empty. Aborting.";
		die;
	}
}

require_once "../LoopSettings.php";
$db = connect_db ( $wgLoopDbHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, $wgDBname);
foreach( $settings as $key => $value ) {
	if ( $value !== null ) {
		$key = strtolower($key);
		$key = ( $key = "captchaaddurl" ) ? "captchaddurl" : $key; # typo...
		try {
			$db->begin( __METHOD__ );
			$db->query( "DELETE FROM " . $db->addIdentifierQuotes( 'loop_settings' ) . " WHERE lset_property = " . $db->addQuotes( 'lset_'.$key ), __METHOD__ );
			$db->query( "INSERT INTO " . $db->addIdentifierQuotes( 'loop_settings' ) . "(lset_structure, lset_property, lset_value) VALUES (".$db->addQuotes( '0' ) . ",".$db->addQuotes( 'lset_'.$key ) . ",".$db->addQuotes( $value ) . ")", __METHOD__ );
			$db->commit( __METHOD__ );
			echo "Updated $key";
		} catch ( DBQueryError $dqe ) {
			echo "Error! ($key) - $dqe";
			$db->rollback( __METHOD__ );
		}
	}
}
$db->close();
echo "Done Updating LOOP Settings!";
