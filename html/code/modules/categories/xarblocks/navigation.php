<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * initialise block
 */
sys::import('xaraya.structures.containers.blocks.basicblock');

class Categories_NavigationBlock extends BasicBlock implements iBlock
{
    // File Information, supplied by developer, never changes during a versions lifetime, required
    protected $type                = 'navigation';
    protected $module              = 'categories';
    protected $text_type           = 'Show navigation';
    protected $text_type_long      = 'Show navigation';
    // Additional info, supplied by developer, optional 
    protected $type_category       = 'block'; // options [(block)|group] 
    protected $author              = 'Jim McDonald';

    // blocks subsystem flags
    protected $show_preview = true;  // let the subsystem know if it's ok to show a preview
    protected $show_help    = false; // let the subsystem know if this block type supplies help info

    public $layout              = false;
    public $showcatcount        = false;
    public $showempty           = false;
    public $startmodule         = '';
    public $dynamictitle        = false;

    /**
     * Display block
     * 
     * @param void N/A
     */
    function display()
    {
        $vars = $this->getContent();

        extract($vars);

        // Get requested layout
        if (empty($layout)) $layout = $this->layout; // default tree here

        if (!empty($startmodule)) {
            // static behaviour
            list($module,$itemtype,$rootcid) = explode('.',$startmodule);
            if (empty($rootcid)) {
                $rootcids = null;
            } elseif (strpos($rootcid,' ')) {
                $rootcids = explode(' ',$rootcid);
            } elseif (strpos($rootcid,'+')) {
                $rootcids = explode('+',$rootcid);
            } else {
                $rootcids = explode('-',$rootcid);
            }
        }

    // TODO: for multi-module pages, we'll need some other reference point(s)
    //       (e.g. cross-module categories defined in categories admin ?)
        // Get current module
        if (empty($module)) {
            if (xarVarIsCached('Blocks.categories','module')) {
               $modname = xarVarGetCached('Blocks.categories','module');
            }
            if (empty($modname)) {
                $modname = xarModGetName();
            }
        } else {
            $modname = $module;
        }
        $modid = xarMod::getRegId($modname);
        if (empty($modid)) {
            return;
        }

        // Get current item type (if any)
        if (!isset($itemtype)) {
            if (xarVarIsCached('Blocks.categories','itemtype')) {
                $itemtype = xarVarGetCached('Blocks.categories','itemtype');
            } else {
                // try to get itemtype from input
                xarVarFetch('itemtype', 'id', $itemtype, NULL, XARVAR_DONT_SET);
            }
        }
        if (empty($itemtype)) {
            $itemtype = null;
        }

        // Get current item id (if any)
        if (!isset($itemid)) {
            if (xarVarIsCached('Blocks.categories','itemid')) {
                $itemid = xarVarGetCached('Blocks.categories','itemid');
            } else {
                // try to get itemid from input
                xarVarFetch('itemid', 'id', $itemid, NULL, XARVAR_DONT_SET);
            }
        }
        if (empty($itemid)) {
            $itemid = null;
        }

        if (isset($rootcids)) {
            $mastercids = $rootcids;
        } else {
            // Get number of categories for this module + item type
            sys::import('modules.categories.class.worker');
            $worker = new CategoryWorker();
            $numcats = $worker->gettoplevelcount();

            if (empty($numcats)) {
                // no categories to show here -> return empty output
                return;
            }

            // Get master cids for this module + item type
            $toplevelcats = $worker->gettoplevel();

            if (empty($toplevelcats)) {
                // no categories to show here -> return empty output
                return;
            }
            $mastercids = array();
            foreach ($toplevelcats as $tlc) $mastercids[$tlc['id']] = (int)$tlc['id'];

            if (!empty($startmodule)) {
                $rootcids = $mastercids;
            }
        }

        // See if we need to show a count per category
        if (!isset($showcatcount)) {
            $showcatcount = 0;
        }

        // See if we need to show the children of current categories
        if (!isset($showchildren)) {
            $showchildren = 1;
        }

        // Get current category counts (optional array of cid => count)
        if (empty($showcatcount)) {
            $catcount = array();
        }
        if (empty($showempty) || !empty($showcatcount)) {
            // A 'deep count' sums the totals at each node with the totals of all descendants.
            if (xarVarIsCached('Blocks.categories', 'deepcount') && empty($startmodule)) {
                $deepcount = xarVarGetCached('Blocks.categories', 'deepcount');
            } else {
                $deepcount = xarMod::apiFunc(
                    'categories', 'user', 'deepcount',
                    array('modid' => $modid, 'itemtype' => $itemtype)
                );
                xarVarSetCached('Blocks.categories','deepcount', $deepcount);
            }
        }
        if (!empty($showcatcount)) {
            if (xarVarIsCached('Blocks.categories', 'catcount') && empty($startmodule)) {
                $catcount = xarVarGetCached('Blocks.categories', 'catcount');
            } else {
                // Get number of items per category (for this module).
                // If showcatcount == 2 then add in all descendants too.

                if ($showcatcount == 1) {
                    // We want to display only children category counts.
                    $catcount = xarMod::apiFunc(
                        'categories','user', 'groupcount',
                        array('modid' => $modid, 'itemtype' => $itemtype)
                    );
                } else {
                    // We want to display the deep counts.
                    $catcount =& $deepcount;
                }

                xarVarSetCached('Blocks.categories', 'catcount', $catcount);
            }
        }

        // Specify type=... & func = ... arguments for xarModURL()
        if (empty($type)) {
            if (xarVarIsCached('Blocks.categories','type')) {
                $type = xarVarGetCached('Blocks.categories','type');
            }
            if (empty($type)) {
                $type = 'user';
            }
        }
        if (empty($func)) {
            if (xarVarIsCached('Blocks.categories','func')) {
                $func = xarVarGetCached('Blocks.categories','func');
            }
            if (empty($func)) {
                $func = 'view';
            }
        }

        // Get current categories
        if (xarVarIsCached('Blocks.categories','catid')) {
           $catid = xarVarGetCached('Blocks.categories','catid');
        }
        if (empty($catid)) {
            // try to get catid from input
            xarVarFetch('catid', 'str', $catid, NULL, XARVAR_DONT_SET);
        }
        // turn $catid into $cids array (and set $andcids flag)
        $istree = 0;
        if (!empty($catid)) {
            // if we're viewing all items below a certain category, i.e. catid = _NN
            if (strstr($catid,'_')) {
                 $catid = preg_replace('/_/','',$catid);
                 $istree = 1;
            }
            if (strpos($catid,' ')) {
                $cids = explode(' ',$catid);
                $andcids = true;
            } elseif (strpos($catid,'+')) {
                $cids = explode('+',$catid);
                $andcids = true;
            } else {
                $cids = explode('-',$catid);
                $andcids = false;
            }
        } elseif (empty($cids)) {
            if (xarVarIsCached('Blocks.categories','cids')) {
                $cids = xarVarGetCached('Blocks.categories','cids');
            }
            if (xarVarIsCached('Blocks.categories','andcids')) {
                $andcids = xarVarGetCached('Blocks.categories','andcids');
            }
            if (empty($cids)) {
                // try to get cids from input
                xarVarFetch('cids',    'isset', $cids,    NULL,  XARVAR_DONT_SET);
                xarVarFetch('andcids', 'isset', $andcids, false, XARVAR_NOT_REQUIRED);

                if (empty($cids)) {
                    $cids = array();
                    if ((empty($module) || $module == $modname) && !empty($itemid)) {
                        $links = xarMod::apiFunc('categories','user','getlinks',
                                              array('modid' => $modid,
                                                    'itemtype' => $itemtype,
                                                    'iids' => array($itemid)));
                        if (!empty($links) && count($links) > 0) {
                            $cids = array_keys($links);
                        }
                    }
                }
            }
        }
        if (count($cids) > 0) {
            $seencid = array();
            foreach ($cids as $cid) {
                if (empty($cid) || ! is_numeric($cid)) {
                    continue;
                }
                $seencid[$cid] = 1;
            }
            $cids = array_keys($seencid);
        }

        $data = array();
        $data['cids'] = $cids;
        // pass information about current module, item type and item id (if any) to template
        $data['module'] = $modname;
        $data['itemtype'] = $itemtype;
        $data['itemid'] = $itemid;
        // pass information about current function to template
        $data['type'] = $type;
        $data['func'] = $func;

        // Generate output
        switch ($layout) {

            case 3: // prev/next category
                $template = 'prevnext';
                if (empty($cids) || count($cids) != 1 || in_array($cids[0], $mastercids)) {
                    // nothing to show here
                    return;
                } else {
                    // See if we need to show anything
                    if (empty($showprevnext)) {
                        if (xarVarIsCached('Blocks.categories','showprevnext')) {
                            $showprevnext = xarVarGetCached('Blocks.categories','showprevnext');
                            if (empty($showprevnext)) {
                                return;
                            }
                        }
                    }
                    $cat = xarMod::apiFunc('categories','user','getcatinfo',
                                    array('cid' => $cids[0]));
                    if (empty($cat)) {
                        return;
                    }
                    $neighbours = xarMod::apiFunc('categories','user','getneighbours',
                                               $cat);
                    if (empty($neighbours) || count($neighbours) == 0) {
                        return;
                    }
                    foreach ($neighbours as $neighbour) {
    //                    if ($neighbour['link'] == 'parent') {
    //                        $data['uplabel'] = $neighbour['name'];
    //                        $data['upcid'] = $neighbour['cid'];
    //                        $data['uplink'] = xarModURL($modname,$type,$func,
    //                                                   array('itemtype' => $itemtype,
    //                                                         'catid' => $neighbour['cid']));
    //                    } elseif ($neighbour['link'] == 'previous') {
                        if ($neighbour['link'] == 'previous') {
                            $data['prevlabel'] = $neighbour['name'];
                            $data['prevcid'] = $neighbour['cid'];
                            $data['prevlink'] = xarModURL($modname,$type,$func,
                                                         array('itemtype' => $itemtype,
                                                               'catid' => $neighbour['cid']));
                        } elseif ($neighbour['link'] == 'next') {
                            $data['nextlabel'] = $neighbour['name'];
                            $data['nextcid'] = $neighbour['cid'];
                            $data['nextlink'] = xarModURL($modname,$type,$func,
                                                         array('itemtype' => $itemtype,
                                                               'catid' => $neighbour['cid']));
                        }
                    }
                    if (!isset($data['nextlabel']) &&
                        !isset($data['prevlabel'])) {
                        return;
                    }
    //                if (!isset($data['uplabel'])) {
    //                    $data['uplabel'] = '&#160;';
    //                }
                }
                break;

            case 2: // crumbtrails
                $template = 'trails';
                if (empty($cids) || count($cids) == 0) {
                    $template = 'rootcats';
                    $data['cattitle'] = xarML('Browse in');
                    $data['catitems'] = array();

                    // Get root categories
                    $catlist = xarMod::apiFunc('categories','user','getcatinfo',
                                            array('cids' => $mastercids));
                    $join = '';
                    if (empty($catlist) || !is_array($catlist)) {
                        return;
                    }
                    foreach ($catlist as $cat) {
                    // TODO: now this is a tricky part...
                        $link = xarModURL($modname,$type,$func,
                                         array('itemtype' => $itemtype,
                                               'catid' => $cat['id']));
                        $label = xarVarPrepForDisplay($cat['name']);
                        $data['catitems'][] = array('catlabel' => $label,
                                                    'catid' => $cat['id'],
                                                    'catlink' => $link,
                                                    'catjoin' => $join);
                        $join = ' | ';
                    }
                } else {
                    $template = 'trails';
                    if (!empty($andcids)) {
                        $data['cattitle'] = xarML('Browse in');
                    } else {
                        $data['cattitle'] = xarML('Browse in');
                    }
                    $data['cattrails'] = array();

                    $descriptions = array();
        // TODO: stop at root categories
                    foreach ($cids as $cid) {
                        // Get category information
                        $parents = xarMod::apiFunc('categories','user','getparents',
                                                array('cid' => $cid));
                        if (empty($parents)) {
                            continue;
                        }
                        $catitems = array();
                        $curcount = 0;
                    // TODO: now this is a tricky part...
                        $label = xarML('All');
                        $link = xarModURL($modname,$type,$func,
                                         array('itemtype' => $itemtype));
                        $join = '';
                        $catitems[] = array('catlabel' => $label,
                                            'catid' => $cid,
                                            'catlink' => $link,
                                            'catjoin' => $join);
                        $join = ' &gt; ';
                        foreach ($parents as $cat) {
                            $label = xarVarPrepForDisplay($cat['name']);
                            if ($cat['id'] == $cid && empty($itemid) && empty($andcids)) {
                                $link = '';
                            } else {
                            // TODO: now this is a tricky part...
                                $link = xarModURL($modname,$type,$func,
                                                 array('itemtype' => $itemtype,
                                                       'catid' => $cat['id']));
                            }
                            if ($cat['id'] == $cid) {
                                // show optional count
                                if (isset($catcount[$cat['id']])) {
                                    $curcount = $catcount[$cat['id']];
                                }
                                if (!empty($cat['description'])) {
                                    $descriptions[] = xarVarPrepHTMLDisplay($cat['description']);
                                } else {
                                    $descriptions[] = xarVarPrepForDisplay($cat['name']);
                                }
                                // save current category info for icon etc.
                                if (count($cids) == 1) {
                                    $curcat = $cat;
                                }
                            }
                            $catitems[] = array('catlabel' => $label,
                                                'catid' => $cat['id'],
                                                'catlink' => $link,
                                                'catjoin' => $join);
                        }
                        $data['cattrails'][] = array('catitems' => $catitems,
                                                     'catcount' => $curcount);
                    }

                    // Add filters to select on all categories or any categories
                    if (count($cids) > 1) {
                        $catitems = array();
                        if (!empty($itemid) || !empty($andcids)) {
                            $label = xarML('Any of these categories');
                            $link = xarModURL($modname,$type,$func,
                                              array('itemtype' => $itemtype,
                                                    'catid' => join('-',$cids)));
                            $join = '';
                            $catitems[] = array('catlabel' => $label,
                                                'catid' => join('-',$cids),
                                                'catlink' => $link,
                                                'catjoin' => $join);
                        }
                        if (empty($andcids)) {
                            $label = xarML('All of these categories');
                            $link = xarModURL($modname,$type,$func,
                                              array('itemtype' => $itemtype,
                                                    'catid' => join('+',$cids)));
                            if (!empty($itemid)) {
                                $join = '-';
                            } else {
                                $join = '';
                            }
                            $catitems[] = array('catlabel' => $label,
                                                'catid' => join('+',$cids),
                                                'catlink' => $link,
                                                'catjoin' => $join);
                        }
                        $curcount = 0;
                        $data['cattrails'][] = array('catitems' => $catitems,
                                                     'catcount' => $curcount);
                    }

                // TODO: move off to nav-trails template ?
                    // Build category description
                    if (!empty($itemid)) {
                        $data['catdescr'] = join(' + ', $descriptions);
                    } elseif (!empty($andcids)) {
                        $data['catdescr'] = join(' ' . xarML('and') . ' ', $descriptions);
                    } else {
                        $data['catdescr'] = join(' ' . xarML('or') . ' ', $descriptions);
                    }

                    if (count($cids) != 1) {
                        break;
                    }

                    if (!empty($curcat)) {
    /*
                        $curcat['module'] = 'categories';
                        $curcat['itemtype'] = 0;
                        $curcat['itemid'] = $cids[0];
                        $curcat['returnurl'] = xarModURL($modname,$type,$func,
                                                         array('itemtype' => $itemtype,
                                                               'catid' => $cids[0]));
                        // calling item display hooks *for the categories module* here !
                        $data['cathooks'] = xarModCallHooks('item','display',$cid,$curcat,'categories');
    */
                        // saving the current cat id for use e.g. with DD tags (<xar:data-display module="categories" itemid="$catid"/>)
                        $data['catid'] = $curcat['cid'];
                    }
    /*
                    // set the page title to the current module + category if no item is displayed
                    if (empty($itemid)) {
                        // Get current title
                        if (empty($title)) {
                            if (xarVarIsCached('Blocks.categories','title')) {
                                $title = xarVarGetCached('Blocks.categories','title');
                            }
                        }
                        if (!empty($curcat['name'])) {
                            $title = xarVarPrepForDisplay($curcat['name']);
                        }
                        xarTplSetPageTitle($title);
                    }
    */
                // TODO: don't show icons when displaying items ?
                    if (!empty($curcat['image'])) {
                        // find the image in categories (we need to specify the module here)
                        $data['catimage'] = xarTplGetImage($curcat['image'],'categories');
                        $data['catname'] = xarVarPrepForDisplay($curcat['name']);
                    }
                    if ($showchildren == 2) {
                        // Get child categories (all sub-levels)
                        $childlist = xarMod::apiFunc('categories','visual','listarray',
                                                  array('cid' => $cids[0]));
                        if (empty($childlist) || count($childlist) == 0) {
                            break;
                        }
                        foreach ($childlist as $info) {
                            if ($info['id'] == $cids[0]) {
                                continue;
                            }
                            $label = xarVarPrepForDisplay($info['name']);
                        // TODO: now this is a tricky part...
                            $link = xarModURL($modname,$type,$func,
                                             array('itemtype' => $itemtype,
                                                   'catid' => $info['id']));
                            if (!empty($catcount[$info['id']])) {
                                $count = $catcount[$info['id']];
                            } else {
                                $count = 0;
                            }
        /* don't show descriptions in (potentially) multi-level trees
                            if (!empty($info['description'])) {
                                $descr = xarVarPrepHTMLDisplay($info['description']);
                            } else {
                                $descr = '';
                            }
        */
                            $data['catlines'][] = array('catlabel' => $label,
                                                        'catid' => $info['id'],
                                                        'catlink' => $link,
                                                      //  'catdescr' => $descr,
                                                        'catdescr' => '',
                                                        'catcount' => $count,
                                                        'beforetags' => $info['beforetags'],
                                                        'aftertags' => $info['aftertags']);

                        }
                        unset($childlist);
                    } elseif ($showchildren == 1) {
                        // Get child categories (1 level only)
                        $children = xarMod::apiFunc('categories','user','getchildren',
                                                 array('cid' => $cids[0]));
                        if (empty($children) || count($children) == 0) {
                            break;
                        }
                        $data['catlines'] = array();
                    // TODO: don't show icons when displaying items ?
                        $data['caticons'] = array();
                        $numicons = 0;
                        foreach ($children as $cat) {
                        // TODO: now this is a tricky part...
                            $label = xarVarPrepForDisplay($cat['name']);
                            $link = xarModURL($modname,$type,$func,
                                             array('itemtype' => $itemtype,
                                                   'catid' => $cat['id']));
                            if (!empty($catcount[$cat['id']])) {
                                $count = $catcount[$cat['id']];
                            } else {
                                $count = 0;
                            }
                            if (!empty($cat['image'])) {
                                // find the image in categories (we need to specify the module here)
                                $image = xarTplGetImage($cat['image'],'categories');
                                $numicons++;
                                $data['caticons'][] = array('catlabel' => $label,
                                                            'catid' => $cat['id'],
                                                            'catlink' => $link,
                                                            'catimage' => $image,
                                                            'catcount' => $count,
                                                            'catnum' => $numicons);
                            } else {
                                if (!empty($cat['description']) && $cat['description'] != $cat['name']) {
                                    $descr = xarVarPrepHTMLDisplay($cat['description']);
                                } else {
                                    $descr = '';
                                }
                                $beforetags = '<li>';
                                $aftertags = '</li>';
                                $data['catlines'][] = array('catlabel' => $label,
                                                            'catid' => $cat['id'],
                                                            'catlink' => $link,
                                                            'catdescr' => $descr,
                                                            'catcount' => $count,
                                                            'beforetags' => $beforetags,
                                                            'aftertags' => $aftertags);
                            }
                        }
                        unset($children);
                        if (count($data['catlines']) > 0) {
                            $numitems = count($data['catlines']);
                            // add leading <ul> tag
                            $data['catlines'][0]['beforetags'] = '<ul>' .
                                                       $data['catlines'][0]['beforetags'];
                            // add trailing </ul> tag
                            $data['catlines'][$numitems - 1]['aftertags'] .= '</ul>';
                            // add new column
                            if ($numitems > 7) {
                                $miditem = round(($numitems + 0.5) / 2) - 1;
                                $data['catlines'][$miditem]['aftertags'] .=
                                                       '</ul></td><td valign="top"><ul>';
                            }
                        }
                    }
                }
                break;

            case 1: // tree
            default:

                $template = 'tree';
                // Get current title (if dynamic)
                if (!empty($dynamictitle)) {
                    if (empty($title) && empty($module)) {
                        if (xarVarIsCached('Blocks.categories','title')) {
                            $title = xarVarGetCached('Blocks.categories','title');
                        }
                    }
                    if (empty($title) && !empty($itemtype)) {
                        // Get the list of all item types for this module (if any)
                        $mytypes = xarMod::apiFunc($modname,'user','getitemtypes',
                                                 // don't throw an exception if this function doesn't exist
                                                 array(), 0);
                        if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                            $title = $mytypes[$itemtype]['label'];
                        }
                    }
                    if (empty($title)) {
                        $modinfo = xarMod::getInfo($modid);
                        $title = ucwords($modinfo['displayname']);
                    }
                    $blockinfo['title'] = xarML('Browse in #(1)', $title);
                }

                $data['cattrees'] = array();

                if (empty($cids) || count($cids) == 0) {
                    foreach ($mastercids as $cid) {
                        $catparents = array();
                        $catitems = array();
                        // Get child categories
                        $children = xarMod::apiFunc('categories','user','getchildren',
                                                 array('cid' => $cid,
                                                       'return_itself' => true));
                        foreach ($children as $cat) {
                            // TODO: now this is a tricky part...
                            if (!empty($catcount[$cat['id']])) {
                                $count = $catcount[$cat['id']];
                            } else {
                                $count = 0;

                                if (!empty($showempty) || !empty($deepcount[$cat['id']])) {
                                    // We are not hiding empty categories - set count to zero.
                                    $count = 0;
                                } else {
                                    // We want to hide empty categories - so skip this loop.
                                    continue;
                                }
                            }

                            $link = xarModURL($modname,$type,$func,
                                             array('itemtype' => $itemtype,
                                                   'catid' => $cat['id']));

                            $label = xarVarPrepForDisplay($cat['name']);
                            if ($cat['id'] == $cid) {
                                $catparents[] = array('catlabel' => $label,
                                                      'catid' => $cat['id'],
                                                      'catlink' => $link,
                                                      'catcount' => $count);
                            } else {
                                $catitems[] = array('catlabel' => $label,
                                                    'catid' => $cat['id'],
                                                    'catlink' => $link,
                                                    'catcount' => $count);
                            }
                        }
                        if (empty($catitems) && empty ($catparents)) continue;
                        $data['cattrees'][] = array('catitems' => $catitems,
                                                    'catparents' => $catparents);
                    }
                } elseif (isset($rootcids) && count($rootcids) > 0) {
                    foreach ($rootcids as $cid) {
                        $catparents = array();
                        $catitems = array();
                        // Get child categories
                        $children = xarMod::apiFunc('categories','user','getchildren',
                                                 array('cid' => $cid,
                                                       'return_itself' => true));
                        foreach ($children as $cat) {
                            if (!empty($catcount[$cat['id']])) {
                                $count = $catcount[$cat['id']];
                            } else {
                                $count = 0;

                                // Note: when hiding empty categories, check the deep count
                                // as a child category may be empty, but it could still have
                                // descendants with items.

                                if (!empty($showempty) || !empty($deepcount[$cat['id']])) {
                                    // We are not hiding empty categories - set count to zero.
                                    $count = 0;
                                } else {
                                    // We want to hide empty categories - so skip this loop.
                                    continue;
                                }
                            }

                            $label = xarVarPrepForDisplay($cat['name']);
                        // TODO: now this is a tricky part...
                            $link = xarModURL($modname,$type,$func,
                                             array('itemtype' => $itemtype,
                                                   'catid' => $cat['id']));

                            if ($cat['id'] == $cid) {
                                $catparents[] = array('catlabel' => $label,
                                                      'catid' => $cat['id'],
                                                      'catlink' => $link,
                                                      'catcount' => $count);
                            } elseif ($showchildren > 0) {
                                $catitems[] = array('catlabel' => $label,
                                                    'catid' => $cat['id'],
                                                    'catlink' => $link,
                                                    'catcount' => $count);
                            }
                        }
                        $data['cattrees'][] = array('catitems' => $catitems,
                                                    'catparents' => $catparents);
                    }
                } else {
                    foreach ($cids as $cid) {
                        $catparents = array();
                        $catitems = array();
                        // Get category information
                        $parents = xarMod::apiFunc('categories','user','getparents',
                                                array('cid' => $cid));
                        if (empty($parents)) {
                            continue;
                        }
                    // TODO: do something with parents
                        $root = '';
                        $parentid = 0;
                        foreach ($parents as $id => $info) {
                            if (empty($root)) {
                                $root = xarVarPrepForDisplay($info['name']);
                            }
                            if ($id == $cid) {
                                $parentid = $info['parent'];
                            }
                        }
                        // yes, this excludes the top-level categories too :-)
                        if (empty($parentid) || empty($root)) {
                            $parentid = $cid;
                    //        return;
                        }
                        if (!empty($parents[$parentid])) {
                            $cat = $parents[$parentid];
                            $label = xarVarPrepForDisplay($cat['name']);
                            $link = xarModURL($modname,$type,$func,
                                             array('itemtype' => $itemtype,
                                                   'catid' => $cat['id']));
                            if (!empty($catcount[$cat['id']])) {
                                $count = $catcount[$cat['id']];
                            } else {
                                $count = 0;
                            }
                            $catparents[] = array('catlabel' => $label,
                                                  'catid' => $cat['id'],
                                                  'catlink' => $link,
                                                  'catcount' => $count);
                        }

                        // Get sibling categories
                        $siblings = xarMod::apiFunc('categories','user','getchildren',
                                                 array('cid' => $parentid));
                        if ($showchildren && $parentid != $cid) {
                            // Get child categories
                            $children = xarMod::apiFunc('categories','user','getchildren',
                                                     array('cid' => $cid));
                        }

                        // Generate list of sibling categories
                        foreach ($siblings as $cat) {
                            if (!empty($catcount[$cat['id']])) {
                                $count = $catcount[$cat['id']];
                            } else {
                                $count = 0;

                                // Note: when hiding empty categories, check the deep count
                                // as a child category may be empty, but it could still have
                                // descendants with items.

                                if (!empty($showempty) || !empty($deepcount[$cat['id']])) {
                                    // We are not hiding empty categories - set count to zero.
                                    $count = 0;
                                } else {
                                    // We want to hide empty categories - so skip this loop.
                                    continue;
                                }
                            }

                            $label = xarVarPrepForDisplay($cat['name']);
                            $link = xarModURL(
                                $modname, $type, $func,
                                array(
                                    'itemtype' => $itemtype,
                                    'catid' => $cat['id']
                                )
                            );


                            $savecid = $cat['id'];
                            $catchildren = array();
                            if ($cat['id'] == $cid) {
                                if (empty($itemid) && empty($andcids)) {
                                    $link = '';
                                }
                                if ($showchildren && !empty($children) && count($children) > 0) {
                                    foreach ($children as $cat) {
                                        $clabel = xarVarPrepForDisplay($cat['name']);
                                    // TODO: now this is a tricky part...
                                        $clink = xarModURL($modname,$type,$func,
                                                          array('itemtype' => $itemtype,
                                                                'catid' => $cat['id']));
                                        if (!empty($catcount[$cat['id']])) {
                                            $ccount = $catcount[$cat['id']];
                                        } else {
                                            $ccount = 0;
                                        }
                                        $catchildren[] = array('clabel' => $clabel,
                                                               'cid' => $cat['id'],
                                                               'clink' => $clink,
                                                               'ccount' => $ccount);
                                    }
                                }
                            }
                            $catitems[] = array('catlabel' => $label,
                                                'catid' => $savecid,
                                                'catlink' => $link,
                                                'catcount' => $count,
                                                'catchildren' => $catchildren);
                        }
                        $data['cattrees'][] = array('catitems' => $catitems,
                                                    'catparents' => $catparents);
                    }
                }
                break;
        }

        // The template base is set by this block if not already provided.
        // The base is 'nav-tree', 'nav-trails' or 'nav-prevnext', but allow
        // the admin to override this completely.
        // Set template base.
        $this->setTemplateBase('nav-' . $template);

        return $data;
        
    }
}

?>