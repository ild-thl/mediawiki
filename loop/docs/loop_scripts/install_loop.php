<?php
/*
* hashtag => 'loop' for loop.eduloop.de
* domain => 'eduloop.de'
* token
* dbname
* dbuser
* dbpass
* adminpass => Password of admin user in loop
* lang => language for loop
*/

define( 'MW_CONFIG_CALLBACK', 'Installer::overrideConfig' );
define( 'MEDIAWIKI_INSTALL', true );

require '../includes/WebStart.php';
require_once "../LoopSettings.php";
require_once "functions_lib.php";

use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\DBConnectionError;

$hashtag = mb_strtolower( trim( $_REQUEST[ 'hashtag' ] ), 'UTF-8' );
$domain = mb_strtolower( trim( $_REQUEST[ 'domain' ] ), 'UTF-8' );
$fqdn = $hashtag . '.' . $domain;
$token = $_REQUEST[ 'token' ];

if ( !check_token( $token, $fqdn ) ) {
	echo "Wrong token!";
	die;
}

$dbUser = $_REQUEST[ 'dbuser' ];
$dbPass = $_REQUEST[ 'dbpass' ];
$dbName = $_REQUEST[ 'dbname' ];

ob_start();


# Create DB User before the installer does.
$db = connect_db ( $wgLoopDbInstallHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, false );
if ( $db == false ) {
    exit;
}
$create_user = create_user( $db, $wgLoopDbInstallHost, $dbUser, $dbPass );
$db->close();
if ( $create_user == false ) {
    "ERROR! No install user. Aborting. ";
    exit;
}


$uploaddir = $IP . '/images/' . $fqdn;
if ( !is_dir( $uploaddir ) ) {
	mkdir( $uploaddir, 0774 );
}
$folders = array( "math", "export", "screenshots" );
foreach ( $folders as $folder ) {
    $dir = $IP . '/images/' . $fqdn . '/' . $folder;
    if ( !is_dir( $dir ) ) {
        mkdir( $dir, 0774 );
    }
}
$siteName = mb_strtoupper( $hashtag, 'UTF-8' );
$adminName = 'Administrator';
$options = array();
$options[ 'dbname' ] = $dbName;
$options[ 'dbuser' ] = $dbUser;
$options[ 'dbpass' ] = $dbPass;
$options[ 'pass' ] = $_REQUEST[ 'adminpass' ];
$options[ 'lang' ] = $_REQUEST[ 'lang' ];
$options[ 'server' ] = 'https://' . $fqdn;
$options[ 'confpath' ] = $IP . '/LocalSettings/tmp';
$options[ 'scriptpath' ] = '/mediawiki';
$options[ 'dbserver' ] = $wgLoopDbInstallHost;
$options[ 'dbtype' ] = 'mysql';
$options[ 'installdbuser' ] = $wgLoopDbInstallUser;
$options[ 'installdbpass' ] = $wgLoopDbInstallPass; 
$options[ 'skins' ] = "Loop";
$options[ 'extensions' ] = ["Loop", "FlaggedRevs", "Cite", "ConfirmEdit", "EmbedVideo", "Lingo", "Math", "MsUpload", "Quiz", "Score", "WikiEditor"];

$installer = new CliInstaller( $siteName, $adminName, $options );
$status = $installer->doEnvironmentChecks();
$installer->execute();

if ( strpos ( $_SERVER['SERVER_NAME'], "eduloop.de" ) !== false ) { # don't delete DB user on devloop 

    $db2 = connect_db ( $wgLoopDbInstallHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, $dbName );
    if ( $db2 != false ) {
        # Delete obsolete DB users
        try {
            $hosts = [$wgLoopDbInstallHost];
            $db2->begin( __METHOD__ );
            foreach ( $hosts as $host ) {
                $fullName = $db2->addQuotes( $dbUser ) . '@' . $db2->addQuotes( $host );
                $db2->query( "DROP USER IF EXISTS $fullName", __METHOD__ );
            }
            $db2->commit( __METHOD__ );
        } catch ( DBQueryError $dqe ) {
            echo "User could not be deleted: $dqe";
        }
        $create_user = create_user( $db2, $wgLoopDbHost, $dbUser, $dbPass );
        if ( $create_user ) {
            user_grant_privileges ( $db2, $dbUser, $dbName, $wgLoopDbHost );
        }
        $db2->close();
    }
}
shell_exec( "$IP/maintenance/update.php --wiki $fqdn --quick" ); # um sicher zu gehen, Update noch mal ausführen

ob_end_flush();