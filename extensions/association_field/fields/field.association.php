<?php

if (!defined('__IN_SYMPHONY__')) {
    die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
}

require_once FACE . '/interface.exportablefield.php';
require_once FACE . '/interface.importablefield.php';

class FieldAssociation extends Field implements ExportableField, ImportableField
{
    private static $cache = array();

/*-------------------------------------------------------------------------
    Definition:
-------------------------------------------------------------------------*/

    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Association');
        $this->_required = true;
        $this->_showassociation = true;

        // Default settings
        $this->set('show_column', 'no');
        $this->set('show_association', 'yes');
        $this->set('hide_when_prepopulated', 'no');
        $this->set('required', 'yes');
        $this->set('limit', 20);
        $this->set('related_field_id', array());
    }

    public function canToggle()
    {
        return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
    }

    public function canPrePopulate()
    {
        return true;
    }

    public function canFilter()
    {
        return true;
    }

    public function allowDatasourceOutputGrouping()
    {
        return ($this->get('allow_multiple_selection') == 'yes' ? false : true);
    }

    public function allowDatasourceParamOutput()
    {
        return true;
    }

    public function requiresSQLGrouping()
    {
        return ($this->get('allow_multiple_selection') == 'yes' ? true : false);
    }

    public function fetchSuggestionTypes()
    {
        return array('association');
    }

