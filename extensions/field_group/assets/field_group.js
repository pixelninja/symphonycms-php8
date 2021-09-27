(function ($) {
	'use strict';

	$(window).on('load', function() {
		if ($('.field-field_group_start').length <= 0) return false;

		var group_start_fields = $('.field-field_group_start');

		group_start_fields.each(function () {
			var self = $(this),
				wrapper = self.after('<div class="field-group" />').next(),
				siblings = self.nextUntil('.field-field_group_end');

			siblings.appendTo(wrapper);
		});
	});

})(jQuery);
