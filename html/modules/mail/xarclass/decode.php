<?php

/**
 * We do this tiny hack to be independent of PEAR
 * if it's there it will work, if it's not it will work too
 *
 */
if(!class_exists('PEAR')) {
    class PEAR extends Object
    {
        /**
         * Re-implement raiseError method which normally would be in PEAR
         */
        function raiseError($msg='Unknown error')
        {
            echo $msg."\n"; // TODO: raise exception here?
            return false;
        }
        
        // Signature from PEAR
        function isError($data, $code = null) 
        {
            return ($data === false);
        }
    }
}

// Include the main 3rd party functionaliy for
// parsing the mails, that file has only one modification
// namely NOT including PEAR.php
sys::import('modules.mail.xarclass.mimeDecode');

/**
 * Mail parser class
 *
 * This class extends the pear Mail_MimeDecode class
 * See that file for XARAYA specific modifications
 *
 * @author Marcel van der Bom <marcel@xaraya.com>
 * @todo use implements and interface definition
 */
class xarMailParser extends Mail_mimeDecode
{

}

?>