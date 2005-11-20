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
    if ((!isset($table)) ||
        (!isset($column))) {
        $msg = xarML('Empty table (#(1)) or column (#(2)).', $table, $column);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $dbconn =& xarDBGetConn();

    $query = "SELECT MAX($column)
              FROM $table";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    list($maxseq) = $result->fields;
    $result->Close();

    return($maxseq);
}

?>
