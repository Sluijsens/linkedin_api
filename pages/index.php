<?php
session_start( 'linkedin' );

require("../lib/fpdf/fpdf.php");
require( '../classes/class-fpdf-html.php' );
require( '../classes/class-linkedin-api-controller.php' );

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
	$resource = '/v1/people/~:(email-address,first-name,last-name,picture-url,phone-numbers,main-address,headline,date-of-birth,location:(name,country:(code)),industry,summary,specialties,positions,educations,site-standard-profile-request,public-profile-url,interests,publications,languages,skills,certifications,courses,volunteer,honors-awards,last-modified-timestamp,recommendations-received)';
	$result = $linkedin_api->fetch( $resource );

	$pdf = new FPDF_HTML();

	$pdf->AddPage();

	// Start PDF page Block with Name, headline, email and linked in profile link
	$pdf->SetFont( 'Times', 'B', 22 );
	$pdf->Cell( null, 10, "{$result->firstName} {$result->lastName}", 0, 1 );
	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->Cell( null, 10, $result->headline, 0, 1 );
	$pdf->Write( 5, "{$result->emailAddress}, " );
	$pdf->SetTextColor( 0, 0, 255 );
	$pdf->SetFont( 'Arial', 'U' );
	$pdf->Write( 5, $result->publicProfileUrl, $result->publicProfileUrl );

	$pdf->WriteHTML( "<br /><br /><hr><br />" );
	// End Block

	/*
	 * fields
	 * 
	 * 
	  Phone numbers
	  Address
	  Headline
	  Date of birth
	  Location name
	  Location country code
	  Industry
	 * URL standard profile
	  URL public profiel
	 * Contact info (meestal niet ingevuld): address and phone numbers
	  Summary
	  Specialties
	  Current positions
	  Past positions
	  Educations

	  Interests
	  Publications
	  Languages
	  Skills
	  Certifications
	  Courses
	  Volunteer
	  Honors and awards
	  last-modified-timestamp


	 */

	/* Start Block Summary */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Summary", 0, 1 );

	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->SetTextColor( 0, 0, 0 );
	if ( isset( $result->summary ) ) {
		$pdf->WriteHTML( "<br />{$result->summary}" );
	} else {
		$pdf->WriteHTML( "<br />Geen samenvatting" );
	}
	$pdf->Image( $result->pictureUrl, 165, 10, null, null, "JPG" );
	$pdf->WriteHTML( "<br /><br /><hr><br />" );
	/* End Block Summary */

	/* Start Block Experience */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Experience", 0, 1 );

	foreach ( $result->positions->values as $position ) {

		$title = $position->title . ' at ' . $position->company->name;
		$start_date = date( "F Y", mktime( 0, 0, 0, $position->startDate->month, 0, $position->startDate->year ) );
		$end_date = ( $position->isCurrent ) ? "Heden" : date( "F Y", mktime( 0, 0, 0, $position->endDate->month, 0, $position->endDate->year ) );
		$working_period = "$start_date - $end_date";

		$pdf->SetFont( 'Times', 'B', 13 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $title, 0, 1 );

		$pdf->SetFont( 'Arial', null, 11 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $working_period, 0, 1 );

		if ( isset( $position->summary ) ) {
			$pdf->WriteHTML( "<br />{$position->summary}<br /><br />" );
		}
		$pdf->WriteHTML( "<br />" );
	}
	$pdf->WriteHTML( "<hr><br />" );
	/* End Block Experience */
	
	/* Start Block Educations */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Opleidingen", 0, 1 );

	foreach ( $result->educations->values as $education ) {

		$title = $education->schoolName;
		$start_date = $education->startDate->year;
		$end_date = ( empty( $education->endDate ) ) ? "Heden" : $education->endDate->year;
		$school_data = "{$education->degree}, {$education->fieldOfStudy} ($start_date - $end_date)";

		$pdf->SetFont( 'Times', 'B', 13 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $title, 0, 1 );

		$pdf->SetFont( 'Arial', null, 11 );
		$pdf->SetTextColor( 0, 0, 0 );

		$pdf->Cell( null, 10, $school_data, 0, 1 );

		if ( isset( $education->notes ) ) {
			$pdf->WriteHTML( "<br />{$education->notes}<br /><br />" );
		}
		$pdf->WriteHTML( "<br />" );
	}
	$pdf->WriteHTML( "<hr><br />" );
	/* End Block Educations */
	
	/* Start Block Skills */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Vaardigheden en Expertises", 0, 1 );
	
	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->SetTextColor( 0, 0, 0 );
	
	//Create the list with skills
	$list = array();
	$list['bullet'] = chr( 149 );
	$list['margin'] = ' ';
	$list['indent'] = 0;
	$list['spacer'] = 0;
	$list['text'] = array();
	
	$i = 0;
	foreach ( $result->skills->values as $skill ) {
		$list['text'][$i] = $skill->skill->name;
		$i++;
	}
	
	$column_width = $pdf->w-30;
	$pdf->SetX( 10 );
	$pdf->MultiCellBltArray( $column_width - $pdf->x, 6, $list );
	
	$pdf->WriteHTML( "<br /><hr><br />" );
	/* End Block Skills */
	
	/* Start Block Languages */
	$pdf->SetFont( 'Times', 'B', 17 );
	$pdf->SetTextColor( 150, 150, 150 );
	$pdf->Cell( null, 10, "Talen", 0, 1 );
	
	$pdf->SetFont( 'Arial', null, 11 );
	$pdf->SetTextColor( 0, 0, 0 );
	
	//Create the list with Languages
	$list = array();
	$list['bullet'] = chr( 149 );
	$list['margin'] = ' ';
	$list['indent'] = 0;
	$list['spacer'] = 0;
	$list['text'] = array();
	
	$i = 0;
	foreach ( $result->languages->values as $language ) {
		$list['text'][$i] = $language->language->name;
		$i++;
	}
	
	$column_width = $pdf->w-30;
	$pdf->SetX( 10 );
	$pdf->MultiCellBltArray( $column_width - $pdf->x, 6, $list );
	
	$pdf->WriteHTML( "<br /><hr><br />" );
	/* End Block Languages */
	
	$pdf->Output();
} else {
	echo 'Nothing here';
}
