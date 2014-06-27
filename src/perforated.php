<?php /*

                                   Perforated

................................................................................

Copyright 2013-2014: Patrick Smith

This content is released under the MIT License:
http://opensource.org/licenses/MIT

Requires glaze: https://github.com/BurntCaramel/glaze

TODO:
- does not having a structure passed make sense? will keys will not be in any particular order?
- add constants for entry types? e.g. text, URL, email, checkbox, etc.

*/


define ('PERFORATED_VERSION', '1.5.2');

// Perforated uses Glaze to display text and values.
if (!function_exists('glazeText')) {
	require_once('../lib/glaze/glaze.php');
}


function perforatedDefaultExternalValues()
{
	/*
		These values can be used by your form for dependencies,
		even though they are externally defined.
	
		For example, we could add information about the currently logged-in user:
	
		$loggedInMemberInfo = exampleLoggedInMemberInfo();
		$specialValues = array(
			'loggedIn' => !empty($loggedInMemberInfo),
			'loggedOut' => empty($loggedInMemberInfo),
			'loggedInMember' => $loggedInMemberInfo
		);
		return $specialValues;
	*/
	
	return array(); // A map.
}

function perforatedFormValidateEntry($entry, $value)
{
	$entryProblems = array();
	
	// If entry is required yet its value empty:
	if (!empty($entry['required']) && empty($value)) {
		$entryProblems['empty'] = true;
	}
	else if ($entry['type'] === 'email' && !empty($value)) {
		if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
			$entryProblems['invalid'] = true;
		}
	}
	else if ($entry['type'] === 'url' && !empty($value)) {
		if (filter_var($value, FILTER_VALIDATE_URL) === false) {
			$entryProblems['invalid'] = true;
		}
	}
	else if ($entry['type'] === 'number' && !empty($value)) {
		if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
			$entryProblems['invalid'] = true;
		}
	}
	else if ($entry['type'] === 'integer' && !empty($value)) {
		if (filter_var($value, FILTER_VALIDATE_INT) === false) {
			$entryProblems['invalid'] = true;
		}
	}
	
	return $entryProblems;
}

function perforatedFormAdjustEntryValue($entry, $detailValue)
{
	if (($entry['type'] === 'url') && !empty($detailValue)):
		$offset = stripos($detailValue, '://');
		if ($offset === false):
			$detailValue = "http://{$detailValue}";
		endif;
	endif;
	
	return $detailValue;
}

// HTML <input type="your return value">
function perforatedInputElementTypeForEntryType($entryType)
{
	switch ($entryType) {
		case 'integer':
			return 'number';
		default:
			return $entryType;
	}
}

function perforatedProblemIDsToMessages()
{
	return array(
		'base' => array(
			'empty' => 'Please enter'
		),
		'types' => array(
			'email' => array(
				'invalid' => 'Please enter a valid email address'
			),
			'url' => array(
				'invalid' => 'Please enter a valid URL'
			),
			'number' => array(
				'invalid' => 'Please enter a valid number'
			),
			'integer' => array(
				'invalid' => 'Please enter a valid integer (no decimal point)'
			)
		)
	);
}