/*-------------------------------------------------------------------------
    Setup:
-------------------------------------------------------------------------*/

    public function createTable()
    {
        return Symphony::Database()->query(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `entry_id` int(11) unsigned NOT NULL,
                `relation_id` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `entry_id` (`entry_id`),
                KEY `relation_id` (`relation_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    }

/*-------------------------------------------------------------------------
    Utilities:
-------------------------------------------------------------------------*/

    public function set($field, $value)
    {
        if ($field == 'related_field_id' && !is_array($value)) {
            $value = explode(',', $value);
        }
        $this->_settings[$field] = $value;
    }

    public function findOptions(array $selected_ids = array(), $entry_id = null)
    {
        $values = array();
        $limit = $this->get('limit');

        if (!is_array($this->get('related_field_id'))) {
            return $values;
        }

        // find the sections of the related fields
        $sections = Symphony::Database()->fetch(
            "SELECT DISTINCT (s.id), s.sortorder, f.id as `field_id`
             FROM `tbl_sections` AS `s`
             LEFT JOIN `tbl_fields` AS `f` ON `s`.id = `f`.parent_section
             WHERE `f`.id IN ('" . implode("','", $this->get('related_field_id')) . "')
             ORDER BY s.sortorder ASC"
        );

        if (is_array($sections) && !empty($sections)) {
            foreach ($sections as $_section) {
                $results = array();

                $section = SectionManager::fetch($_section['id']);
                $group = array(
                    'name' => $section->get('name'),
                    'section' => $section->get('id'),
                    'values' => array()
                );

                if ($limit > 0) {

                    $where = '';
                    $joins = '';

                    /**
                     * Allow the results to be modified using adjust publish filtering functionality on the core
                     *
                     * @delegate AssociationFiltering
                     * @since Symphony 1.1.0
                     * @param string $context
                     * '/publish/'
                     * @param array $options
                     *  An array which should contain the section id
                     *  and the joins and where clauses by reference both passed by reference
                     */
                    Symphony::ExtensionManager()->notifyMembers('AssociationFiltering', '/publish/', array(
                        'section-id' => $section->get('id'),
                        'joins' => &$joins,
                        'where' => &$where
                    ));

                    EntryManager::setFetchSorting($section->getSortingField(), $section->getSortingOrder());
                    $entries = EntryManager::fetch(null, $section->get('id'), $limit, 0, $where, $joins, false, false);
                    foreach ($entries as $entry) {
                        $results[] = (int) $entry['id'];
                    }
                }

                // If a value is already selected, exclude it from the list
                $results = array_diff($results, $selected_ids);

                if (is_array($results) && !empty($results)) {
                    $related_values = $this->findRelatedValues($results);
                    foreach ($related_values as $value) {
                        $group['values'][$value['id']] = $value['value'];
                    }
                }

                if (!is_null($entry_id) && isset($group['values'][$entry_id])) {
                    unset($group['values'][$entry_id]);
                }
                $values[] = $group;
            }
        }

        return $values;
    }

    public function findSelected(array $selected_ids)
    {
        if (empty($selected_ids)) {
            return array();
        }

        $group = array();
        $values = array();
        $related_values = $this->findRelatedValues($selected_ids);

        // Group values
        foreach ($related_values as $value) {
            $values[$value['id']] = $value['value'];
        }

        // Build selection
        foreach ($selected_ids as $id) {
            if (isset($values[$id])) {
                $group[] = array($id, true, $values[$id]);
            }
        }

        return $group;
    }

    public function getToggleStates()
    {
        $options = $this->findOptions();
        $output = $options[0]['values'];

        foreach ($output as $key => $value) {
            $output[$key] = htmlspecialchars($value);
        }

        if ($this->get('required') !== 'yes') {
            $output[""] = __('None');
        }

        return $output;
    }

    public function toggleFieldData(array $data, $newState, $entry_id = null)
    {
        $data['relation_id'] = $newState;

        return $data;
    }

    public function fetchAssociatedEntryCount($value)
    {
        return Symphony::Database()->fetchVar('count', 0, sprintf(
            "SELECT COUNT(*) as `count`
             FROM `tbl_entries_data_%d`
             WHERE `relation_id` = %d",
            $this->get('id'),
            $value
        ));
    }

    public function fetchAssociatedEntryIDs($value)
    {
        return Symphony::Database()->fetchCol('entry_id', sprintf(
            "SELECT `entry_id`
             FROM `tbl_entries_data_%d`
             WHERE `relation_id` = %d",
            $this->get('id'),
            $value
        ));
    }

    public function fetchAssociatedEntrySearchValue($data, $field_id = null, $parent_entry_id = null)
    {
        // We dont care about $data, but instead $parent_entry_id
        if (!is_null($parent_entry_id)) {
            return $parent_entry_id;
        }
        if (!is_array($data)) {
            return $data;
        }

        $searchvalue = Symphony::Database()->fetchRow(0, sprintf(
            "SELECT `entry_id` FROM `tbl_entries_data_%d`
             WHERE `handle` = '%s'
             LIMIT 1",
            $field_id,
            addslashes($data['handle'])
        ));

        return $searchvalue['entry_id'];
    }

    protected function findRelatedValues(array $relation_id = array())
    {
        // 1. Get the field instances from the SBL's related_field_id's
        // FieldManager->fetch doesn't take an array of ID's (unlike other managers)
        // so instead we'll instead build a custom where to emulate the same result
        // We also cache the result of this where to prevent subsequent calls to this
        // field repeating the same query.
        $where = ' AND id IN (' . implode(',', $this->get('related_field_id')) . ') ';
        $hash = md5($where);
        if (!isset(self::$cache[$hash]['fields'])) {
            $fields = FieldManager::fetch(null, null, 'ASC', 'sortorder', null, null, $where);
            if (!is_array($fields)) {
                $fields = array($fields);
            }

            self::$cache[$hash]['fields'] = $fields;
        } else {
            $fields = self::$cache[$hash]['fields'];
        }

        if (empty($fields)) {
            return array();
        }

        // 2. Find all the provided `relation_id`'s related section
        // We also cache the result using the `relation_id` as identifier
        // to prevent unnecessary queries
        $relation_id = array_filter($relation_id);
        if (empty($relation_id)) {
            return array();
        }

        $hash = md5(serialize($relation_id).$this->get('element_name'));

        if (!isset(self::$cache[$hash]['relation_data'])) {
            $relation_ids = Symphony::Database()->fetch(sprintf(
                "SELECT e.id, e.section_id, s.name, s.handle
                 FROM `tbl_entries` AS `e`
                 LEFT JOIN `tbl_sections` AS `s` ON (s.id = e.section_id)
                 WHERE e.id IN (%s)",
                implode(',', $relation_id)
            ));

            // 3. Group the `relation_id`'s by section_id
            $section_ids = array();
            $section_info = array();
            foreach ($relation_ids as $relation_information) {
                $section_ids[$relation_information['section_id']][] = $relation_information['id'];

                if (!array_key_exists($relation_information['section_id'], $section_info)) {
                    $section_info[$relation_information['section_id']] = array(
                        'name' => $relation_information['name'],
                        'handle' => $relation_information['handle']
                    );
                }
            }

            // 4. Foreach Group, use the EntryManager to fetch the entry information
            // using the schema option to only return data for the related field
            $relation_data = array();
            foreach ($section_ids as $section_id => $entry_data) {
                $schema = array();
                // Get schema
                foreach ($fields as $field) {
                    if ($field->get('parent_section') == $section_id) {
                        $schema = array($field->get('element_name'));
                        break;
                    }
                }

                $section = SectionManager::fetch($section_id);
                if (($section instanceof Section) === false) {
                    continue;
                }

                EntryManager::setFetchSorting($section->getSortingField(), $section->getSortingOrder());
                $entries = EntryManager::fetch(array_values($entry_data), $section_id, null, null, null, null, false, true, $schema);

                foreach ($entries as $entry) {
                    $field_data = $entry->getData($field->get('id'));

                    if (is_array($field_data) === false || empty($field_data)) {
                        continue;
                    }

                    // Get unformatted content:
                    if ($field instanceof ExportableField && in_array(ExportableField::UNFORMATTED, $field->getExportModes())) {
                        $value = $field->prepareExportValue(
                            $field_data,
                            ExportableField::UNFORMATTED,
                            $entry->get('id')
                        );
                    } else if ($field instanceof ExportableField && in_array(ExportableField::VALUE, $field->getExportModes())) {

                        // Get values:
                        $value = $field->prepareExportValue(
                            $field_data,
                            ExportableField::VALUE,
                            $entry->get('id')
                        );
                    } else {

                        // Handle fields that are not exportable:
                        $value = $field->getParameterPoolValue(
                            $field_data,
                            $entry->get('id')
                        );

                        if(is_array($value) && count($value) === 1) {
                            $value = implode($value);
                        }
                    }

                    /**
                     * To ensure that the output is 'safe' for whoever consumes this function,
                     * we will sanitize the value. Before sanitizing, we will reverse sanitise
                     * the value to handle the scenario where the Field has been good and
                     * has already sanitized the value.
                     *
                     * @see https://github.com/symphonycms/symphony-2/issues/2318
                     */
                    $value = General::sanitize(General::reverse_sanitize($value));

                    $relation_data[] = array(
                        'id' =>             $entry->get('id'),
                        'section_handle' => $section_info[$section_id]['handle'],
                        'section_name' =>   $section_info[$section_id]['name'],
                        'value' =>          $value
                    );
                }
            }

            self::$cache[$hash]['relation_data'] = $relation_data;
        } else {
            $relation_data = self::$cache[$hash]['relation_data'];
        }

        // 6. Return the resulting array containing the id, section_handle, section_name and value
        return $relation_data;
    }

    /**
     * Given a string (assumed to be a handle or value), this function
     * will do a lookup to field the `entry_id` from the related fields
     * of the field and returns the `entry_id`.
     *
     * @since 1.27
     * @param string $value
     * @return integer
     */
    public function fetchIDfromValue($value)
    {
        $id = null;
        $related_field_ids = $this->get('related_field_id');

        foreach ($related_field_ids as $related_field_id) {
            try {
                $return = Symphony::Database()->fetchCol("id", sprintf(
                    "SELECT `entry_id` as `id`
                     FROM `tbl_entries_data_%d`
                     WHERE `handle` = '%s'
                     LIMIT 1",
                    $related_field_id,
                    Lang::createHandle($value)
                ));

                // Skipping returns wrong results when doing an
                // AND operation, return 0 instead.
                if (!empty($return)) {
                    $id = $return[0];
                    break;
                }
            } catch (Exception $ex) {
                // Do nothing, this would normally be the case when a handle
                // column doesn't exist!
            }
        }

        $value = (is_null($id)) ? 0 : (int) $id;

        return $value;
    }

/*-------------------------------------------------------------------------
    Settings:
-------------------------------------------------------------------------*/

    public function findDefaults(array &$settings)
    {
        if (!isset($settings['allow_multiple_selection'])) {
            $settings['allow_multiple_selection'] = 'no';
        }
        if (!isset($settings['show_association'])) {
            $settings['show_association'] = 'yes';
        }
    }

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        // Only append selected ids, load full section information asynchronously
        $options = array();

        if (is_array($this->get('related_field_id'))) {
            foreach ($this->get('related_field_id') as $related_field_id) {
                $options[] = array($related_field_id);
            }
        }

        $label = Widget::Label(__('Values'));
        $label->appendChild(
            Widget::Select('fields['.$this->get('sortorder').'][related_field_id][]', $options, array(
                'multiple' => 'multiple',
                'class' => 'js-fetch-sections',
                'data-required' => 'true'
            ))
        );

        // Add options
        if (isset($errors['related_field_id'])) {
            $wrapper->appendChild(Widget::Error($label, $errors['related_field_id']));
        } else {
            $wrapper->appendChild($label);
        }

        // Maximum entries
        $label = Widget::Label(__('Maximum entries'));
        $input = Widget::Input('fields['.$this->get('sortorder').'][limit]', (string) $this->get('limit'));
        $label->appendChild($input);
        $wrapper->appendChild($label);

        // Options
        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $wrapper->appendChild($div);

        // Allow selection of multiple items
        $label = Widget::Label();
        $label->setAttribute('class', 'column');
        $input = Widget::Input('fields['.$this->get('sortorder').'][allow_multiple_selection]', 'yes', 'checkbox');

        if ($this->get('allow_multiple_selection') == 'yes') {
            $input->setAttribute('checked', 'checked');
        }

        $label->setValue($input->generate() . ' ' . __('Allow selection of multiple options'));
        $div->appendChild($label);

        // Hide when prepopulated
        $label = Widget::Label();
        $label->setAttribute('class', 'column');
        $input = Widget::Input('fields['.$this->get('sortorder').'][hide_when_prepopulated]', 'yes', 'checkbox');

        if ($this->get('hide_when_prepopulated') == 'yes') {
            $input->setAttribute('checked', 'checked');
        }

        $label->setValue($input->generate() . ' ' . __('Hide when prepopulated'));
        $div->appendChild($label);

        // Associations
        $fieldset = new XMLElement('fieldset');
        $this->appendAssociationInterfaceSelect($fieldset);
        $this->appendShowAssociationCheckbox($fieldset);
        $wrapper->appendChild($fieldset);

        // Requirements and table display
        $this->appendStatusFooter($wrapper);
    }

    public function checkPostFieldData($data, &$message, $entry_id = null)
    {
        $message = null;

        $data = is_array($data) && isset($data['relation_id'])
                ? array_filter($data['relation_id'])
                : $data;

        if ($this->get('required') == 'yes' && (empty($data))) {
            $message = __('‘%s’ is a required field.', array($this->get('label')));

            return self::__MISSING_FIELDS__;
        }

        return self::__OK__;
    }

    public function checkFields(array &$errors, $checkForDuplicates = true)
    {
        parent::checkFields($errors, $checkForDuplicates);

        $related_fields = $this->get('related_field_id');
        if (empty($related_fields)) {
            $errors['related_field_id'] = __('This is a required field.');
        }

        return (is_array($errors) && !empty($errors) ? self::__ERROR__ : self::__OK__);
    }

    public function commit()
    {
        if (!parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        if ($id === false) {
            return false;
        }

        $fields = array();
        $fields['field_id'] = $id;
        if ($this->get('related_field_id') != '') {
            $fields['related_field_id'] = $this->get('related_field_id');
        }
        $fields['related_field_id'] = implode(',', $this->get('related_field_id'));
        $fields['allow_multiple_selection'] = $this->get('allow_multiple_selection') ? $this->get('allow_multiple_selection') : 'no';
        $fields['hide_when_prepopulated'] = $this->get('hide_when_prepopulated') == 'yes' ? 'yes' : 'no';
        $fields['limit'] = max(0, (int) $this->get('limit'));

        if (!FieldManager::saveSettings($id, $fields)) {
            return false;
        }

        SectionManager::removeSectionAssociation($id);
        foreach ($this->get('related_field_id') as $field_id) {
            SectionManager::createSectionAssociation(null, $id, (int) $field_id, $this->get('show_association') == 'yes' ? true : false, $this->get('association_ui'), $this->get('association_editor'));
        }

        return true;
    }

/*-------------------------------------------------------------------------
    Publish:
-------------------------------------------------------------------------*/

    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        $entry_ids = array();
        $options = array(
            array(null, false, null)
        );

        if (isset($data['relation_id']) && !is_null($data['relation_id'])) {
            if (!is_array($data['relation_id'])) {
                $entry_ids = array($data['relation_id']);
            } else {
                $entry_ids = array_values($data['relation_id']);
            }
        }

        $options = array_merge($options, $this->findSelected($entry_ids));
        $states = $this->findOptions($entry_ids, $entry_id);

        if (!empty($states)) {
            foreach ($states as $s) {
                $group = array('label' => $s['name'], 'options' => array());
                if (count($s['values']) == 0) {
                    $group['options'][] = array(null, false, __('None found.'), null, null, array('disabled' => 'disabled'));
                } else {
                    foreach ($s['values'] as $id => $v) {
                        $group['options'][] = array($id, in_array($id, $entry_ids), General::sanitize($v));
                    }
                }

                if (count($states) == 1) {
                    $options = array_merge($options, $group['options']);
                } else {
                    $options[] = $group;
                }
            }
        }

        $fieldname = 'fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix;
        if ($this->get('allow_multiple_selection') == 'yes') {
            $fieldname .= '[]';
        }

        $label = Widget::Label($this->get('label'));
        if ($this->get('required') != 'yes') {
            $label->appendChild(new XMLElement('i', __('Optional')));
        }
        $label->appendChild(
            Widget::Select($fieldname, $options, ($this->get('allow_multiple_selection') == 'yes' ? array(
                'multiple' => 'multiple') : null
            ))
        );

        if (!is_null($flagWithError)) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
        }

        // Set field context data
        $wrapper->setAttribute('data-limit', $this->get('limit'));
        $wrapper->setAttribute('data-type', 'numeric');
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;
        $result = array();

        if (!is_array($data)) {
            $data = array($data);
        }

        foreach ($data as $key => $relation) {
            if (!empty($relation)) {
                $result['relation_id'][] = $relation;
            }
        }

        return $result;
    }

    public function getExampleFormMarkup()
    {
        return Widget::Input('fields['.$this->get('element_name').']', '…', 'hidden');
    }

/*-------------------------------------------------------------------------
    Output:
-------------------------------------------------------------------------*/

    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        if (!is_array($data) || empty($data) || is_null($data['relation_id'])) {
            return;
        }

        $list = new XMLElement($this->get('element_name'));

        if (!is_array($data['relation_id'])) {
            $data['relation_id'] = array($data['relation_id']);
        }
        $related_values = $this->findRelatedValues($data['relation_id']);

        // Group values by id
        $grouped_values = array();
        foreach ($related_values as $value) {
            $grouped_values[$value['id']] = $value;
        }

        // Append items (keeping sort order)
        foreach ($data['relation_id'] as $id) {
            $grouped_values[$id] = $grouped_values[$id] ?? array();
            $relation = $grouped_values[$id];
            $relation['value'] = $relation['value'] ?? null;
            $relation['id'] = $relation['id'] ?? null;
            $relation['section_handle'] = $relation['section_handle'] ?? null;
            $relation['section_name'] = $relation['section_name'] ?? null;
            $value = $relation['value'];

            $item = new XMLElement('item');
            $item->setAttribute('id', $relation['id']);
            $handle = $relation['value'];
            $handle = str_replace('&amp;', '&', trim($handle));
            $handle = Lang::createHandle($handle);
            $item->setAttribute('handle', $handle);
            $item->setAttribute('section-handle', $relation['section_handle']);
            $item->setAttribute('section-name', General::sanitize($relation['section_name']));
            $item->setValue($relation['value']);

            $list->appendChild($item);
        }

        $wrapper->appendChild($list);
    }

    public function getParameterPoolValue(array $data, $entry_id = null)
    {
        return $this->prepareExportValue($data, ExportableField::LIST_OF + ExportableField::ENTRY, $entry_id);
    }

    public function prepareTableValue($data, XMLElement $link = null, $entry_id = null)
    {
        $result = array();

        if (!is_array($data) || (is_array($data) && !isset($data['relation_id']))) {
            return parent::prepareTableValue(null);
        }

        if (!is_array($data['relation_id'])) {
            $data['relation_id'] = array($data['relation_id']);
        }

        if (!is_null($link)) {
            $link->setValue($this->prepareReadableValue($data, $entry_id));
            return $link->generate();
        }

        $result = $this->findRelatedValues($data['relation_id']);
        $context = $this->getAssociationContext();
        $output = new XMLElement('div', null, array(
            'class' => ($this->get('allow_multiple_selection') === 'yes' ? 'multi' : 'single'),
            'data-interface' => $context['interface'],
            'data-count' => count($result),
            'data-label-singular' => __('association'),
            'data-label-plural' => __('associations')
        ));
        $list = new XMLElement('ul');

        foreach ($result as $item) {
            $link = Widget::Anchor(is_null($item['value']) ? '' : htmlspecialchars_decode($item['value']), sprintf('%s/publish/%s/edit/%d/', SYMPHONY_URL, $item['section_handle'], $item['id']));
            $item = new XMLElement('li');
            $item->appendChild($link);
            $list->appendChild($item);
        }

        $output->appendChild($list);
        return $output->generate();
    }

    public function prepareReadableValue($data, $entry_id = null, $truncate = false, $defaultValue = null)
    {
        if (!is_array($data) || (is_array($data) && !isset($data['relation_id']))) {
            return parent::prepareReadableValue($data, $entry_id, $truncate, $defaultValue);
        }

        if (!is_array($data['relation_id'])) {
            $data['relation_id'] = array($data['relation_id']);
        }

        $result = $this->findRelatedValues($data['relation_id']);

        $label = '';
        foreach ($result as $item) {
            $label .= strip_tags(htmlspecialchars_decode($item['value'])) . ', ';
        }

        return trim($label, ', ');
    }

/*-------------------------------------------------------------------------
    Import:
-------------------------------------------------------------------------*/

    public function getImportModes()
    {
        return array(
            'getPostdata' =>    ImportableField::ARRAY_VALUE,
            'getValue' =>       ImportableField::STRING_VALUE
        );
    }

    public function prepareImportValue($data, $mode, $entry_id = null)
    {
        $message = $status = null;
        $modes = (object) $this->getImportModes();

        if (!is_array($data)) {
            $data = array($data);
        }

        if ($mode === $modes->getValue) {
            if ($this->get('allow_multiple_selection') === 'no') {
                $data = array(implode('', $data));
            }

            return implode($data);
        } elseif ($mode === $modes->getPostdata) {
            // Iterate over $data, and where the value is not an ID,
            // do a lookup for it!
            foreach ($data as $key => &$value) {
                if (!is_numeric($value) && !is_null($value)) {
                    $value = $this->fetchIDfromValue($value);
                }
            }

            return $data;
        }

        return null;
    }

/*-------------------------------------------------------------------------
    Export:
-------------------------------------------------------------------------*/

    /**
     * Return a list of supported export modes for use with `prepareExportValue`.
     *
     * @return array
     */
    public function getExportModes()
    {
        return array(
            'getPostdata' =>        ExportableField::POSTDATA,
            'listEntry' =>          ExportableField::LIST_OF
                                    + ExportableField::ENTRY,
            'listEntryObject' =>    ExportableField::LIST_OF
                                    + ExportableField::ENTRY
                                    + ExportableField::OBJECT,
            'listEntryToValue' =>   ExportableField::LIST_OF
                                    + ExportableField::ENTRY
                                    + ExportableField::VALUE,
            'listValue' =>          ExportableField::LIST_OF
                                    + ExportableField::VALUE
        );
    }

    /**
     * Give the field some data and ask it to return a value using one of many
     * possible modes.
     *
     * @param mixed $data
     * @param integer $mode
     * @param integer $entry_id
     * @return array|null
     */
    public function prepareExportValue($data, $mode, $entry_id = null)
    {
        $modes = (object) $this->getExportModes();

        if (isset($data['relation_id']) === false) {
            return null;
        }

        if (is_array($data['relation_id']) === false) {
            $data['relation_id'] = array(
                $data['relation_id']
            );
        }

        if ($mode === $modes->getPostdata) {

            // Return postdata:
            return $data;
        } else if ($mode === $modes->listEntry) {

            // Return the entry IDs:
            return $data['relation_id'];
        } else if ($mode === $modes->listEntryObject) {

            // Return entry objects:
            $items = array();

            $entries = EntryManager::fetch($data['relation_id']);
            foreach ($entries as $entry) {
                if (is_array($entry) === false || empty($entry)) {
                    continue;
                }

                $items[] = current($entry);
            }

            return $items;
        }

        // All other modes require full data:
        $data = $this->findRelatedValues($data['relation_id']);
        $items = array();

        foreach ($data as $item) {
            $item = (object) $item;

            if ($mode === $modes->listValue) {
                $items[] = General::reverse_sanitize($item->value);
            } elseif ($mode === $modes->listEntryToValue) {
                $items[$item->id] = General::reverse_sanitize($item->value);
            }
        }

        return $items;
    }

/*-------------------------------------------------------------------------
    Filtering:
-------------------------------------------------------------------------*/

    public function fetchFilterableOperators()
    {
        return array(
            array(
                'title' => 'is',
                'filter' => ' ',
                'help' => __('Find values that are an exact match for the given string.')
            ),
            array(
                'title' => 'related:',
                'filter' => 'related: ',
                'help' => __('Find values by filtering on the associated field, can use regexp.')
            ),
            array(
                'filter' => 'sql: NOT NULL',
                'title' => 'is not empty',
                'help' => __('Find entries where any value is selected.')
            ),
            array(
                'filter' => 'sql: NULL',
                'title' => 'is empty',
                'help' => __('Find entries where no value is selected.')
            ),
            array(
                'filter' => 'sql-null-or-not: ',
                'title' => 'is empty or not',
                'help' => __('Find entries where no value is selected or it is not equal to this value.')
            ),
            array(
                'filter' => 'not: ',
                'title' => 'is not',
                'help' => __('Find entries where the value is not equal to this value.')
            )
        );
    }

    public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false)
    {
        $field_id = $this->get('id');

        if (preg_match('/^related:\s*/', $data[0], $matches)) {

            $data[0] = preg_replace('/^related: /', null, $data[0]);

            $related_field_id = current($this->get('related_field_id'));
            $related_field = FieldManager::fetch($related_field_id);

            $newJoin = '';
            $newWhere = '';

            $related_field->buildDSRetrievalSQL($data,$newJoin,$newWhere);

            if (empty($newWhere)){
                // the filter is empty eg an empty regexp so no need to continue
                return true;
            }
        
            $relatedTableName = "t{$related_field_id}";
            if ($related_field->_key){
                $relatedTableName .= '_'.$related_field->_key;
            }

            // Join the main field
            $joins .= " LEFT JOIN
                            `tbl_entries_data_{$field_id}` AS `t{$field_id}`
                        ON (`e`.`id` = `t{$field_id}`.`entry_id`)";

            // Join the related field
            $joins .= " LEFT JOIN
                            `tbl_entries_data_{$related_field_id}` AS `$relatedTableName`
                        ON (`t{$field_id}`.`relation_id` = `$relatedTableName`.`entry_id`)";

            $where .= $newWhere;

        } else if (preg_match('/^sql:\s*/', $data[0], $matches)) {
            $data = trim(array_pop(explode(':', $data[0], 2)));

            if (strpos($data, "NOT NULL") !== false) {

                // Check for NOT NULL (ie. Entries that have any value)
                $joins .= " LEFT JOIN
                                `tbl_entries_data_{$field_id}` AS `t{$field_id}`
                            ON (`e`.`id` = `t{$field_id}`.entry_id)";
                $where .= " AND `t{$field_id}`.relation_id IS NOT NULL ";

            } else if (strpos($data, "NULL") !== false) {

                // Check for NULL (ie. Entries that have no value)
                $joins .= " LEFT JOIN
                                `tbl_entries_data_{$field_id}` AS `t{$field_id}`
                            ON (`e`.`id` = `t{$field_id}`.entry_id)";
                $where .= " AND `t{$field_id}`.relation_id IS NULL ";

            }
        } else {
            $negation = false;
            $null = false;
            if (preg_match('/^not:/', $data[0])) {
                $data[0] = preg_replace('/^not:/', null, $data[0]);
                $negation = true;
            } elseif (preg_match('/^sql-null-or-not:/', $data[0])) {
                $data[0] = preg_replace('/^sql-null-or-not:/', null, $data[0]);
                $negation = true;
                $null = true;
            }

            foreach ($data as $key => &$value) {
                // for now, I assume string values are the only possible handles.
                // of course, this is not entirely true, but I find it good enough.
                if (!is_numeric($value) && !is_null($value)) {
                    $value = $this->fetchIDfromValue($value);
                }
            }

            if ($andOperation) {
                $condition = ($negation) ? '!=' : '=';
                foreach ($data as $key => $bit) {
                    $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id$key` ON (`e`.`id` = `t$field_id$key`.entry_id) ";
                    $where .= " AND (`t$field_id$key`.relation_id $condition '$bit' ";

                    if ($null) {
                        $where .= " OR `t$field_id$key`.`relation_id` IS NULL) ";
                    } else {
                        $where .= ") ";
                    }
                }
            } else {
                $condition = ($negation) ? 'NOT IN' : 'IN';

                // Apply a different where condition if we are using $negation. RE: #29
                if ($negation) {
                    $condition = 'NOT EXISTS';
                    $where .= " AND $condition (
                        SELECT *
                        FROM `tbl_entries_data_$field_id` AS `t$field_id`
                        WHERE `t$field_id`.entry_id = `e`.id AND `t$field_id`.relation_id IN (".implode(", ", $data).")
                    )";
                } else {

                    // Normal filtering
                    $joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
                    $where .= " AND (`t$field_id`.relation_id $condition ('".implode("', '", $data)."') ";

                    // If we want entries with null values included in the result
                    $where .= ($null) ? " OR `t$field_id`.`relation_id` IS NULL) " : ") ";
                }
            }
        }

        return true;
    }

