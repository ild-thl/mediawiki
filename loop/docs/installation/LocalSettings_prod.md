
# LOOP CONFIG

	# General
	$wgLogo = '';
	$wgArticlePath = "/loop/$1";
	wfLoadExtension('ConfirmEdit/ReCaptchaNoCaptcha');

	# USER RIGHTS #
	$wgGroupPermissions['*']['edit'] = false;
	$wgGroupPermissions['*']['read'] = true;
	$wgGroupPermissions['*']['createaccount'] = false;

	$wgAddGroups['sysop'] = array ( 'student_no_edit', 'student_edit', 'teacher_no_edit', 'teacher_edit', 'teacher_approve', 'author', 'sysop', 'shared' );
	$wgRemoveGroups['sysop'] = array ( 'student_no_edit', 'student_edit', 'teacher_no_edit', 'teacher_edit', 'teacher_approve', 'author', 'sysop', 'shared' );
	$localSettingsPermissions = array( 'createaccount' => $wgGroupPermissions['*']['createaccount'], 'read' => $wgGroupPermissions['*']['read'] );
	$wgGroupPermissions['*'] = array( 'createaccount' => $localSettingsPermissions['createaccount'], 'read' => $localSettingsPermissions['read'] );

	$unsetUserGroups = array ( 'editor', 'bureaucrat', 'autoconfirmed', 'reviewer', 'autoreview', 'editor' );
	foreach ( $unsetUserGroups as $userGroup ) {
		if ( isset( $wgGroupPermissions[ $userGroup ] ) ) {
			unset( $wgGroupPermissions[ $userGroup ] );
		}
	}

	# Export
	$wgText2Speech = false; # requires $wgText2SpeechServiceUrl
	$wgAudioExport = false; # requires $wgText2SpeechServiceUrl
	$wgPdfExport = false; # requires $wgXmlfo2PdfServiceUrl and $wgXmlfo2PdfServiceToken 
	$wgXmlExport = false;
	$wgHtmlExport = false;

	# mathoid
	$wgMathMathMLUrl = '';

	# RestBase
	$wgMathFullRestbaseURL = '';
	$wgDefaultUserOptions['math'] = 'mathml';

	# PDF
	$wgXmlfo2PdfServiceUrl = '';
	$wgXmlfo2PdfServiceToken = '';

	# Audio
	$wgText2SpeechServiceUrl = '';

	# Pygments (SyntaxHighlight_GeSHi)
	$wgPygmentizePath = '';

	# H5P custom/oncampus hosting
	$wgH5PHostUrl = '';

	# Screenshot tool URL
	$wgScreenshotUrl = '';
	
	/*
	* Detect LOOP by Servername and include specific LocalSettings
	*/
	if ( defined( 'MW_DB' ) ) {
		// Command-line mode and maintenance scripts (e.g. update.php)
		$server = MW_DB;
	} else {
		// Web server
		$server = $_SERVER['SERVER_NAME'];
	}

	$custom_config_file = "$IP/LocalSettings/LocalSettings_".$server.".php";
	$loop_exists = file_exists( $custom_config_file );
	if ( $loop_exists ) {
		require_once $custom_config_file;
	} else {
		echo "This LOOP is not available. Check configuration.";
		exit(0);
	}

	# DEV SETTINGS #
	/*
	$wgShowSQLErrors = true;
	$wgDebugDumpSql  = true;
	$wgShowDBErrorBacktrace = true;
	$wgShowExceptionDetails = true;
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
	ini_set('display_startup_errors', 1);
	$wgDevelopmentWarnings = true;
	*/
