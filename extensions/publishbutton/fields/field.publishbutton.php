<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT.'/fields/field.checkbox.php');

	Class FieldPublishButton extends FieldCheckbox {

		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct();
			$this->_name = __('Publish Button');
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			if(Administration::instance()->isLoggedIn() && isset($_GET['preview'])) {
				$data = array('yes', 'no');
			}

			return parent::buildDSRetrievalSQL($data, $joins, $where, $andOperation);
		}

	}
