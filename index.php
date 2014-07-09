<?php
session_start('linkedin');
// API Keys and codes
$api_key = '77bdyvpgspv2g9';
$api_secret = 'LHrWbXI8LIBeTbDi';
$http = ( ! empty( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? "https://" : "http://";
var_dump($http.$_SERVER['SERVER_NAME']);

if( isset( $_SESSION['redirect_to'] ) ) {
	$url = $_SESSION['redirect_to'] . '?' . http_build_query( $_GET );
	unset( $_SESSION['redirect_to'] );
	Header("Location: $url");
	exit;
}

require( './lib/fpdf/fpdf.php' );
require( './classes/class-fpdf-html.php' );
require( './classes/class-linkedin-api-controller.php' );

// /v1/people/~:(email-address,first-name,last-name,picture-url,phone-numbers,main-address,headline,date-of-birth,location:(name,country:(code)),industry,summary,specialties,positions,educations,site-standard-profile-request,public-profile-url,interests,publications,languages,skills,certifications,courses,volunteer,honors-awards,last-modified-timestamp)

$settings = array(
    'api_key' => '77bdyvpgspv2g9',
    'api_secret' => 'LHrWbXI8LIBeTbDi',
    'redirect_uri' => 'https://local.test.linkedin',
    'scope' => 'r_emailaddress r_fullprofile r_contactinfo'
);
$linkedin_api = new LinkedIN_API_Controller( $settings );

//echo "<pre>";
//var_dump($linkedin_api);
//exit;

if ( ! empty( $_SESSION['state'] ) && isset( $_GET['code'] ) && isset( $_GET['state'] ) ) {

	// Authorized, did everything go as planned?
	if ( $_SESSION['state'] === $_GET['state'] ) {

		$linkedin_api->getAccessToken( $_GET['code'] );
		unset( $_SESSION['state'] );
	} else {
		// CSRF attack or messed up states
		echo "States do not match";
		exit;
	}
} else if ( ! $linkedin_api->hasAccessToken() ) {
	?>
	<button onclick="location.href = '<?php echo $linkedin_api->getAuthorizationCode( false ); ?>'">Authorize</button>
	<?php
}

if ( $linkedin_api->hasAccessToken() ) {
	$resource = '/v1/people/~:(email-address,first-name,last-name,picture-url,phone-numbers,main-address,headline,date-of-birth,location:(name,country:(code)),industry,summary,specialties,positions,educations,site-standard-profile-request,public-profile-url,interests,publications,languages,skills,certifications,courses,volunteer,honors-awards,last-modified-timestamp)';
	$result = $linkedin_api->fetch( $resource );
	
	echo "<pre>";
	var_dump($result);
	
} else {
	echo 'Nothing here';
}
