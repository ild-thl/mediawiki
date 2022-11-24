<?php

$image_url = '';
$image_url = utf8_encode( $_REQUEST['url'] );
$fqdn = '';
$fqdn = utf8_encode( $_REQUEST['fqdn'] );
$image_overwrite = '';
$image_overwrite = utf8_encode( $_REQUEST['overwrite']) ;
$image_token = '';
$image_token = utf8_encode( $_REQUEST['token'] );
$username = '';
$username = utf8_encode( $_REQUEST['u'] );

if ( $image_url != '' && $fqdn != '' ) {
	
	$fqdn = strtolower( $fqdn );
	
    // Authorisierung
	if ( ( $fqdn != '' ) && ( $image_token != '' ) ) {

        require_once "functions_lib.php";
        
        if ( check_token( $image_token, $fqdn ) ) {
			$return = true;	
		} else {
			$return = false;
			print "<font class=\"mbared\">{INSCR_ERROR} 9</font>";
		}
	} else {
		$return = false;
		print "<font class=\"mbared\">{INSCR_ERROR} 8</font>";
	}
	
	// Wenn authorisiert
	if ( $return ) {
        
        require_once '../includes/WebStart.php';
        global $IP;
        $images_folder = "$IP/images";
        $maintenance_folder = "$IP/maintenance";
        $tmp_folder = "$IP/loop/tmp";

		$url = $wgLoopImportImagesUrl . "?url=" . $image_url;
		
		$filename = "";
		$filename = substr( $image_url, strripos( $image_url, "/" ) );
		
		$ts = time();
		define( 'TP', $tmp_folder . "/" . $fqdn . $ts );
		if ( !is_dir( TP ) )
			mkdir( TP, 0775 );
		else {
			print "<font class=\"mbared\">{INSCR_ERROR} 6: {INSCR_could_not_copy_file}</font>";
			unset( $filename );
		}
		
		if ( $filename ) {
			
			# copy file into tmp dir
			$file_copy_return = file_put_contents( TP . $filename, file_get_contents( $url ) );
			
			if ( $file_copy_return === FALSE)
				print "<font class=\"mbared\">{INSCR_ERROR} 1: {INSCR_could_not_copy_file}</font>";
			else {
				
				# import file into loop
				$exec = "php $maintenance_folder/importImages.php ".TP."/ --fqdn $fqdn --skip-dupes --user=$username --comment='Importing image from Moodalis'";
				# overwrite if asked
				if ( $image_overwrite ) {
					$exec .= " --overwrite";
                }
				$shell_return = shell_exec( $exec );
				# delete file from tmp dir
				$exec = "rm ".TP."/*";
				shell_exec( $exec );
				
				# generate output
				if ( strpos( $shell_return, "skipping" ) != FALSE ) {
					print "<font class=\"mbayellow\">{INSCR_skip_existing_file}</font>";
				}
				else {
					print "<font class=\"mbagreen\">{INSCR_file_copied}</font>";
					
					// setze Gruppe auf Schreibrechte
					$exec = "sudo chown -R $wgLoopApacheUser $images_folder/$fqdn/ ";
					shell_exec( $exec );
					
					$exec = "sudo chmod -R ug+rw $images_folder/$fqdn/ ";
					shell_exec( $exec );
				}
			}
		}
		if ( is_dir( TP ) )
			rmdir( TP );
	} else {
		print "<font class=\"mbared\">{INSCR_ERROR} 7: {INSCR_could_not_copy_file}</font>";
	}
} else {
	print "<font class=\"mbared\">{INSCR_ERROR} 2: {INSCR_could_not_copy_file}</font>";
}
?>