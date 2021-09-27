<?php

Class extension_association_field extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function getSubscribedDelegates()  {
        return array(
            array(
                'page' => '/backend/',
                'delegate' => 'InitaliseAdminPageHead',
                'callback' => 'appendAssets'
            )
        );
    }

    /**
     * Append assets
     */
    public function appendAssets()
    {
        $callback = Symphony::Engine()->getPageCallback();

        if ($callback['driver'] == 'publish' && $callback['context']['page'] === 'index') {
            Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/association_field/assets/association_field.publish.css');
        }
    }

    public function install()
    {
        try {
            Symphony::Database()->query(
                "CREATE TABLE IF NOT EXISTS `tbl_fields_association` (
                    `id` int(11) unsigned NOT NULL auto_increment,
                    `field_id` int(11) unsigned NOT NULL,
                    `allow_multiple_selection` enum('yes','no') NOT NULL default 'no',
                    `hide_when_prepopulated` enum('yes','no') NOT NULL default 'no',
                    `related_field_id` VARCHAR(255) NOT NULL,
                    `limit` int(4) unsigned NOT NULL default '20',
                    PRIMARY KEY  (`id`),
                    KEY `field_id` (`field_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
            );
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (parent::uninstall() == true) {
            Symphony::Database()->query("DROP TABLE `tbl_fields_association`");

            return true;
        }

        return false;
    }
}
