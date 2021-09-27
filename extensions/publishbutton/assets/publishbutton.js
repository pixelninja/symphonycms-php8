(function ($) {
    $(window).load(function () {
		'use strict';

		Symphony.Language.add({
			'Published': false,
			'Unpublished': false
		});

		$(function() {
			if ($('.page-edit .field-publishbutton').length <= 0) return false;

			var entry_id = Symphony.Context.get('env').entry_id,
				field_wrapper = $('.field-publishbutton'),
				field_id = /field-([\d]+)/g.exec(field_wrapper.attr('id'))[1],
				input = field_wrapper.find('input'),
				input_state = input.is(':checked'),
				button;

			if (field_wrapper.hasClass('role-hidden')) return false;

			if (!$('#context .actions').length) $('#context #breadcrumbs').after('<ul class="actions" />');

			button = $('#context .actions').append('<li><a class="publishbutton-trigger create button disabled">' + Symphony.Language.get('Unpublished') + '</a></li>').find('.create');

			if (input_state) {
				button
					.removeClass('disabled')
					.text(Symphony.Language.get('Published'));
			}

			button.on('click', function (e) {
				e.preventDefault();


				var data = {
					'xsrf': Symphony.Utilities ? Symphony.Utilities.getXSRF() : '',
					'entry_id' : entry_id,
					'field_id' : field_id
				};

				input_state = input.is(':checked');

				data.state = (input_state) ?  'no' : 'yes';

				addLoader(button);
				updateState(data);

				return false;
			});

			function updateState(data) {
				$.ajax({
					url: Symphony.Context.get('root') + '/extensions/publishbutton/lib/update.php',
					data: data,
					dataType: 'html',
					type: 'POST',
					error: function(e){
						// console.log('error', e);
						showAlert('Error while trying to save. Please try again.');
					},
					complete: function(e){
						removeLoader();
					},
					success: function(e) {
						// console.log('success');

						showAlert('Entry updated.', true);

						if (button.hasClass('disabled')) {
							button
								.removeClass('disabled')
								.text(Symphony.Language.get('Published'));

							input.prop('checked', true);
						}
						else {
							button
								.addClass('disabled')
								.text(Symphony.Language.get('Unpublished'));

							input.prop('checked', false);
						}
					},
				});
			}

			function addLoader(element) {
				element.before('<div class="publishbutton-loader" />');
			}

			function removeLoader() {
				$(".publishbutton-loader").remove();
			}

			function showAlert(msg, success) {
				Symphony.Elements.header.find('div.notifier').trigger('attach.notify', [Symphony.Language.get(msg), success ? 'success' : 'error']
				);
			};

		});
	});

})(jQuery);
