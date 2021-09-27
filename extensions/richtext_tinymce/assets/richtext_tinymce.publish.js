/*
	Implements tinyMCE for available textareas. The tinyMCE.init call can be
	extended with further buttons/configuration options. For details, see:
	http://tinymce.moxiecode.com/wiki.php/Configuration
*/

jQuery(document).ready(function() {

	tinymce.init({
		selector: 'textarea.tinymce',
		theme : 'modern',
		branding: false,
		relative_urls : false,
		entity_encoding : 'raw',
		block_formats: 'Paragraph=p;Header 1=h1;Header 2=h2;Header 3=h3;Header 4=h4',
		plugins : 'lists link image media code preview searchreplace paste wordcount ',
		menu : {
		},
		paste_as_text: true,
		toolbar : 'undo redo removeformat | formatselect | bold italic underline | bullist numlist | blockquote link unlink | image media | preview code searchreplace',
		file_picker_types: 'image media',
		file_picker_callback: function(callback, value, meta) {
			ml_source_input = callback;
			localStorage.setItem('add-to-editor', 'yes');
			$('#nav .ml-link').trigger('click');
		},
  	});

});

