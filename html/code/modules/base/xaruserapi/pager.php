<?php
/**
 * Time Since
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base Module
 * @link http://xaraya.com/index.php/release/151.html
*/
/* Returns a pager based on url, startnum, itemsperpage and totalitems
 * Used by the base pager template tag
 * Usage <xar:base-pager param="value" ... />
 * @param int $args['total'] - required, total items of this type
 * @param int $args['startnum'] optional, the current page startnum, if empty
 * the tag will try to fetch the startnum from the currenturl, and fall back to
 * 1 if none found
 * @param int $args['itemsperpage'] optional, the itemsperpage for this type,
 * if empty, the tag will attempt to retrieve the items_per_page moduservar setting
 * for the current module
 * @param int $args['module'] - module to get items_per_page setting for
 * (if itemsperpage is empty)
 * @param int $args['urltemplate'] - optional, the url template to use for
 * building page links defaults to current url, replacing startnum=xx with
 * startnum=[urlitemmatch]
 * @param int $args['urlitemmatch'] - optional, the string signifying the
 * position of the startnum to be replaced in [urltemplate], default '%%'
 * @param int $args['tplmodule'] - optional, the module to look for pager
 * templates in, default 'base'
 * @param int $args['template'] - optional, the template to use
 * (pager-[template]), default 'default'
 * @param int $args['blocksize'] - optional, the number of pages per block, default 10
 * advanced options of xarTplPagerInfo
 * (not sure what they do, included for completeness)
 * @param int $args['firstitem']
 * @param int $args['firstpage']
 */
function base_userapi_pager($args)
{
    extract($args);
    if (empty($startnum) || !is_numeric($startnum))
        if (!xarVarFetch('startnum', 'int:1', $startnum, 1, XARVAR_NOT_REQUIRED)) return;

    if (!isset($itemsperpage) || !is_numeric($itemsperpage)) {
        if (empty($module))
            list($module) = xarRequest::getInfo();
        if (!empty($module))
            $itemsperpage = xarModUserVars::get($module, 'items_per_page');
    }
    if ((empty($itemsperpage) || (empty($total) || !is_numeric($total))) || ($total <= $itemsperpage)) return '';

    if (empty($urlitemmatch))
        $urlitemmatch = '%%';

    if (empty($urltemplate))
        $urltemplate = xarServer::getCurrentURL(array('startnum' => $urlitemmatch));

    if (strpos($urltemplate, $urlitemmatch) === false) {
        if (preg_match('/startnum=(.*)?(&amp;|$)/', $urltemplate)) {
            $urltemplate = preg_replace('/startnum=(.*)?(&amp;|$)/', 'startnum='.$urlitemmatch.'//2', $urltemplate);
        } else {
            $urljoin = preg_match('/\?/', $urltemplate) ? '$amp;' : '?';
            $urltemplate .= $urljoin . 'startnum=' . $urlitemmatch;
        }
    }

    $blockoptions = array();
    if (empty($blocksize) || !is_numeric($blocksize)) $blocksize = 10;
    $blockoptions['blocksize'] = $blocksize;
    $blockoptions['urltemplate'] = $urltemplate;
    $blockoptions['urlitemmatch'] = $urlitemmatch;
    if (!empty($firstitem) && is_numeric($firstitem)) $blockoptions['firstitem'] = $firstitem;
    if (!empty($firstpage) && is_numeric($firstpage)) $blockoptions['firstpage'] = $firstpage;


    sys::import('xaraya.pager');
    $data = xarTplPagerInfo($startnum, $total, $itemsperpage, $blockoptions);

    if (empty($tplmodule)) $tplmodule = 'base';
    if (empty($template)) $template = 'default';

    return xarTplModule($tplmodule, 'pager', $template, $data);
}
?>