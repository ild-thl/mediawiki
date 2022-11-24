<?php

require_once '../includes/WebStart.php';
require_once "../LoopSettings.php";

function check_token( $token = '', $fqdn = '' ) {

	global $wgMoodleData;
	if ( !empty ( $fqdn ) && !empty( $token ) && array_key_exists( "moodalis.oncampus.de", $wgMoodleData ) ) {
		if ( $token == md5 ( strtolower( $fqdn ) . $wgMoodleData["moodalis.oncampus.de"]["token"] ) ) {
			return true;	
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function write_localSettings( $overwrite, $loop, $content ) {
	global $IP;

	$filename = $IP . '/LocalSettings/LocalSettings_' . $loop . '.php';

	if ( $overwrite == 'yes' || !file_exists( $filename )) {
		$tmp = "";
	} else {
		$tmpdir = $IP . '/LocalSettings/tmp';
		if ( !is_dir( $tmpdir ) ) {
			mkdir( $tmpdir, 0774 );
		}
		$filename = $tmpdir.'/LocalSettings_' . $loop . '.php';
		$tmp = " (tmp)";
	}
	
	$filehandle = fopen( $filename, "w+" );	
	fwrite( $filehandle, $content );
	fclose( $filehandle );	
	chmod( $filename, 0774 );
	echo "\nLocalSettings_$loop.php updated$tmp\n";
}

# connect to DB
function connect_db ( $dbHost, $dbUser, $dbPass, $dbName = false ) {

    try {
        $db = Database::factory( 'mysql', [
            'host' => $dbHost,
            'user' => $dbUser,
            'password' => $dbPass,
            'dbname' => $dbName,
            'flags' => 0,
            'tablePrefix' => ""
        ] );
		return $db;
    } catch ( DBConnectionError $e ) {
        echo "ERROR! Could not connect to DB. $e";
        return false;
    }
}

function create_user ( $db, $dbHost, $dbUser, $dbPass ) {

    try {
        $escPass = $db->addQuotes( $dbPass );
        $fullName = $db->addQuotes( $dbUser ) . '@' . $db->addQuotes( $dbHost );
        $db->begin( __METHOD__ );
        $db->query( "CREATE USER $fullName IDENTIFIED BY $escPass", __METHOD__ );
        $db->commit( __METHOD__ );
        return true;
    } catch ( DBQueryError $dqe ) {
        if ( $db->lastErrno() == 1396 /* ER_CANNOT_USER */ ) {
            $db->rollback( __METHOD__ );
            echo "User already exists! Continuing... ";
			return true;
        } else {
            $db->rollback( __METHOD__ );
            echo "ERROR! Could not create user. $dqe"; 
            return false;
        }
    }
}

function user_grant_privileges ( $db, $dbUser, $dbName, $dbHost ) {

    try {
        $fullName = $db->addQuotes( $dbUser ) . '@' . $db->addQuotes( $dbHost );
        $db->begin( __METHOD__ );
        $db->query( "GRANT ALL PRIVILEGES ON ".$db->addIdentifierQuotes( $dbName ).".* TO $fullName", __METHOD__ );
        $db->commit( __METHOD__ );

    } catch ( DBQueryError $dqe ) {
		$db->rollback( __METHOD__ );
        echo "ERROR! User rights could not be set: $dqe";
    }
}

function create_db ( $db, $dbName ) {

    try {
        $fullDbName = $db->addIdentifierQuotes( $dbName );
        $db->begin( __METHOD__ );
        $db->query( "CREATE DATABASE IF NOT EXISTS $fullDbName", __METHOD__ );
        $db->commit( __METHOD__ );
        return true;
    } catch ( DBQueryError $dqe ) {
        echo "ERROR! $dqe";
        return false;
    }
    return;
}


# basiert auf Kevins copy_loop.php
function import_sql ( $db, $filename ) {

	// Temporary variable, used to store current query
	$templine = '';

	// Read in entire file
	$lines = file($filename);

	// Loop through each line
	foreach ($lines as $line)
	{
		// Skip it if it's a comment
		if (substr($line, 0, 2) == '--' || $line == '')
			continue;

			// Add this line to the current segment
			$templine .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if ( substr(trim( $line ), -1, 1 ) == ';' )
			{
                try {
                    // Perform the query
                    $db->begin( __METHOD__ );
                    $db->query( $templine, __METHOD__ );
                    $db->commit( __METHOD__ );
                } catch ( DBQueryError $dqe ) {
					$db->rollback( __METHOD__ );
                    echo "ERROR! DB query failed: $dqe\n";
                    return false;
                }
				$templine = '';
			}
	}
	return true;

}
