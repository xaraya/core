<?php

include_once 'includes/caching/storage.php';

class xarCache_FileSystem_Storage extends xarCache_Storage
{

    function xarCache_FileSystem_Storage($args = array())
    {
        $this->xarCache_Storage($args);

        $this->storage = 'fs';
    }
}

?>
