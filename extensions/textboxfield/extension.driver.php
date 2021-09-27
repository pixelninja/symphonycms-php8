<?php

	/**
	 * @package textboxfield
	 */

	/**
	 * An enhanced text input field.
	 */
	class Extension_TextBoxField extends Extension {
		/**
		 * The name of the field settings table.
		 */
		const FIELD_TABLE = 'tbl_fields_textbox';

		/**
		 * Publish page headers.
		 */
		const PUBLISH_HEADERS = 1;

		/**
		 * What headers have been appended?
		 *
		 * @var integer
		 */
		static protected $appendedHeaders = 0;

		/**
		 * Add headers to the page.
		 */
		static public function appendHeaders($type) {
			if (
				(self::$appendedHeaders & $type) !== $type
				&& class_exists('Administration', false)
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				$page = Administration::instance()->Page;

				if ($type === self::PUBLISH_HEADERS) {
					$page->addStylesheetToHead(URL . '/extensions/textboxfield/assets/textboxfield.publish.css', 'screen', null, false);
					$page->addScriptToHead(URL . '/extensions/textboxfield/assets/textboxfield.publish.js', null, false);
				}

				self::$appendedHeaders |= $type;
			}
		}

		/**
		 * Create tables and configuration.
		 *
		 * @return boolean
		 */
		public function install() {
			Symphony::Database()->query(sprintf("
				CREATE TABLE IF NOT EXISTS `%s` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`column_length` INT(11) UNSIGNED DEFAULT 75,
					`text_size` ENUM('single', 'small', 'medium', 'large', 'huge') DEFAULT 'medium',
					`text_formatter` VARCHAR(255) DEFAULT NULL,
					`text_validator` VARCHAR(255) DEFAULT NULL,
					`text_length` INT(11) UNSIGNED DEFAULT 0,
					`text_cdata` ENUM('yes', 'no') DEFAULT 'no',
					`text_handle` ENUM('yes', 'no') DEFAULT 'no',
					`handle_unique` ENUM('yes', 'no') DEFAULT 'yes',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
				self::FIELD_TABLE
			));

			return true;
		}

		/**
		 * Cleanup installation.
		 *
		 * @return boolean
		 */
		public function uninstall() {
			Symphony::Database()->query(sprintf(
				"DROP TABLE `%s`",
				self::FIELD_TABLE
			));

			return true;
		}

		/**
		 * Update extension from previous releases.
		 *
		 * @see toolkit.ExtensionManager#update()
		 * @param string $previousVersion
		 * @return boolean
		 */
		public function update($previousVersion=false) {
			// Column length:
			if ($this->updateHasColumn('show_full')) {
				$this->updateRemoveColumn('show_full');
			}

			if (!$this->updateHasColumn('column_length')) {
				$this->updateAddColumn('column_length', 'INT(11) UNSIGNED DEFAULT 75 AFTER `field_id`');
			}

			// Text size:
			if ($this->updateHasColumn('size')) {
				$this->updateRenameColumn('size', 'text_size');
			}

			// Text formatter:
			if ($this->updateHasColumn('formatter')) {
				$this->updateRenameColumn('formatter', 'text_formatter');
			}

			// Text validator:
			if ($this->updateHasColumn('validator')) {
				$this->updateRenameColumn('validator', 'text_validator');
			}

			// Text length:
			if ($this->updateHasColumn('length')) {
				$this->updateRenameColumn('length', 'text_length');
			}

			else if (!$this->updateHasColumn('text_length')) {
				$this->updateAddColumn('text_length', 'INT(11) UNSIGNED DEFAULT 0 AFTER `text_formatter`');
			}

			// Text CDATA:
			if (!$this->updateHasColumn('text_cdata')) {
				$this->updateAddColumn('text_cdata', "ENUM('yes', 'no') DEFAULT 'no' AFTER `text_length`");
			}

			// Text handle:
			if (!$this->updateHasColumn('text_handle')) {
				$this->updateAddColumn('text_handle', "ENUM('yes', 'no') DEFAULT 'no' AFTER `text_cdata`");
			}

			// is handle unique:
			if (!$this->updateHasColumn('handle_unique')) {
				$this->updateAddColumn('handle_unique', "ENUM('yes', 'no') NOT NULL DEFAULT 'yes' AFTER `text_handle`");
			}

			// Add handle index to textbox entry tables:
			$textbox_fields = FieldManager::fetch(null, null, 'ASC', 'sortorder', 'textbox');
			foreach($textbox_fields as $field) {
				$table = "tbl_entries_data_" . $field->get('id');

				// Handle length
				if ($this->updateHasIndex('handle', $table)) {
					$this->updateDropIndex('handle', $table);
				}
				$this->updateModifyColumn('handle', 'VARCHAR(1024)', $table);

				// Make sure we have an index on the handle
				if ($this->updateHasColumn('text_handle') && !$this->updateHasIndex('handle', $table)) {
					$this->updateAddIndex('handle', $table, 333);
				}
				
				// Make sure we have a unique key on `entry_id`
				if ($this->updateHasColumn('entry_id', $table) && !$this->updateHasUniqueKey('entry_id', $table)) {
					$this->updateAddUniqueKey('entry_id', $table);
				}
			}

			return true;
		}

		/**
		 * Add a new Index. Note that this does not check to see if an
		 * index already exists.
		 *
		 * @param string $index
		 * @param string $table
		 * @return boolean
		 */
		public function updateAddIndex($index, $table, $limit = null) {
			$col = "`{$index}`";
			if ($limit) {
				$col .= '(' . General::intval($limit) . ')';
			}
			return Symphony::Database()->query("
				ALTER TABLE
					`$table`
				ADD INDEX
					`{$index}` ($col)
			");
		}

		/**
		 * Check if the given `$table` has the `$index`.
		 *
		 * @param string $index
		 * @param string $table
		 * @return boolean
		 */
		public function updateHasIndex($index, $table) {
			return (boolean)Symphony::Database()->fetchVar(
				'Key_name', 0,
				"
					SHOW INDEX FROM
						`$table`
					WHERE
						Key_name = '{$index}'
				"
			);
		}

		/**
		 * Drop the given `$index` from `$table`.
		 *
		 * @param string $index
		 * @param string $table
		 * @return boolean
		 */
		public function updateDropIndex($index, $table)
		{
			return Symphony::Database()->query("
				ALTER TABLE
					`$table`
				DROP INDEX
					`{$index}`
			");
		}

		/**
		 * Add a new Unique Key. Note that this does not check to see if an
		 * unique key already exists and will remove any existing key on the column.
		 *
		 * @param string $column
		 * @param string $table
		 * @return boolean
		 */
		public function updateAddUniqueKey($column, $table = self::FIELD_TABLE) {
			try {
				Symphony::Database()->query("
					ALTER TABLE
						`$table`
					DROP KEY
						`$column`
				");
			} catch (Exception $ex) {
				// ignore
			}
			return Symphony::Database()->query("
				ALTER TABLE
					`$table`
				ADD UNIQUE KEY
					`$column` (`$column`)
			");
		}

		/**
		 * Check if the given `$table` has a unique key on `$column`.
		 *
		 * @param string $column
		 * @param string $table
		 * @return boolean
		 */
		public function updateHasUniqueKey($column, $table = self::FIELD_TABLE) {
			$db = Symphony::Configuration()->get('database', 'db');
			return (boolean)Symphony::Database()->fetchVar(
				'CONSTRAINT_NAME', 0,
				"
					SELECT DISTINCT CONSTRAINT_NAME
					FROM information_schema.TABLE_CONSTRAINTS
					WHERE CONSTRAINT_SCHEMA = '$db' AND
						CONSTRAINT_NAME = '$column' AND
						table_name = '$table' AND
						constraint_type = 'UNIQUE';
				"
			);
		}

		/**
		 * Add a new column to the settings table.
		 *
		 * @param string $column
		 * @param string $type
		 * @return boolean
		 */
		public function updateAddColumn($column, $type, $table = self::FIELD_TABLE) {
			return Symphony::Database()->query(sprintf("
				ALTER TABLE
					`%s`
				ADD COLUMN
					`{$column}` {$type}
				",
				$table
			));
		}

		/**
		 * Add a new column to the settings table.
		 *
		 * @param string $column
		 * @param string $type
		 * @return boolean
		 */
		public function updateModifyColumn($column, $type, $table = self::FIELD_TABLE) {
			return Symphony::Database()->query(sprintf("
				ALTER TABLE
					`%s`
				MODIFY COLUMN
					`{$column}` {$type}
				",
				$table
			));
		}

		/**
		 * Does the settings table have a column?
		 *
		 * @param string $column
		 * @return boolean
		 */
		public function updateHasColumn($column, $table = self::FIELD_TABLE) {
			return (boolean)Symphony::Database()->fetchVar('Field', 0, sprintf("
					SHOW COLUMNS FROM
						`%s`
					WHERE
						Field = '{$column}'
				",
				$table
			));
		}

		/**
		 * Remove a column from the settings table.
		 *
		 * @param string $column
		 * @return boolean
		 */
		public function updateRemoveColumn($column, $table = self::FIELD_TABLE) {
			return Symphony::Database()->query(sprintf("
				ALTER TABLE
					`%s`
				DROP COLUMN
					`{$column}`
				",
				$table
			));
		}

		/**
		 * Rename a column in the settings table.
		 *
		 * @param string $column
		 * @return boolean
		 */
		public function updateRenameColumn($from, $to, $table = self::FIELD_TABLE) {
			$data = Symphony::Database()->fetchRow(0, sprintf("
					SHOW COLUMNS FROM
						`%s`
					WHERE
						Field = '{$from}'
				",
				$table
			));

			if (!is_null($data['Default'])) {
				$type = 'DEFAULT ' . var_export($data['Default'], true);
			}

			else if ($data['Null'] == 'YES') {
				$type .= 'DEFAULT NULL';
			}

			else {
				$type .= 'NOT NULL';
			}

			return Symphony::Database()->query(sprintf("
				ALTER TABLE
					`%s`
				CHANGE
					`%s` `%s` %s
				",
				$table, $from, $to,
				$data['Type'] . ' ' . $type
			));
		}
	}