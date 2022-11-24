<?php
exit;
/*
* oldloop => 'loop.oncampus.de'
* newloop => 'loop.eduloop.de'
* token (newloop)
* dbname
* dbuser wird von dbname übernommen
* dbpass
*/
if ( !function_exists ( "ssh2_connect" ) )  {
	echo "PECL SSH2 needs to be installed to transfer LOOPs from a different Server.";
	die;
}

require_once "functions_lib.php";

$hashtag = "";
$dbPass = "";
$dbName = "";
$adminpass = "";


$token = "";
$domain = "eduloop.de";
$old_loop = "$hashtag.oncampus.de";
$new_loop = "$hashtag.eduloop.de";

$ls_file = "$IP/LocalSettings/LocalSettings_$new_loop";

$dbUser = $dbName;
$debug = isset( $_REQUEST[ 'debug' ] ) ? boolval( $_REQUEST[ 'debug' ] ) : false;

if ( !check_token( $token, $new_loop ) ) {
	echo "Wrong token!";
	#die;
}

ini_set('memory_limit', '512M');

require_once '../includes/WebStart.php';
require_once "../LoopSettings.php";
#exit;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\DBConnectionError;

try {
    $tmpdir = $IP . '/loop/tmp/';
    if ( !is_dir( $tmpdir ) ) {
        mkdir( $tmpdir, 0774 );
    }
} catch ( Exception $e ) {

}

ob_start();

/*
1. drop db
1a. rename localsettings file
2. call installer
3. loop1_ dumpbackup
4. ssh transfer dump
4a. re-rename localsettings file
5. update
6. importdump
7. files in einen ordner schieben
8. alte files löschen
9. files importieren
10. saveallpages
11. reviewallpages
12. migrateterminology
13. migrateglossary

*/
# Connect to LOOP2 DB for DB creation
$loop2_db = connect_db ( $wgLoopDbInstallHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, false );
if ( is_object( $loop2_db ) ) {
    echo "Connected to LOOP2 DB. ";

    $loop1_ssh1 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );
    echo "Connected to LOOP1 SSH. ";

    # 1. FETCH XML

    if ( !is_file( "$tmpdir$dbName.xml" ) ) {
        $loop1_xmldump_path = ssh_export_xml( $loop1_ssh1, $old_loop, "$tmpdir$dbName.xml" );
    } else {
        $loop1_xmldump_path = "$tmpdir$dbName.xml";
    }
    ssh2_exec( $loop1_ssh1, "exit" );


    if ( $loop1_xmldump_path != false ) {
        echo "XML Dump received. ";

        # 2. RENAME LOCALSETTINGS
        if ( is_file( "$ls_file.php" ) ) {
            rename( "$ls_file.php", $ls_file."2.php" );
            echo "Renamed LocalSettings. ";
        }

        # 3. DROP DB
        drop_db( $loop2_db, $dbName );
        echo "Deleted DB. ";

        # 4. DELETE FILES
        echo exec("rm -r $IP/images/$hashtag.eduloop.de/");
        echo "Deleted local files. ";

        # 5. INSTALL FRESH
        call_installer ( $hashtag, $adminpass, "de-formal", $dbName, $dbPass );
        echo "Called Installer. ";

        # 6. RE-RENAME LOCALSETTINGS
        if ( is_file( $ls_file."2.php" ) ) {
            rename( $ls_file."2.php", "$ls_file.php" );
            echo "Re-renamed LocalSettings. ";

        # 7. PREPARE FILES
            $loop1_ssh2 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );

            $dirs = ssh_fetch_images_data ( $loop1_ssh2, $old_loop, "directories" );
            #create_directories ($dirs);
            $files = ssh_fetch_images_data ( $loop1_ssh2, $old_loop, "files" );

            ssh2_exec( $loop1_ssh2, "exit" ); # new connection required
            echo "Prepared files. ";

        # 8. DOWNLOAD FILES
            $loop1_ssh3 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );
            ssh_transfer_images_dump ( $loop1_ssh3, $files, $dirs );
            ssh2_exec( $loop1_ssh3, "exit" );
            echo "Transfered images. ";

            exec("chown -R loop2:apache /opt/www/eduloop.de/mediawiki/images/$new_loop");
            exec("chmod -R ug+rw loop2:apache /opt/www/eduloop.de/mediawiki/images/$new_loop");

        # 9. IMPORT FILES
            echo exec("php $IP/maintenance/importImages.php --wiki $new_loop $IP/images/$new_loop/dump/");
            echo "Imported files. ";

        # 10. RUN UPDATE
            $updcmd = "$IP/maintenance/update.php --quick --wiki $new_loop";
            echo exec("php $updcmd");

        # 11. IMPORT PAGES
            echo exec("php $IP/maintenance/importDump.php --wiki $new_loop $tmpdir$dbName.xml");
            echo "Imported XML Dump. ";

        # 12. ReviewAll
            #echo exec("php $IP/extensions/FlaggedRevs/maintenance/reviewAllPages.php --wiki $new_loop --username LOOP_SYSTEM");
            echo "Reviewed all pages. ";

        # 13. Saveall
            #LoopUpdater::saveAllWikiPages();
            #echo "Saved all pages. ";
            echo "<br><br>TODO: Struktur überführen, ImportImages, SaveAllPages<br>";
            echo "php $IP/maintenance/importImages.php --wiki $new_loop $IP/images/$new_loop/dump/";
            echo "<br>";
            echo "ZIP Dateien fetchen";
            echo " php $IP/loop/manual_transfer_files.php ";
            echo "<br>";
            echo "php $IP/extensions/FlaggedRevs/maintenance/reviewAllPages.php --wiki $new_loop --username LOOP_SYSTEM";
            echo "<br>Save all pages: ";
            echo "https://$new_loop/loop/Spezial:LoopManualUpdater";
            echo "<br>";
        }

        # DELETE DUMP FILES
        shell_exec("rm $loop1_xmldump_path");
        shell_exec("rm $IP/images/$new_loop/dump/");
    } else {
        echo "ERROR! Did not receive XML dump. ";
    }
}
ob_end_flush();