// Options and callbacks are kept separate to enabled $options to be created as pure JSON.
function perforatedFormCheckAndProcess($options, $callbacks = null)
{
	$results = $options;
	
	// Get submitted values from POST.
	$formID = $options['baseID'];
	$submittedValues = &$_POST[$formID];
	// If there are no values then it is not being submitted.
	if (empty($submittedValues)):
		$results['isBeingSubmitted'] = false;
		return $results;
	endif;
	
	// Continuing on, values are being submitted.
	$results['isBeingSubmitted'] = true;
	$entries = $options['entries'];
	
	// Callbacks
	$callbacksDefault = array(
		'externalValues' => 'perforatedDefaultExternalValues',
		'validateEntry' => 'perforatedFormValidateEntry',
		'adjustEntryValue' => 'perforatedFormAdjustEntryValue'
	);
	$callbacks = empty($callbacks) ? $callbacksDefault : array_merge($callbacksDefault, $callbacks);
	
	// Get special external values.
	if (!empty($callbacks['externalValues'])):
		$externalValuesCallback = $callbacks['externalValues'];
		$externalValues = call_user_func($externalValuesCallback);
	else:
		$externalValues = array();
	endif;
	
	
	// Fills $entryIDsToProcess with 'entryID' => true for each entry to process.
	// Use structure if there is one:
	if (!empty($options['structure'])):
		$groupIDsToIsFulfilled = array();
		$entryIDsToProcess = array();
		$formStructure = $options['structure'];
		foreach ($formStructure as $formStructureElement):
			$groupID = $formStructureElement['id'];
			
			if (!empty($formStructureElement['dependentOn'])):
				$groupIDsToIsFulfilled[$groupID] = false;
				$dependantOnEntryIDs = (array)$formStructureElement['dependentOn'];
				$dependantEntryIsOn = false;
				
				foreach ($dependantOnEntryIDs as $dependantOnEntryID):
					$dependantEntryIsOn = !empty($submittedValues[$dependantOnEntryID]) || !empty($externalValues[$dependantOnEntryID]);
					if (!$dependantEntryIsOn):
						break;
					endif;
				endforeach;
				
				
				if (!$dependantEntryIsOn && !empty($formStructureElement['alsoProcessIf'])):
					$alsoProcessIf = (array)$formStructureElement['alsoProcessIf'];
					foreach ($alsoProcessIf as $dependantOnEntryID):
						$dependantEntryIsOn = !empty($submittedValues[$dependantOnEntryID]) || !empty($externalValues[$dependantOnEntryID]);
						if (!$dependantEntryIsOn):
							break;
						endif;
					endforeach;
				endif;
				
				// Only use this structure if it is on.
				if (!$dependantEntryIsOn):
					continue;
				endif;
			endif;
			
			$groupIDsToIsFulfilled[$groupID] = true;
			
			// Add structure's to the list of those to process.
			foreach ($formStructureElement['entries'] as $entryID):
				$entryIDsToProcess[$entryID] = true;
			endforeach;
		endforeach;
	else:
		$entryIDsToProcess = array_fill_keys(array_keys($entries), true);
	endif;
	
	
	// Add automatically filled entries.
	if (!empty($options['automaticallyFillEntriesFrom'])):
		$automaticallyFillEntriesFrom = $options['automaticallyFillEntriesFrom'];
		foreach ($automaticallyFillEntriesFrom as $sourceEntryID => $valuesLookUp):
			if (!empty($externalValues[$sourceEntryID])):
				$sourceValue = $externalValues[$sourceEntryID];
			elseif (!empty($submittedValues[$sourceEntryID])):
				$sourceValue = $submittedValues[$sourceEntryID];
			else:
				continue;
			endif;
			
			if (is_array($valuesLookUp)):
				foreach ($valuesLookUp as $sourceKey => $entryID):
					$submittedValues[$entryID] = $sourceValue[$sourceKey];
				endforeach;
			else:
				$submittedValues[$valuesLookUp] = $sourceValue;
			endif;
		endforeach;
	endif;
	
	
	if (empty($callbacks['validateEntry'])):
		throw new Exception('Perforated: A validate entry callback *must* be set: $callbacks["validateEntry"] is empty.');
	else:
		$validateEntryCallback = $callbacks['validateEntry'];
	endif;
	
	if (!empty($callbacks['adjustEntryValue'])):
		$adjustEntryValueCallback = $callbacks['adjustEntryValue'];
	endif;
	
	// Check through all entries.
	$processedEntries = $entries;
	$formProblems = array();
	foreach ($entryIDsToProcess as $entryID => $entryEnabled):
		$entry = $entries[$entryID];
		if ($entry['type'] === 'checkbox'):
			$detailValue = isset($submittedValues[$entryID]);
		else:
			$detailValue = isset($submittedValues[$entryID]) ? trim($submittedValues[$entryID]) : '';
		endif;
		
		if (!empty($adjustEntryValueCallback)):
			$detailValue = call_user_func($adjustEntryValueCallback, $entry, $detailValue);
		endif;
		
		// Use the entry's info and replace the value with the submitted value.
		$processedEntries[$entryID] = $entry;
		$processedEntries[$entryID]['value'] = $detailValue;
		
		// Validate entry using callback.
		$entryProblems = call_user_func($validateEntryCallback, $entry, $detailValue);
		if (!empty($entryProblems)):
			$formProblems[$entryID] = $entryProblems;
		endif;
	endforeach;
	
	
	$results['entries'] = $processedEntries;
	
	if (isset($groupIDsToIsFulfilled)):
		$results['sectionIDsToIsFulfilled'] = $groupIDsToIsFulfilled;
	endif;
	
	// Entries are only valid if there were no problems with them.
	if (!empty($formProblems)):
		$results['problems'] = $formProblems;
		$results['entriesAreValid'] = false;
	else:
		$results['entriesAreValid'] = true;
	endif;
	
	
	return $results;
}

