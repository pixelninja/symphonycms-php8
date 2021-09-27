(function ($, undefined) {
	'use strict';

	$(function() {
		var body = $('body'),
			block_fields = $('.field-publish_blocks'),
			publish_blocks = Symphony.Context.get('publish-blocks');

		// thy shalt not pass if no Publish Block fields exist
		if (!block_fields.length) return;

		for(var i in publish_blocks) {
			var main_fields = '',
				sidebar_fields = '',
				publish_blocks_field = $('#field-' + publish_blocks[i]['block_id']);

			for(var field in publish_blocks[i].main) main_fields += '#' + publish_blocks[i].main[field] + ', ';
			for(var field in publish_blocks[i].sidebar) sidebar_fields += '#' + publish_blocks[i].sidebar[field] + ', ';

			main_fields = main_fields.replace(/, $/,'');
			sidebar_fields = sidebar_fields.replace(/, $/,'');

			$(main_fields).wrapAll(`<div class="block-group block-group-main" data-label="${publish_blocks_field.text()}" data-id="${publish_blocks[i]['block_id']}"></div>`);
			$(sidebar_fields).wrapAll(`<div class="block-group block-group-secondary" data-id="${publish_blocks[i]['block_id']}"></div>`);

			// var tab_field = $('#field-' + publish_blocks[i]['block_id']).remove();
			publish_blocks_field.remove();
		}

		// unwrap default primary/secondary columns
		body.find('.primary, .secondary').contents().unwrap();

		body.find('.block-group-main').each(function () {
			var self = $(this),
				// Wrap each block group into a fieldset container
				fieldset = self.wrap('<fieldset class="block-group-wrapper" data-label="' + self.data('label') +'" />').removeAttr('data-label').parent(),
				// Locate any secondary columns
				secondary = body.find('.block-group-secondary[data-id="' + self.data('id') + '"]');

			// Make this block group the primary
			self.addClass('primary column');
			// Update secondary classes and append to this fieldset
			if (secondary.length) secondary.addClass('secondary column').appendTo(fieldset);
		})
	});

})(jQuery);