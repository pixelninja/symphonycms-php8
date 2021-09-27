<?php

	class extension_field_divider extends Extension {

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_field_divider`");
		}

		public function install() {
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_field_divider` (
					`id` int(11) NOT NULL auto_increment,
					`field_id` int(11) NOT NULL,
					`margin` varchar(255) default NULL,
					`show-label` enum('yes', 'no') default 'no',
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
				)
			);
		}

		public function initializeAdmin($context) {
			$page = Administration::instance()->Page;
			$context = $page->getContext();

			// only proceed on New or Edit publish pages
			if ($page instanceof contentPublish and in_array($context['page'], array('new', 'edit'))) {
				$page->addStylesheetToHead(URL . '/extensions/field_divider/assets/field_divider.css', 'screen', 9001);
			}
		}
	}
