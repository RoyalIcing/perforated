perforated
=====

Simple but powerful way to make forms in PHP.

#### Easy to create, easy to read

- Create your form using simple readable keyed arrays. Even define them in a JSON file.
- Adding new entries to your form is just a few lines of simple code.
- Use HTML5 entry types such as URL, email address, number, and checkbox.
- Automatically validates on the server with no extra code.
- Automatically shows type-specific errors for incorrectly entered fields.
- Easily group related entries together in a `<fieldset>`.
- Very easy to style in CSS.

#### Have sections appear and disappear, with no extra JS.

- Subsections can be made dependant on another entry, so they only show when that particular entry is on.

#### Extendable

- Easily extend validation. Add your own error messages.
- External values can be used to automatically fill entries or for dependencies.
- Form processing and validation is completely separate from form display.
- Submitted forms have their own namespace e.g. $_POST['formID']

#### Creating a form

### Example

```php
define ('EXAMPLE_SUBMIT_VIDEO', 'submitVideo');

// This can be written using PHP arrays, or loaded from a JSON file.
$formOptions = array(
	'baseID' => EXAMPLE_SUBMIT_VIDEO,
	// All the entries to be used in the form.
	'entries' => array(
		'videoLink' => array(
			'title' => 'Video link '.BURNT_ENDASH.' Vimeo / Youtube',
			'value' => '',
			'type' => 'text',
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
```

#### Using external values from the server

```php

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
```

#### Validating

```php
// Check if form is being submitted and if so, process the submitted entries.
$resultsForSubmitVideo = perforatedFormCheckAndProcess($formOptions, array(
	'externalValues' => 'exampleExternalValues' // Options and callback are kept separate to make having $formOptions kept as JSON cleaner.
));
```

#### Doing something with the results

```php
?>
<div id="content">
<h1>Video Submissions</h1>
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
```

#### Displaying the form

```php
<?php
else:
	// Display the form, which does a POST request to the current page.
	// If $resultsForSubmitVideo['entriesAreValid'] is false, then the errors will be displayed within the form.
?>
<form id="submitVideoForm" class="perforatedForm" action="" method="post" novalidate>
<?php
	perforatedFormDisplayEntries($resultsForSubmitVideo);
?>

<input type="submit" value="Submit Video">
</form>
<?php
endif;
?>
</div>
<?php

```