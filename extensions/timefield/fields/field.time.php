<?php

	Class fieldTime extends Field{

		function __construct(){
			parent::__construct();
			$this->_name = 'Time';
			$this->_required = true;
			$this->set('required', 'yes');
		}

		function isSortable(){
			return true;
		}

		function canFilter(){
			return true;
		}

		function allowDatasourceOutputGrouping(){
			return true;
		}

		function allowDatasourceParamOutput(){
			return true;
		}

		function canPrePopulate(){
			return true;
		}

		function commit(){
			if(!parent::commit()) return false;
			$id = $this->get('id');
			if($id == false) return false;
			return FieldManager::saveSettings($id, array());
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			if(in_array(strtolower($order), array('random', 'rand'))) {
				$sort = 'ORDER BY RAND()';
			} else {
				$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
				$sort .= "ORDER BY `ed`.`seconds` $order";
			}
		}

		function groupRecords($records){
			if(!is_array($records) || empty($records)) return;

			$groups = array($this->get('element_name') => array());

			foreach($records as $r){
				$data = $r->getData($this->get('id'));

				$value = $data['value'];

				if(!isset($groups[$this->get('element_name')][$value])){
					$groups[$this->get('element_name')][$value] = array('attr' => array('value' => $value),
																		 'records' => array(), 'groups' => array());
				}

				$groups[$this->get('element_name')][$value]['records'][] = $r;

			}

			return $groups;
		}

		function displaySettingsPanel(XMLElement &$wrapper, $data = NULL, $flagWithError = NULL, $fieldnamePrefix = NULL, $fieldnamePostfix = NULL, $entry_id = NULL){
			$errors = $errors ?? null;
			parent::displaySettingsPanel($wrapper, $errors);
			$div =  new XMLElement('div', NULL, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);
		}

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = NULL, $entry_id = NULL){
			$element = new XMLElement($this->get('element_name'), $data['value']);
			$element->setAttribute('seconds', (int)$data['seconds']);
			$wrapper->appendChild($element);
		}

		public function prepareTableValue($data, XMLElement $link = NULL, $entry_id = NULL){
			$value = $data['value'];

			if($link){
				$link->setValue($value);
				return $link->generate();
			}

			return $value;
		}

		function displayPublishPanel(XMLElement &$wrapper, $data = NULL, $flagWithError = NULL, $fieldnamePrefix = NULL, $fieldnamePostfix = NULL, $entry_id = NULL){
			$data['value'] = $data['value'] ?? null;
			$data['seconds'] = $data['seconds'] ?? null;
			$value = (int)$data['seconds'];

			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', 'Optional'));
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($data['value']) != 0 ? self::timeIntToString($value) : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function displayDatasourceFilterPanel(XMLElement &$wrapper, $data = NULL, $errors = NULL, $fieldnamePrefix = NULL, $fieldnamePostfix = NULL){
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors, $fieldnamePrefix, $fieldnamePostfix);
			$text = new XMLElement('p', new XMLElement('p', __('To filter by ranges, use the format <code>HH:MM:SS to HH:MM:SS</code>'), array('class' => 'help')));
			$wrapper->appendChild($text);
		}

		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;

			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = __('This is a required field');
				return self::__MISSING_FIELDS__;
			}

			if(!preg_match('@^\d{1,2}:\d{1,2}(:\d+)?@', $data) && strlen($data) > 0){
				$message = __('Time must be entered in the format <code>HH:MM:SS</code>');
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function createTable(){

			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `seconds` bigint(20) default NULL,
				  `value` varchar(20) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `seconds` (`seconds`)
				) TYPE=MyISAM;"
			);
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate = false, $entry_id = null){
			$status = self::__OK__;

			if(strlen($data) > 0){
				return array(
					'seconds' => self::timeStringToInt($data),
					'value' => self::timeIntToString(self::timeStringToInt($data)),
				);
			}
		}

		public static function timeStringToInt($string){
			preg_match_all('/^(\d{1,2}):(\d{1,2})(:(\d+))?$/', $string, $matches, PREG_SET_ORDER);
			$matches[0][1] = $matches[0][1] ?? 0;
			$matches[0][2] = $matches[0][2] ?? 0;
			$matches[0][4] = $matches[0][4] ?? 0;
			return (((int)$matches[0][1] * 3600) + ((int)$matches[0][2] * 60) + (int)$matches[0][4]);
		}

		public static function parseTimeInt($int){
			$hours = floor(($int * (1/60)) * (1/60));
			$minutes = floor(($int - ($hours * 3600)) * (1/60));
			$seconds = $int - (($hours * 3600) + ($minutes * 60));
			return array($hours, $minutes, $seconds);
		}

		public static function timeIntToString($int){
			list($hours, $minutes, $seconds) = self::parseTimeInt($int);

			$hours = max(0, (int)$hours);
			$minutes = max(0, (int)$minutes);
			$seconds = max(0, (int)$seconds);

			return "$hours:".($minutes < 10 ? '0' : NULL)."$minutes:" . ($seconds < 10 ? '0' : NULL) . $seconds;
		}

		function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation=false){
			$field_id = $this->get('id');

			## Check its not a regexp
			if(preg_match('/\bto\b/i', $data[0])){

				list($from, $to) = preg_split('/\bto\b/i', $data[0]);

				$from = trim($from);
				$to = trim($to);

				$from_sec = self::timeStringToInt($from);
				$to_sec = self::timeStringToInt($to);

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND (t$field_id.`value` > $from_sec AND t$field_id.`value` < $to_sec) ";

			}
			else {
				$sec = self::timeStringToInt($data[0]);
				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND (t$field_id.`seconds` = $sec) ";
			}

			return true;

		}

	}
