<?php

class ADODB_xaroci8 extends ADODB_oci8 {
	var $_genIDSQL = "SELECT (seq%s.nextval) FROM DUAL";
	var $_genSeqSQL = "CREATE SEQUENCE seq%s START WITH %s";
	var $_dropSeqSQL = "DROP SEQUENCE seq%s";

    // Add some debug timings to the driver execute method.
    function &_Execute($sql, $inputarr = false) {
        if (xarCoreIsDebugFlagSet(XARDBG_SQL)) {
            global $xarDebug_sqlCalls;
            $xarDebug_sqlCalls++;
            $lmtime = explode(' ', microtime());
            $lstarttime = $lmtime[1] + $lmtime[0];
        }

        // Execute the standard driver.
        $result = ADODB_mysql::_Execute($sql, $inputar);

        if (xarCoreIsDebugFlagSet(XARDBG_SQL)) {
            $lmtime = explode(' ', microtime());
            $lendtime = $lmtime[1] + $lmtime[0];
            $ltotaltime = ($lendtime - $lstarttime);
            xarLogMessage('Query (' . $ltotaltime . ' Seconds): ' . $sql);
        }

        return $result;
    }
}

?>