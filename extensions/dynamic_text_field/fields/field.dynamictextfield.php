<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	/**
	 * @package dynamic_text_field
	 */

	Class fieldDynamicTextField extends Field {

		public function __construct() {
			parent::__construct();
			$this->_name = __('Dynamic Text Field');
			$this->_required = true;

			$this->set('required', 'no');
			$this->set('show_column', 'no');
			$this->set('location', 'main');
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable() {
			try {
				Symphony::Database()->query(sprintf("
						CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
							`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
							`entry_id` INT(11) UNSIGNED NOT NULL,
							`handle` VARCHAR(255) DEFAULT NULL,
							`value` TEXT NULL,
							PRIMARY KEY (`id`),
							KEY `entry_id` (`entry_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
					", $this->get('id')
				));

				return true;
			}
			catch (Exception $ex) {
				return false;
			}
		}

		public function canFilter(){
			return true;
		}

		public function prePopulate(){
			return false;
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function applyValidationRules($data) {
			$rule = $this->get('validator');

			return ($rule ? General::validateString($data, $rule) : true);
		}

		public function buildField($value = null, $i = -1) {
			$element_name = $this->get('element_name');

			$li = new XMLElement('li');
			if($i == -1) {
				$li->setAttribute('class', 'template');
			}

			// Header
			$header = new XMLElement('header');
			$label = !is_null($value) ? $value : __('New Field');
			$header->appendChild(new XMLElement('h4', '<strong>' . $label . '</strong>'));
			$li->appendChild($header);

			// Value
			$label = Widget::Label();
			$label->appendChild(
				Widget::Input(
					"fields[$element_name][$i][value]", General::sanitize($value), 'text', array('placeholder' => __('Value'))
				)
			);
			$li->appendChild($label);

			return $li;
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		/**
		 * Displays setting panel in section editor.
		 *
		 * @param XMLElement $wrapper - parent element wrapping the field
		 * @param array $errors - array with field errors, $errors['name-of-field-element']
		 */
		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			// Initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

			$order = $this->get('sortorder');

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			// Validator
			$div = new XMLElement('div');
			$div->setAttribute('class', 'column');
			$this->buildValidationSelect(
				$div, $this->get('validator'), "fields[{$order}][validator]"
			);
			// Remove 'column' from `buildValidationSelect`
			$div->getChild(0)->setAttribute('class', '');

			$group->appendChild($div);
			$wrapper->appendChild($group);

			// Default options
			$div = new XMLElement('div', null, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);

			$wrapper->appendChild($div);
		}

		/**
		 * Save field settings in section editor.
		 */
		public function commit() {
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$handle = $this->handle();

			if($id === false) return false;

			$fields = array(
				'field_id' => $id,
				'validator' => $this->get('validator')
			);

			return Symphony::Database()->insert($fields, "tbl_fields_{$handle}", true);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null) {
			extension_dynamic_text_field::appendAssets();

			// Label
			$label = Widget::Label($this->get('label'));
			if ($this->get('required') == 'no') {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}
			$wrapper->appendChild($label);

			// Setup Duplicator
			$duplicator = new XMLElement('div', null, array('class' => 'frame dynamic_text_field-duplicator'));
			$fields = new XMLElement('ol');
			$fields->setAttribute('data-add', __('Add Field'));
			$fields->setAttribute('data-remove', __('Remove Field'));

			// Add a blank template
			$fields->appendChild(
				$this->buildField()
			);

			// If there is actually $data, show that
			if(!empty($data)) {
				// If there's only one 'field', we'll need to make them an array
				// so the logic remains consistant
				if(!is_array($data['value'])) {
					$data = array(
						'value' => array($data['value']),
						'handle' => array($data['handle'])
					);
				}

				for($i = 0, $ii = count($data['value']); $i < $ii; $i++) {
					$fields->appendChild(
						$this->buildField($data['value'][$i], $i)
					);
				}
			}

			$duplicator->appendChild($fields);
			$wrapper->appendChild($duplicator);

			if (!is_null($flagWithError)) {
				$wrapper = Widget::Error($wrapper, $flagWithError);
			}
		}

		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$data[0]['value'] = $data[0]['value'] ?? null;

			// Check required
			if($this->get('required') == 'yes' && General::strlen($data[0]['value']) == 0) {
				$message = __(
					"'%s' is a required field.", array(
						$this->get('label')
					)
				);

				return self::__MISSING_FIELDS__;
			}

			// Return if it's allowed to be empty (and is empty)
			if ($this->get('required') == 'no') {
				if(!isset($data[0]['value']) && General::strlen($data[0]['value']) == 0) return self::__OK__;
			}

			// Process Validation Rules
			foreach($data as $string) {
				if (!$this->applyValidationRules($string['value'])) {
					$message = __(
						"'%s' contains invalid data. Please check the contents.", array(
							$this->get('label')
						)
					);

					return self::__INVALID_FIELDS__;
				}
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=null) {
			$status = self::__OK__;

			$result = array();

			if(is_array($data)) foreach($data as $i => $field) {
				// Value is not empty, then skip adding that field in the result
				if(General::strlen($field['value']) > 0) {
					$result['handle'][] = Lang::createHandle($field['value']);
					$result['value'][] = $field['value'];
				}
			}

			// If there's no values, return null:
			if(empty($result)) return null;

			return $result;
		}

		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(
				Widget::Input('fields['.$this->get('element_name').'][0][value]')
			);

			return $label;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function fetchIncludableElements() {
			return array(
				$this->get('element_name')
			);
		}

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if(!is_array($data) || empty($data)) return;

			$field = new XMLElement($this->get('element_name'));

			if(!is_array($data['handle'])) {
				$data = array(
					'handle' => array($data['handle']),
					'value' => array($data['value'])
				);
			}

			for($i = 0, $ii = count($data['handle']); $i < $ii; $i++) {
				$value = new XMLElement('item');
				$value->setAttribute('handle', $data['handle'][$i]);
				$value->setValue(
					General::sanitize($data['value'][$i])
				);

				$field->appendChild($value);
			}

			$wrapper->appendChild($field);
		}

		/**
		 * At this stage we will just return the Handle
		 */
		public function getParameterPoolValue(array $data, $entry_id=NULL) {
			return $data['handle'];
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			if(is_null($data)) return __('None');

			$values = is_array($data['value'])
						? implode(', ', $data['value'])
						: $data['value'];

			return parent::prepareTableValue(array('value' => $values), $link);
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		/**
		 * Returns the keywords that this field supports for filtering. Note
		 * that no filter will do a simple 'straight' match on the value.
		 *
		 * @since Symphony 2.6.0
		 * @return array
		 */
		public function fetchFilterableOperators() {
			return array(
				array(
					'title'				=> 'boolean',
					'filter'			=> 'boolean:',
					'help'				=> __('Find values that match the given query. Can use operators <code>and</code> and <code>not</code>.')
				),
				array(
					'title'				=> 'not-boolean',
					'filter'			=> 'not-boolean:',
					'help'				=> __('Find values that do not match the given query. Can use operators <code>and</code> and <code>not</code>.')
				),

				array(
					'title'				=> 'regexp',
					'filter'			=> 'regexp:',
					'help'				=> __('Find values that match the given <a href="%s">MySQL regular expressions</a>.', array(
						'http://dev.mysql.com/doc/mysql/en/Regexp.html'
					))
				),
				array(
					'title'				=> 'not-regexp',
					'filter'			=> 'not-regexp:',
					'help'				=> __('Find values that do not match the given <a href="%s">MySQL regular expressions</a>.', array(
						'http://dev.mysql.com/doc/mysql/en/Regexp.html'
					))
				),

				array(
					'title'				=> 'contains',
					'filter'			=> 'contains:',
					'help'				=> __('Find values that contain the given string.')
				),
				array(
					'title'				=> 'not-contains',
					'filter'			=> 'not-contains:',
					'help'				=> __('Find values that do not contain the given string.')
				),

				array(
					'title'				=> 'starts-with',
					'filter'			=> 'starts-with:',
					'help'				=> __('Find values that start with the given string.')
				),
				array(
					'title'				=> 'not-starts-with',
					'filter'			=> 'not-starts-with:',
					'help'				=> __('Find values that do not start with the given string.')
				),

				array(
					'title'				=> 'ends-with',
					'filter'			=> 'ends-with:',
					'help'				=> __('Find values that end with the given string.')
				),
				array(
					'title'				=> 'not-ends-with',
					'filter'			=> 'not-ends-with:',
					'help'				=> __('Find values that do not end with the given string.')
				),

				array(
					'title'				=> 'handle',
					'filter'			=> 'handle:',
					'help'				=> __('Find values by exact match of their handle representation only.')
				),
				array(
					'title'				=> 'not-handle',
					'filter'			=> 'not-handle:',
					'help'				=> __('Find values by exact exclusion of their handle representation only.')
				),
			);
		}

		private static function replaceAnds($data) {
			if (!preg_match('/((\W)and)|(and(\W))/i', $data)) {
				return $data;
			}

			// Negative match?
			if (preg_match('/^not(\W)/i', $data)) {
				$mode = '-';

			} else {
				$mode = '+';
			}

			// Replace ' and ' with ' +':
			$data = preg_replace('/(\W)and(\W)/i', '\\1+\\2', $data);
			$data = preg_replace('/(^)and(\W)|(\W)and($)/i', '\\2\\3', $data);
			$data = preg_replace('/(\W)not(\W)/i', '\\1-\\2', $data);
			$data = preg_replace('/(^)not(\W)|(\W)not($)/i', '\\2\\3', $data);
			$data = preg_replace('/([\+\-])\s*/', '\\1', $mode . $data);
			return $data;
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->buildRegexSQL($data[0], array('value', 'handle'), $joins, $where);
			}

			else if (preg_match('/^(not-)?boolean:\s*/', $data[0], $matches)) {
				$data = trim(array_pop(explode(':', implode(' + ', $data), 2)));
				$negate = ($matches[1] == '' ? '' : 'NOT');

				if ($data == '') return true;

				$data = self::replaceAnds($data);
				$data = $this->cleanValue($data);
				$this->_key++;
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND {$negate}(MATCH (t{$field_id}_{$this->_key}.value) AGAINST ('{$data}' IN BOOLEAN MODE))
				";
			}

			else if (preg_match('/^(not-)?((starts|ends)-with|contains):\s*/', $data[0], $matches)) {
				$data = trim(array_pop(explode(':', $data[0], 2)));
				$negate = ($matches[1] == '' ? '' : 'NOT');
				$data = $this->cleanValue($data);

				if ($matches[2] == 'ends-with') $data = "%{$data}";
				if ($matches[2] == 'starts-with') $data = "{$data}%";
				if ($matches[2] == 'contains') $data = "%{$data}%";

				$this->_key++;
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND {$negate}(
						t{$field_id}_{$this->_key}.handle LIKE '{$data}'
						OR t{$field_id}_{$this->_key}.value LIKE '{$data}'
					)
				";
			}

			else if (preg_match('/^(not-)?handle:\s*/', $data[0], $matches)) {
				$data = trim(array_pop(explode(':', implode(' + ', $data), 2)));
				$op = ($matches[1] == '' ? '=' : '!=');

				if ($data == '') return true;

				$data = $this->cleanValue($data);
				$this->_key++;
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (t{$field_id}_{$this->_key}.handle {$op} '{$data}')
				";
			}

			else if ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND (
							t{$field_id}_{$this->_key}.handle = '{$value}'
							OR t{$field_id}_{$this->_key}.value = '{$value}'
						)
					";
				}
			}

			else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.handle IN ('{$data}')
						OR t{$field_id}_{$this->_key}.value IN ('{$data}')
					)
				";
			}

			return true;
		}

	}
