<?php
class Extension_PublishButton extends Extension {

	public function getSubscribedDelegates() {
		return array(
			array(
				'page' => '/backend/',
				'delegate' => 'InitaliseAdminPageHead',
				'callback' => 'appendPageHead'
			)
		);
	}

	public function install() {
		// Create field database:
		Symphony::Database()->query("
			CREATE TABLE IF NOT EXISTS `tbl_fields_publishbutton` (
				`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`field_id` INT(11) UNSIGNED NOT NULL,
				`default_state` ENUM('on', 'off') NOT NULL DEFAULT 'on',
				PRIMARY KEY (`id`),
				KEY `field_id` (`field_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		");
	}

	public function uninstall() {
		Symphony::Database()->query("DROP TABLE `tbl_fields_publishbutton`");
	}

	public function appendPageHead($context) {
		$page = Administration::instance()->Page;
		$page->addScriptToHead(URL . '/extensions/publishbutton/assets/publishbutton.js', 667);
		$page->addStylesheetToHead(URL . '/extensions/publishbutton/assets/publishbutton.css', 'screen', 666);
	}

}
