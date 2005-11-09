<?php
/**
 * Migrate module items
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
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

// TODO: find some easier way to handle migration to/from other modules

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

        case 'xarbb':
            $topics = xarModAPIFunc('xarbb','user','getalltopics',
                                   array('tids' => $itemids));
            if (!isset($topics)) return;
            // re-assign by itemid
            foreach ($topics as $topic) {
                $items[$topic['tid']] = $topic;
            }
            unset($topics);
            // Note: although xarbb is normally not hooked to comments,
            // we'll want to move the original replies for the topic too
            if ($moduleto != 'xarbb') {
                $hookmap['comments'] = 'comments';
            }
            break;

        case 'xarpages':
            $items = xarModAPIFunc('xarpages','user','getpages',
                                   array('itemtype' => $from['itemtype'],
                                         'pids'     => $itemids,
                                         'key'      => 'pid',
                                         'dd_flag'  => false));
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
                        // Note: this will also set any DD fields for the create hooks
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

        case 'xarbb':
            if ($modulefrom == 'xarbb') { // only allow updates within xarbb atm, not copies
                $sameid = true;
                foreach ($items as $itemid => $item) {
                    $topic = array('tid' => $itemid);
                    if ($from['itemtype'] != $to['itemtype']) {
                        $topic['fid'] = $to['itemtype'];
                    }
                    foreach ($fieldmap as $fromfield => $tofield) {
                        if (empty($fromfield) || empty($tofield)) continue;
                        if (!isset($item[$fromfield])) continue;
                        // we only need to pass updated fields to the xarbb updatetopic function
                        if ($fromfield == $tofield) continue;
                        // Note: this will also set any DD fields for the update hooks
                        $topic[$tofield] = $item[$fromfield];
                    }
                    if (count($topic) < 2) {
                        continue;
                    }
                    // fix inconsistency in field names between get/create and update
                    if (isset($topic['ttime'])) {
                        $topic['time'] = $topic['ttime'];
                    }
                    if (empty($debug)) {
                        if (!xarModAPIFunc('xarbb','user','updatetopic',$topic)) return;
                    } else {
                        $debug .= xarML('Updating topic #(1) :', $itemid);
                        $debug .= "\n";
                        foreach ($topic as $field => $value) {
                            $debug .= "$field = $value\n";
                        }
                    }
                    // Note: updatetopic will also remap comments if necessary,
                    // so we don't need to do it twice (in case comments was hooked)
                    if (isset($hookmap['comments'])) {
                        unset($hookmap['comments']);
                    }
                    $newitemids[$itemid] = $itemid;
                }
            } else {
                foreach ($items as $itemid => $item) {
                    $topic = array();
                    $topic['fid'] = $to['itemtype'];
                    foreach ($fieldmap as $fromfield => $tofield) {
                        if (empty($fromfield) || empty($tofield)) continue;
                        if (!isset($item[$fromfield])) continue;
                        // Note: this will also set any DD fields for the create hooks
                        $topic[$tofield] = $item[$fromfield];
                    }
                    if (count($article) < 2) {
                        continue;
                    }
                    if (!empty($to['itemid'])) {
                        $topic['tid'] = $itemid; // this may give us trouble with create hooks
                    }
                    if (empty($debug)) {
                        $newid = xarModAPIFunc('xarbb','user','createtopic',$topic);
                        if (empty($newid)) return;
                    } else {
                        $newid = -$itemid; // simulate some new itemid :-)
                        $debug .= xarML('Creating topic #(1) :', $newid);
                        $debug .= "\n";
                        foreach ($topic as $field => $value) {
                            $debug .= "$field = $value\n";
                        }
                    }
                    // Note: although xarbb is normally not hooked to comments,
                    // we'll want to move the original comments to the topic too
                    if (isset($hookmap['comments'])) {
                        $hookmap['comments'] = 'comments';
                    }
                    // Note: xarbb topics are not assigned directly to categories
                    if (isset($hookmap['categories'])) {
                        unset($hookmap['categories']);
                    }
                    $newitemids[$itemid] = $newid;
                }
            }
            break;

        case 'xarpages':
            if ($modulefrom == 'xarpages') { // only allow updates within xarpages atm, not copies
                $sameid = true;
                foreach ($items as $itemid => $item) {
                    $page = array('pid' => $itemid);
                    if ($from['itemtype'] != $to['itemtype']) {
// FIXME: changing itemtype is not supported by xarpages updatepage yet !
                        $page['itemtype'] = $to['itemtype'];
                    }
                    foreach ($fieldmap as $fromfield => $tofield) {
                        if (empty($fromfield) || empty($tofield)) continue;
                        if (!isset($item[$fromfield])) continue;
                        // we only need to pass updated fields to the xarpages updatepage function
                        if ($fromfield == $tofield) continue;
                        // Note: this will also set any DD fields for the update hooks
                        $page[$tofield] = $item[$fromfield];
                    }
                    if (count($page) < 2) {
                        continue;
                    }
                    if (empty($debug)) {
                        if (!xarModAPIFunc('xarpages','admin','updatepage',$page)) return;
                    } else {
                        $debug .= xarML('Updating page #(1) :', $itemid);
                        $debug .= "\n";
                        foreach ($page as $field => $value) {
                            $debug .= "$field = $value\n";
                        }
                    }
                    $newitemids[$itemid] = $itemid;
                }
            } else {
                foreach ($items as $itemid => $item) {
                    $page = array();
                    $page['itemtype'] = $to['itemtype'];
                    foreach ($fieldmap as $fromfield => $tofield) {
                        if (empty($fromfield) || empty($tofield)) continue;
                        if (!isset($item[$fromfield])) continue;
                        // Note: this will also set any DD fields for the create hooks
                        $page[$tofield] = $item[$fromfield];
                    }
                    if (count($page) < 2) {
                        continue;
                    }
                    if (!empty($to['itemid'])) {
// FIXME: specifying pid is not supported by xarpages createpage yet !
                        $page['pid'] = $itemid; // this may give us trouble with create hooks
                    }
                    if (empty($debug)) {
                        $newid = xarModAPIFunc('xarpages','admin','createpage',$page);
                        if (empty($newid)) return;
                    } else {
                        $newid = -$itemid; // simulate some new itemid :-)
                        $debug .= xarML('Creating page #(1) :', $newid);
                        $debug .= "\n";
                        foreach ($page as $field => $value) {
                            $debug .= "$field = $value\n";
                        }
                    }
                    $newitemids[$itemid] = $newid;
                }
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
        if ($from['module'] == $to['module'] && $newid == $itemid &&
            ($moduleto == 'articles' || $moduleto == 'xarbb' || $moduleto == 'xarpages')) {
            // don't delete articles or topics when moving itemtypes
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

            case 'xarbb':
                if (empty($debug)) {
                    if (!xarModAPIFunc('xarbb','admin','deletetopics',
                                       array('tid'  => $itemid))) {
                        return;
                    }
                } else {
                    $debug .= xarML('Deleting topic #(1) from forum #(2)',
                                    $itemid, $from['itemtype']);
                    $debug .= "\n";
                }
                break;

            default:
                break;
        }
    }

    if ($modulefrom == 'xarbb') {
        if (empty($debug)) {
            // re-sync original forum
            if (!xarModAPIFunc('xarbb','admin','sync',
                               array('fid' => $from['itemtype']))) {
                return;
            }
        } else {
            $debug .= xarML('Re-synchronizing forum #(1)',
                            $from['itemtype']);
            $debug .= "\n";
        }
    }
    if ($moduleto == 'xarbb' && ($modulefrom != 'xarbb' || $from['itemtype'] != $to['itemtype'])) {
        if (empty($debug)) {
            foreach ($newitemids as $itemid => $newid) {
                if (empty($itemid) || empty($newid)) continue;
                if (!xarModAPIFunc('xarbb','user','updatetopicsview',
                                   array('tid' => $newid))) {
                    return;
                }
            }
            // re-sync new forum
            if (!xarModAPIFunc('xarbb','admin','sync',
                               array('fid' => $to['itemtype']))) {
                return;
            }
        } else {
            $itemlist = array();
            foreach ($newitemids as $itemid => $newid) {
                if (empty($itemid) || empty($newid)) continue;
                $itemlist[] = $newid;
            }
            $debug .= xarML('Updating topic view for items #(1)',
                            join(',',$itemlist));
            $debug .= "\n";
            $debug .= xarML('Re-synchronizing forum #(1)',
                            $to['itemtype']);
            $debug .= "\n";
        }
    }

    if (!empty($debug)) {
        return $debug;
    } else {
        return true;
    }
}
?>
