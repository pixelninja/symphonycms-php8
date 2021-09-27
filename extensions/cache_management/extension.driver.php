<?php
	/*
	Copyright: Deux Huit Huit 2013-2014
	License: MIT, http://deuxhuithuit.mit-license.org
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	/**
	 *
	 * @author Deux Huit Huit
	 * http://www.deuxhuithuit.com
	 *
	 */
	class extension_cache_management extends Extension {

		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Cache Management';

		/*********** DELEGATES ***********************/
		
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'NavigationPreRender',
					'callback' => 'navigationPreRender'
				)
			);
		}
		
		/**
		 * Delegate fired to add a link to Cache Management
		 */
		public function fetchNavigation() {
			if (is_callable(array('Symphony', 'Author'))) {
					$author = Symphony::Author();
			} else {
					$author = Administration::instance()->Author;
			}
			
			// Work around single group limit in nav
			$group = $author->isDeveloper() ? 'developer' : 'manager';
			
			return array(
					array (
						'location' => __('System'),
						'name' => __(self::EXT_NAME),
						'link' => 'cache_management',
						'limit' => $group,
					) // nav group
			); // nav
		}
		
		public function navigationPreRender($context) {
			$c = Administration::instance()->getPageCallback();
			if ($c['driver'] == 'cache_management') {
				foreach ($context['navigation'] as $key => $section) {
					if ($section['name'] == 'System') {
						$context['navigation'][$key]['class'] = 'active';
					}
				}
			}
		}


		/* ********* INSTALL/UPDATE/UNISTALL ******* */

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install() {
			return true;
		}

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function update($previousVersion = false) {
			return true;
		}

		/**
		 *
		 * Drops the table needed for the settings of the field
		 */
		public function uninstall() {
			return true;
		}

	}
