<?php

define('DOCROOT', str_replace('/extensions/cache_management/cron', '', rtrim(dirname(__FILE__), '\\/') ));

if (file_exists(DOCROOT . '/vendor/autoload.php')) {
	require_once(DOCROOT . '/vendor/autoload.php');
	require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
}
else {
	require_once(DOCROOT . '/symphony/lib/boot/bundle.php');
	require_once(DOCROOT . '/symphony/lib/core/class.cacheable.php');
	require_once(DOCROOT . '/symphony/lib/core/class.symphony.php');
	require_once(DOCROOT . '/symphony/lib/core/class.administration.php');
	require_once(DOCROOT . '/symphony/lib/toolkit/class.general.php');
}

// creates the DB
Administration::instance();

require_once(DOCROOT . '/extensions/cache_management/lib/class.cachemanagement.php');