/*-------------------------------------------------------------------------
    Grouping:
-------------------------------------------------------------------------*/

    public function groupRecords($records)
    {
        if (!is_array($records) || empty($records)) {
            return;
        }

        $groups = array($this->get('element_name') => array());

        $related_field_id = current($this->get('related_field_id'));
        $field = FieldManager::fetch($related_field_id);

        if (!$field instanceof Field) {
            return;
        }

        foreach ($records as $r) {
            $data = $r->getData($this->get('id'));
            $value = (int) $data['relation_id'];

            if ($value === 0) {
                if (!isset($groups[$this->get('element_name')][$value])) {
                    $groups[$this->get('element_name')][$value] = array(
                        'attr' => array(
                            'link-handle' => 'none',
                            'value' => "None"
                        ),
                        'records' => array(),
                        'groups' => array()
                    );
                }
            } else {
                $related_data = EntryManager::fetch($value, $field->get('parent_section'), 1, null, null, null, false, true, array($field->get('element_name')));
                $related_data = current($related_data);

                if (!$related_data instanceof Entry) {
                    continue;
                }

                $primary_field = $field->prepareTableValue($related_data->getData($related_field_id));

                if (!isset($groups[$this->get('element_name')][$value])) {
                    $groups[$this->get('element_name')][$value] = array(
                        'attr' => array(
                            'link-id' => $data['relation_id'],
                            'link-handle' => Lang::createHandle($primary_field),
                            'value' => General::sanitize($primary_field)),
                        'records' => array(),
                        'groups' => array()
                    );
                }
            }

            $groups[$this->get('element_name')][$value]['records'][] = $r;
        }

        return $groups;
    }
}
