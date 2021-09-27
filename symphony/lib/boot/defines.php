<?php

/**
 * @package boot
 */

/**
 * Used to determine if Symphony has been loaded, useful to prevent
 * files from being accessed directly.
 * @var boolean
 */
define_safe('__IN_SYMPHONY__', true);

/**
 * The filesystem path to the `manifest` folder
 * @var string
 */
define_safe('MANIFEST', DOCROOT . '/manifest');

/**
 * The filesystem path to the `extensions` folder
 * @var string
 */
define_safe('EXTENSIONS', DOCROOT . '/extensions');

/**
 * The filesystem path to the `workspace` folder
 * @var string
 */
define_safe('WORKSPACE', DOCROOT . '/workspace');

/**
 * The filesystem path to the `symphony` folder
 * @var string
 */
define_safe('SYMPHONY', DOCROOT . '/symphony');

/**
 * The filesystem path to the `lib` folder which is contained within
 * the `symphony` folder.
 * @var string
 */
define_safe('LIBRARY', SYMPHONY . '/lib');

/**
 * The filesystem path to the `assets` folder which is contained within
 * the `symphony` folder.
 * @var string
 */
define_safe('ASSETS', SYMPHONY . '/assets');

/**
 * The filesystem path to the `content` folder which is contained within
 * the `symphony` folder.
 * @var string
 */
define_safe('CONTENT', SYMPHONY . '/content');

/**
 * The filesystem path to the `template` folder which is contained within
 * the `symphony` folder.
 * @var string
 */
define_safe('TEMPLATE', SYMPHONY . '/template');

/**
 * The filesystem path to the `utilities` folder which is contained within
 * the `workspace` folder.
 * @var string
 */
define_safe('UTILITIES', WORKSPACE . '/utilities');

/**
 * The filesystem path to the `data-sources` folder which is contained within
 * the `workspace` folder.
 * @var string
 */
define_safe('DATASOURCES', WORKSPACE . '/data-sources');

/**
 * The filesystem path to the `events` folder which is contained within
 * the `workspace` folder.
 * @var string
 */
define_safe('EVENTS', WORKSPACE . '/events');

/**
 * The filesystem path to the `text-formatters` folder which is contained within
 * the `workspace` folder.
 * @var string
 */
define_safe('TEXTFORMATTERS', WORKSPACE . '/text-formatters');

/**
 * The filesystem path to the `pages` folder which is contained within
 * the `workspace` folder.
 * @var string
 */
define_safe('PAGES', WORKSPACE . '/pages');

/**
 * The filesystem path to the `cache` folder which is contained within
 * the `manifest` folder.
 * @var string
 */
define_safe('CACHE', MANIFEST . '/cache');

$dir = @sys_get_temp_dir();

if ($dir == false || !@is_writable($dir)) {
    $dir = @ini_get('upload_tmp_dir');
}

if ($dir == false || !is_writable($dir)) {
    $dir = MANIFEST . '/tmp';
}
/**
 * The filesystem path to the `tmp` folder which is contained within
 * the system's temp directory (sys_get_temp_dir()), or the `upload_tmp_dir`
 * or falling back to use `manifest/tmp`.
 * @var string
 */
define_safe('TMP', $dir);
unset($dir);

/**
 * The filesystem path to the `logs` folder which is contained within
 * the `manifest` folder. The default Symphony Log file is saved at this
 * path.
 * @var string
 */
define_safe('LOGS', MANIFEST . '/logs');

/**
 * The filesystem path to the `main` file which is contained within
 * the `manifest/logs` folder. This is the default Symphony log file.
 * @var string
 */
define_safe('ACTIVITY_LOG', LOGS . '/main');

/**
 * The filesystem path to the `config.php` file which is contained within
 * the `manifest` folder. This holds all the Symphony configuration settings
 * for this install.
 * @var string
 */
define_safe('CONFIG', MANIFEST . '/config.php');

/**
 * The filesystem path to the `boot` folder which is contained within
 * the `symphony/lib` folder.
 * @var string
 */
