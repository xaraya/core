<?php

/**
 * Response class
 *
 * @package core\controllers
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

use Xaraya\Services\xar;

class xarResponse extends xarObject
{
    public string $output;
    public int $status;
    public string $mediaType;
    /** @var array<string, mixed> */
    public array $headers;

    /**
     * @todo no arguments are set/known by xarController::setResponse() before dispatch()
     * @param array<string, mixed> $headers
     */
    public function __construct(?string $output = null, int $status = 200, string $mediaType = '', array $headers = [])
    {
        $this->output = $output ?? '';
        $this->status = $status;
        $this->mediaType = $mediaType;
        $this->headers = $headers;
    }

    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getMediaType(): string
    {
        $mls = xar::getServicesClass()->mls();
        $this->mediaType = $this->mediaType ?: 'text/html; charset=' . $mls->getCharsetFromLocale($mls->getSiteLocale());
        return $this->mediaType;
    }

    /**
     * initialize
     *
     * @param array<string, mixed> $args
     * @return void
     */
    public static function init(array $args = []) {}

    // CHECKME: Should we support this kind of high-level user response in module GUI functions ?
    //          And should some of the existing exceptions (to be defined) call those methods too ?

    /**
     * Return a 404 Not Found header, and fill in the template message-notfound.xt from the base module
     *
     * Usage in GUI functions etc.:
     *
     *    if (something not found, e.g. item $id) {
     *        $msg = xarMLS::translate("Sorry, item #(1) is not available right now", $id);
     *        return xarResponse::NotFound($msg);
     *    }
     *    ...
     *
     * @param string $msg the message
     * @param string $modName template overrides, cfr. xarTpl::module (optional)
     * @param string $modType template overrides, cfr. xarTpl::module (optional)
     * @param string $funcName template overrides, cfr. xarTpl::module (optional)
     * @param string $templateName template overrides, cfr. xarTpl::module (optional)
     * @param mixed $context
     * @return string output display string
     */
    public static function NotFound($msg = '', $modName = 'base', $modType = 'message', $funcName = 'notfound', $templateName = null, $context = null)
    {
        $xar = xar::getServicesClass();
        $xar->setModName($modName);
        $xar->cache()->noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 404 Not Found');
        }

        $xar->tpl()->setPageTitle('404 Not Found');

        $tplData = [
            'msg' => $msg,
            'context' => $context,
        ];
        return $xar->tpl()->module($modName, $modType, $funcName, $tplData, $templateName);
    }

    /**
     * Return a 403 Forbidden header, and fill in the message-forbidden.xt template from the base module
     *
     * Usage in GUI functions etc.:
     *
     *    if (something not allowed, e.g. edit item $id) {
     *        $msg = xarMLS::translate("Sorry, you are not allowed to edit item #(1)", $id);
     *        return xarResponse::Forbidden($msg);
     *    }
     *    ...
     *
     * @param string $msg the message
     * @param string $modName template overrides, cfr. xarTpl::module (optional)
     * @param string $modType template overrides, cfr. xarTpl::module (optional)
     * @param string $funcName template overrides, cfr. xarTpl::module (optional)
     * @param string $templateName template overrides, cfr. xarTpl::module (optional)
     * @param mixed $context
     * @return string output display string
     */
    public static function Forbidden($msg = '', $modName = 'base', $modType = 'message', $funcName = 'forbidden', $templateName = null, $context = null)
    {
        $xar = xar::getServicesClass();
        $xar->setModName($modName);
        $xar->cache()->noCache();
        if (!headers_sent()) {
            header('HTTP/1.0 403 Forbidden');
        }

        $xar->tpl()->setPageTitle('403 Forbidden');

        $tplData = [
            'msg' => $msg,
            'context' => $context,
        ];
        return $xar->tpl()->module($modName, $modType, $funcName, $tplData, $templateName);
    }
}
