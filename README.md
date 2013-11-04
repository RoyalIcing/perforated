## Perforated – Simple yet pretty powerful forms

Perforated forms are easy to create, read, and change later on.
Define your form's structure using a simple set up of key-based arrays. See below for an example.
The syntax is so simple you can even define your form in a JSON file, if you wish.

### Edit easily.

The easy-to-understand syntax means you can quickly create a form in two minutes.
Use HTML5 entry types such as URL, email address, number, and checkbox.
They are automatically validated on the server with no extra code.

Come back to it later and add, edit, remove.
Adding new entries means just adding a few lines of simple code.

### Style sensibly.

Perforated is designed to be smart. It groups related entries together in `<fieldset>` elements.
Entries are automatically created with an associated `<label>`.
This means it is very easy to style in CSS.
Target specific form entries.
Target all entries of a certain type.

### Make sections dependant.

Sections can be made to only show when a checkbox is on with one line. Just declare what you want, there's no extra JS.

### Extend.

Perforated will automatically shows type-specific errors for incorrectly entered fields.
Easily extend this validation. Add your own error messages.

Form processing and validation is completely separate from form display, so extend or even replace either however you wish.

### Namespaced.

Submitted forms have their own namespace e.g. `$_POST['formID']`
This means it will not clash with other POST variables, use `name` without worry in WordPress: http://stackoverflow.com/questions/15810948/post-returns-empty-on-form-submit-in-wordpress

### Use external values for dependencies.

External values can be used to automatically fill entries or for dependencies, using a simple callback.
e.g. Only use a particular part of a form if the user is logged in.


### Example:

```php
define ('EXAMPLE_SUBMIT_VIDEO', 'submitVideo');
define ('BURNT_ENDASH', "\xE2\x80\x93");

// This can be written using PHP arrays, or loaded from a JSON file.
$formOptions = array(
	'baseID' => EXAMPLE_SUBMIT_VIDEO,
	// All the entries to be used in the form.
	'entries' => array(
		'videoLink' => array(
			'title' => 'Video link '.BURNT_ENDASH.' Vimeo / Youtube',
			'value' => '',
			'type' => 'url',
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

#### Validating & processing

```php
// Check if form is being submitted and if so, process the submitted entries.
$resultsForSubmitVideo = perforatedFormCheckAndProcess($formOptions, array(
	'externalValues' => 'exampleExternalValues' // Options and callbacks are kept separate to enabled $formOptions to be created as pure JSON.
));
```

#### Working with the results

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

### TODO

- Front-end validation, probably tie in with an existing JavaScript project instead of writing form scratch.
