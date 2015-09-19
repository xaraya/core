<?php
/**
 * Entry point for custom error messages
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
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
    if (!xarVarFetch('errortype', 'str', $errortype, '', XARVAR_NOT_REQUIRED)) return;
    switch ($errortype) {
        case 'forbidden':
            if (!xarVarFetch('message',  'str', $msg,      '',   XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('template', 'str', $template, NULL, XARVAR_NOT_REQUIRED)) return;
            return xarResponse::Forbidden($msg, 'base', 'message', 'forbidden', $template);
            break;
        case 'exception':
        case 'systemerror':
        case 'systeminfo':
        case 'usererror':
        case 'notfound':
        default:
            if (!xarVarFetch('message',  'str', $msg,      '',   XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('template', 'str', $template, NULL, XARVAR_NOT_REQUIRED)) return;
            return xarResponse::NotFound($msg, 'base', 'message', 'notfound', $template);
            break;
    }
}

?>