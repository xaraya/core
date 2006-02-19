<?php

if (! defined("_ADODB_XARMYSQL_LAYER")) {
 define("_ADODB_XARMYSQL_LAYER", 1 );

include_once(ADODB_DIR . '/drivers/adodb-mysql.inc.php');

class ADODB_xarmysql extends ADODB_mysql
{
    // Override this.
    var $hasGenID = false;

    function GenID($seqname = 'adodbseq', $startID = 1)
    {
        // Xaraya expects a zero (i.e. numeric).
        if (!$this->hasGenID) {
            return 0;
        }

        // Continue with the standard driver.
        return ADODB_mysql::GenID($seqname, $startID);
    }

    // Add some debug timings to the driver execute method.
    function &_Execute($sql, $inputarr = false) {
        if (xarCoreIsDebugFlagSet(XARDBG_SQL)) {
            global $xarDebug_sqlCalls;
            $xarDebug_sqlCalls++;
            // initialise time to render by proca
            $lmtime = explode(' ', microtime());
            $lstarttime = $lmtime[1] + $lmtime[0];
        }

        // Execute the standard driver.
        $result = ADODB_mysql::_Execute($sql, $inputarr);

        if (xarCoreIsDebugFlagSet(XARDBG_SQL)) {
            $lmtime = explode(' ', microtime());
            $lendtime = $lmtime[1] + $lmtime[0];
            $ltotaltime = ($lendtime - $lstarttime);
            xarLogMessage('Query (' . $ltotaltime . ' Seconds): ' . $sql);
        }

        return $result;
    }
}

}

?>