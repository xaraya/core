<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\UserGui;
use Xaraya\Modules\Categories\UserApi;
use Exception;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories user main function
 * @extends MethodClass<UserGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main user function
     * @return array|void Returns display data array
     * @see UserGui::main()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $data = [];

        $this->var()->check('catid', $catid);
        if (empty($catid) || !is_numeric($catid)) {
            // for DMOZ-like URLs
            // $this->mod()->setVar('enable_short_urls',1);
            // replace with DMOZ top cid
            $catid = 0;
        }

        $parents = $userapi->getparents(['cid' => $catid]);
        $data['parents'] = [];
        $data['hooks'] = '';
        $path = '';
        $title = '';
        if (count($parents) > 0) {
            foreach ($parents as $id => $info) {
                $path .= rawurlencode($info['name']);
                $info['name'] = preg_replace('/_/', ' ', $info['name']);
                $title .= $info['name'];
                if ($id == $catid) {
                    $info['module'] = 'categories';
                    $info['itemtype'] = 0;
                    $info['itemid'] = $catid;
                    $info['returnurl'] = $this->ctl()->getModuleURL('categories', 'user', 'main', ['catid' => $catid, 'path' => $path]);
                    $hooks = $this->mod()->callHooks('item', 'display', $catid, $info);
                    if (!empty($hooks) && is_array($hooks)) {
                        // TODO: do something specific with pubsub, hitcount, comments etc.
                        $data['hooks'] = join('', $hooks);
                    }
                    $data['parents'][] = ['catid' => $catid, 'name' => $info['name'], 'link' => '', 'path' => $path];
                } else {
                    $link = $this->ctl()->getModuleURL('categories', 'user', 'main', ['catid' => $id, 'path' => $path]);
                    $data['parents'][] = ['catid' => $info['cid'], 'name' => $info['name'], 'link' => $link, 'path' => $path];
                    $title .= ' > ';
                    $path .= '/';
                }
            }
        }
        if (!empty($path)) {
            $path .= '/';
        }

        // set the page title to the current category
        if (!empty($title)) {
            $this->tpl()->setPageTitle($this->var()->prep($title));
        }

        $children = $userapi->getchildren(['cid' => $catid]);
        $category = [];
        $letter = [];
        foreach ($children as $id => $info) {
            if (strlen($info['name']) == 1) {
                $letter[$id] = $info['name'];
            } else {
                $category[$id] = $info['name'];
            }
        }

        /* test only - requires *_categories_symlinks table for symbolic links :
            $xartable = $this->db()->getTables();
            if (empty($xartable['categories_symlinks'])) {
                $xartable['categories_symlinks'] = $this->db()->getPrefix() . '_categories_symlinks';
            }
            // created by DMOZ import script
        //    $query = "CREATE TABLE $xartable[categories_symlinks] (
        //              id int(11) NOT NULL default 0,
        //              name varchar(64) NOT NULL,
        //              parent_id int(11) NOT NULL default 0,
        //              PRIMARY KEY (parent_id, id)
        //              )";

            // Symbolic links
            $dbconn = $this->db()->getConn();

            $query = "SELECT id, name FROM $xartable[categories_symlinks] WHERE parent_id = '$catid'";
            $result = $dbconn->Execute($query);
            if (!$result) return;
            while ($result->next()) {
                list($id,$name) = $result->fields;
                $category[$id] = $name . '@';
            }

            $result->Close();
        */

        $data['letters'] = [];
        if (count($letter) > 0) {
            asort($letter);
            reset($letter);
            foreach ($letter as $id => $name) {
                $here = $path . rawurlencode($name);
                $link = $this->ctl()->getModuleURL('categories', 'user', 'main', ['catid' => $id, 'path' => $here]);
                $data['letters'][] = ['catid' => $id, 'name' => $name, 'link' => $link, 'path' => $here];
            }
        }
        $data['categories'] = [];
        if (count($category) > 0) {
            asort($category);
            reset($category);
            foreach ($category as $id => $name) {
                $here = $path . rawurlencode($name);
                $name = preg_replace('/_/', ' ', $name);
                $link = $this->ctl()->getModuleURL('categories', 'user', 'main', ['catid' => $id, 'path' => $here]);
                $data['categories'][] = ['catid' => $id, 'name' => $name, 'link' => $link, 'path' => $here];
            }
        }

        $data['moditems'] = [];
        if (empty($catid)) {
            return $data;
        }

        $modlist = $userapi->getmodules(['cid' => $catid]);
        if (count($modlist) > 0) {
            foreach ($modlist as $modid => $itemtypes) {
                $modinfo = $this->mod()->getInfo($modid);
                // Get the list of all item types for this module (if any)
                try {
                    $mytypes = $this->mod()->apiFunc($modinfo['name'], 'user', 'getitemtypes');
                } catch (Exception $e) {
                    $mytypes = [];
                }
                foreach ($itemtypes as $itemtype => $stats) {
                    $moditem = [];
                    if ($itemtype == 0) {
                        $moditem['name'] = ucwords($modinfo['displayname']);
                        $moditem['link'] = $this->ctl()->getModuleURL($modinfo['name'], 'user', 'main');
                    } else {
                        if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                            $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype . ' - ' . $mytypes[$itemtype]['label'];
                            $moditem['link'] = $mytypes[$itemtype]['url'];
                        } else {
                            $moditem['name'] = ucwords($modinfo['displayname']) . ' ' . $itemtype;
                            $moditem['link'] = $this->ctl()->getModuleURL($modinfo['name'], 'user', 'view', ['itemtype' => $itemtype]);
                        }
                    }
                    $moditem['numitems'] = $stats['items'];
                    $moditem['numcats'] = $stats['cats'];
                    $moditem['numlinks'] = $stats['links'];

                    $links = $userapi->getlinks(['modid' => $modid,
                        'itemtype' => $itemtype,
                        'cids' => [$catid]]);
                    $moditem['items'] = [];
                    if (!empty($links[$catid])) {
                        try {
                            $itemlinks = $this->mod()->apiFunc(
                                $modinfo['name'],
                                'user',
                                'getitemlinks',
                                ['itemtype' => $itemtype,
                                    'itemids' => $links[$catid]]
                            );
                        } catch (Exception $e) {
                            $itemlinks = [];
                        }
                        if (!empty($itemlinks)) {
                            $moditem['items'] = $itemlinks;
                        } else {
                            // we're dealing with unknown items - skip this if you prefer
                            foreach ($links[$catid] as $iid) {
                                $moditem['items'][$iid] = ['url'   => $this->ctl()->getModuleURL(
                                    $modinfo['name'],
                                    'user',
                                    'display',
                                    ['objectid' => $iid]
                                ),
                                    'title' => $this->ml('Display Item'),
                                    'label' => $this->ml('item #(1)', $iid)];
                            }
                        }
                    }
                    $data['moditems'][] = $moditem;
                }
            }
        }
        return $data;
    }
}
