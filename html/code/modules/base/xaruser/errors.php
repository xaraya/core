<?php
/**
 * Entry point for custom error messages
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */
/**
 * Entry point for custom error messages
 * Use this for redirecting pages from other applications or within Xaraya
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
function base_user_errors($args)
{
    if (!xarVar::fetch('errortype', 'str', $errortype, '', xarVar::NOT_REQUIRED)) return;
    switch ($errortype) {
        case 'forbidden':
            if (!xarVar::fetch('message',  'str', $msg,      '',   xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('template', 'str', $template, NULL, xarVar::NOT_REQUIRED)) return;
            return xarResponse::Forbidden($msg, 'base', 'message', 'forbidden', $template);
        case 'exception':
        case 'systemerror':
        case 'systeminfo':
        case 'usererror':
        case 'notfound':
        default:
            if (!xarVar::fetch('message',  'str', $msg,      '',   xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('template', 'str', $template, NULL, xarVar::NOT_REQUIRED)) return;
            return xarResponse::NotFound($msg, 'base', 'message', 'notfound', $template);
    }
}
