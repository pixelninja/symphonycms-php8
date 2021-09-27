<?php

class extension_importcsv extends Extension
{
    public function fetchNavigation()
    {
        // Author: Use the accessor function if available (Symphony 2.5)
        if (is_callable(array('Symphony', 'Author'))) {
            $author = Symphony::Author();
        } else {
            $author = Administration::instance()->Author;
        }

        if ($author->isDeveloper()) {
            return array(
                array(
                    'location'	=> __('System'),
                    'name'		=> __('Import / Export CSV'),
                    'link'		=> '/'
                )
            );
        }
    }

    public function update($previousVersion = false)
    {
        if (file_exists(TMP.'/importcsv.csv')) {
            @unlink(TMP.'/importcsv.csv');
        }
    }

}
