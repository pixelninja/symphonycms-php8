<?php

	class extension_field_group extends Extension {

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_field_group_start`");
			Symphony::Database()->query("DROP TABLE `tbl_fields_field_group_end`");
		}

		public function install() {
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_field_group_start` (
					`id` int(11) NOT NULL auto_increment,
					`field_id` int(11) NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_field_group_end` (
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
				$page->addStylesheetToHead(URL . '/extensions/field_group/assets/field_group.css', 'screen', 9001);
				$page->addScriptToHead(URL . '/extensions/field_group/assets/field_group.js', 9002);
			}
		}
	}
