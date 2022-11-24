<?php
exit;
# Diese Datei erstellt einmalig die Auth Datenbank und Tabelle, mit der Moodalis umgangen wird.
# Muss nur einmal pro Server ausgeführt werden.

require "../LoopSettings.php";
require_once "functions_lib.php";

# Create DB User before the installer does.
$db = connect_db ( $wgLoopDbInstallHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, false );
if ( $db == false ) {
    echo "Could not connect to DB. ";
    exit;
}
$success = create_db( $db, "loop_auth" );
if ( $success ) {
    echo "DB Created or existed already. ";
} else {
    echo "Could not create DB. ";
    exit;
}
$db->close();


$db2 = connect_db ( $wgLoopDbInstallHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, "loop_auth" );
try {
    $db2->begin( __METHOD__ );
    $loop_auth = $db2->addIdentifierQuotes( "loop_auth" );
    $username = $db2->addIdentifierQuotes( "username" );
    $auth_json = $db2->addIdentifierQuotes( "auth_json" );

    $db2->query( "CREATE TABLE IF NOT EXISTS $loop_auth( $username varchar(128) NOT NULL, $auth_json TEXT NOT NULL, PRIMARY KEY ($username) )", __METHOD__ );
    $db2->commit( __METHOD__ );
    echo "Table created.";
} catch ( DBQueryError $dqe ) {
    $db2->rollback( __METHOD__ );
    echo "Table could not be created: $dqe";
}
$db2->close();