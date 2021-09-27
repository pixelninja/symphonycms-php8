<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.pagemanager.php');

	Class fieldPages extends Field{

		public function __construct(){
			parent::__construct();
			$this->_name = 'Page Select Box';
			$this->_required = true;

			// Set default
			$this->set('required', 'yes');
			$this->set('show_column', 'no');
			$this->set('unique_value', 'no');
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function canToggle(){
			return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
		}

		public function getToggleStates(){
			$states = $this->getPossibleValues();
			$filteredStates = null;
			if ($this->get('unique_value') == 'yes') {
				$values = self::fetchAllSelectedValues($this->get('id'));
				$filteredStates = array();
				foreach ($states as $id => $value) {
					if (!in_array($id, $values)) {
						$filteredStates[$id] = $value;
					}
				}
				$states = $filteredStates;
			}
			return empty($states) ? null : $filteredStates;
		}

		public function toggleFieldData(array $data, $newState, $entry_id = null){
			$page = PageManager::fetchPageByID($newState, array('handle', 'title', 'id'));

			$data['handle'] = $page['handle'];
			$data['title'] = $page['title'];
			$data['page_id'] = $page['id'];

			return $data;
		}

		public function canFilter(){
			return true;
		}

		public function canPrePopulate(){
			return true;
		}

		public function isSortable(){
			return true;
		}

		public function allowDatasourceOutputGrouping(){
			// Grouping follows the same rule as toggling.
			return $this->canToggle();
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

		public function valueMustBeUnique(){
			return $this->get('unique_value') == 'yes';
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `page_id` int(11) unsigned NOT NULL,
				  `title` varchar(255) default NULL,
				  `handle` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `page_id` (`page_id`),
				  KEY `title` (`title`),
				  KEY `handle` (`handle`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function __sortTitlesAscending($t1, $t2){
			return strcmp(strtolower($t1[2]), strtolower($t2[2]));
		}

		/**
		 *
		 * Utility function to check if the $page param
		 * is not already in the DB for this field
		 * @param $url
		 */
		private function checkUniqueness($page, $entry_id = null) {
			$id = General::intval($this->get('field_id'));
			if (is_array($page)) {
				$page = implode(',', $page);
			}

			$query = "
				SELECT count(`id`) as `c` FROM `tbl_entries_data_$id`
				WHERE `page_id` IN ($page)
			";

			if ($entry_id != null) {
				$entry_id = General::intval($entry_id);
				$query .= " AND `entry_id` != $entry_id";
			}

			$count = Symphony::Database()->fetchVar('c', 0, $query);

			return $count == null || $count == 0;
		}

		private static $fieldValuesCache = array();
		private static function fetchAllSelectedValues($field_id) {
			$field_id = General::intval($field_id);
			if (isset(self::$fieldValuesCache[$field_id])) {
				return self::$fieldValuesCache[$field_id];
			}
			$query = "
				SELECT `page_id` FROM `tbl_entries_data_$field_id`
			";
			return (self::$fieldValuesCache[$field_id] = Symphony::Database()->fetchCol('page_id', $query));
		}

		private function getPossibleValues($include_parent_titles=true) {
			$negate = self::isFilterNegation($this->get('page_types'));
			$types = ($negate ? preg_replace('/^not:\s*/i', null, $this->get('page_types')) : $this->get('page_types'));
			$andOperation = self::isAndOperation($types);

			$types = explode(($andOperation ? '+' : ','), $types);
			$types = array_map('trim', $types);
			$types = array_filter($types);

			$pages = self::fetchPageByTypes($types, $andOperation, $negate);
			// Make sure that $pages is an array of pages.
			// PageManager::fetchPageByID() returns an array of page properties for a single page.
			if (!is_array(current($pages))) {
				$pages = array($pages);
			}

			$result = array();
			foreach($pages as $p){
				$title = ($include_parent_titles ? PageManager::resolvePageTitle($p['id']) : $p['title']);
				$result[$p['id']] = $title;
			}

			return $result;
		}

		/**
		 * Returns Pages that match the given `$types`. If no `$types` is provided
		 * the function returns the result of `PageManager::fetch`.
		 *
		 * @param array $types
		 *	An array of some of the available Page Types.
		 * @param boolean $negate (optional)
		 *	If true, the logic gets inversed to return Pages that don't match the given `$types`.
		 * @return array|null
		 *	An associative array of Page information with the key being the column
		 *	name from `tbl_pages` and the value being the data. If multiple Pages
		 *	are found, an array of Pages will be returned. If no Pages are found
		 *	null is returned.
		 */
		public static function fetchPageByTypes(array $types = array(), $andOperation = false, $negate = false) {
			// Don't filter when not types are set
			if(empty($types)) return PageManager::fetch(false);

			$types = array_map(array('MySQL', 'cleanValue'), $types);

			// Build SQL parts depending on query parameters. There are four possibilities.
			// 1. Without negation and with OR filter
			if (!$andOperation && !$negate) {
				$join = "LEFT JOIN `tbl_pages_types` AS `pt` ON (p.id = pt.page_id)";
				$where = sprintf("
						AND `pt`.type IN ('%s')
					",
					implode("', '", $types)
				);
			}
			// 2. Without negation and with AND filter
			elseif ($andOperation && !$negate) {
				$join = "";
				$where = "";
				foreach($types as $index => $type) {
					$join .= " LEFT JOIN `tbl_pages_types` AS `pt_{$index}` ON (p.id = pt_{$index}.page_id)";
					$where .= " AND pt_{$index}.type = '" . $type . "'";
				}
			}
			// 3. With negation and with OR filter
			elseif (!$andOperation && $negate) {
				$join = sprintf("
						LEFT JOIN `tbl_pages_types` AS `pt` ON (p.id = pt.page_id AND pt.type IN ('%s'))
					",
					implode("', '", $types)
				);
				$where = "AND `pt`.type IS NULL";
			}
			// 4. With negation and with AND filter
			elseif ($andOperation && $negate) {
				$join = "";
				$where = "AND (";
				foreach($types as $index => $type) {
					$join .= sprintf("
							LEFT JOIN `tbl_pages_types` AS `pt_%s` ON (p.id = pt_%s.page_id AND pt_%s.type IN ('%s'))
						",
						$index, $index, $index,
						$type
					);
					$where .= ($index === 0 ? "" : " OR ") . "pt_{$index}.type IS NULL";
				}
				$where .= ")";
			}

			$pages = Symphony::Database()->fetch(sprintf("
					SELECT `p`.*
					FROM `tbl_pages` AS `p`
					%s
					WHERE 1 %s
					ORDER BY `p`.`sortorder` ASC
				",
				$join,
				$where
			));

			return count($pages) == 1 ? array_pop($pages) : $pages;
		}

		/**
		 * Test whether the input string has a negation filter modifier, by searching
		 * for the prefix of `not:` in the given `$string`.
		 *
		 * @param string $string
		 *	The string to test.
		 * @return boolean
		 *	True if the string is prefixed with `not:`, false otherwise.
		 */
		public static function isFilterNegation($string){
			return (preg_match('/^not:/i', $string)) ? true : false;
		}

		public static function isAndOperation($string){
			return (strpos($string, '+') === false) ? false : true;
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function findDefaults(array &$settings){
			if(!isset($settings['allow_multiple_selection'])) $settings['allow_multiple_selection'] = 'no';
			if(!isset($settings['unique_value'])) $settings['unique_value'] = 'no';
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null){
			parent::displaySettingsPanel($wrapper, $errors);

			// Page types filter
			$label = new XMLElement('label', __('Filter pages by type'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][page_types]', $this->get('page_types')));
			$wrapper->appendChild($label);
			$tags = new XMLElement('ul');
			$tags->setAttribute('class', 'tags');
			$tags->setAttribute('data-interactive', 'data-interactive');
			$types = PageManager::fetchPageTypes();
			if(is_array($types) && !empty($types)) {
				foreach($types as $type) $tags->appendChild(new XMLElement('li', $type));
			}
			$wrapper->appendChild($tags);

			// Allow selection of multiple items
			$label = Widget::Label();
			$label->setAttribute('class', 'column');
			$input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');
			if($this->get('allow_multiple_selection') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Allow selection of multiple options'));

			$div = new XMLElement('div', NULL, array('class' => 'two columns'));
			$div->appendChild($label);

			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$this->appendValueMustBeUniqueCheckbox($div);
			$wrapper->appendChild($div);
		}

		private function appendValueMustBeUniqueCheckbox(&$wrapper) {
			$label = new XMLElement('label');
			$label->setAttribute('class', 'column');
			$chk = new XMLElement('input', NULL, array(
				'name' => 'fields['.$this->get('sortorder').'][unique_value]',
				'type' => 'checkbox',
				'value' => 'yes'
			));

			$label->appendChild($chk);
			$label->setValue(__('Make this field checks pages are used only once'), false);

			if ($this->valueMustBeUnique()) {
				$chk->setAttribute('checked','checked');
			}

			$wrapper->appendChild($label);
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			// uniqueness
			if (!empty($data) && $this->valueMustBeUnique() && !$this->checkUniqueness($data, $entry_id)) {
				$message = __("%s: This field must be unique. An entry already contains this page.", array($this->get('label')));
				return self::__INVALID_FIELDS__;
			}
			return parent::checkPostFieldData($data, $message, $entry_id);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$page_types = $this->get('page_types'); // TODO safe
			$allow_multiple_selection = $this->get('allow_multiple_selection');
			$unique_value = $this->get('unique_value');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['allow_multiple_selection'] = empty($allow_multiple_selection) ? 'no' : $this->get('allow_multiple_selection');
			$fields['page_types'] = $page_types;
			$fields['unique_value'] = empty($unique_value) ? 'no' : $this->get('unique_value');

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			$states = $this->getPossibleValues();

			$data['handle'] = $data['handle'] ?? null;			
			$data['page_id'] = $data['page_id'] ?? null;			
			$data['title'] = $data['title'] ?? null;			

			if(!is_array($data['handle'])) $data['handle'] = array($data['handle']);
			if(!is_array($data['page_id'])) $data['page_id'] = array($data['page_id']);
			if(!is_array($data['title'])) $data['title'] = array($data['title']);

			$options = array();
			$values = array();
			$isRequired = $this->get('required') == 'yes';
			$isUniqueValue = $this->get('unique_value') == 'yes';

			if(!$isRequired && $this->get('allow_multiple_selection') != 'yes') {
				$options[] = array(NULL, false, NULL);
			}
			if ($isUniqueValue) {
				$values = self::fetchAllSelectedValues($this->get('id'));
			}

			foreach($states as $id => $title){+
				$selected = in_array($id, $data['page_id']);
				$attrs = null;
				if (!$selected && in_array($id, $values)) {
					$attrs = array('disabled' => 'disabled');
				}
				$options[] = array($id, $selected, General::sanitize($title), null, null, $attrs);
			}

			$fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$data['url'] = $data['url'] ?? null;

			$value = General::sanitize($data['url']);
			$label = Widget::Label($this->get('label'));

			// not required and unique label
			if(!$isRequired && $isUniqueValue) {
				$label->appendChild(new XMLElement('i', __('Optional') . ', ' . __('Unique')));

			// not required label
			} else if(!$isRequired) {
				$label->appendChild(new XMLElement('i', __('Optional')));

			// unique label
			} else if($isUniqueValue) {
				$label->appendChild(new XMLElement('i', __('Unique')));
			}
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			if(empty($data)) return NULL;

			if(!is_array($data)) $data = array($data);

			$result = array('title' => array(), 'handle' => array(), 'page_id' => array());

			foreach($data as $page_id){
				$page = PageManager::fetchPageByID($page_id, array('handle', 'title'));

				$result['handle'][] = $page['handle'];
				$result['title'][] = $page['title'];
				$result['page_id'][] = $page_id;
			}

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if(!is_array($data) || empty($data)) return;

			$list = new XMLElement($this->get('element_name'));

			if(!is_array($data['handle'])) $data['handle'] = array($data['handle']);
			if(!is_array($data['page_id'])) $data['page_id'] = array($data['page_id']);
			if(!is_array($data['title'])) $data['title'] = array($data['title']);

			for($ii = 0; $ii < count($data['handle']); $ii++){
				$page = PageManager::fetchPageByID($data['page_id'][$ii], array('id', 'parent'));
				$attributes = array('handle' => $data['handle'][$ii], 'id' => $data['page_id'][$ii]);

				if (!is_null($page['parent'])) {
					$attributes['parent-id'] = $page['parent'];
				}

				$list->appendChild(new XMLElement('page', General::sanitize($data['title'][$ii]), $attributes));
			}

			$wrapper->appendChild($list);
		}

		public function prepareTextValue($data, $entry_id = null){
			// stop when no page is set
			if(!isset($data['page_id'])) return;

			$pages = PageManager::fetchPageByID($data['page_id'], array('id'));
			// Make sure that $pages is an array of pages.
			// PageManager::fetchPageByID() returns an array of page properties for a single page.
			if (isset($pages['id'])) {
				$pages = array($pages);
			}

			$result = array();
			foreach($pages as $page) {
				$result[] = PageManager::resolvePageTitle($page['id']);
			}

			$value = implode(', ', $result);

			return $value;
		}

		public function getParameterPoolValue(array $data, $entry_id = null) {
			return $data['page_id'];
		}
	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function displayFilteringOptions(XMLElement &$wrapper)
		{
			$existing_options = $this->getPossibleValues(false);

			if (is_array($existing_options) && !empty($existing_options)) {
				$optionlist = new XMLElement('ul');
				$optionlist->setAttribute('class', 'tags');
				$optionlist->setAttribute('data-interactive', 'data-interactive');

				foreach ($existing_options as $option) {
					$optionlist->appendChild(
						new XMLElement('li', General::sanitize($option))
					);
				}

				$wrapper->appendChild($optionlist);
			}
		}

		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->buildRegexSQL($data[0], array('title', 'handle'), $joins, $where);
			}
			else if($andOperation) {
				foreach($data as $key => $bit){
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id$key` ON (`e`.`id` = `t$field_id$key`.entry_id) ";
					$where .= " AND (`t$field_id$key`.page_id = '$bit' OR `t$field_id$key`.handle = '$bit' OR `t$field_id$key`.title = '$bit') ";
				}
			}
			else {
				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND (`t$field_id`.page_id IN ('".@implode("', '", $data)."') OR `t$field_id`.handle IN ('".@implode("', '", $data)."') OR `t$field_id`.title IN ('".@implode("', '", $data)."')) ";
			}

			return true;
		}

	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$joins .= "LEFT OUTER JOIN `tbl_pages` ON (`tbl_pages`.`id` = `ed`.`page_id`) ";
			$sort  .= "GROUP BY `e`.`id` ORDER BY " . (strtolower($order) == 'random' ? 'RAND()' : "`tbl_pages`.`sortorder` $order");
		}

	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/

		public function groupRecords($records){
			if(!is_array($records) || empty($records)) return;

			$groups = array($this->get('element_name') => array());

			foreach($records as $r){
				$data = $r->getData($this->get('id'));
				$handle = $data['handle'];

				if(!isset($groups[$this->get('element_name')][$handle])){
					$groups[$this->get('element_name')][$handle] = array(
						'attr' => array(
							'handle' => $handle, 'name' => General::sanitize($data['title'])
						),
						'records' => array(),
						'groups' => array()
					);
				}

				$groups[$this->get('element_name')][$handle]['records'][] = $r;
			}

			return $groups;
		}

	/*-------------------------------------------------------------------------
		Events:
	-------------------------------------------------------------------------*/

		public function getExampleFormMarkup(){
			$states = $this->getPossibleValues();

			$options = array();

			foreach($states as $handle => $v){
				$options[] = array($handle, NULL, General::sanitize($v));
			}

			$fieldname = 'fields['.$this->get('element_name').']';
			if($this->get('allow_multiple_selection') == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array('multiple' => 'multiple') : NULL)));

			return $label;
		}
	}