define_safe('BOOT', LIBRARY . '/boot');

/**
 * The filesystem path to the `core` folder which is contained within
 * the `symphony/lib` folder.
 * @var string
 */
define_safe('CORE', LIBRARY . '/core');

/**
 * The filesystem path to the `lang` folder which is contained within
 * the `symphony/lib` folder. By default, the Symphony install comes with
 * an english language translation.
 * @var string
 */
define_safe('LANG', LIBRARY . '/lang');

/**
 * The filesystem path to the `toolkit` folder which is contained within
 * the `symphony/lib` folder.
 * @var string
 */
define_safe('TOOLKIT', LIBRARY . '/toolkit');

/**
 * The filesystem path to the `interface` folder which is contained within
 * the `symphony/lib` folder.
 * @since Symphony 2.3
 * @var string
 */
define_safe('FACE', LIBRARY . '/interface');

/**
 * The filesystem path to the `email-gateways` folder which is contained within
 * the `symphony/lib/toolkit` folder.
 * @since Symphony 2.2
 * @var string
 */
define_safe('EMAILGATEWAYS', TOOLKIT . '/email-gateways');

/**
 * Used as a default seed, this returns the time in seconds that Symphony started
 * to load. Most profiling runs use this as a benchmark.
 * @var float
 */
define_safe('STARTTIME', precision_timer());

/**
 * Returns the number of seconds that represent two weeks.
 * @var integer
 */
define_safe('TWO_WEEKS', (60*60*24*14));

/**
 * Returns the environmental variable if HTTPS is in use.
 * @var string|boolean
 */
define_safe('HTTPS', server_safe('HTTPS'));

/**
 * Returns the current host, e.g. google.com
 * @var string
 */
$http_host = server_safe('HTTP_HOST');
if (function_exists('idn_to_utf8')) {
    // In PHP 7.2, `idn_to_utf8` should not be called with default parameters,
    // because the default for `variant` has been deprecated. However, the
    // alternative variant `INTL_IDNA_VARIANT_UTS46` was not introduced before
    // PHP 5.4, so we must be careful.
    // https://wiki.php.net/rfc/deprecate-and-remove-intl_idna_variant_2003
    // https://bugs.php.net/bug.php?id=75609
    // @deprecated: This 'hack' can be removed later; when dropping PHP < 5.4,
    // `idn_to_utf8($http_host, 0, INTL_IDNA_VARIANT_UTS46)` can be used
    // exclusively.
    if (defined('INTL_IDNA_VARIANT_UTS46')) {
        $host_utf8 = idn_to_utf8($http_host, 0, INTL_IDNA_VARIANT_UTS46);
    } else {
        $host_utf8 = idn_to_utf8($http_host);
    }

    if ($host_utf8 !== false) {
        $http_host = $host_utf8;
    }
    unset($host_utf8);
}
define_safe('HTTP_HOST', $http_host);
unset($http_host);

/**
 * Returns the IP address of the machine that is viewing the current page.
 * @var string
 */
define_safe('REMOTE_ADDR', server_safe('REMOTE_ADDR'));

/**
 * Returns the User Agent string of the browser that is viewing the current page
 * @var string
 */
define_safe('HTTP_USER_AGENT', server_safe('HTTP_USER_AGENT'));

/**
 * If HTTPS is on, `__SECURE__` will be set to true, otherwise false. Use union of
 * the `HTTPS` environmental variable and the X-Forwarded-Proto header to allow
 * downstream proxies to inform the webserver of secured downstream connections
 * @var string|boolean
 */
define_safe('__SECURE__',
    (HTTPS == 'on' || server_safe('HTTP_X_FORWARDED_PROTO') == 'https')
);

/**
 * Returns the protocol used to this request.
 * If __SECURE__ it will be https:
 * If not, http:
 * @var string
 */
define_safe('HTTP_PROTO', 'http' . (defined('__SECURE__') && __SECURE__ ? 's' : '') . ':');

/**
 * The root url directory.
 * This constant will be empty if Symphony
 * is installed at the root level
 *
 * @since Symphony 2.7.0
 * @var string
 */
