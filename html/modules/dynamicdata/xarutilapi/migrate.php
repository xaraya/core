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
 * @param $args['from'] the module id, itemtype and itemid(s) for the original item
 * @param $args['to'] the module id, itemtype and itemid preserve flag for the new item
 * @param $args['fieldmap'] the field mapping
 * @param $args['hookmap'] the hook mapping
 * @param $args['debug'] don't actually update anything :-)
 * @returns mixed
 * @return true or debug string on success, null on failure
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

    if (!empty($debug)) {
        //echo "Arguments :\n";
        //echo var_dump($args);
        //echo "Items :\n";
        //echo var_dump($items);
        //echo "Fields :\n";
        //echo var_dump($fields);
    }

    $sameid = false;
    $newitemids = array();
    switch ($moduleto)
    {
        case 'articles':
            if ($modulefrom == 'articles') { // only allow updates within articles atm, not copies
                $sameid = true;
                foreach ($items as $itemid => $item) {
                    $article = array('aid' => $itemid);
                    if ($from['itemtype'] != $to['itemtype']) {
                        $article['ptid'] = $to['itemtype'];
                    }
                    foreach ($fieldmap as $fromfield => $tofield) {
                        if (empty($fromfield) || empty($tofield)) continue;
                        if (!isset($item[$fromfield])) continue;
                        // we only need to pass title + updated fields to the articles update function
                        if ($fromfield == $tofield && $tofield != 'title') continue;
                        // Note: this will also set any DD fields for the update hooks
                        $article[$tofield] = $item[$fromfield];
                    }
                    if (count($article) < 2) {
                        continue;
                    }
                    if (empty($debug)) {
                        if (!xarModAPIFunc('articles','admin','update',$article)) return;
                    } else {
                        $debug .= xarML('Updating article #(1) :', $itemid);
                        $debug .= "\n";
                        foreach ($article as $field => $value) {
                            $debug .= "$field = $value\n";
                        }
                    }
                    $newitemids[$itemid] = $itemid;
                }
            } else {
                foreach ($items as $itemid => $item) {
                    $article = array();
                    $article['ptid'] = $to['itemtype'];
                    foreach ($fieldmap as $fromfield => $tofield) {
                        if (empty($fromfield) || empty($tofield)) continue;
                        if (!isset($item[$fromfield])) continue;
                        // Note: this will also set any DD fields for the update hooks
                        $article[$tofield] = $item[$fromfield];
                    }
                    if (count($article) < 2) {
                        continue;
                    }
                    if (!empty($to['itemid'])) {
                        $article['aid'] = $itemid; // this may give us trouble with create hooks
                    }
                    if (empty($debug)) {
                        $newid = xarModAPIFunc('articles','admin','create',$article);
                        if (empty($newid)) return;
                    } else {
                        $newid = -$itemid; // simulate some new itemid :-)
                        $debug .= xarML('Creating article #(1) :', $newid);
                        $debug .= "\n";
                        foreach ($article as $field => $value) {
                            $debug .= "$field = $value\n";
                        }
                    }
                    $newitemids[$itemid] = $newid;
                }
            }
            break;

        case 'dynamicdata':
            foreach ($items as $itemid => $item) {
                $values = array();
                foreach ($fieldmap as $fromfield => $tofield) {
                    if (empty($fromfield) || empty($tofield)) continue;
                    if (!isset($item[$fromfield])) continue;
                    // Note: this will also set any DD fields for the update hooks
                    $values[$tofield] = $item[$fromfield];
                }
                if (empty($values)) {
                    continue;
                }
                if (empty($debug)) {
                    $newid = xarModAPIFunc('dynamicdata','admin','create',
                                           array('modid'    => $to['module'],
                                                 'itemtype' => $to['itemtype'],
                                                 // try to preset the itemid if necessary
                                                 'itemid'   => !empty($to['itemid']) ? $itemid : 0,
                                                 'values'   => $values));
                } else {
                    $newid = -$itemid; // simulate some new itemid :-)
                    $debug .= xarML('Creating DD item #(1) :', $newid);
                    $debug .= "\n";
                    foreach ($values as $field => $value) {
                        $debug .= "$field = $value\n";
                    }
                }
                $newitemids[$itemid] = $newid;
            }
            break;

        default:
            break;
    }

    // update hook modules
    $result = xarModAPIFunc('dynamicdata','util','updatehooks',
                            array('from'    => $from,
                                  'to'      => $to,
                                  'hookmap' => $hookmap,
                                  'itemids' => $newitemids,
                                  'debug'   => empty($debug) ? '' : $debug));
    if (!$result) {
         return;
    }
    if (!empty($debug)) {
        $debug = $result;
    }

    // delete old items now
    foreach ($newitemids as $itemid => $newid) {
        if (empty($itemid) || empty($newid)) continue;
        if ($from['module'] == $to['module'] && $newid == $itemid && $moduleto == 'articles') {
            // don't delete articles when moving itemtypes
            continue;
        } elseif ($from['module'] == $to['module'] && $from['itemtype'] == $to['itemtype'] && $newid == $itemid) {
            // don't delete identical items either
            continue;
        }
        // TODO: check itemtype difference for non-articles et al. ?
        switch ($modulefrom)
        {
            case 'articles':
                if (empty($debug)) {
                    if (!xarModAPIFunc('articles','admin','delete',
                                       array('ptid' => $from['itemtype'],
                                             'aid'  => $itemid))) {
                        return;
                    }
                } else {
                    $debug .= xarML('Deleting article #(1) from pubtype #(2)',
                                    $itemid, $from['itemtype']);
                    $debug .= "\n";
                }
                break;

            case 'dynamicdata':
                if (empty($debug)) {
                    if (!xarModAPIFunc('dynamicdata','admin','delete',
                                       array('modid'    => $from['module'],
                                             'itemtype' => $from['itemtype'],
                                             'itemid'   => $itemid))) {
                        return;
                    }
                } else {
                    $debug .= xarML('Deleting DD item #(1) from module #(2) itemtype #(3)',
                                    $itemid, $from['module'], $from['itemtype']);
                    $debug .= "\n";
                }
                break;

            default:
                break;
        }
    }

    if (!empty($debug)) {
        return $debug;
    } else {
        return true;
    }
}
?>
