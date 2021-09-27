<?php

	Class extension_are_you_sure extends Extension{

		public function getSubscribedDelegates(){
			return array(
				array(
					'page'     => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendAssets'
				),
			);
		}

	/*-------------------------------------------------------------------------
		Delegates:
	-------------------------------------------------------------------------*/

    public function appendAssets()
    {
        $callback = Symphony::Engine()->getPageCallback();

        if ($callback['driver'] == 'publish' && $callback['context']['page'] !== 'index') {
            Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/are_you_sure/assets/are-you-sure.publish.css');
            
            Administration::instance()->Page->addScriptToHead(URL . '/extensions/are_you_sure/assets/are-you-sure.js');
            Administration::instance()->Page->addScriptToHead(URL . '/extensions/are_you_sure/assets/are-you-sure.publish.js');
        }
    }

	}
