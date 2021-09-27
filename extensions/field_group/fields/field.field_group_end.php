<?php

	Class fieldField_Group_End extends Field {


	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function __construct(){
			parent::__construct();

			$this->_name = 'Group End';
	        $this->set('show_column', 'no');
	        $this->set('required', 'no');
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` double default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

		/**
		 * Save field settings in section editor.
		 */
		public function commit() {
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$handle = $this->handle();

			if($id === false) return false;

			$fields = array();
			$fields['field_id'] = $id;
			return FieldManager::saveSettings($id, $fields);
		}

		public function processRawFieldData($data, &$status, &$message = NULL, $simulate = false, $entry_id = NULL) {
			$status = self::__OK__;

			return array(
				'value' => ''
			);
		}

		/**
		 * Exclude field from DS output.
		 */
		public function fetchIncludableElements() {
 			return null;
 		}

	}
