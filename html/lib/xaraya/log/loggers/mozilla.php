<?php
/**
 * @package core\logging
 * @subpackage logging
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Mozilla js console logger
 *
 * @copyright see the html/credits.html file in this release
*/

/**
 * Make sure the base class is available
 *
 */
sys::import('xaraya.log.loggers.xarLogger');

/**
 * MozJSConsoleLogger
 *
 * Uses Mozillas Javascript Console to log messages
 *
 */
class xarLogger_mozilla extends xarLogger
{
    var $loggerdesc = "Mozilla Javascript Console Logger";

    /**
    * Buffer for logging messages
    */
    var $_buffer;

    /**
    * Write out the buffer if it is possible (the template system is already loaded)
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
     * @param array     $conf               Configuration options for the specific driver.
     * 
     * @return boolean
     */
    function setConfig(Array &$conf)
    {
        parent::setConfig($conf);
        $this->_buffer = $this->getCommonCode();
    }

    function getCommonCode()
    {
        // Common javascript to get a variable which has the logmessage method
        $code="
function mozConsole(msg, level)
{
    // Only relevant for moz engine
    if(navigator.appName.indexOf('Netscape') != -1) {
      netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');\n
      var consoleService = Components.classes['@mozilla.org/consoleservice;1']
                                 .getService(Components.interfaces.nsIConsoleService);

      if(level < 32 ) {
         if(level >= 0) flag = 0; // error
         if(level >= 16) flag= 1; // warning
         var scriptError = Components.classes['@mozilla.org/scripterror;1']
                                   .createInstance(Components.interfaces.nsIScriptError);
        scriptError.init(msg, null, null, null, null, flag, '');\n
         consoleService.logMessage(scriptError);\n
      } else {
        consoleService.logStringMessage(msg);\n
     }
   }
}";
  return $code;
    }

   /**
    * Updates the Observer
    *
    * @param string $message Log message
    * @param int $level Level of priority of the message
    * @return boolean  True on success or false on failure.
    * 
    */
    function notify($message, $level)
    {
        if (!$this->doLogLevel($level)) return false;

        // FIXME: this code depends on a user setting to use principal codebase support (same origin policy)
        // In mozilla//ff:
        // 1. about:config in address bar
        // 2. look up signed.applets.codebase_principal_support
        // 3. make sure it is set to true
        // alternatively user_pref("signed.applets.codebase_principal_support", true);
        // in the profile prefs.js file
        //
        // it should be done with a signed script eventually, but this is rather complex
        // TODO: check on windows and browsers other than mozilla, to fall back gracefully

        $logentry = $this->getTime(). " - (" .$this->levelToString($level).") ".$message;

        // Add \ for problematic chars and for each newline format unix, mac and windows
        $logentry = addslashes($logentry);
        $trans = array("\n" => "\\\n","\r" => "\\\r","\r\n" => "\\\r\n");
        $logentry = strtr($logentry,$trans);
        $this->_buffer .= "mozConsole('$logentry', $level);\n";
        $this->writeOut();
    }
 }

?>