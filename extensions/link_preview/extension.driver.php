<?php
	/*
	Copyight: Deux Huit Huit 2013
	License: MIT, http://deuxhuithuit.mit-license.org
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/link_preview/fields/field.link_preview.php');

	/**
	 *
	 * @author Deux Huit Huit
	 * http://www.deuxhuithuit.com
	 *
	 */
	class extension_link_preview extends Extension {

		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Link Preview';

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
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendResources'
				),
			);
		}


		/*********** DELEGATES ***********************/

		/**
		 *
		 * Appends resource (js/css) files referneces into the head, if needed
		 * @param array $context
		 */
		public function appendResources(Array $context) {
			// store de callback array localy
			$c = Administration::instance()->getPageCallback();

			// publish page, new or edit
			if(isset($c['context']['section_handle']) && in_array($c['context']['page'], array('new', 'edit'))){
				
				Administration::instance()->Page->addStylesheetToHead(
						URL . '/extensions/link_preview/assets/publish.link_preview.css',
						'screen',
						time(),
						false
				);
				
				Administration::instance()->Page->addScriptToHead(
					URL . '/extensions/link_preview/assets/publish.link_preview.js',
					time() + 1,
					false
				);

				return;
			}
		}
		
		
		/* ********* INSTALL/UPDATE/UNINSTALL ******* */
		
		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install() {
			return FieldLink_Preview::createFieldTable();
		}
		
		/**
		 * Creates the table needed for the settings of the field
		 */
		public function update($previousVersion = false) {
		
			$ret = true;

			// are we updating from lower than 1.2 ?
			if ($ret && version_compare($previousVersion,'1.2') < 0) {
				// update for the `anchor label` and `display url` settings
				$ret_anchor = FieldLink_Preview::updateFieldTable_AnchorLabel();
				$ret_display = FieldLink_Preview::updateFieldTable_DisplayUrl();
				// set the return value
				$ret = $ret_anchor && $ret_display;
			}
			
			// are we updating from lower than 1.3.0 ?
			if ($ret && version_compare($previousVersion,'1.3.0') < 0) {
				$ret = FieldLink_Preview::updateFieldTable_DisplayNew();
			}
			
			return true;
		}
		
		/**
		 *
		 * Drops the table needed for the settings of the field
		 */
		public function uninstall() {
			return FieldLink_Preview::deleteFieldTable();
		}

	}
