<?php
/**
 * @package core
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
 * JavaScriptLogger
 *
 * Implements a javascript logger in a separate HTML window
 *
 *
 */

/**
 * Make sure base class is available
 *
 */
sys::import('xaraya.log.loggers.xarLogger');

/**
 * Javascript logger
 *
 */
class xarLogger_javascript extends xarLogger
{
    /**
    * Buffer for logging messages
    */
    var $_buffer;

    /**
    * Write out the buffer if it is possible (the template system is already loaded)
    *
    * 
    */
    function writeOut()
    {
        xarMod::apiFunc('themes', 'user', 'registerjs',
            array('position' => 'head', 'type' => 'code', 'code' => $this->_buffer));
        $this->_buffer = '';
        return true;
    }

    /**
     * Sets up the configuration specific parameters for each driver
     *
     * @param array     $conf               Configuration options for the specific driver.
     *
     * 
     * @return boolean
     */
    function setConfig(array &$conf)
    {
        parent::setConfig($conf);
        $this->_buffer = $this->getCommonCode();
    }

    /**
    * Common Code. This will create the javascript debug window.
    *
    * 
    */
    function getCommonCode()
    {
        $header = "<hr size=\\\"1\\\"></hr><span style=\\\"font-face: Verdana,arial; font-size: 10pt;\\\">".
                  date("Y-m-d H:i:s").
                  "</span>";

        $code = "\ndebugWindow = window.open(\"\",".
                "\"Xaraya_Javascript_Logger\",\"width=450,height=500,scrollbars=yes,resizable=yes\");\n".
                "if (debugWindow) {\n".
                "    debugWindow.document.write(\"".$header."\"+'<p><b>'+window.location.href+'</b></p>');\n".
                "}\n";
        return $code;
    }

   /**
    * Updates the Observer
    *
    * @param string $message Log message
    * @param int $level level of priority of the message
    * @return boolean  True on success or false on failure.
    * 
    */
    function notify($message, $level)
    {
        $strings = array ("\r\n", "\r", "\n");
        $replace = array ("<br />", "<br />", "<br />");

        // Abort early if the level of priority is above the maximum logging level.
        if (!$this->doLogLevel($level)) return false;

        $this->_buffer .= "if (debugWindow) {\n".
                "    debugWindow.document.write('".$this->getTime().
                ' - ('.$this->levelToString($level).')<br/>'.
                addslashes(str_replace($strings, $replace, $message) ). "<br/><br/>');\n".
                "    debugWindow.scrollBy(0,100000);\n".
                "}\n";

        $this->writeOut();
    }
}

?>