define_safe('DIRROOT', rtrim(dirname(server_safe('PHP_SELF')), '\/'));

/**
 * The current domain name.
 * @var string
 */
define_safe('DOMAIN', HTTP_HOST . DIRROOT);

/**
 * The base URL of this Symphony install, minus the symphony path.
 * @var string
 */
define_safe('URL', HTTP_PROTO . '//' . DOMAIN);

/**
 * Returns the folder name for Symphony as an application
 * @since Symphony 2.3.2
 * @var string
 */
define_safe('APPLICATION_URL', URL . '/symphony');

/**
 * Returns the folder name for Symphony as an application
 * @since Symphony 2.6.0
 * @var string
 */
define_safe('ASSETS_URL', APPLICATION_URL . '/assets');

/**
 * The integer value for event-type resources.
 *
 * @deprecated Use ResourceManager::RESOURCE_TYPE_EVENT, this will be removed in Symphony 3.0.
 * @var integer
 */
define_safe('RESOURCE_TYPE_EVENT', 20);

/**
 * The integer value for datasource-type resources.
 *
 * @deprecated Use ResourceManager::RESOURCE_TYPE_DS, this will be removed in Symphony 3.0.
 * @var integer
 */
define_safe('RESOURCE_TYPE_DS', 21);

 /**
  * The constant for when an Entry is ok, that is, no errors have
  * been raised by any of it's Fields.
  * @deprecated Use Entry::__ENTRY_OK__, this will be removed in Symphony 3.0.
  * @var integer
  */
define_safe('__ENTRY_OK__', 0);

/**
 * The constant for an Entry if there is an error is raised by any of
 * it's Fields.
 * @deprecated Use Entry::__ENTRY_FIELD_ERROR__, this will be removed in Symphony 3.0.
 * @var integer
 */
define_safe('__ENTRY_FIELD_ERROR__', 100);

/**
 * Status when an extension is installed and enabled
 * @deprecated Use Extension::EXTENSION_ENABLED, this will be removed in Symphony 3.0.
 * @var integer
 */
define_safe('EXTENSION_ENABLED', 10);

/**
 * Status when an extension is disabled
 * @deprecated Use Extension::EXTENSION_DISABLED, this will be removed in Symphony 3.0.
 * @var integer
 */
define_safe('EXTENSION_DISABLED', 11);

/**
 * Status when an extension is in the file system, but has not been installed.
 * @deprecated Use Extension::EXTENSION_NOT_INSTALLED, this will be removed in Symphony 3.0.
 * @var integer
 */
define_safe('EXTENSION_NOT_INSTALLED', 12);

/**
 * Status when an extension version in the file system is different to
 * the version stored in the database for the extension
 * @deprecated Use Extension::EXTENSION_REQUIRES_UPDATE, this will be removed in Symphony 3.0.
 * @var integer
 */
define_safe('EXTENSION_REQUIRES_UPDATE', 13);

/**
 * Status when the extension is not compatible with the current version of
 * Symphony
 * @deprecated Use Extension::EXTENSION_NOT_COMPATIBLE, this will be removed in Symphony 3.0.
 * @since Symphony 2.3
 * @var integer
 */
define_safe('EXTENSION_NOT_COMPATIBLE', 14);

/**
 * Defines a constant for when the Profiler should be a complete snapshot of
 * the page load, from the very start, to the very end.
 * @var integer
 */
define_safe('PROFILE_RUNNING_TOTAL', 0);

/**
 * Defines a constant for when a snapshot should be between two points,
 * usually when a start time has been given
 * @var integer
 */
define_safe('PROFILE_LAP', 1);

/**
 * Defines a constant for the opening tag of a CDATA section in xml
 * @since Symphony 2.3.4
 * @var string
 */
define_safe('CDATA_BEGIN', '<![CDATA[');

/**
 * Defines a constant for the closing tag of a CDATA section in xml
 * @since Symphony 2.3.4
 * @var string
 */
define_safe('CDATA_END', ']]>');