# todo new loop folder
function ssh_transfer_images_dump ( $ssh, $files, $dirs ) {
    global $IP, $old_loop, $new_loop;
    if ( !is_dir( $IP. "/images/$new_loop/" ) ) {
        mkdir( $IP. "/images/$new_loop/", 0775 );
    }
    if ( !is_dir( $IP. "/images/$new_loop/dump/" ) ) {
        mkdir( $IP. "/images/$new_loop/dump/", 0775 );
    }

    foreach ( $files as $file ) {
        if ( !in_array( $file, $dirs )) {
            $remote_file = $file;
            $old_loop_strlen = strlen( $old_loop );
            $remote_file_arr = explode( "/", $remote_file );
            $remote_file_name = end( $remote_file_arr );
            #dd($remote_file, $remote_file_name);
            $local_file = "$IP/images/$new_loop/dump/" . $remote_file_name;
            try {
                if ( !is_file( $local_file ) ) {
                    $received = ssh2_scp_recv ( $ssh, $remote_file, $local_file );
                }
                #dd($received, $remote_file, $local_file,$files, $dirs);
            } catch ( Exception $e ) {
                echo "Could not create file: $local_file<br>";
            }
        }
    }
    #shell_exec( "chmod -R 0775 $IP/images/$new_loop" );

    return;
}

function ssh_fetch_images_data ( $ssh, $loop, $mode = "files" ) {

    $path = "/opt/www/loop.oncampus.de/mediawiki/images/$loop";

    # excluding dirs: audio, math, pdf, temp, thumb
    switch ( $mode ) {
        case "directories":
            $command = "find $path -type d | egrep -v \"/deleted|/audio|/math|/pdf|/temp|/thumb|extracted/\"";
        break;
        case "files":
            $command = "find $path | egrep -v \"/deleted/|/audio/|/math/|/pdf/|/temp/|/thumb/|extracted/|/deleted$|/audio$|/math$|/pdf$|/temp$|/thumb$|.extracted$\"";
        break;
    }

    $result = ssh2_exec( $ssh , $command );

    $t0 = time();
    $out_buf = null;
    $span = 0;
    $done = 0;
    $duration = 60;
    do {
        $fread = fread($result, 8192);
        if ( $span > 5 && strlen($fread) == 0 ) {
            $done++;
        }
        $out_buf.= $fread;
        $t1 = time();
        $span = $t1 - $t0;

        sleep(1);
    } while (($span <= $duration) && $done <= 2);
    $cmd = stream_get_contents($result);
    #dd($cmd,$out_buf);
    #if ($mode == "files"){dd($out_buf);}
    if ( $out_buf != null ) {
        return explode("\n", $out_buf);
    }
    return $out_buf;
}


