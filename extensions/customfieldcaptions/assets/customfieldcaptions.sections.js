/*-----------------------------------------------------------------------------
	Language strings
-----------------------------------------------------------------------------*/

	Symphony.Language.add({
		'Custom caption': false
	});

/*-----------------------------------------------------------------------------
	Section Editor
-----------------------------------------------------------------------------*/

	jQuery(document).ready(function() {
		// Add a input field for every field instance
		const $duplicator = jQuery('#fields-duplicator');
		const $fields = $duplicator.find('.instance');
		// Get JSON data for the fields
		const data = Symphony.Context.get('custom_captions');
		// Template to clone for each field instance
		const field_template = jQuery('<label class="cc-label" />')
			.text(Symphony.Language.get('Custom caption'))
			.append(
				jQuery('<input />').attr('type', 'text')
			);
		// Inject the template into current $field
		const addCaption = function($field, template) {
			const $input = $field.find('div.content label:first');
			const $input_container = $input.closest('div:not(.content, .invalid)');

			if ($input_container.length) {
				$input_container.after(template);
			}
			else {
				$input.after(template);
			}
		};
		// Inject template on the fly (as new fields as added)
		const insertCaption = function($field) {
			// If the field doesn't have a captions field already, add one
			if($field.filter(':has(input[name*=custom_caption])').length == 0) {
				const template = field_template.clone();

				template
					.find('input')
					.attr('name', 'fields[' + ($field.index() - 1) + '][custom_caption]')

				addCaption($field, template);
			}
		};

		// Initially run over the all the existing fields
		$fields.each(function(i) {
			const $field = jQuery(this);
			const field_id = $field.find(':hidden[name*=id]').val();
			const template = field_template.clone();

			template
				.find('input')
				.attr('name', 'fields[' + i + '][custom_caption]')

			if(data != undefined && data[field_id] != undefined) {
				template.find('input').val(data[field_id].caption);
			}

			addCaption($field, template);

			const new_height = parseInt($field.data('heightMax')) + parseInt($field.find('.cc-label').outerHeight()) + parseInt($field.find('.cc-label').css('margin-bottom'));

			$field.data('heightMax', new_height);
			if (!$field.hasClass('collapsed')) $field.css('max-height', new_height);
		});

		// Listen for when the duplicator changes [2.3]
		jQuery('.frame').on('constructshow.duplicator', 'li', function() {
			insertCaption(jQuery(this));
		});
	});