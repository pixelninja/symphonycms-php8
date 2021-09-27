<?php
    define('DOCROOT', rtrim(realpath(__DIR__ . '/../../../'), '/'));

    // Is there vendor autoloader?
    require_once DOCROOT . '/vendor/autoload.php';
    require_once DOCROOT . '/symphony/lib/boot/bundle.php';

    // Get the field ID that's uploading
    if(!isset($_REQUEST['field_id']) || !isset($_REQUEST['entry_id'])) {
        header("HTTP/1.0 400 Bad Request", true, 400);
        exit;
    }
    else {
        $field_id = (int)$_REQUEST['field_id'];
        $entry_id = (int)$_REQUEST['entry_id'];
        $state = $_REQUEST['state'];
    }

    // var_dump(Symphony::Database()->update(array('value' => $state), 'tbl_entries_data_' . $field_id, '`entry_id` = ' . $entry_id)); exit;
    // Update the field in the database:
    Symphony::Database()->update(array('value' => $state), 'tbl_entries_data_' . $field_id, '`entry_id` = ' . $entry_id);
