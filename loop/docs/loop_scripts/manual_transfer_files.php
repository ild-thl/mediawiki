<?php
#exit;

require_once '../includes/WebStart.php';

# /TRANSFER FILES ########################################################################################
if ( !array_key_exists( "mode", $_REQUEST ) || !array_key_exists( "loop", $_REQUEST ) ) {
	exit;
}
$mode = $_REQUEST["mode"];
$loop = $_REQUEST["loop"];

if ( empty($loop) ) {
	exit;
}

$hashtag = $loop;
$old_loop = "$hashtag.oncampus.de";
$new_loop = "$hashtag.eduloop.de";
if ( $mode != "files" && $mode != "dirs" ) {
	exit;
}
require_once "functions_lib.php";
require_once "../LoopSettings.php";

global $IP;
$query = "chmod -R 0775 $IP/images/$new_loop";
ob_start();
    $exec = "chown -R $wgLoopApacheUser $IP/images/$new_loop/ ";
    shell_exec( $exec );

    $exec = "chmod -R ug+rw $IP/images/$new_loop/ ";
    shell_exec( $exec );

ob_end_flush();


ob_start();

# nicht vorhandener ordner returnt true?
#dd(is_dir("/opt/www/eduloop.de/mediawiki/images/vfhfin.eduloop.de/f/f9"));
$loop1_ssh2 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );

$dirs = ssh_fetch_images_data ( $loop1_ssh2, $old_loop, "directories" );
create_directories ($dirs);
$files = ssh_fetch_images_data ( $loop1_ssh2, $old_loop, "files" );

#dd($dirs, $files);
ssh2_exec( $loop1_ssh2, "exit" ); # new connection required
if ( $mode == "dirs" ) {
	dd($dirs);
}
$loop1_ssh3 = connect_ssh ( "loop.oncampus.de", $loop1_user, $loop1_pass );
ssh_transfer_images ( $loop1_ssh3, $files, $dirs );
ssh2_exec( $loop1_ssh3, "exit" );
ob_end_flush();

dd($dirs, $files);
# /TRANSFER FILES ########################################################################################

function ssh_fetch_images_data ( $ssh, $loop, $mode = "files" ) {

    $path = "/opt/www/loop.oncampus.de/mediawiki/images/$loop";

    # excluding dirs: audio, math, pdf, temp, thumb
    switch ( $mode ) {
        case "directories":
            $command = "find $path -type d | egrep -v \"/deleted|/audio|/math|/pdf|/temp|/thumb|/archive|/tmp\"";
        break;
        case "files":
            $command = "find $path | egrep -v \"/deleted/|/audio/|/math/|/pdf/|/temp/|/archive/|/tmp/|/thumb/|/deleted$|/audio$|/math$|/pdf$|/temp$|/thumb$|/archive$|/tmp$\"";
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
function connect_ssh ( $sshHost, $sshUser, $sshPass ) {

    $connection = ssh2_connect( $sshHost, 22);

    if ( ssh2_auth_password( $connection, $sshUser, $sshPass ) ) {
      return $connection;
    } else {
      echo "ERROR! Could not connect to SSH $sshHost";
      return false;
    }

}

function create_directories ( $dirs ) {
    echo "create dirs<br>";
    foreach ( $dirs as $dir ) {
        #echo "$dir<br>";
        try {
            #echo "try<br>";
            global $IP, $old_loop, $new_loop;
            #$tmpdir =  $IP . substr($dir, 35);
            $old_loop_strlen = strlen( $old_loop );
            $tmpdir = "$IP/images/$new_loop" . substr( $dir, 43 + $old_loop_strlen );
            #dd($tmpdir);
            #dd($dirs, $dir,  $tmpdir);
            if ( !is_dir( $tmpdir ) ) {
                mkdir( $tmpdir, 0775 );
                echo "Created dir: $tmpdir<br>";
            } else {
                #echo "already exists: $tmpdir<br>";
            }
        } catch ( Exception $e ) {
            echo "Could not create folder: $tmpdir<br>";
        }
    }
    return;
}

# todo new loop folder
function ssh_transfer_images ( $ssh, $files, $dirs ) {
    global $IP, $old_loop, $new_loop;
    foreach ( $files as $file ) {
        if ( !in_array( $file, $dirs )) {
            $remote_file = $file;
            $old_loop_strlen = strlen( $old_loop );
            $local_file = "$IP/images/$new_loop" . substr( $remote_file, 43 + $old_loop_strlen );

            try {
                if (!is_file($local_file) ) {
                    echo $local_file . "<br>";
                    $received = ssh2_scp_recv ( $ssh, $remote_file, $local_file );
                }
                #dd($received, $remote_file, $local_file,$files, $dirs);
            } catch ( Exception $e ) {
                echo "Could not create file: $local_file<br>";
            }
        }
    }
    #ssh2_exec( $ssh, "chmod -R 0775 $IP/images/$new_loop" );
    return;
}