function perforatedFormIsBeingSubmittedAndHasValidEntries($form)
{
	return $form['isBeingSubmitted'] && $form['entriesAreValid'];
}

function perforatedFormCopyProcessedValuesForEntryIDs($form, $entryIDs, $desiredKeysToEntryIDs = null)
{
	$processedEntries = $form['entries'];
	$copiedValues = array();
	foreach ($entryIDs as $entryID):
		if (isset($processedEntries[$entryID])):
			if (!empty($desiredKeysToEntryIDs[$entryID])):
				$desiredID = $desiredKeysToEntryIDs[$entryID];
			else:
				$desiredID = $entryID;
			endif;
			
			$copiedValues[$desiredID] = $processedEntries[$entryID]['value'];
		endif;
	endforeach;
	
	return $copiedValues;
}

function perforatedFormCopyProcessedValuesForGroupWithID($form, $groupID, $desiredKeysToEntryIDs = null)
{
	$sectionIDsToIsFulfilled = $form['sectionIDsToIsFulfilled'];
	if (empty($sectionIDsToIsFulfilled[$groupID])):
		return null;
	endif;
	
	$entryIDs = null;
	foreach ($form['structure'] as $group):
		$groupIDToCheck = $group['id'];
		if ($groupID === $groupIDToCheck):
			$entryIDs = $group['entries'];
			break;
		endif;
	endforeach;
	
	if (empty($entryIDs)):
		return null;
	endif;
	
	return perforatedFormCopyProcessedValuesForEntryIDs($form, $entryIDs, $desiredKeysToEntryIDs);
}

function perforatedFormDisplayEntries($options, $callbacks = null)
{
	$formEntriesDetails = $options['entries'];
	
	// Callbacks
	$callbacksDefault = array(
		'externalValues' => 'perforatedDefaultExternalValues',
		'inputElementTypeForEntryType' => 'perforatedInputElementTypeForEntryType'
	);
	$callbacks = empty($callbacks) ? $callbacksDefault : array_merge($callbacksDefault, $callbacks);
	
	// Get special external values.
	if (!empty($callbacks['externalValues'])):
		$externalValuesCallback = $callbacks['externalValues'];
		$externalValues = call_user_func($externalValuesCallback);
	else:
		$externalValues = array();
	endif;
	
	// Get problem IDs to messages map.
	if (empty($options['problemIDsToMessages'])):
		$options['problemIDsToMessages'] = perforatedProblemIDsToMessages();
	endif;
	
	if (!empty($options['structure'])):
		$formStructure = $options['structure'];
		foreach ($formStructure as $formStructureElement):
			if (!empty($formStructureElement['hidden'])):
				continue;
			endif;
			
			$groupID = $formStructureElement['id'];
			$groupClasses = array($groupID);
			
			$depenciesRemainingCount = 0;
			
			if (!empty($formStructureElement['dependentOn'])):
				$dependantOnEntryIDs = (array)$formStructureElement['dependentOn'];
				
				foreach ($dependantOnEntryIDs as $dependantOnEntryID):
					$groupClasses[] = 'dependentOn-'.$dependantOnEntryID;
				endforeach;
				
				foreach ($dependantOnEntryIDs as $dependantOnEntryID):
					$dependantEntryIsOn = !empty($formEntriesDetails[$dependantOnEntryID]['value']) || !empty($externalValues[$dependantOnEntryID]);
					if (!$dependantEntryIsOn)
						$depenciesRemainingCount++;
				endforeach;
				
				if ($depenciesRemainingCount > 0):
					$groupClasses[] = 'dependenciesUnfulfilled';
				endif;
			endif;
?>
<fieldset<?php
glazyAttribute('class', $groupClasses);
glazyAttributeCheck('data-dependencies-remaining-count', $dependantOnEntryIDs, $depenciesRemainingCount);
?>>
<?php
			foreach ($formStructureElement['entries'] as $entryID):
				$entryDetails = $formEntriesDetails[$entryID];
				perforatedFormDisplayEntry($entryID, $entryDetails, $options, $callbacks);
			endforeach;
?>
</fieldset>
<?php
		endforeach;
	else:
		foreach ($formEntriesDetails as $entryID => $entryDetails):
			perforatedFormDisplayEntry($entryID, $entryDetails, $options, $callbacks);
		endforeach;
	endif;
}

