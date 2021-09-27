<?php

require_once TOOLKIT.'/class.fieldmanager.php';

	Class extension_pagesfield extends Extension{

		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_fields_pages`");
		}

		public function install(){
			return Symphony::Database()->query("CREATE TABLE `tbl_fields_pages` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  `allow_multiple_selection` enum('yes','no') NOT NULL default 'no',
			  `unique_value` enum('yes','no') NOT NULL default 'no',
			  `page_types` varchar(255) default NULL,
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `field_id` (`field_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
		}

		public function update($previousVersion = false) {
			if(version_compare($previousVersion, '1.3', '<')){
				$updated = Symphony::Database()->query(
					"ALTER TABLE `tbl_fields_pages` ADD `page_types` varchar(255) default NULL"
				);
				if(!$updated) return false;
			}
			if(version_compare($previousVersion, '1.7', '<')){
				$updated = Symphony::Database()->query(
					"ALTER TABLE `tbl_fields_pages` ADD `unique_value` enum('yes','no') NOT NULL default 'no'"
				);
				if(!$updated) return false;
			}
			return true;
		}

		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/blueprints/pages/',
					'delegate'	=> 'AppendPageContent',
					'callback'	=> 'appendAssociationsDrawer'
				)
			);
		}


		public function appendAssociationsDrawer($context) {
			$page = Administration::instance()->Page;
			$callback = Administration::instance()->getPageCallback();

			$show_entries = Symphony::Configuration()->get('association_maximum_rows', 'symphony');

			if (is_null($show_entries) || $show_entries == 0) {
				return;
			}

			$content = new XMLElement('div', null, array('class' => 'content'));
			$content->setSelfClosingTag(false);
			$drawer_position = 'vertical-right';

			if ($page instanceOf contentBlueprintsPages && $callback['context'][0] == 'edit') {
				$fields = FieldManager::fetch(null, null, 'asc', 'sortorder', 'pages');

				foreach ($fields as $field) {
					$section = SectionManager::fetch($field->get('parent_section'));

					$visible_field = current($section->fetchVisibleColumns());
					$schema = $visible_field ? array($visible_field->get('element_name')) : array();

					$entries = EntryManager::fetchByPage(1, $field->get('parent_section'), $show_entries, null, null, false, false, true, null);
					$has_entries = !empty($entries) && $entries['total-entries'] != 0;
					$use_entries = array();
					$entry_ids = array();

					foreach ($entries['records'] as $entry) {
						$data = $entry->getData($field->get('id'));

						$page_ids = (is_array($data['page_id']) ? $data['page_id'] : array($data['page_id']));

						if (in_array($context['fields']['id'], $page_ids)) {
							$use_entries[] = $entry;
							$entry_ids[] = $entry->get('id');
						}
					}

					// Create the HTML for the association
					$element = new XMLElement('section', null, array('class' => 'association entry'));
					$header = new XMLElement('header');

					// Filtering and Prepopulation is coming soon!
					$filter = '?filter[' . $field->get('name') . ']=' . $context['fields']['id'];
					$prepopulate = '?prepopulate[' . $field->get('id') . ']=' . $context['fields']['id'];

					// Create link to containing section
					$link = SYMPHONY_URL . '/publish/' . $section->get('handle')  . '/';
					$a = new XMLElement('a', $section->get('name'), array(
						'class' => 'association-section',
						'href' => $link
					));

					// Create new entries link
					$create = new XMLElement('a', __('Create New'), array(
						'class' => 'button association-new',
						'href' => SYMPHONY_URL . '/publish/' . $section->get('handle') . '/new/' . $prepopulate
					));

					if ($has_entries) {
						$header->appendChild(new XMLElement('p', __('Links in %s', array($a->generate()))));

						$ul = new XMLElement('ul', null, array(
							'class' => 'association-links',
							'data-section-id' => $section->get('id'),
							'data-association-ids' => implode(', ', $entry_ids)
						));

						foreach ($use_entries as $key => $e) {
							$value = $visible_field ?
							         $visible_field->prepareTableValue($e->getData($visible_field->get('id')), null, $e->get('id')) :
							         $e->get('id');
							$li = new XMLElement('li');
							$a = new XMLElement('a', strip_tags($value));
							$a->setAttribute('href', SYMPHONY_URL . '/publish/' . $section->get('handle') . '/edit/' . $e->get('id') . '/' . $prepopulate);
							$li->appendChild($a);
							$ul->appendChild($li);
						}

						$element->appendChild($ul);

						// If we are only showing 'some' of the entries, then show this on the UI
						if ($entries['total-entries'] > $show_entries) {
							$total_entries = new XMLElement('a', __('%d entries', array($entries['total-entries'])), array(
								'href' => $link,
							));
							$pagination = new XMLElement('li', null, array(
								'class' => 'association-more',
								'data-current-page' => '1',
								'data-total-pages' => ceil($entries['total-entries'] / $show_entries)
							));
							$counts = new XMLElement('a', __('Show more entries'), array(
								'href' => $link
							));

							$pagination->appendChild($counts);
							$ul->appendChild($pagination);
						}

					// No entries
					} else {
						$element->setAttribute('class', 'association empty');
						$header->appendChild(new XMLElement('p', __('No links in %s', array($a->generate()))));
					}

					$header->appendChild($create);
					$element->prependChild($header);
					$content->appendChild($element);
				}

				$drawer = Widget::Drawer('entry-associations', __('Show Associations'), $content);
				$page->insertDrawer($drawer, $drawer_position, 'prepend');
			}

		}

		private function createSectionElement() {
			$element = new XMLElement('section', null, array('class' => 'association pages'));
			$header = new XMLElement('header');
			$header->appendChild(new XMLElement('p', __('Links in %s', array('<a class="association-section" href="' . SYMPHONY_URL . '/publish/' . $as['handle'] . '/">' . $as['name'] . '</a>'))));
			$element->appendChild($header);
		}

		private function createULElement($section_id, array $entry_ids) {
			return new XMLElement('ul', null, array(
				'class' => 'association-links',
				'data-section-id' => $section_id,
				'data-association-ids' => implode(', ', $entry_ids)
			));
		}
	}

