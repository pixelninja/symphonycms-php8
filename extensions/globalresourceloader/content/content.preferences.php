<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.pagemanager.php');

	class contentExtensionGlobalResourceLoaderPreferences extends AdministrationPage {
		protected $driver;

		public function __viewIndex() {
			$this->driver = ExtensionManager::create('globalresourceloader');
			$bIsWritable = true;

			if (!is_writable(CONFIG)) {
				$this->pageAlert(__('The Symphony configuration file, %s, is not writable. You will not be able to save changes to preferences.', array('<code>/manifest/config.php</code>')), Alert::ERROR);
				$bIsWritable = false;
			}

			if(isset($this->_context[1]) == 'saved') {
				$this->pageAlert(
					__('Global Resource Loader settings updated at %s.', array(Widget::Time()->generate()))
					, Alert::SUCCESS);
			}

			$this->setPageType('form');
			$this->setTitle('Symphony &ndash; ' . __('Global Resources'));

			$this->appendSubheading(__('Global Resources'));

		// Events --------------------------------------------------------

			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');
			$container->appendChild(
				new XMLElement('legend', __('Events'))
			);

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			$this->__viewIndexEventNames($group);
			$this->__viewIndexEventPages($group);

			$container->appendChild($group);
			$this->Form->appendChild($container);

		// Datasources --------------------------------------------------------

			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');
			$container->appendChild(
				new XMLElement('legend', __('Datasources'))
			);

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			$this->__viewIndexDSNames($group);
			$this->__viewIndexDSPages($group);

			$container->appendChild($group);
			$this->Form->appendChild($container);

		//---------------------------------------------------------------------

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			$attr = array('accesskey' => 's');
			if (!$bIsWritable) $attr['disabled'] = 'disabled';
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);
		}

	/*-------------------------------------------------------------------------
		Events:
	-------------------------------------------------------------------------*/

		public function __viewIndexEventNames($context) {
			$events = EventManager::listAll();
			$options = array();

			foreach ($events as $event) {
				$selected = $this->driver->isEventNameSelected($event['handle']);

				$options[] = array(
					$event['handle'], $selected, $event['name']
				);
			}

			$section = Widget::Label(__('Selected'));
			$section->setAttribute('class', 'column');
			$section->appendChild(Widget::Select(
				'settings[event-names][]', $options, array(
					'multiple' => 'multiple'
				)
			));

			$context->appendChild($section);
		}

		public function __viewIndexEventPages($context) {
			$pages = PageManager::fetch(false, array(), array(), 'sortorder ASC');
			$options = array();

			foreach ($pages as $page) {
				$selected = $this->driver->isEventPageSelected($page['id']);

				$options[] = array(
					$page['id'], $selected, '/' . PageManager::resolvePagePath($page['id'])
				);
			}

			$section = Widget::Label(__('Excluded Pages'));
			$section->setAttribute('class', 'column');
			$section->appendChild(Widget::Select(
				'settings[event-pages][]', $options, array(
					'multiple' => 'multiple'
				)
			));

			$context->appendChild($section);
		}

	/*-------------------------------------------------------------------------
		Datasources:
	-------------------------------------------------------------------------*/

		public function __viewIndexDSNames($context) {
			$datasources = DatasourceManager::listAll();
			$options = array();

			foreach ($datasources as $datasource) {
				$selected = $this->driver->isDSNameSelected($datasource['handle']);

				$options[] = array(
					$datasource['handle'], $selected, $datasource['name']
				);
			}

			$section = Widget::Label(__('Selected'));
			$section->setAttribute('class', 'column');
			$section->appendChild(Widget::Select(
				'settings[ds-names][]', $options, array(
					'multiple' => 'multiple'
				)
			));

			$context->appendChild($section);
		}

		public function __viewIndexDSPages($context) {
			$pages = PageManager::fetch(false, array(), array(), 'sortorder ASC');
			$options = array();

			foreach ($pages as $page) {
				$selected = $this->driver->isDSPageSelected($page['id']);

				$options[] = array(
					$page['id'], $selected, '/' . PageManager::resolvePagePath($page['id'])
				);
			}

			$section = Widget::Label(__('Excluded Pages'));
			$section->setAttribute('class', 'column');
			$section->appendChild(Widget::Select(
				'settings[ds-pages][]', $options, array(
					'multiple' => 'multiple'
				)
			));

			$context->appendChild($section);
		}

		public function __actionIndex() {
			$settings  = @$_POST['settings'];
			$settings['event-names'] = $settings['event-names'] ?? null;
			$settings['event-pages'] = $settings['event-pages'] ?? null;
			$settings['ds-names'] = $settings['ds-names'] ?? null;
			$settings['ds-pages'] = $settings['ds-pages'] ?? null;

			if (empty($this->driver)) {
				$this->driver = ExtensionManager::create('globalresourceloader');
			}

			if (@isset($_POST['action']['save'])) {
				$this->driver->setEventNames($settings['event-names']);
				$this->driver->setEventPages($settings['event-pages']);
				$this->driver->setDSNames($settings['ds-names']);
				$this->driver->setDSPages($settings['ds-pages']);

				// "Trick" symphony to use context. RE: #9
				redirect(SYMPHONY_URL . '/extension/globalresourceloader/preferences/index/saved/');
			}
		}
	}
