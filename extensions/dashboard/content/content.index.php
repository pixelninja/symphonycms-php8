<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/dashboard/extension.driver.php');

Class contentExtensionDashboardIndex extends AdministrationPage {
	
	public function __viewIndex() {
		
		$this->setPageType('form');
		$this->setTitle(__('Symphony') . ' &ndash; ' . __('Dashboard'));
		
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/jquery-ui-1.11.4.custom.min.js', 29421);
		$this->addStylesheetToHead(URL . '/extensions/dashboard/assets/dashboard.index.css', 'screen', 29422);
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/dashboard.index.js', 29423);
		
		// https://github.com/symphonycms/symphony-2/wiki/Migration-Guide-to-2.5-for-Developers#properties
		$author = null;
		if (is_callable(array('Symphony', 'Author'))) {
			$author = Symphony::Author();
		} else {
			$author = Administration::instance()->Author;
		}

		// Add welcome message
		$hour = date('H');
		$welcome = __('Nice to meet you');
		if($author->get('last_see') != NULL) {
			if($hour < 10) $welcome = __('Good morning');
			elseif($hour < 17) $welcome = __('Welcome back');
			else $welcome = __('Good evening');
		}
			
		$panel_types = array();

		/**
		* Ask panel extensions to list their panel types.
		*
		* @delegate DashboardPanelTypes
		* @param string $context
		* '/backend/'
		* @param array $types
		*/
		Symphony::ExtensionManager()->notifyMembers('DashboardPanelTypes', '/backend/', array(
			'types' => &$panel_types
		));
		
		$actions = array();

		if($author->isDeveloper()) {
			$panel_types_options = array(
				array('', FALSE, __('New Panel'))
			);
			
			natsort($panel_types);
			foreach($panel_types as $handle => $name) {
				$panel_types_options[] = array($handle, false, $name);
			}
			
			$actions[] = Widget::Select('panel-type', $panel_types_options);
			$actions[] = Widget::Anchor(
				__('Enable Editing'),
				'#',
				__('Disable Editing'),
				'edit-mode button'
			);
		}

		$this->Form->setAttribute('class', 'two columns');
		
		$this->appendSubheading($welcome . ', ' . $author->get('first_name'), $actions);
		$this->insertDrawer(Widget::Drawer('dashboard', 'Dashboard', new XMLElement('span', ''), 'closed', time()), 'horizontal', FALSE);
		
		$container = new XMLElement('div', NULL, array('id' => 'dashboard'));
		
		$primary = new XMLElement('div', NULL, array('class' => 'primary column sortable-container'));
		$secondary = new XMLElement('div', NULL, array('class' => 'secondary column sortable-container'));
		
		$panels = Extension_Dashboard::getPanels();
		
		foreach($panels as $p) {
			
			$html = Extension_Dashboard::buildPanelHTML($p);
			
			switch($p['placement']) {
				case 'primary': $primary->appendChild($html); break;
				case 'secondary': $secondary->appendChild($html); break;
			}
			
		}
		
		$container->appendChild($primary);
		$container->appendChild($secondary);
		$this->Form->appendChild($container);
		
	}
	
}
