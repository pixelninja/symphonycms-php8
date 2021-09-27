<?php

	Class Extension_Git_Head_Parameter extends Extension{
		
		public function install() {
			try {
			    if (!file_exists(DOCROOT . '/.git/FETCH_HEAD')) {
			        throw new Exception('File ' . DOCROOT . '/.git/FETCH_HEAD' . ' could not be found.');
			    }
			}
			catch(Exception $ex) {	
				Administration::instance()->Page->pageAlert(__('An error occurred while installing %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
				Administration::instance()->Page->pageAlert(__('An error occurred while installing %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
				return false;
			}

			return true;
		}

	    public function uninstall() {
			return false;
	    }

		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendParamsResolve',
					'callback'	=> 'add_git_param'
				),
			);
		}

		public function add_git_param($page) {
			$git = DOCROOT . '/.git/FETCH_HEAD';

			if (file_exists($git)) {
				$git_head = substr(file_get_contents($git), 0, 40);
				$page['params']['git-head'] = $git_head;
			} 
		}

	}