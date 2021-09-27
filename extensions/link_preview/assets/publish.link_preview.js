/*
	Copyight: Deux Huit Huit 2013
	License: MIT, http://deuxhuithuit.mit-license.org
*/

/**
 * Client code for link_preview
 *
 * @author deuxhuithuit
 */
(function ($, undefined) {

	var FIELD = 'field-link_preview';
	var FIELD_CLASS = '.' + FIELD;
	var target = $();
	
	var hookOne = function (index, elem) {
		elem = $(elem);
		
		var url = elem.attr('data-url');
		var text = elem.attr('data-text');
		
		if (!!url) {
			var li = $('<li />'),
				link = $('<a />')
				.text(text)
				.attr('class', 'button drawer vertical-right link-preview')
				.attr('href', url)
				.attr('target', '_blank');

			li.append(link);

			target.append(li);
		}
	};

	var init = function () {
		target = Symphony.Elements.context.find('.actions');
		if (!target.length) {
			target = $('<ul>').attr('class', 'actions');
			Symphony.Elements.breadcrumbs.after(target);
		}
		return $(FIELD_CLASS).each(hookOne);
	};

	$(init);

})(jQuery);