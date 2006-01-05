<?php
/**
 * Maximum sequence number in a given table
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage adminpanels module
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * get the maximum sequence number currently in a given table
 * @param $args['table'] the table name
 * @param $args['column'] the sequence column name
 * @returns int
 * @return the maximum sequence number
 */
function adminpanels_adminapi_maxsequence($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((!isset($table)) || (!isset($column))) {
        throw new BadParameterException(array($table,$column),'Empty table (#(1)) or column (#(2)).');
    }

    $dbconn =& xarDBGetConn();

    $query = "SELECT MAX($column) FROM $table";
    $result =& $dbconn->executeQuery($query);
    $maxseq = $result->getInt(1);
    $result->close();
    
    return($maxseq);
}

?>
