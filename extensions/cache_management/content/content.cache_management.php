<?php
	/*
	Copyight: Deux Huit Huit 2013
	License: MIT, http://deuxhuithuit.mit-license.org
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/cache_management/lib/class.cachemanagement.php');

	class contentExtensionCache_managementCache_Management extends AdministrationPage {
		
		private $_Result = null;
		private $showResult = false;
		
		public function __construct() {
			parent::__construct();
			$this->_Result = new XMLElement('span', null, array('class' => 'frame'));
		}
		
		
		/**
		 * Builds the content view
		 */
		public function __viewIndex() {
			$title = __('Cache Management');
			
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), $title)));
			
			$this->appendSubheading(__($title));
			
			$fieldset = new XMLElement('fieldset', null, array('class' => 'settings'));
			
			$fieldset->appendChild(new XMLElement('legend',__('Please choose what to delete')));
			$fieldset->appendChild(new XMLElement('p',__('Removing will delete expired entries. Clearing will delete everything.', array('class' => 'help'))));
			
			$fieldset->appendChild(Widget::Input('action[pur-file]', __('Remove expired file cache'), 'submit'));
			$fieldset->appendChild(Widget::Input('action[pur-db]', __('Remove expired DB cache'), 'submit'));
			
			$fieldset->appendChild(Widget::Input('action[del-file]', __('Clear file cache'), 'submit'));
			$fieldset->appendChild(Widget::Input('action[del-db]', __('Clear DB cache'), 'submit'));
			
			$fieldset->appendChild(Widget::Input('action[del-cachelite]', __('Clear cachelite files'), 'submit'));
			
			if (is_dir(CACHE . '/cacheabledatasource')) {
				$fieldset->appendChild(Widget::Input('action[del-cacheabledatasource]', __('Clear Cacheable Datasource files'), 'submit'));
			}
			
			$this->Form->appendChild($fieldset);
			
			if ($this->showResult) {
				$this->Form->appendChild($this->_Result);
			}
		}
		
		/**
		 * Method that handles user actions on the page
		 */
		public function __actionIndex() {
			// if actions were launch
			if (isset($_POST['action']) && is_array($_POST['action'])) {
		
				// for each action
				foreach ($_POST['action'] as $key => $action) {
					switch ($key) {
						case 'del-file':
							$this->deleteFileCache();
							break;
						case 'del-db':
							$this->deleteDBCache();
							break;
						case 'del-cachelite':
							$this->deleteCachelite();
							break;
						case 'pur-file':
							$this->purgeFileCache();
							break;
						case 'pur-db':
							$this->purgeDBCache();
							break;
						case 'del-cacheabledatasource':
							$this->deleteCDCache();
							break;
					}
				}
			}
		}
		
		/* Cachelite */
		private function deleteCachelite() {
			$count = CacheManagement::purgeFileCache(false, '/^cache_(.+)/');
			
			$this->_Result->appendChild(new XMLElement('p', __('All %d cachelite files deleted.', array($count))));
			$this->showResult = true;
		}
		
		/* File cache */
		private function deleteFileCache() {
			$count = CacheManagement::deleteFileCache();
			
			$this->_Result->appendChild(new XMLElement('p', __('All %d files in cache deleted.', array($count))));
			$this->showResult = true;
		}
		
		private function purgeFileCache() {
			$count = CacheManagement::purgeFileCache();
			
			$this->_Result->appendChild(new XMLElement('p', __('Deleted %d expired files.', array($count))));
			$this->showResult = true;
		}
		
		
		/* DB cache */
		private function deleteDBCache() {
			$count = CacheManagement::deleteDBCache();
			
			$this->_Result->appendChild(new XMLElement('p', __('All %d entries in cache deleted.', array($count))));
			$this->showResult = true;
		}
		
		private function purgeDBCache() {
			$count = CacheManagement::purgeDBCache();
				
			$this->_Result->appendChild(new XMLElement('p', __('Deleted %d expired cache entries.', array($count))));
			$this->showResult = true;
		}
		
		/* Cacheable Datasource */
		private function deleteCDCache() {
			$count = CacheManagement::purgeFileCache(false, null, '/cacheabledatasource');
			
			$this->_Result->appendChild(new XMLElement('p', __('All %d Cacheable Datasource files deleted.', array($count))));
			$this->showResult = true;
		}
	}