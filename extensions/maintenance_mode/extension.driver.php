<?php

Class extension_maintenance_mode extends Extension
{
    public function install()
    {
        Symphony::Configuration()->set('enabled', 'no', 'maintenance_mode');

        return Symphony::Configuration()->write();
    }

    public function uninstall()
    {
        Symphony::Configuration()->remove('maintenance_mode');
    }

    public function getSubscribedDelegates()
    {
        return array(

            array('page'     => '/system/preferences/',
                  'delegate' => 'AddCustomPreferenceFieldsets',
                  'callback' => 'appendPreferences'),

            array('page'     => '/system/preferences/',
                  'delegate' => 'Save',
                  'callback' => '__SavePreferences'),

            array('page'     => '/system/preferences/',
                  'delegate' => 'CustomActions',
                  'callback' => '__toggleMaintenanceMode'),

            array('page'     => '/backend/',
                  'delegate' => 'AppendPageAlert',
                  'callback' => '__appendAlert'),

            array('page'     => '/blueprints/pages/',
                  'delegate' => 'AppendPageContent',
                  'callback' => '__appendType'),

            array('page'     => '/frontend/',
                  'delegate' => 'FrontendPrePageResolve',
                  'callback' => '__checkForMaintenanceMode'),

            array('page'     => '/frontend/',
                  'delegate' => 'FrontendParamsResolve',
                  'callback' => '__addParam')
        );
    }

    /**
     * Append maintenance mode preferences
     *
     * @param array $context
     *  delegate context
     */
    public function appendPreferences($context)
    {
        // Create preference group
        $group = new XMLElement('fieldset');
        $group->setAttribute('class', 'settings');
        $group->appendChild(new XMLElement('legend', __('Maintenance Mode')));

        // Append settings
        $label = Widget::Label();
        $input = Widget::Input('settings[maintenance_mode][enabled]', 'yes', 'checkbox');

        if (Symphony::Configuration()->get('enabled', 'maintenance_mode') === 'yes') {
            $input->setAttribute('checked', 'checked');
        }

        $label->setValue($input->generate() . ' ' . __('Enable maintenance mode'));
        $group->appendChild($label);

        // Append help
        $group->appendChild(new XMLElement('p', __('Maintenance mode will redirect all visitors, other than developers, to the specified maintenance page. To specify a maintenance page, give a page a type of <code>maintenance</code>'), array('class' => 'help')));

        // IP White list
        $label = Widget::Label(__('IP Whitelist'));
        $label->appendChild(Widget::Input('settings[maintenance_mode][ip_whitelist]', General::sanitize(Symphony::Configuration()->get('ip_whitelist', 'maintenance_mode'))));
        $group->appendChild($label);

        // Append help
        $group->appendChild(new XMLElement('p', __('Any user that has an IP listed above will be granted access. This eliminates the need to allow a user backend access. Separate each with a space.'), array('class' => 'help')));


        // Useragent White list
        $label = Widget::Label(__('Useragent Whitelist'));
        $whitelist = json_decode(Symphony::Configuration()->get('useragent_whitelist', 'maintenance_mode'));
        $useragent = '';
        if (is_array($whitelist) && !empty($whitelist)) {
            $useragent = implode("\r\n",$whitelist);
        }
        $label->appendChild(Widget::Textarea('settings[maintenance_mode][useragent_whitelist]', 5, 50, General::sanitize($useragent)));
        $group->appendChild($label);

        // Append help
        $group->appendChild(new XMLElement('p', __('Any useragent that listed above will be granted access. This eliminates the need to allow a user backend access, useful when third party services need to access your site prior to launch. Insert in json array format eg ["useragent1","useragent2"].'), array('class' => 'help')));


        // Append new preference group
        $context['wrapper']->appendChild($group);
    }

    /**
     * Save preferences
     *
     * @param array $context
     *  delegate context
     */
    public function __SavePreferences($context)
    {
        if ($context['settings']['maintenance_mode']['useragent_whitelist']){
            // Convert to a json encoded array
            $context['settings']['maintenance_mode']['useragent_whitelist'] = json_encode(explode("\r\n",$context['settings']['maintenance_mode']['useragent_whitelist']));
        }

        if (!is_array($context['settings'])) {

            // Disable maintenance mode by default

            $context['settings'] = array('maintenance_mode' => array('enabled' => 'no'));

        } elseif (!isset($context['settings']['maintenance_mode']['enabled'])) {

            // Disable maintenance mode if it has not been set to 'yes'

            $context['settings']['maintenance_mode']['enabled'] = 'no';
        }
    }

    /**
     * Toggle maintenance mode and redirect to the previous page, if specified.
     */
    public function __toggleMaintenanceMode()
    {
        if ($_REQUEST['action'] === 'toggle-maintenance-mode') {

            // Toggle mode
            $value = (Symphony::Configuration()->get('enabled', 'maintenance_mode') === 'no' ? 'yes' : 'no');
            Symphony::Configuration()->set('enabled', $value, 'maintenance_mode');
            Symphony::Configuration()->write();

            // Redirect
            redirect((isset($_REQUEST['redirect']) ? SYMPHONY_URL . $_REQUEST['redirect'] : Administration::instance()->getCurrentPageURL() . '/'));
        }
    }

    /**
     * Append notice that the site is currently in maintenance mode offering a link
     * to switch to live mode if no other alert is set.
     *
     * @param array $context
     *  delegate context
     */
    public function __appendAlert($context)
    {
        $author = null;
        if (is_callable(array('Symphony', 'Author'))) {
            $author = Symphony::Author();
        } else {
            $author = Administration::instance()->Author;
        }
        // Site in maintenance mode
        if(Symphony::Configuration()->get('enabled', 'maintenance_mode') == 'yes') {
            if ($author != null && $author->isDeveloper()) {
                Administration::instance()->Page->pageAlert(
                    __('This site is currently in maintenance mode.') . ' <a href="' . SYMPHONY_URL . '/system/preferences/?action=toggle-maintenance-mode&amp;redirect=' . getCurrentPage() . '">' . __('Restore?') . '</a>',
                    Alert::NOTICE
                );
            } else {
                Administration::instance()->Page->pageAlert(
                    __('This site is currently in maintenance mode.'),
                    Alert::NOTICE
                );
            }
        }
    }

    /**
     * Append type for maintenance pages to page editor.
     *
     * @param array $context
     *  delegate context
     */
    public function __appendType($context)
    {
        // Find page types
        $elements = $context['form']->getChildren();
        $fieldset = $elements[0]->getChildren();
        $group = $fieldset[2]->getChildren();
        $div = $group[1]->getChildren();
        $types = $div[2]->getChildren();

        // Search for existing maintenance type
        $flag = false;

        foreach ($types as $type) {

            if ($type->getValue() === 'maintenance') {

                $flag = true;
            }
        }

        // Append maintenance type
        if ($flag === false) {

            $mode = new XMLElement('li', 'maintenance');
            $div[2]->appendChild($mode);
        }
    }

    /**
     * Redirect to maintenance page, if site is in maintenance and the user is not logged in
     *
     * @param array $context
     *  delegate context
     */
    public function __checkForMaintenanceMode($context)
    {
        if (!Symphony::Engine()->isLoggedIn() && Symphony::Configuration()->get('enabled', 'maintenance_mode') === 'yes'){

            // Check the IP white list
            $whitelist = Symphony::Configuration()->get('ip_whitelist', 'maintenance_mode');
            if (strlen(trim($whitelist)) > 0) {

                $whitelist = explode(' ', $whitelist);

                if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {

                    return;
                }
            }

            // Check if useragent is allowed
            $useragent = Symphony::Configuration()->get('useragent_whitelist', 'maintenance_mode');
            if (strlen(trim($useragent)) > 0) {

                $useragent = json_decode($useragent);

                if (in_array($_SERVER['HTTP_USER_AGENT'], $useragent)) {

                    return;
                }
            }

            // Find custom maintenance page
            $row = PageManager::fetchPageByType('maintenance');

            if (is_array($row) && isset($row[0])) {

                // There's more than a `maintenance` page
                $row = $row[0];
            }

            $context['row'] = $row;

            // Default maintenance message
            if (empty($context['row'])) {

                Symphony::Engine()->throwCustomError(
                    __('Website Offline'),
                    __('This site is currently in maintenance. Please check back at a later date.')
                );
            }
        }
    }

    /**
     * Add site mode to parameter pool
     *
     * @param array $context
     *  delegate context
     */
    public function __addParam($context)
    {
        $context['params']['site-mode'] = (Symphony::Configuration()->get('enabled', 'maintenance_mode') === 'yes' ? 'maintenance' : 'live');
    }
}
