<?php

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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list($dbconn) = xarDBGetConn();

    $query = "SELECT MAX($column)
              FROM $table";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    list($maxseq) = $result->fields;
    $result->Close();

    return($maxseq);
}

?>