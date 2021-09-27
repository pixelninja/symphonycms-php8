<?php 
require('include.cron.php');

$count = CacheManagement::purgeFileCache();
echo sprintf('Deleted %d files in cache', $count) . PHP_EOL;

$count = CacheManagement::purgeDBCache();
echo sprintf('Deleted %d DB cache entries', $count) . PHP_EOL;