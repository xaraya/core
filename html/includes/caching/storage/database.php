<?php

include_once 'includes/caching/storage.php';

class xarCache_Database_Storage extends xarCache_Storage
{
    var $table = '';

    function xarCache_Database_Storage($args = array())
    {
        $this->xarCache_Storage($args);

        $this->table = xarDBGetSiteTablePrefix() . '_cache_data';
        $this->storage = 'db';
    }

}

?>
