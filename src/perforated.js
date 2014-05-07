/*

                                   Perforated

................................................................................

Copyright 2013-2014: Patrick Smith

This content is released under the MIT License: http://opensource.org/licenses/MIT

Requires jQuery http://jquery.com/ or something compatibile.

*/

(function($, baseElement) {

function perforatedFormsUpdateDependency($forms, entryID, on)
{
	$forms.find('.dependentOn-'+entryID).each(function() {
		var $fieldset = $(this);
		var dependenciesRemainingCount = $fieldset.data('dependenciesRemainingCount');
		dependenciesRemainingCount += (on ? -1 : 1);
		$fieldset.data('dependenciesRemainingCount', dependenciesRemainingCount);
		$fieldset.toggleClass('dependenciesUnfulfilled', dependenciesRemainingCount > 0);
	});
}


$(baseElement).on('change', '.perforatedForm label.checkbox input', function(event) {
	var $input = $(event.target);
	var $label = $input.closest('label');
	var entryID = $label.data('entryID');
	var $form = $input.closest('.perforatedForm');
	var isChecked = $input.is(':checked');
	$label.toggleClass('checked', isChecked);
	perforatedFormsUpdateDependency($form, entryID, isChecked);
});

)(jQuery, document);