function drop_db ( $db, $dbName ) {

    try {
        $fullDbName = $db->addIdentifierQuotes( $dbName );
        $db->begin( __METHOD__ );
        $db->query( "DROP DATABASE IF EXISTS $fullDbName", __METHOD__ );
        $db->commit( __METHOD__ );
        return true;
    } catch ( DBQueryError $dqe ) {
        echo "ERROR! $dqe";
        return false;
    }
    return;
}



function ssh_export_xml( $ssh, $loop, $dumpName ) {

    $remote_file = "/home/oc_loop/loop2/$loop.xml";
    $command = 'php /opt/www/loop.oncampus.de/mediawiki/maintenance/dumpBackup.php --wiki='.$loop.' --full > ' . $remote_file;

    ssh2_exec( $ssh , $command );

    #sleep(5);
    $received = ssh2_scp_recv ( $ssh , $remote_file, $dumpName );
    if ( $received ) {
        $command = "rm $remote_file";
        ssh2_exec( $ssh , $command );
    } else {
        echo "ERROR! MySQL dump could not be received.";
        return false;
    }

    return $dumpName;
}

function connect_ssh ( $sshHost, $sshUser, $sshPass ) {

    $connection = ssh2_connect( $sshHost, 22);

    if ( ssh2_auth_password( $connection, $sshUser, $sshPass ) ) {
      return $connection;
    } else {
      echo "ERROR! Could not connect to SSH $sshHost";
      return false;
    }

}

function get_basic_localsettings( $loop, $dbName, $dbPass ) {
    return '<?php
# BASIC LOCAL SETTINGS AFTER LOOP UPGRADE
$wgSitename          = "'.strtoupper($loop).'";
$wgDBname            = "'.$dbName.'";
$wgDBuser            = "'.$dbName.'";
$wgDBpassword        = "'.$dbPass.'";
$wgServer            = "https://'.$loop.'";
$wgSecretKey         = "'.uniqid().'";
$wgUpgradeKey        = "'.uniqid().'";
$wgUploadDirectory   = $IP . "/images/'.$loop.'";
$wgUploadPath        = $wgScriptPath . "/images/'.$loop.'";';

}

function call_installer ( $hashtag, $adminpass, $lang, $dbName, $dbPass ) {
    global $wgMoodleData;
    $token = md5 ( $hashtag . '.eduloop.de' . $wgMoodleData["moodalis.oncampus.de"]["token"] );

    $url = "https://$hashtag.eduloop.de/mediawiki/loop/install_loop.php?hashtag=$hashtag&domain=eduloop.de&dbname=$dbName&dbpass=$dbPass&dbuser=$dbName&token=$token&lang=$lang&adminpass=$adminpass";

    $cha = curl_init();
    curl_setopt($cha, CURLOPT_URL, ($url));
    curl_setopt($cha, CURLOPT_ENCODING, "UTF-8" );
    curl_setopt($cha, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cha, CURLOPT_FOLLOWLOCATION, true);
    $return = curl_exec($cha);
    curl_close($cha);

    return true;
}

function create_directories ( $dirs ) {
    foreach ( $dirs as $dir ) {
        try {
            global $IP, $old_loop, $new_loop;
            #$tmpdir =  $IP . substr($dir, 35);
            $old_loop_strlen = strlen( $old_loop );
            $tmpdir = "$IP/images/$new_loop" . substr( $dir, 43 + $old_loop_strlen );
            #dd($tmpdir);
            #dd($dirs, $dir,  $tmpdir);
            if ( !is_dir( $tmpdir ) ) {
                mkdir( $tmpdir, 0775 );
            }
        } catch ( Exception $e ) {
            echo "Could not create folder: $tmpdir<br>";
        }
    }
    return;
}
