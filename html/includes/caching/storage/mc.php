<?php

include_once 'includes/caching/storage.php';

class xarCache_MemCached_Storage extends xarCache_Storage
{

    function xarCache_MemCached_Storage($args = array())
    {
        $this->xarCache_Storage($args);
        $this->storage = 'ms';
    }
}

?>
