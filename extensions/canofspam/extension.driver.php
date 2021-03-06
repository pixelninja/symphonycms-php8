<?php
	
	class Extension_CanOfSpam extends Extension {

		// delegates

		public function getSubscribedDelegates() {

			return array(
			
				array('page'     => '/blueprints/events/new/',
				      'delegate' => 'AppendEventFilter',
				      'callback' => 'appendEventFilter'),

				array('page'     => '/blueprints/events/edit/',
				      'delegate' => 'AppendEventFilter',
				      'callback' => 'appendEventFilter'),

				array('page'     => '/blueprints/events/new/',
					  'delegate' => 'AppendEventFilterDocumentation',
					  'callback' => 'appendEventFilterDocumentation'),

				array('page'     => '/blueprints/events/edit/',
					  'delegate' => 'AppendEventFilterDocumentation',
					  'callback' => 'appendEventFilterDocumentation'),
					  
				array('page'     => '/frontend/',
					  'delegate' => 'EventPreSaveFilter',
					  'callback' => 'eventPreSaveFilter'),
					  
				array('page'     => '/frontend/',
					  'delegate' => 'EventPostSaveFilter',
					  'callback' => 'eventPostSaveFilter')
			);
		}

		// update old versions

		public function update($previousVersion = false){

			if(version_compare($previousVersion, '2.0', '<')){

				Symphony::Configuration()->remove('canofspam');
				Symphony::Configuration()->write();
			
				Symphony::Database()->query("DROP TABLE `tbl_canofspam_tracking`");
			}

			return true;
		}

		// append filter to event editor

		public function appendEventFilter($context) {
			$context['options'][] = array(
				'canofspam', 
				is_array($context['selected']) && in_array('canofspam', $context['selected']),
				__('Can Of Spam')
			);
		}

		// append filter documentation to event editor
		
		public function appendEventFilterDocumentation($context) {
			if (is_array($context['selected']) && in_array('canofspam', $context['selected'])) {
				$context['documentation'][] = new XMLElement('h3', __('Can Of Spam Filter'));
				$context['documentation'][] = new XMLElement('p', __(
					'To use the Can Of Spam filter, add the Can Of Spam event to your page and the following field to your form:')
				);
				$context['documentation'][] = contentBlueprintsEvents::processDocumentationCode(
					'<input name="canofspam" value="{$canofspam}" type="hidden" />'
				);
			}
		}

		// check if submitted hash is valid
		
		public function eventPreSaveFilter($context) {

			if (in_array('canofspam', $context['event']->eParamFILTERS)) {
				$_POST['canofspam'] = $_POST['canofspam'] ?? null;
				$_SESSION['canofspam'] = $_SESSION['canofspam'] ?? null;
				$context['messages'] = $context['messages'] ?? array();

				if ($_POST['canofspam'] == $_SESSION['canofspam']) {
					// valid
					$context['messages'][] = array('canofspam', true, null);
				}
				else {

					// not valid
					$context['messages'][] = array(
						'canofspam',
						false,
						__('Data was identified as spam.')
					);
					
					// clear session data
					unset($_SESSION['canofspam']);
				}
			}
		}

		// reset hash value

		public function eventPostSaveFilter($context) {

			// clear session data
			unset($_SESSION['canofspam']);
		}		
	}