
	#General
	$wgLogo = '';
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
	$wgText2Speech = false; #requires $wgText2SpeechServiceUrl
	$wgAudioExport = false; #requires $wgText2SpeechServiceUrl
	$wgPdfExport = false; #requires $wgXmlfo2PdfServiceUrl and $wgXmlfo2PdfServiceToken
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

	# NgSpice Service URL
	$wgNgSpiceUrl = '';
	
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