function perforatedFormDisplayEntry($entryID, $entryDetails, $options, $callbacks)
{
	$baseID = $options['baseID'];
	$problemIDsToMessages = $options['problemIDsToMessages'];
	
	$entryTitle = $entryDetails['title'];
	$entryType = $entryDetails['type'];
	$entryValue = !empty($entryDetails['value']) ? $entryDetails['value'] : '';
	$entryInputName = "{$baseID}[{$entryID}]";
	$entryTextTag = !empty($options['textTagName']) ? $options['textTagName'] : 'h5';
	$entryIsRequired = !empty($entryDetails['required']);
	
	$glazyLabel = glazyBegin(array(
		'tagName' => 'label',
		'id' => $entryID,
		'class' => $entryType,
		'data-entry-i-d' => $entryID
	));
	{
		$textElement = glazyBegin(array(
			'tagName' => $entryTextTag
		));
		{
			glazyElement('span.title', $entryTitle);
			
			if (isset($options['problems'][$entryID])):
				foreach ($options['problems'][$entryID] as $problemID => $problemValue):
					if (isset($problemIDsToMessages['types'][$entryType][$problemID])):
						$problemMessage = $problemIDsToMessages['types'][$entryType][$problemID];
					else:
						$problemMessage = $problemIDsToMessages['base'][$problemID];
					endif;
					
					glazyElement('span.problem', $problemMessage);
				endforeach;
			endif;
		}
		glazyFinish($textElement);

	if (empty($callbacks['inputElementTypeForEntryType'])):
		throw new Exception('Perforated: A input element type for entry type callback *must* be set: $callbacks["inputElementTypeForEntryType"] is empty.');
	else:
		$inputElementTypeForEntryTypeCallback = $callbacks['inputElementTypeForEntryType'];
	endif;
	
	$inputType = call_user_func($inputElementTypeForEntryTypeCallback, $entryType);

	if (($inputType === 'text' && empty($entryDetails['multipleLines'])) || $inputType === 'email' || $inputType === 'url' || $inputType === 'number' || $inputType === 'checkbox'):
		$input = glazyBegin('input');
		{
			glazyAttribute('type', $inputType);
			glazyAttribute('name', $entryInputName);
			glazyAttributeCheck('required', $entryIsRequired);
			
			if ($inputType === 'checkbox'):
				glazyAttributeCheck('checked', $entryDetails['value'], 'checked');
				glazyAttribute('data-on-title', $entryDetails['titleWhenOn']);
				glazyAttribute('data-off-title', $entryDetails['titleWhenOff']);
			else:
				glazyAttribute('value', $entryValue);
			endif;
			
			if ($entryType === 'integer'):
				glazyAttribute('step', '1');
			endif;
		}
		glazyFinish($input);
	elseif ($inputType === 'text' && !empty($entryDetails['multipleLines'])):
		glazyElement(array(
			'tagName' => 'textarea',
			'name' => $entryInputName,
			'required' => !empty($entryDetails['required']),
			'cols' => 40,
			'rows' => 4
		), $entryValue);
	endif;
	}
	glazyFinish($glazyLabel);
}
