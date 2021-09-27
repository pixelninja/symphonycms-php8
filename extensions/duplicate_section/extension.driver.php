<?php
	/*
	Copyight: Solutions Nitriques 2011
	License: MIT, see the LICENCE file
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	/**
	 *
	 * Duplicate Section Decorator/Extension
	 * Permits admin to duplicate/clone a section data model
	 * @author nicolasbrassard, pascalpiche
	 *
	 */
	class extension_duplicate_section extends Extension {

		/**
		 *
		 * Symphony utility function that permits to
		 * implement the Observer/Observable pattern.
		 * We register here delegate that will be fired by Symphony
		 */
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => '__action'
				)
			);
		}
		
		/**
		 * 
		 * Fired on each backend page, detect when it's time to append elements into the backend page
		 * @param array $context
		 */
		public function appendElementBelowView(Array &$context) {
			// only if logged in
			// this prevents the clone button from appearing on the login screen
			if (Administration::instance()->isLoggedIn()) {
				
				$c = Administration::instance()->getPageCallback();
				
				$c['context'][0] = $c['context'][0] ?? null;

				// when editing a section
				if ($c['driver'] == 'blueprintssections' && $c['context'][0] == 'edit') {
					
					$form = Administration::instance()->Page->Form;
					
					$button_wrap = new XMLELement('div', NULL, array(
						'id' => 'duplicate-section'
					));
					
					$btn = new XMLElement('button', __('Clone'), array(
						'id' => 'duplicate-section-clone',
						'class' => 'button',
						'name' => 'action[clone]',
						'type' => 'submit',
						'title' => __('Duplicate this section'),
						'style' => 'margin-left: 10px; background: #81B934',
						'onclick' => "jQuery('fieldset.settings').empty();return true;"
					));
					
					$button_wrap->appendChild($btn);
					
					// add content to the right div
					$div_action = self::getChildrenWithClass($form, 'div', 'actions');
					
					if ($div_action != NULL) {
						$div_action->appendChild($button_wrap);
					}
				}
			}
		}
		
		/**
		 * 
		 * Recursive search for an Element with the right name and css class.
		 * Stops at fists match
		 * @param XMLElement $rootElement
		 * @param string $tagName
		 * @param string $className
		 */
		private static function getChildrenWithClass($rootElement, $tagName, $className) {
			if (! ($rootElement) instanceof XMLElement) {
				return NULL; // not and XMLElement
			}
			
			// contains the right css class and the right node name
			if (strpos($rootElement->getAttribute('class'), $className) > -1 && $rootElement->getName() == $tagName) {
				return $rootElement;
			}
			
			// recursive search in child elements
			foreach ($rootElement->getChildren() as $child) {
				$res = self::getChildrenWithClass($child, $tagName, $className);
				
				if ($res != NULL) {
					return $res;
				}
			}
			return NULL;
		}

		
		/**
		 * 
		 * Delegate AdminPagePreGenerate that handles the click of the 'clone' button and append the button in the form
		 * @param array $context
		 */
		public function __action(Array &$context) {	

			self::appendElementBelowView($context);
			
			$_POST['action'] = $_POST['action'] ?? null;

			// if the clone button was hit
			if (is_array($_POST['action']) && isset($_POST['action']['clone'])) {
				$c = Administration::instance()->getPageCallback();
				
				$section_id = $c['context'][1];
				
				$section = SectionManager::fetch($section_id);
				
				if ($section != null) {
					$section_settings = $section->get();
				
					// remove id
					unset($section_settings['id']);
					// remove dates
					unset($section_settings['creation_date']);
					unset($section_settings['creation_date_gmt']);
					unset($section_settings['modification_date']);
					unset($section_settings['modification_date_gmt']);
					
					// new name
					$section_settings['name'] .= ' ' . time();
					$section_settings['handle'] = Lang::createHandle($section_settings['name']);
					
					// save it
					$new_section_id = SectionManager::add($section_settings);
					
					// if the create new section was successful
					if ( is_numeric($new_section_id) && $new_section_id > 0) {
						
					
						// get the fields of the section
						$fields = $section->fetchFields();
						
						// if we have some
						if (is_array($fields)) {
		
							// copy each field
							foreach ($fields as &$field) {
								
								// get field settings
								$fs = $field->get();
								
								// un set the current id
								unset($fs['id']);
								
								// set the new section as the parent
								$fs['parent_section'] = $new_section_id;
								
								// create the new field
								$f = FieldManager::create($fs['type']);
								
								// set its settings
								$f->setArray($fs);
								
								// save
								$f->commit();
							}
						}

						// redirect to the new cloned section
						redirect(sprintf(
							'%s/blueprints/sections/edit/%s/',
							SYMPHONY_URL,
							$new_section_id
						));
						
						// stop everything now
						exit;
					}
				}
			}
		}
	}