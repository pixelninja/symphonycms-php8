<?php

	Class Extension_DuplicateField extends Extension {

		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendPageHead'
				)
			);
		}

		public function appendPageHead($context) {
			// $author = Symphony::Author();
			$callback = Administration::instance()->getPageCallback();
			$page = Administration::instance()->Page;

			// $javascript = 'var user_id = "' . $author->get('id') . '",';
			// $javascript .= ' doc_root = "' . DOCROOT . '",';
			// $javascript .= ' user_type = "' . $author->get('user_type') . '",';
			// $javascript .= ' driver = "' . $callback['driver'] . '"';
			// $javascript .= (isset($_GET['folder']) && $_GET['folder'] !== '') ? ', folder_path = "' . $_GET['folder'] . '"' : ', folder_path';
			// $javascript .= ';';
			//
			// $html = new XMLElement('script', $javascript, array('type'=>'text/javascript'));

			// $page->addElementToHead($html);

			$callback['context'][0] = $callback['context'][0] ?? null;

			if ($callback['driver'] === 'blueprintssections' && $callback['context'][0] === 'edit') {
				$page->addScriptToHead(URL . '/extensions/duplicatefield/assets/duplicate.js', 667);
				$page->addStylesheetToHead(URL . '/extensions/duplicatefield/assets/duplicate.css', 'screen', 666);
			}
		}
	}
