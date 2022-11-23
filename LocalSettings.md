# Add this to the end of your LocalSettings.php

# SKIN #
$wgDefaultSkin = 'Loop';
wfLoadSkin( 'Loop' );

# EXTENSION REGISTRATION #
wfLoadExtension( 'Cite' );
wfLoadExtension( 'ConfirmEdit' );
wfLoadExtension( 'ConfirmEdit/ReCaptchaNoCaptcha' );
wfLoadExtension( 'EmbedVideo' );
wfLoadExtension( 'FlaggedRevs' );
wfLoadExtension( 'Lingo' );
wfLoadExtension( 'Math' );
wfLoadExtension( 'MsUpload' );
wfLoadExtension( 'Quiz' );
wfLoadExtension( 'Score' );
wfLoadExtension( 'SyntaxHighlight_GeSHi' );
wfLoadExtension( 'WikiEditor' );

wfLoadExtension( 'Loop' );

# GENERAL CONFIG #
$wgArticlePath = "/loop/$1";
$wgLogo = '';
$wgDefaultUserOptions['math'] = 'mathml';

# EXPORT SETTINGS #
$wgText2Speech = false; #requires $wgText2SpeechServiceUrl
$wgAudioExport = false; #requires $wgText2SpeechServiceUrl
$wgPdfExport = false; #requires $wgXmlfo2PdfServiceUrl and $wgXmlfo2PdfServiceToken
$wgXmlExport = false; 
$wgHtmlExport = false; 
#$wgScormExport = false; 
#$wgEpubExport = false; 

# SERVICE URLS #
    # mathoid (optional for Math)
    //$wgMathMathMLUrl = '';

    # RestBase 
    //$wgMathFullRestbaseURL = '';

    # PDF (optional)
    //$wgXmlfo2PdfServiceUrl = '';
    //$wgXmlfo2PdfServiceToken = '';

    # Audio (optional)
    //$wgText2SpeechServiceUrl = '';

    # Pygments (SyntaxHighlight_GeSHi)
    //$wgPygmentizePath = '';

    # H5P custom/oncampus hosting (optional)
    //$wgH5PHostUrl = '';

    # Screenshot tool URL (optional)
    //$wgScreenshotUrl = '';

    # NgSpice Service URL (optional)
    //$wgNgSpiceUrl = '';
    
    # ConfirmEdit reCAPTCHA keys (optional)
    //$wgReCaptchaSiteKey = '';
    //$wgReCaptchaSecretKey = '';

# USER RIGHTS #
$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['*']['read'] = false;
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
$wgShowSQLErrors = true;
$wgDebugDumpSql  = true;
$wgShowDBErrorBacktrace = true;
$wgShowExceptionDetails = true;
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set('display_startup_errors', 1);
$wgDevelopmentWarnings = true;
