<?php
/**
 * Response class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarResponse extends Object
{
    public $output;
    
    /**
     * initialize
     *
     */
    static function init(Array $args=array()) { }

// CHECKME: Should we support this kind of high-level user response in module GUI functions ?
//          And should some of the existing exceptions (to be defined) call those methods too ?

    /**
     * Return a 404 Not Found header, and fill in the template message-notfound.xt from the base module
     *
     * Usage in GUI functions etc.:
     *
     *    if (something not found, e.g. item $id) {
     *        $msg = xarML("Sorry, item #(1) is not available right now", $id);
     *        return xarResponse::NotFound($msg);
     *    }
     *    ...
     *
     * 
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string output display string
     */
    static public function NotFound($msg = '', $modName = 'base', $modType = 'message', $funcName = 'notfound', $templateName = NULL)
    {
        xarCache::noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 404 Not Found');
        }

        xarTplSetPageTitle('404 Not Found');

        return xarTplModule($modName, $modType, $funcName, array('msg' => $msg), $templateName);
    }

    /**
     * Return a 403 Forbidden header, and fill in the message-forbidden.xt template from the base module
     *
     * Usage in GUI functions etc.:
     *
     *    if (something not allowed, e.g. edit item $id) {
     *        $msg = xarML("Sorry, you are not allowed to edit item #(1)", $id);
     *        return xarResponse::Forbidden($msg);
     *    }
     *    ...
     *
     * 
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string output display string
     */
    static public function Forbidden($msg = '', $modName = 'base', $modType = 'message', $funcName = 'forbidden', $templateName = NULL)
    {
        xarCache::noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 403 Forbidden');
        }

        xarTplSetPageTitle('403 Forbidden');

        return xarTplModule($modName, $modType, $funcName, array('msg' => $msg), $templateName);
    }

    /**
     * Carry out a redirect - legacy support for Jamaica 2.0 and 2.1
     *
     * @access public
     * @param redirectURL string the URL to redirect to
     */
    static public function Redirect($url = '')
    {
        return xarController::redirect($url);
    }

    function getOutput() { return $this->output; }
}
?>
