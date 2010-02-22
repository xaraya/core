<?php
class xarResponse extends Object
{
    public $output;
    
    /**
     * initialize
     *
     */
    static function init($args) { }

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
     * @access public
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string the template message-notfound.xt from the base module filled in
     */
    function NotFound($msg = '', $modName = 'base', $modType = 'message', $funcName = 'notfound', $templateName = NULL)
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
     * @access public
     * @param msg string the message
     * @param ... string template overrides, cfr. xarTplModule (optional)
     * @return string the template message-forbidden.xt from the base module filled in
     */
    function Forbidden($msg = '', $modName = 'base', $modType = 'message', $funcName = 'forbidden', $templateName = NULL)
    {
        xarCache::noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 403 Forbidden');
        }

        xarTplSetPageTitle('403 Forbidden');

        return xarTplModule($modName, $modType, $funcName, array('msg' => $msg), $templateName);
    }

    function getOutput() { return $this->output; }
}
?>