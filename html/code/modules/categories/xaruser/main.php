<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * The main user function
 * 
 * @param void N/A
 * @return array Returns display data array
 */
function categories_user_main()
{
    $data = array();

    $out = '';
    if (!xarVarFetch('catid', 'isset', $catid, NULL, XARVAR_DONT_SET)) return;
    if (empty($catid) || !is_numeric($catid)) {
        // for DMOZ-like URLs
        // xarModVars::set('categories','enable_short_urls',1);
        // replace with DMOZ top cid
        $catid = 0;
    }

    if (!xarModAPILoad('categories','user')) return;

    $parents = xarMod::apiFunc('categories','user','getparents',
                            array('cid' => $catid));
    $data['parents'] = array();
    $data['hooks'] = '';
    $title = '';
    if (count($parents) > 0) {
        foreach ($parents as $id => $info) {
            $info['name'] = preg_replace('/_/',' ',$info['name']);
            $title .= $info['name'];
            if ($id == $catid) {
                $info['module'] = 'categories';
                $info['itemtype'] = 0;
                $info['itemid'] = $catid;
                $info['returnurl'] = xarModUrl('categories', 'user', 'main', array('catid' => $catid));
                $hooks = xarModCallHooks('item','display',$catid,$info);
                if (!empty($hooks) && is_array($hooks)) {
                // TODO: do something specific with pubsub, hitcount, comments etc.
                    $data['hooks'] = join('',$hooks);
                }
                $data['parents'][] = array('catid' => $catid, 'name' => $info['name'], 'link' => '');
            } else {
                $link = xarModURL('categories','user','main',array('catid' => $id));
                $data['parents'][] = array('catid' => $info['cid'], 'name' => $info['name'], 'link' => $link);
                $title .= ' > ';
            }
        }
    }

    // set the page title to the current category
    if (!empty($title)) {
        xarTplSetPageTitle(xarVarPrepForDisplay($title));
    }

    $children = xarMod::apiFunc('categories','user','getchildren',
                              array('cid' => $catid));
    $category = array();
    $letter = array();
    foreach ($children as $id => $info) {
        if (strlen($info['name']) == 1) {
            $letter[$id] = $info['name'];
        } else {
            $category[$id] = $info['name'];
        }
    }

/* test only - requires *_categories_symlinks table for symbolic links :
    $xartable =& xarDB::getTables();
    if (empty($xartable['categories_symlinks'])) {
        $xartable['categories_symlinks'] = xarDB::getPrefix() . '_categories_symlinks';
    }
    // created by DMOZ import script
//    $query = "CREATE TABLE $xartable[categories_symlinks] (
//              id int(11) NOT NULL default 0,
//              name varchar(64) NOT NULL,
//              parent_id int(11) NOT NULL default 0,
//              PRIMARY KEY (parent_id, id)
//              )";

    // Symbolic links
    $dbconn = xarDB::getConn();

    $query = "SELECT id, name FROM $xartable[categories_symlinks] WHERE parent_id = '$catid'";
    $result = $dbconn->Execute($query);
    if (!$result) return;
    for (; !$result->EOF; $result->MoveNext()) {
        list($id,$name) = $result->fields;
        $category[$id] = $name . '@';
        }

    $result->Close();
*/

    $data['letters'] = array();
    if (count($letter) > 0) {
        asort($letter);
        reset($letter);
        foreach ($letter as $id => $name) {
            $link = xarModURL('categories','user','main',array('catid' => $id));
            $data['letters'][] = array('catid' => $id, 'name' => $name, 'link' => $link);
        }
    }
    $data['categories'] = array();
    if (count($category) > 0) {
        asort($category);
        reset($category);
        foreach ($category as $id => $name) {
            $name = preg_replace('/_/',' ',$name);
            $link = xarModURL('categories','user','main',array('catid' => $id));
            $data['categories'][] = array('catid' => $id, 'name' => $name, 'link' => $link);
        }
    }

    $data['moditems'] = array();
    if (empty($catid)) {
        return $data;
    }

    $modlist = xarMod::apiFunc('categories','user','getmodules',
                             array('cid' => $catid));
    if (count($modlist) > 0) {
        foreach ($modlist as $modid => $itemtypes) {
            $modinfo = xarMod::getInfo($modid);
            // Get the list of all item types for this module (if any)
            $mytypes = xarMod::apiFunc($modinfo['name'],'user','getitemtypes',
                                     // don't throw an exception if this function doesn't exist
                                     array(), 0);
            foreach ($itemtypes as $itemtype => $stats) {
                $moditem = array();
                if ($itemtype == 0) {
                    $moditem['name'] = ucwords($modinfo['displayname']);
                    $moditem['link'] = xarModURL($modinfo['name'],'user','main');
                } else {
                    if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                        $moditem['link'] = $mytypes[$itemtype]['url'];
                    } else {
                        $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                        $moditem['link'] = xarModURL($modinfo['name'],'user','view',array('itemtype' => $itemtype));
                    }
                }
                $moditem['numitems'] = $stats['items'];
                $moditem['numcats'] = $stats['cats'];
                $moditem['numlinks'] = $stats['links'];

                $links = xarMod::apiFunc('categories','user','getlinks',
                                       array('modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'cids' => array($catid)));
                $moditem['items'] = array();
                if (!empty($links[$catid])) {
                    $itemlinks = xarMod::apiFunc($modinfo['name'],'user','getitemlinks',
                                               array('itemtype' => $itemtype,
                                                     'itemids' => $links[$catid]),
                                               // don't throw an exception if this function doesn't exist
                                               0);
                    if (!empty($itemlinks)) {
                        $moditem['items'] = $itemlinks;
                    } else {
                    // we're dealing with unknown items - skip this if you prefer
                        foreach ($links[$catid] as $iid) {
                            $moditem['items'][$iid] = array('url'   => xarModURL($modinfo['name'],'user','display',
                                                                                 array('objectid' => $iid)),
                                                            'title' => xarML('Display Item'),
                                                            'label' => xarML('item #(1)', $iid));
                        }
                    }
                }
                $data['moditems'][] = $moditem;
            }
        }
    }
    return $data;
}

?>
