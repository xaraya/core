<?php
/**
 * Response class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarResponse extends xarObject
{
    public string $output;

    /**
     * initialize
     *
     * @param array<string, mixed> $args
     * @return void
     */
    public static function init(array $args = array())
    {
    }

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
     * @param string $msg the message
     * @param string $modName template overrides, cfr. xarTpl::module (optional)
     * @param string $modType template overrides, cfr. xarTpl::module (optional)
     * @param string $funcName template overrides, cfr. xarTpl::module (optional)
     * @param string $templateName template overrides, cfr. xarTpl::module (optional)
     * @return string output display string
     */
    public static function NotFound($msg = '', $modName = 'base', $modType = 'message', $funcName = 'notfound', $templateName = null)
    {
        xarCache::noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 404 Not Found');
        }

        xarTpl::setPageTitle('404 Not Found');

        return xarTpl::module($modName, $modType, $funcName, array('msg' => $msg), $templateName);
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
     * @param string $msg the message
     * @param string $modName template overrides, cfr. xarTpl::module (optional)
     * @param string $modType template overrides, cfr. xarTpl::module (optional)
     * @param string $funcName template overrides, cfr. xarTpl::module (optional)
     * @param string $templateName template overrides, cfr. xarTpl::module (optional)
     * @return string output display string
     */
    public static function Forbidden($msg = '', $modName = 'base', $modType = 'message', $funcName = 'forbidden', $templateName = null)
    {
        xarCache::noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 403 Forbidden');
        }

        xarTpl::setPageTitle('403 Forbidden');

        return xarTpl::module($modName, $modType, $funcName, array('msg' => $msg), $templateName);
    }

    /**
     * Carry out a redirect - legacy support for Jamaica 2.0 and 2.1
     *
     * @access public
     * @param string $url the URL to redirect to
     * @return bool|never
     */
    public static function Redirect($url = '')
    {
        return xarController::redirect($url);
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
