<?php
/**
 * File: $Id$
 *
 * Migrate module items
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Migrate module items
 *
 * @author the DynamicData module development team
 * @param $args['from'] the module, itemtype and itemid(s) for the original item
 * @param $args['to'] the module, itemtype and itemid preserve flag for the new item
 * @param $args['fieldmap'] the field mapping
 * @param $args['hookmap'] the hook mapping
 * @returns mixed
 * @return true on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_utilapi_migrate($args)
{
    extract($args);

    $invalid = array();
    if (empty($from)) {
        $invalid[] = 'from array';
    } else {
        if (empty($from['module']) || !is_numeric($from['module'])) {
            $invalid[] = 'from module';
        }
        if (!isset($from['itemtype']) || !is_numeric($from['itemtype'])) {
            $invalid[] = 'from itemtype';
        }
        if (empty($from['itemid'])) {
            $invalid[] = 'from itemid';
        }
    }
    if (empty($to)) {
        $invalid[] = 'to array';
    } else {
        if (empty($to['module']) || !is_numeric($to['module'])) {
            $invalid[] = 'to module';
        }
        if (!isset($to['itemtype']) || !is_numeric($to['itemtype'])) {
            $invalid[] = 'to itemtype';
        }
        // itemid can be empty or not empty here
    }
    if (empty($fieldmap)) {
        $invalid[] = 'fieldmap';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'migrate', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if (is_array($from['itemid'])) {
        $itemids = $from['itemid'];
    } else {
        $itemids = explode(',',$from['itemid']);
    }

    $modinfo = xarModGetInfo($from['module']);
    if (empty($modinfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'from module', 'admin', 'migrate', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
    $modulefrom = $modinfo['name'];

    $modinfo = xarModGetInfo($to['module']);
    if (empty($modinfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'to module', 'admin', 'migrate', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
    $moduleto = $modinfo['name'];

    $items = array();
    switch ($modulefrom)
    {
        case 'articles':
            $articles = xarModAPIFunc('articles','user','getall',
                                      array('aids' => $itemids,
                                            // get the categories and dynamicdata fields too
                                            'extra' => array('cids','dynamicdata')));
            if (!isset($articles)) return;
            // re-assign by itemid
            foreach ($articles as $article) {
                $items[$article['aid']] = $article;
            }
            unset($articles);
            break;

        case 'dynamicdata':
            $items = xarModAPIFunc('dynamicdata','user','getitems',
                                   array('modid' => $from['module'],
                                         'itemtype' => $from['itemtype'],
                                         'itemids' => $itemids));
            if (!isset($items)) return;
            break;

        default:
            break;
    }

    if (empty($items)) {
        // we're done here
        return true;
    }

    // get the list of fields for this module+itemtype
    $fields = xarModAPIFunc($moduleto,'user','getitemfields',
                            array('itemtype' => $to['itemtype']),
                            0);
    if (empty($fields)) {
        // we're done here
        return true;
    }
    $fieldlist = array_keys($fields);

    $newitemids = array();
    switch ($moduleto)
    {
        case 'articles':
            if (!empty($to['itemid']) || $modulefrom == 'articles') { // no copy within articles atm
                foreach ($items as $itemid => $item) {
                    $article = array('aid' => $itemid);
                    if ($from['itemtype'] != $to['itemtype']) {
                        $article['ptid'] = $to['itemtype'];
                    }
                    foreach ($fieldmap as $fromfield => $tofield) {
                        if (empty($fromfield) || empty($tofield)) continue;
                        if (!isset($item[$fromfield])) continue;
                        // we only need to pass updated fields to the articles update function
                        if ($fromfield == $tofield) continue;
                        // Note: this will also set any DD fields for the update hooks
                        $article[$tofield] = $item[$fromfield];
                    }
                    if (count($article) > 1) {
//                        if (!xarModAPIFunc('articles','admin','update',$article)) return;
                    }
                }
            } else {
            }
            break;

        case 'dynamicdata':
            break;

        default:
            break;
    }


    return true;
}
?>
