<?php

/**
 * JavaScriptLogger
 *
 * Implements a javascript logger in a separate HTML window
 *
 *
 * @package logging
 */

/**
 * Include the base file
 *
 */
include_once ('./includes/loggers/xarLogger.php');

/**
 * Javascript logger
 *
 * @package logging
 */
class xarLogger_javascript extends xarLogger
{
    /**
    * Common Code. This will create the javascript debug window.
    *
    * @access private
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
    * @access public
    */

    function notify($message, $level)
    {
        static $first = true;

        if ($first) {
            xarTplAddJavaScript('head', 'code', $this->getCommonCode());
            $first = false;
        }

        $code = "if (debugWindow) {\n".
                "    debugWindow.document.write(\"".$this->getTime().
                ' - ('.$this->levelToString($level).')<br/>'.
                nl2br(addslashes($message)) . "<br/><br/>\");\n".
                "    debugWindow.scrollBy(0,100000);\n".
                "}\n";

        xarTplAddJavaScript('head', 'code', $code);
    }
}

?>