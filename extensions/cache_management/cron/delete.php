<?php 
require('include.cron.php');

$count = CacheManagement::deleteFileCache();
echo sprintf('Deleted %d files in cache', $count) . PHP_EOL;

$count = CacheManagement::deleteDBCache();
echo sprintf('Deleted %d DB cache entries', $count) . PHP_EOL;