<?php
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

$token = $_REQUEST[ 'token' ];
$old_loop = $_REQUEST[ 'oldloop' ];
$new_loop = $_REQUEST[ 'newloop' ];
$dbPass = $_REQUEST[ 'dbpass' ];
$dbName = $_REQUEST[ 'dbname' ];
$dbUser = $dbName;
$debug = isset( $_REQUEST[ 'debug' ] ) ? boolval( $_REQUEST[ 'debug' ] ) : false;

if ( !check_token( $token, $new_loop ) ) {
	echo "Wrong token!";
	die;
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
set_time_limit(3600);

# Connect to LOOP2 DB for DB creation
$loop2_db = connect_db ( $wgLoopDbInstallHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, false );
if ( is_object( $loop2_db ) ) {
    echo "Connected to LOOP2 DB. ";
    $loop1_ssh1 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );
    echo "Connected to LOOP1 SSH. ";
    $loop1_sqldump_path = ssh_export_sql( $loop1_ssh1, $loop1_user, $loop1_pass, $dbName, "$tmpdir$dbName.sql" );
    ssh2_exec( $loop1_ssh1, "exit" );

    if ( $loop1_sqldump_path != false ) {
        echo "SQL Dump received. ";
        # Create DB with given name on LOOP2
        $success = create_db( $loop2_db, $dbName );
        $loop2_db->close();
        if ( $success ) {
            # New connection; this time with DB name
            $loop2_db = connect_db ( $wgLoopDbInstallHost, $wgLoopDbInstallUser, $wgLoopDbInstallPass, $dbName );
            echo "Connected to LOOP2 DB (again). ";
            if ( is_object( $loop2_db ) ) {
                # Create User with privileges
                $create_user = create_user( $loop2_db, $wgLoopDbHost, $dbUser, $dbPass );
                if ( $create_user ) {
                    user_grant_privileges ( $loop2_db, $dbUser, $dbName, $wgLoopDbHost );
                    echo "DB User ready. ";
                }

                # Import SQL dump
                $success = import_sql ( $loop2_db, $loop1_sqldump_path );

                if ( $success ) {
                    echo "DB imported. ";
                    if ( !$debug ) {
                        $loop1_ssh2 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );

                        $dirs = ssh_fetch_images_data ( $loop1_ssh2, $old_loop, "directories" );
                        create_directories ($dirs);
                        $files = ssh_fetch_images_data ( $loop1_ssh2, $old_loop, "files" );

                        ssh2_exec( $loop1_ssh2, "exit" ); # new connection required

                        $loop1_ssh3 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );
                        ssh_transfer_images ( $loop1_ssh3, $files, $dirs );
                        ssh2_exec( $loop1_ssh3, "exit" );
                        echo "Transfered images. ";
                    }
                    # Write basic LocalSettings (if there is none)
                    $content = get_basic_localsettings( $new_loop, $dbName, $dbPass );
                    write_localSettings( "no", $new_loop, $content );

                    echo "<br><br>";
                    if ( !$debug ) {
                        echo shell_exec( "$IP/maintenance/update.php --wiki $new_loop --quick" );
                        echo "Update 1 done.<br><br> ";
                        sleep(3);
                        echo shell_exec( "$IP/maintenance/update.php --wiki $new_loop --quick" );
                        echo "Update 2 done.<br> ";
                        #ssh2_exec( $loop1_ssh4, "exit" );

                        $loop1_ssh4 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );
                        ssh_transfer_images ( $loop1_ssh4, $files, $dirs );
                        ssh2_exec( $loop1_ssh4, "exit" );

                        echo "Finished!";
                    } else {
                        echo "Debug Mode - no update scripts were run. Finished.";
                    }
                } else {
                    echo " ABORTED: SQL could not be imported.";
                }
                $loop2_db->close();
            }
        }
        shell_exec("rm $loop1_sqldump_path");
    } else {
        echo "ERROR! Did not receive SQL dump. ";
    }
}
ob_end_flush();

# todo new loop folder
function ssh_transfer_images ( $ssh, $files, $dirs ) {
	set_time_limit(600);
    global $IP, $old_loop, $new_loop;
    foreach ( $files as $file ) {
        if ( !in_array( $file, $dirs )) {
            $remote_file = $file;
            $old_loop_strlen = strlen( $old_loop );
            $local_file = "$IP/images/$new_loop" . substr( $remote_file, 43 + $old_loop_strlen ); #43 is strlen of images folder in loop1
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
    shell_exec( "chmod -R 0775 $IP/images/$new_loop" );
    return;
}

function ssh_fetch_images_data ( $ssh, $loop, $mode = "files" ) {

    $path = "/opt/www/loop.oncampus.de/mediawiki/images/$loop";

    # excluding dirs: audio, math, pdf, temp, thumb
    switch ( $mode ) {
        case "directories":
            $command = "find $path -type d | egrep -v \"/deleted|/audio|/math|/pdf|/temp|/thumb\"";
        break;
        case "files":
            $command = "find $path | egrep -v \"/deleted/|/audio/|/math/|/pdf/|/temp/|/thumb/|/deleted$|/audio$|/math$|/pdf$|/temp$|/thumb$\"";
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



function ssh_export_sql( $ssh, $dbUser, $dbPass, $dbName, $dumpName ) {

    $remote_file = "/home/oc_loop/loop2/$dbName.sql";
    $command = 'mysqldump -u '.$dbUser.' -p'.$dbPass.' '. $dbName . " > $remote_file";

    ssh2_exec( $ssh , $command );

    sleep(5);
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
