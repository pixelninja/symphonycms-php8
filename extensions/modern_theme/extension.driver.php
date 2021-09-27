<?php
	Class extension_modern_theme extends Extension
	{
		/**
		* About this extension:
		*/
		public function about()
		{
			return array(
				'name' => 'Theme: Modern',
				'version' => '1.0',
				'release-date' => '2020-07-30',
				'author' => array(
					'name' => 'The Bold',
					'website' => 'https://www.thebold.nz'),
				'description' => 'A more modern theme for Symphony CMS'
			);
		}

		/**
		* Set the delegates
		*/
		public function getSubscribedDelegates()
		{
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'addScriptToHead'
				)
			);
		}

		/**
		 * Add script to the <head>-section of the admin area
		 */
		public function addScriptToHead($context)
		{
			Administration::instance()->Page->addStylesheetToHead(URL.'/extensions/modern_theme/assets/modern_theme.theme.css?v=' . $this->get_git_commit());
		}

		public function get_git_commit() {
			$git = DOCROOT . '/.git/FETCH_HEAD';

			if (file_exists($git)) {
				$git_head = substr(file_get_contents($git), 0, 40);
				return $git_head;
			}

			return rand();
		}
	}
?>
