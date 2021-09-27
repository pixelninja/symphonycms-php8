<?php

Class extension_association_ui_editor extends Extension
{
    protected static $provides = array();

    public static function registerProviders()
    {
        self::$provides = array(
            'association-editor' => array(
                'aui-editor' => 'Editor',
                'aui-editor-new' => 'Editor (Create New)'
            )
        );

        return true;
    }

    public static function providerOf($type = null)
    {
        self::registerProviders();

        if (is_null($type)) {
            return self::$provides;
        }

        if (!isset(self::$provides[$type])) {
            return array();
        }

        return self::$provides[$type];
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedDelegates()
    {
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

        if ($callback['driver'] == 'publish' && $callback['context']['page'] !== 'index') {
            Administration::instance()->Page->addScriptToHead(URL . '/extensions/association_ui_editor/assets/aui.editor.publish.js');
            Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/association_ui_editor/assets/aui.editor.publish.css');
        }
    }
}
