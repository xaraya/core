<?php

/**
 * JavaScriptLogger
 *
 * Implements a javascript logger in a separate HTML window
 *
 *
 * @package logging
 */

include_once ('modules/logger/xarLogger.php');

class xarLogger_javascript extends xarLogger
{
    /**
    * Common Code. This will create the javascript debug window.
    *
    * @access private
    */
    function getCommonCode() {
        $header = "<table size=\\\"100%\\\" cellspacing=\\\"0\\\" cellpadding=\\\"0\\\" border=\\\"0\\\"><tr><td>".
                  "<hr size=\\\"1\\\">Xaraya Javascript Logger</hr></td><td width=\\\"1%\\\"><span style=\\\"font-face: Verdana,arial; font-size: 8pt;\\\">".
                  date("Y-m-d H:i:s").
                  "</span></td></tr></table>";

        $code = "\ndebugWindow = window.open(\"\",".
                "\"Xaraya_Javascript_Logger\",\"width=450,height=500,scrollbars=yes,resizable=yes\");\n".
                "if (debugWindow) {\n".
                "    debugWindow.focus();\n".
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
            xarTplAddJavaScriptCode('head', "JavaScriptLogger ", $this->getCommonCode());
            $first = false;
        }

        $code = "if (debugWindow) {\n".
                "    debugWindow.document.write(\"".$this->getTime().
                ' - ('.$this->levelToString($level).')<br/>'.
                nl2br(addslashes($message)) . "<br/><br/>\");\n".
                "    debugWindow.scrollBy(0,100000);\n".
                "}\n";


        xarTplAddJavaScriptCode('head', 'JavaScriptLogger', $code);
    }
}

?>
