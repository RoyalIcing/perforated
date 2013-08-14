<?php
/*
Example page for perforated.php
*/


error_reporting(E_ALL);
ini_set("display_errors", 1);


define ('BURNT_ENDASH', "\xE2\x80\x93");

function get_header()
{
?><!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<title>Perforated Example: Submit Video</title>
<link rel="stylesheet" type="text/css" href="../perforated.css">
<link rel="stylesheet" type="text/css" href="./example.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js" charset="utf-8"></script>
<script src="../perforated.js" charset="utf-8"></script>
</head>
<body>
<?php
}

function get_footer()
{
?>
</body>
</html>
<?php
}

require_once('../perforated.php');


define ('EXAMPLE_SUBMIT_VIDEO', 'submitVideo');

// This can be written using PHP arrays, or loaded from a JSON file.
$formOptions = array(
	'baseID' => EXAMPLE_SUBMIT_VIDEO,
	// All the entries to be used in the form.
	'entries' => array(
		'videoLink' => array(
			'title' => 'Video link '.BURNT_ENDASH.' Vimeo / Youtube',
			'value' => '',
			'type' => 'url', // Uses HTML5 input type for URLs.
			'required' => true
		),
		'story' => array(
			'title' => 'Video details & story', // Don't need to worry about escaping HTML first.
			'value' => '',
			'type' => 'text',
			'multipleLines' => true,
			'required' => true
		),
		'name' => array(
			'title' => 'Your name',
			'value' => '',
			'type' => 'text',
			'required' => true
		),
		'email' => array(
			'title' => 'Your email',
			'value' => '',
			'type' => 'email',
			'required' => true
		),
		'helpedCreate' => array(
			'title' => 'Were you a part of this video?',
			'value' => false,
			'type' => 'checkbox',
			'titleWhenOff' => 'No',
			'titleWhenOn' => 'Yes'
		),
		'role' => array(
			'title' => 'What was your role?',
			'value' => '',
			'type' => 'text',
			'required' => true
		),
		'location' => array(
			'title' => 'Where are you located? (city & country)',
			'value' => '',
			'type' => 'text'
		),
		'companies' => array(
			'title' => 'Who are the companies and teams behind the video?',
			'value' => '',
			'type' => 'text',
			'multipleLines' => true
		),
		'businessEmail' => array(
			'title' => 'Is there an email we can contact for business enquiries?',
			'value' => '',
			'type' => 'email'
		)
	),
	// This is the actual structure of the form as it is displayed, grouped into subsections.
	'structure' => array(
		array(
			'id' => 'videoInfo',
			'entries' => array('videoLink')
		),
		array(
			'id' => 'aboutSubmitter',
			'dependentOn' => 'loggedOut', // Only uses (shows and processes) this if the 'loggedOut' external value is on.
			'alsoProcessIf' => 'loggedIn', // These entries are automatically filled (see 'automaticallyFillEntriesFrom' further down) so process them even if its dependency is off.
			'entries' => array('name', 'email')
		),
		array(
			'id' => 'connectionQuestion',
			'entries' => array('helpedCreate')
		),
		array(
			'id' => 'connection',
			'dependentOn' => 'helpedCreate', // Only show this if the 'helpedCreate' checkbox is on.
			'entries' => array('role', 'location', 'companies', 'businessEmail')
		)
	),
	// Automatically uses external values to fill in entries.
	'automaticallyFillEntriesFrom' => array(
		// Using external value 'loggedInMember', grab the following values:
		'loggedInMember' => array(
			'name' => 'name', // Entry value for 'name' is automatically filled from $externalValues['loggedInMember']['name']
			'email' => 'emailAddress', // Entry value for 'email' is automatically filled from $externalValues['loggedInMember'][emailAddress]
		)
	)
);


function exampleLoggedInMemberInfo()
{
	// Try changing from returning something to returning null and see what happens.
	//return null;
	return array(
		'name' => 'John Smith',
		'emailAddress' => 'email@example.com'
	);
}

function exampleExternalValues()
{
	$loggedInMemberInfo = exampleLoggedInMemberInfo();
	$specialValues = array(
		'loggedIn' => !empty($loggedInMemberInfo),
		'loggedOut' => empty($loggedInMemberInfo),
		'loggedInMember' => $loggedInMemberInfo
	);
	return $specialValues;
}


// Options and callbacks are kept separate to enabled the ability of having $formOptions kept as JSON.
$perforatedCallbacks = array(
	'externalValues' => 'exampleExternalValues'
);

// Check if form is being submitted and if so, process the submitted entries.
$resultsForSubmitVideo = perforatedFormCheckAndProcess($formOptions, $perforatedCallbacks);



// Wordpress page header
get_header();

?>
<section id="content">
<header>
<h1>Perforated Example <?= BURNT_ENDASH ?> Submit Video Form</h1>
</header>
<?php

// Check if the form is being submitted, and that the entries are valid.
if ($resultsForSubmitVideo['isBeingSubmitted'] && $resultsForSubmitVideo['entriesAreValid']):
	$processedEntries = $resultsForSubmitVideo['entries']; // Overwrites the entries field in the options array, meaning the result itself can be used as an options array with values already set.
	
	/*
	
	Grab the values from the processed entries, and do something with them.
		$videoLink = $processedEntries['videoLink']['value']
		$name = $processedEntries['name']['value']
		$validEmail = $processedEntries['email']['value']
	
	Using checkboxes:
		$helpedCreate = !empty($processedEntries['helpedCreate']['value']);
	
	*/
?>
<div id="thanks">
<h3>Thanks <?= glazeText($processedEntries['name']['value']) ?> for submitting your video!</h3>
</div>
<?php
else:
	// Display the form, which does a POST request to the current page.
	// If $resultsForSubmitVideo['entriesAreValid'] is false, then the errors will be displayed within the form.
?>
<form id="submitVideoForm" class="perforatedForm applicationForm" action="" method="post" novalidate>
<?php
	$resultsForSubmitVideo['textTagName'] = 'h3'; // Use whatever tag you like for label's text, technically <h3> is a block element and not allowed inside a <label> I think, but that's just restrictive.
	perforatedFormDisplayEntries($resultsForSubmitVideo, $perforatedCallbacks);
?>
<footer>
<input type="submit" class="submitApplicationButton" value="Submit Video">
</footer>
</form>
<?php
endif;
?>
</section>
<?php

// Wordpress page footer
get_footer();
