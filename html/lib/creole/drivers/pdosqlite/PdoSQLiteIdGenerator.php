<?php

require_once 'creole/common/PdoIdGeneratorCommon.php';

/**
 * SQLite IdGenerator implimenation.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.4 $
 * @package   creole.drivers.sqlite
 */
class PdoSQLiteIdGenerator extends PdoIdGeneratorCommon
{
    // XARAYA MODIFICATION
    public function getNextId($tableName)
    {
        // We dont know it, return null
        return null;
    }

    public function getLastId($tableName)
    {
        // Same as getId
        return $this->getId();
    }
    // END XARAYA MODIFICATION
}
