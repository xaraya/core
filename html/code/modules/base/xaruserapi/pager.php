<?php
/**
 * base-pager template tag
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
*/

/** 
 * Wrapper for xarTplPager::getPager() (see modules/base/class/pager.php)
 * 
 * Used by the base-pager template tag
 * Returns a pager based on url, startnum, itemsperpage and totalitems
 * Usage, eg <xar:pager startnum="1" itemsperpage="10" total="30"/>
 * 
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['total'] - required, total items of this type<br/>
 *        integer  $args['startnum'] optional, the current page startnum, if empty<br/>
 *                 the tag will try to fetch the startnum from the currenturl, and fall back to<br/>
 *                 1 if none found<br/>
 *        integer  $args['itemsperpage'] optional, the itemsperpage for this type,<br/>
 *                 if empty, the tag will attempt to retrieve the items_per_page moduservar setting<br/>
 *                 for the current module<br/>
 *        integer  $args['module'] - module to get items_per_page setting for<br/>
 *                 (if itemsperpage is empty)<br/>
 *        integer  $args['urltemplate'] - optional, the url template to use for<br/>
 *                 building page links defaults to current url, replacing startnum=xx with<br/>
 *                 startnum=[urlitemmatch]<br/>
 *        string   $args['urlitemmatch'] - optional, the string signifying the<br/>
 *                 position of the startnum to be replaced in [urltemplate], default '%%'<br/>
 *        string   $args['tplmodule'] - optional, the module to look for pager<br/>
 *        string   $args['template'] - optional, the template to use<br/>
 *                 (pager-[template]), default 'default',<br/>
 *                 template options are (default|multipage|mulitpagenext|multipageprev|openended)<br/>
 *        integer  $args['blocksize'] - optional, the number of pages per block, default 10<br/>
 *                 advanced options of xarTplPagerInfo<br/>
 *                 (not sure what they do, included for completeness)<br/>
 *        integer  $args['firstitem']<br/>
 *        integer  $args['firstpage']<br/>
 *
 * @return string Output display string
 */
function base_userapi_pager(Array $args=array())
{
    extract($args);
    if (empty($startnum) || !is_numeric($startnum))
        if (!xarVarFetch('startnum', 'int:1', $startnum, 1, XARVAR_NOT_REQUIRED)) return;

    if (!isset($itemsperpage) || !is_numeric($itemsperpage)) {
        if (empty($module))
            list($module) = xarController::$request->getInfo();
        if (!empty($module))
            // @TODO: setting per itemtype?
            // if (!empty($itemtype)) $itemsperpage = xarModUserVars::get($module, 'items_per_page'.$itemtype);
            $itemsperpage = xarModUserVars::get($module, 'items_per_page');
    }
    if ((empty($itemsperpage) || (empty($total) || !is_numeric($total))) || ($total <= $itemsperpage)) return '';

    sys::import('modules.base.class.pager');

    if (empty($urlitemmatch)) $urlitemmatch = '%%';
    if (empty($urltemplate)) $urltemplate = null;
    $urltemplate = xarTplPager::getPagerURL($urlitemmatch, $urltemplate);

    $blockoptions = array();
    if (empty($blocksize) || !is_numeric($blocksize)) $blocksize = 10;
    $blockoptions['blocksize'] = $blocksize;
    $blockoptions['urltemplate'] = $urltemplate;
    $blockoptions['urlitemmatch'] = $urlitemmatch;
    if (!empty($firstitem) && is_numeric($firstitem)) $blockoptions['firstitem'] = $firstitem;
    if (!empty($firstpage) && is_numeric($firstpage)) $blockoptions['firstpage'] = $firstpage;

    if (empty($tplmodule)) $tplmodule = 'base';
    if (empty($template)) $template = 'default';

    return xarTplPager::getPager($startnum, $total, $urltemplate, $itemsperpage, $blockoptions, $template, $tplmodule);
}
?>