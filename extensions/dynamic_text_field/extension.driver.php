<?php

	Class extension_dynamic_text_field extends Extension{

	/*-------------------------------------------------------------------------
		Installation:
	-------------------------------------------------------------------------*/

		public function install(){
			try {
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_fields_dynamictextfield` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`field_id` INT(11) UNSIGNED NOT NULL,
						`validator` VARCHAR(255) DEFAULT NULL,
						PRIMARY KEY (`id`),
						UNIQUE KEY `field_id` (`field_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
				");
			}
			catch (Exception $ex) {
				$extension = $this->about();
				Administration::instance()->Page->pageAlert(__('An error occurred while installing %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
				return false;
			}

			return true;
		}

		public function uninstall(){
			if(parent::uninstall() == true){
				try {
					Symphony::Database()->query("DROP TABLE `tbl_fields_dynamic_text_field`");

					return true;
				}
				catch (Exception $ex) {
					$extension = $this->about();
					Administration::instance()->Page->pageAlert(__('An error occurred while uninstalling %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
					return false;
				}
			}

			return false;
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
		public static function appendAssets() {
			if(class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/dynamic_text_field/assets/dynamic_text_field.publish.css', 'screen', 100, false);
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/dynamic_text_field/assets/dynamic_text_field.publish.js', 100, false);
			}
		}
	}
