<?php

	class extension_publish_blocks extends Extension {

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_publish_blocks`");
		}

		public function install() {
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_publish_blocks` (
					`id` int(11) NOT NULL auto_increment,
					`field_id` int(11) NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
			return true;
		}

		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'initializeAdmin'
				),
			);
		}

		public function initializeAdmin($context) {
			$page = Administration::instance()->Page;
			$context = $page->getContext();

			$callback = Administration::instance()->getPageCallback();

			// only proceed on New or Edit publish pages
			if ($page instanceof contentPublish and in_array($context['page'], array('new', 'edit'))) {
				$page->addStylesheetToHead(URL . '/extensions/publish_blocks/assets/publish_blocks.publish.css', 'screen', 9001);
				$page->addScriptToHead(URL . '/extensions/publish_blocks/assets/publish_blocks.publish.js', 9002);

				include_once(TOOLKIT . '/class.sectionmanager.php');

				$section_id = SectionManager::fetchIDFromHandle($callback['context']['section_handle']);
				$section = SectionManager::fetch($section_id);

				if( !$section instanceof Section ) return;

				$blocks = array();
				$index = -1;

				foreach($section->fetchFieldsSchema() as $i => $field) {
					if ($field['type'] == 'publish_blocks') {
						$blocks[++$index]['block_id'] = $field['id'];
					} else {
						$blocks[$index][$field['location']][] = 'field-' . $field['id'];
					}
				}

				$page->addElementToHead(new XMLElement(
					'script',
					"Symphony.Context.add('publish-blocks', " . json_encode($blocks) . ")",
					array('type' => 'text/javascript')
				), 9003);
			}

		}

	}
