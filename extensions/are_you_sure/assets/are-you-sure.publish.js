(function($, Symphony) {
	'use strict';

	Symphony.Language.add({
		'You have unsaved changes.': false
	});

	$(window).on('load', function() {
    jQuery('body.page-edit form').areYouSure({
      'message': Symphony.Language.get('You have unsaved changes.')
    });

    // check for Editor fields also
    jQuery('body.page-edit form').on("change paste", "textarea", function(){
      jQuery('body.page-edit form').addClass('dirty');
    });
	});

})(window.jQuery, window.Symphony);
