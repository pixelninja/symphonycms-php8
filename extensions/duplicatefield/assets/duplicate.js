/*-----------------------------------------------------------------------------
	Language strings
-----------------------------------------------------------------------------*/
	Symphony.Language.add({
		'Duplicate': false,
		'Copy': false
	});

/*-----------------------------------------------------------------------------
	Make sure selected <options> stay selected
-----------------------------------------------------------------------------*/

// https://github.com/spencertipping/jquery.fix.clone
// Textarea and select clone() bug workaround | Spencer Tipping
// Licensed under the terms of the MIT source code license


(function (original) {
  jQuery.fn.clone = function () {
    var result           = original.apply(this, arguments),
        my_textareas     = this.find('textarea').add(this.filter('textarea')),
        result_textareas = result.find('textarea').add(result.filter('textarea')),
        my_selects       = this.find('select').add(this.filter('select')),
        result_selects   = result.find('select').add(result.filter('select'));

    for (var i = 0, l = my_textareas.length; i < l; ++i) $(result_textareas[i]).val($(my_textareas[i]).val());
    for (var i = 0, l = my_selects.length;   i < l; ++i) {
      for (var j = 0, m = my_selects[i].options.length; j < m; ++j) {
        if (my_selects[i].options[j].selected === true) {
          result_selects[i].options[j].selected = true;
        }
      }
    }
    return result;
  };
}) (jQuery.fn.clone);

/*-----------------------------------------------------------------------------
	Section Editor
-----------------------------------------------------------------------------*/
	jQuery(function($){
		var fields_wrapper = $('#fields-duplicator'),
			fields = fields_wrapper.find('.instance');

		// Add the trigger to each existing field
		fields.each(function(i) {
			var self = $(this),
				field_header = self.find('.frame-header');

			field_header.after('<a class="duplicate-field">' + Symphony.Language.get('Duplicate') + '</a>');
		});

		$('body').on('click', '.duplicate-field', function(e) {
			e.preventDefault();

			var field = $(this).parent(),
				field_index = field.index(),
				duplicate_field = field.clone(),
				duplicate_field_fields = duplicate_field.find('input, select'),
				duplicate_field_index = fields.length;

			// Go over each new input/select and update the index
			duplicate_field_fields.each(function () {
				var self = $(this),
					name = self.attr('name');
        
        // make field names unique and clear @handles
        if (name.indexOf('[label]') > -1) {
					this.value += ' ' + Math.random().toString(36).substring(7);
				}
				if (name.indexOf('[element_name]') > -1) {
					this.value = '';
				}
        
				// If it's an ID field, remove it as this will prevent a new field from being added. Instead the existing field will be updated.
				if (name.indexOf('[id]') > -1) {
					self.remove();
				}
				// Otherwise, we want to update the index
				else {
					self.attr('name', name.replace('[' + field_index + ']', '[' + duplicate_field_index + ']'));
				}
			});



			// Add the new field to the array of all fields, so the index increment works.
			fields.push(duplicate_field);

			// Add the new field to the page
			fields_wrapper.find('ol').append(duplicate_field);

			return false;
		});
	});
