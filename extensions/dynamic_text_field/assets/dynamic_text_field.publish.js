(function($) {

	$(document).on('ready.dynamic_text_field', function() {

		$('.dynamic_text_field-duplicator').symphonyDuplicator({
			orderable: true,
			collapsible: true
		});
	});

})(window.jQuery);
