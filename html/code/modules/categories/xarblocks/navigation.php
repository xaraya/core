<?php

/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
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
     */
    public function display()
    {
        $vars = $this->getContent();

        extract($vars);

        // Get requested layout
        if (empty($layout)) {
            $layout = $this->layout;
        } // default tree here

        if (!empty($startmodule)) {
            // static behaviour
            [$module, $itemtype, $rootcid] = explode('.', $startmodule);
            if (empty($rootcid)) {
                $rootcids = null;
            } elseif (strpos($rootcid, ' ')) {
                $rootcids = explode(' ', $rootcid);
            } elseif (strpos($rootcid, '+')) {
                $rootcids = explode('+', $rootcid);
            } else {
                $rootcids = explode('-', $rootcid);
            }
        }

        // TODO: for multi-module pages, we'll need some other reference point(s)
        //       (e.g. cross-module categories defined in categories admin ?)
        // Get current module
        if (empty($module)) {
            if ($this->mem()->has('Blocks.categories', 'module')) {
                $modname = $this->mem()->get('Blocks.categories', 'module');
            }
            if (empty($modname)) {
                $modname = $this->mod()->getName();
            }
        } else {
            $modname = $module;
        }
        $modid = $this->mod()->getRegID($modname);
        if (empty($modid)) {
            return;
        }

        // Get current item type (if any)
        if (!isset($itemtype)) {
            if ($this->mem()->has('Blocks.categories', 'itemtype')) {
                $itemtype = $this->mem()->get('Blocks.categories', 'itemtype');
            } else {
                // try to get itemtype from input
                $this->var()->check('itemtype', $itemtype, 'id', null);
            }
        }
        if (empty($itemtype)) {
            $itemtype = null;
        }

        // Get current item id (if any)
        if (!isset($itemid)) {
            if ($this->mem()->has('Blocks.categories', 'itemid')) {
                $itemid = $this->mem()->get('Blocks.categories', 'itemid');
            } else {
                // try to get itemid from input
                $this->var()->check('itemid', $itemid, 'id', null);
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
            $mastercids = [];
            foreach ($toplevelcats as $tlc) {
                $mastercids[$tlc['id']] = (int) $tlc['id'];
            }

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
            $catcount = [];
        }
        if (empty($showempty) || !empty($showcatcount)) {
            // A 'deep count' sums the totals at each node with the totals of all descendants.
            if ($this->mem()->has('Blocks.categories', 'deepcount') && empty($startmodule)) {
                $deepcount = $this->mem()->get('Blocks.categories', 'deepcount');
            } else {
                $deepcount = $this->mod()->apiFunc(
                    'categories',
                    'user',
                    'deepcount',
                    ['modid' => $modid, 'itemtype' => $itemtype]
                );
                $this->mem()->set('Blocks.categories', 'deepcount', $deepcount);
            }
        }
        if (!empty($showcatcount)) {
            if ($this->mem()->has('Blocks.categories', 'catcount') && empty($startmodule)) {
                $catcount = $this->mem()->get('Blocks.categories', 'catcount');
            } else {
                // Get number of items per category (for this module).
                // If showcatcount == 2 then add in all descendants too.

                if ($showcatcount == 1) {
                    // We want to display only children category counts.
                    $catcount = $this->mod()->apiFunc(
                        'categories',
                        'user',
                        'groupcount',
                        ['modid' => $modid, 'itemtype' => $itemtype]
                    );
                } else {
                    // We want to display the deep counts.
                    $catcount = & $deepcount;
                }

                $this->mem()->set('Blocks.categories', 'catcount', $catcount);
            }
        }

        // Specify type=... & func = ... arguments for $this->ctl()->getModuleURL()
        if (empty($type)) {
            if ($this->mem()->has('Blocks.categories', 'type')) {
                $type = $this->mem()->get('Blocks.categories', 'type');
            }
            if (empty($type)) {
                $type = 'user';
            }
        }
        if (empty($func)) {
            if ($this->mem()->has('Blocks.categories', 'func')) {
                $func = $this->mem()->get('Blocks.categories', 'func');
            }
            if (empty($func)) {
                $func = 'view';
            }
        }

        // Get current categories
        if ($this->mem()->has('Blocks.categories', 'catid')) {
            $catid = $this->mem()->get('Blocks.categories', 'catid');
        }
        if (empty($catid)) {
            // try to get catid from input
            $this->var()->check('catid', $catid, 'str', null);
        }
        // turn $catid into $cids array (and set $andcids flag)
        $istree = 0;
        if (!empty($catid)) {
            // if we're viewing all items below a certain category, i.e. catid = _NN
            if (strstr($catid, '_')) {
                $catid = preg_replace('/_/', '', $catid);
                $istree = 1;
            }
            if (strpos($catid, ' ')) {
                $cids = explode(' ', $catid);
                $andcids = true;
            } elseif (strpos($catid, '+')) {
                $cids = explode('+', $catid);
                $andcids = true;
            } else {
                $cids = explode('-', $catid);
                $andcids = false;
            }
        } elseif (empty($cids)) {
            if ($this->mem()->has('Blocks.categories', 'cids')) {
                $cids = $this->mem()->get('Blocks.categories', 'cids');
            }
            if ($this->mem()->has('Blocks.categories', 'andcids')) {
                $andcids = $this->mem()->get('Blocks.categories', 'andcids');
            }
            if (empty($cids)) {
                // try to get cids from input
                $this->var()->find('cids', $cids);
                $this->var()->find('andcids', $andcids, 'isset', false);

                if (empty($cids)) {
                    $cids = [];
                    if ((empty($module) || $module == $modname) && !empty($itemid)) {
                        $links = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getlinks',
                            ['modid' => $modid,
                                'itemtype' => $itemtype,
                                'iids' => [$itemid]]
                        );
                        if (!empty($links) && count($links) > 0) {
                            $cids = array_keys($links);
                        }
                    }
                }
            }
        }
        if (count($cids) > 0) {
            $seencid = [];
            foreach ($cids as $cid) {
                if (empty($cid) || ! is_numeric($cid)) {
                    continue;
                }
                $seencid[$cid] = 1;
            }
            $cids = array_keys($seencid);
        }

        $data = [];
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
                        if ($this->mem()->has('Blocks.categories', 'showprevnext')) {
                            $showprevnext = $this->mem()->get('Blocks.categories', 'showprevnext');
                            if (empty($showprevnext)) {
                                return;
                            }
                        }
                    }
                    $cat = $this->mod()->apiFunc(
                        'categories',
                        'user',
                        'getcatinfo',
                        ['cid' => $cids[0]]
                    );
                    if (empty($cat)) {
                        return;
                    }
                    $neighbours = $this->mod()->apiFunc(
                        'categories',
                        'user',
                        'getneighbours',
                        $cat
                    );
                    if (empty($neighbours) || count($neighbours) == 0) {
                        return;
                    }
                    foreach ($neighbours as $neighbour) {
                        //                    if ($neighbour['link'] == 'parent') {
                        //                        $data['uplabel'] = $neighbour['name'];
                        //                        $data['upcid'] = $neighbour['cid'];
                        //                        $data['uplink'] = $this->ctl()->getModuleURL($modname,$type,$func,
                        //                                                   array('itemtype' => $itemtype,
                        //                                                         'catid' => $neighbour['cid']));
                        //                    } elseif ($neighbour['link'] == 'previous') {
                        if ($neighbour['link'] == 'previous') {
                            $data['prevlabel'] = $neighbour['name'];
                            $data['prevcid'] = $neighbour['cid'];
                            $data['prevlink'] = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => $neighbour['cid']]
                            );
                        } elseif ($neighbour['link'] == 'next') {
                            $data['nextlabel'] = $neighbour['name'];
                            $data['nextcid'] = $neighbour['cid'];
                            $data['nextlink'] = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => $neighbour['cid']]
                            );
                        }
                    }
                    if (!isset($data['nextlabel'])
                        && !isset($data['prevlabel'])) {
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
                    $data['cattitle'] = $this->ml('Browse in');
                    $data['catitems'] = [];

                    // Get root categories
                    $catlist = $this->mod()->apiFunc(
                        'categories',
                        'user',
                        'getcatinfo',
                        ['cids' => $mastercids]
                    );
                    $join = '';
                    if (empty($catlist) || !is_array($catlist)) {
                        return;
                    }
                    foreach ($catlist as $cat) {
                        // TODO: now this is a tricky part...
                        $link = $this->ctl()->getModuleURL(
                            $modname,
                            $type,
                            $func,
                            ['itemtype' => $itemtype,
                                'catid' => $cat['id']]
                        );
                        $label = $this->var()->prep($cat['name']);
                        $data['catitems'][] = ['catlabel' => $label,
                            'catid' => $cat['id'],
                            'catlink' => $link,
                            'catjoin' => $join];
                        $join = ' | ';
                    }
                } else {
                    $template = 'trails';
                    if (!empty($andcids)) {
                        $data['cattitle'] = $this->ml('Browse in');
                    } else {
                        $data['cattitle'] = $this->ml('Browse in');
                    }
                    $data['cattrails'] = [];

                    $descriptions = [];
                    // TODO: stop at root categories
                    foreach ($cids as $cid) {
                        // Get category information
                        $parents = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getparents',
                            ['cid' => $cid]
                        );
                        if (empty($parents)) {
                            continue;
                        }
                        $catitems = [];
                        $curcount = 0;
                        // TODO: now this is a tricky part...
                        $label = $this->ml('All');
                        $link = $this->ctl()->getModuleURL(
                            $modname,
                            $type,
                            $func,
                            ['itemtype' => $itemtype]
                        );
                        $join = '';
                        $catitems[] = ['catlabel' => $label,
                            'catid' => $cid,
                            'catlink' => $link,
                            'catjoin' => $join];
                        $join = ' &gt; ';
                        foreach ($parents as $cat) {
                            $label = $this->var()->prep($cat['name']);
                            if ($cat['id'] == $cid && empty($itemid) && empty($andcids)) {
                                $link = '';
                            } else {
                                // TODO: now this is a tricky part...
                                $link = $this->ctl()->getModuleURL(
                                    $modname,
                                    $type,
                                    $func,
                                    ['itemtype' => $itemtype,
                                        'catid' => $cat['id']]
                                );
                            }
                            if ($cat['id'] == $cid) {
                                // show optional count
                                if (isset($catcount[$cat['id']])) {
                                    $curcount = $catcount[$cat['id']];
                                }
                                if (!empty($cat['description'])) {
                                    $descriptions[] = $this->var()->prepHTML($cat['description']);
                                } else {
                                    $descriptions[] = $this->var()->prep($cat['name']);
                                }
                                // save current category info for icon etc.
                                if (count($cids) == 1) {
                                    $curcat = $cat;
                                }
                            }
                            $catitems[] = ['catlabel' => $label,
                                'catid' => $cat['id'],
                                'catlink' => $link,
                                'catjoin' => $join];
                        }
                        $data['cattrails'][] = ['catitems' => $catitems,
                            'catcount' => $curcount];
                    }

                    // Add filters to select on all categories or any categories
                    if (count($cids) > 1) {
                        $catitems = [];
                        if (!empty($itemid) || !empty($andcids)) {
                            $label = $this->ml('Any of these categories');
                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => join('-', $cids)]
                            );
                            $join = '';
                            $catitems[] = ['catlabel' => $label,
                                'catid' => join('-', $cids),
                                'catlink' => $link,
                                'catjoin' => $join];
                        }
                        if (empty($andcids)) {
                            $label = $this->ml('All of these categories');
                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => join('+', $cids)]
                            );
                            if (!empty($itemid)) {
                                $join = '-';
                            } else {
                                $join = '';
                            }
                            $catitems[] = ['catlabel' => $label,
                                'catid' => join('+', $cids),
                                'catlink' => $link,
                                'catjoin' => $join];
                        }
                        $curcount = 0;
                        $data['cattrails'][] = ['catitems' => $catitems,
                            'catcount' => $curcount];
                    }

                    // TODO: move off to nav-trails template ?
                    // Build category description
                    if (!empty($itemid)) {
                        $data['catdescr'] = join(' + ', $descriptions);
                    } elseif (!empty($andcids)) {
                        $data['catdescr'] = join(' ' . $this->ml('and') . ' ', $descriptions);
                    } else {
                        $data['catdescr'] = join(' ' . $this->ml('or') . ' ', $descriptions);
                    }

                    if (count($cids) != 1) {
                        break;
                    }

                    if (!empty($curcat)) {
                        /*
                                            $curcat['module'] = 'categories';
                                            $curcat['itemtype'] = 0;
                                            $curcat['itemid'] = $cids[0];
                                            $curcat['returnurl'] = $this->ctl()->getModuleURL($modname,$type,$func,
                                                                             array('itemtype' => $itemtype,
                                                                                   'catid' => $cids[0]));
                                            // calling item display hooks *for the categories module* here !
                                            $data['cathooks'] = $this->mod()->callHooks('item','display',$cid,$curcat,'categories');
                        */
                        // saving the current cat id for use e.g. with DD tags (<xar:data-display module="categories" itemid="$catid"/>)
                        $data['catid'] = $curcat['cid'];
                    }
                    /*
                                    // set the page title to the current module + category if no item is displayed
                                    if (empty($itemid)) {
                                        // Get current title
                                        if (empty($title)) {
                                            if ($this->mem()->has('Blocks.categories','title')) {
                                                $title = $this->mem()->get('Blocks.categories','title');
                                            }
                                        }
                                        if (!empty($curcat['name'])) {
                                            $title = $this->var()->prep($curcat['name']);
                                        }
                                        $this->tpl()->setPageTitle($title);
                                    }
                    */
                    // TODO: don't show icons when displaying items ?
                    if (!empty($curcat['image'])) {
                        // find the image in categories (we need to specify the module here)
                        $data['catimage'] = $this->tpl()->getImage($curcat['image'], 'categories');
                        $data['catname'] = $this->var()->prep($curcat['name']);
                    }
                    if ($showchildren == 2) {
                        // Get child categories (all sub-levels)
                        $childlist = $this->mod()->apiFunc(
                            'categories',
                            'visual',
                            'listarray',
                            ['cid' => $cids[0]]
                        );
                        if (empty($childlist) || count($childlist) == 0) {
                            break;
                        }
                        foreach ($childlist as $info) {
                            if ($info['id'] == $cids[0]) {
                                continue;
                            }
                            $label = $this->var()->prep($info['name']);
                            // TODO: now this is a tricky part...
                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => $info['id']]
                            );
                            if (!empty($catcount[$info['id']])) {
                                $count = $catcount[$info['id']];
                            } else {
                                $count = 0;
                            }
                            /* don't show descriptions in (potentially) multi-level trees
                                                if (!empty($info['description'])) {
                                                    $descr = $this->var()->prepHTML($info['description']);
                                                } else {
                                                    $descr = '';
                                                }
                            */
                            $data['catlines'][] = ['catlabel' => $label,
                                'catid' => $info['id'],
                                'catlink' => $link,
                                //  'catdescr' => $descr,
                                'catdescr' => '',
                                'catcount' => $count,
                                'beforetags' => $info['beforetags'],
                                'aftertags' => $info['aftertags']];

                        }
                        unset($childlist);
                    } elseif ($showchildren == 1) {
                        // Get child categories (1 level only)
                        $children = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getchildren',
                            ['cid' => $cids[0]]
                        );
                        if (empty($children) || count($children) == 0) {
                            break;
                        }
                        $data['catlines'] = [];
                        // TODO: don't show icons when displaying items ?
                        $data['caticons'] = [];
                        $numicons = 0;
                        foreach ($children as $cat) {
                            // TODO: now this is a tricky part...
                            $label = $this->var()->prep($cat['name']);
                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => $cat['id']]
                            );
                            if (!empty($catcount[$cat['id']])) {
                                $count = $catcount[$cat['id']];
                            } else {
                                $count = 0;
                            }
                            if (!empty($cat['image'])) {
                                // find the image in categories (we need to specify the module here)
                                $image = $this->tpl()->getImage($cat['image'], 'categories');
                                $numicons++;
                                $data['caticons'][] = ['catlabel' => $label,
                                    'catid' => $cat['id'],
                                    'catlink' => $link,
                                    'catimage' => $image,
                                    'catcount' => $count,
                                    'catnum' => $numicons];
                            } else {
                                if (!empty($cat['description']) && $cat['description'] != $cat['name']) {
                                    $descr = $this->var()->prepHTML($cat['description']);
                                } else {
                                    $descr = '';
                                }
                                $beforetags = '<li>';
                                $aftertags = '</li>';
                                $data['catlines'][] = ['catlabel' => $label,
                                    'catid' => $cat['id'],
                                    'catlink' => $link,
                                    'catdescr' => $descr,
                                    'catcount' => $count,
                                    'beforetags' => $beforetags,
                                    'aftertags' => $aftertags];
                            }
                        }
                        unset($children);
                        if (count($data['catlines']) > 0) {
                            $numitems = count($data['catlines']);
                            // add leading <ul> tag
                            $data['catlines'][0]['beforetags'] = '<ul>'
                                                       . $data['catlines'][0]['beforetags'];
                            // add trailing </ul> tag
                            $data['catlines'][$numitems - 1]['aftertags'] .= '</ul>';
                            // add new column
                            if ($numitems > 7) {
                                $miditem = round(($numitems + 0.5) / 2) - 1;
                                $data['catlines'][$miditem]['aftertags']
                                                       .= '</ul></td><td valign="top"><ul>';
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
                        if ($this->mem()->has('Blocks.categories', 'title')) {
                            $title = $this->mem()->get('Blocks.categories', 'title');
                        }
                    }
                    if (empty($title) && !empty($itemtype)) {
                        // Get the list of all item types for this module (if any)
                        try {
                            $mytypes = $this->mod()->apiFunc($modname, 'user', 'getitemtypes');
                        } catch (Exception $e) {
                            $mytypes = [];
                        }
                        if (isset($mytypes) && !empty($mytypes[$itemtype])) {
                            $title = $mytypes[$itemtype]['label'];
                        }
                    }
                    if (empty($title)) {
                        $modinfo = $this->mod()->getInfo($modid);
                        $title = ucwords($modinfo['displayname']);
                    }
                    $blockinfo['title'] = $this->ml('Browse in #(1)', $title);
                }

                $data['cattrees'] = [];

                if (empty($cids) || count($cids) == 0) {
                    foreach ($mastercids as $cid) {
                        $catparents = [];
                        $catitems = [];
                        // Get child categories
                        $children = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getchildren',
                            ['cid' => $cid,
                                'return_itself' => true]
                        );
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

                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => $cat['id']]
                            );

                            $label = $this->var()->prep($cat['name']);
                            if ($cat['id'] == $cid) {
                                $catparents[] = ['catlabel' => $label,
                                    'catid' => $cat['id'],
                                    'catlink' => $link,
                                    'catcount' => $count];
                            } else {
                                $catitems[] = ['catlabel' => $label,
                                    'catid' => $cat['id'],
                                    'catlink' => $link,
                                    'catcount' => $count];
                            }
                        }
                        if (empty($catitems) && empty($catparents)) {
                            continue;
                        }
                        $data['cattrees'][] = ['catitems' => $catitems,
                            'catparents' => $catparents];
                    }
                } elseif (isset($rootcids) && count($rootcids) > 0) {
                    foreach ($rootcids as $cid) {
                        $catparents = [];
                        $catitems = [];
                        // Get child categories
                        $children = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getchildren',
                            ['cid' => $cid,
                                'return_itself' => true]
                        );
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

                            $label = $this->var()->prep($cat['name']);
                            // TODO: now this is a tricky part...
                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => $cat['id']]
                            );

                            if ($cat['id'] == $cid) {
                                $catparents[] = ['catlabel' => $label,
                                    'catid' => $cat['id'],
                                    'catlink' => $link,
                                    'catcount' => $count];
                            } elseif ($showchildren > 0) {
                                $catitems[] = ['catlabel' => $label,
                                    'catid' => $cat['id'],
                                    'catlink' => $link,
                                    'catcount' => $count];
                            }
                        }
                        $data['cattrees'][] = ['catitems' => $catitems,
                            'catparents' => $catparents];
                    }
                } else {
                    foreach ($cids as $cid) {
                        $catparents = [];
                        $catitems = [];
                        // Get category information
                        $parents = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getparents',
                            ['cid' => $cid]
                        );
                        if (empty($parents)) {
                            continue;
                        }
                        // TODO: do something with parents
                        $root = '';
                        $parentid = 0;
                        foreach ($parents as $id => $info) {
                            if (empty($root)) {
                                $root = $this->var()->prep($info['name']);
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
                            $label = $this->var()->prep($cat['name']);
                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                ['itemtype' => $itemtype,
                                    'catid' => $cat['id']]
                            );
                            if (!empty($catcount[$cat['id']])) {
                                $count = $catcount[$cat['id']];
                            } else {
                                $count = 0;
                            }
                            $catparents[] = ['catlabel' => $label,
                                'catid' => $cat['id'],
                                'catlink' => $link,
                                'catcount' => $count];
                        }

                        // Get sibling categories
                        $siblings = $this->mod()->apiFunc(
                            'categories',
                            'user',
                            'getchildren',
                            ['cid' => $parentid]
                        );
                        if ($showchildren && $parentid != $cid) {
                            // Get child categories
                            $children = $this->mod()->apiFunc(
                                'categories',
                                'user',
                                'getchildren',
                                ['cid' => $cid]
                            );
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

                            $label = $this->var()->prep($cat['name']);
                            $link = $this->ctl()->getModuleURL(
                                $modname,
                                $type,
                                $func,
                                [
                                    'itemtype' => $itemtype,
                                    'catid' => $cat['id'],
                                ]
                            );


                            $savecid = $cat['id'];
                            $catchildren = [];
                            if ($cat['id'] == $cid) {
                                if (empty($itemid) && empty($andcids)) {
                                    $link = '';
                                }
                                if ($showchildren && !empty($children) && count($children) > 0) {
                                    foreach ($children as $cat) {
                                        $clabel = $this->var()->prep($cat['name']);
                                        // TODO: now this is a tricky part...
                                        $clink = $this->ctl()->getModuleURL(
                                            $modname,
                                            $type,
                                            $func,
                                            ['itemtype' => $itemtype,
                                                'catid' => $cat['id']]
                                        );
                                        if (!empty($catcount[$cat['id']])) {
                                            $ccount = $catcount[$cat['id']];
                                        } else {
                                            $ccount = 0;
                                        }
                                        $catchildren[] = ['clabel' => $clabel,
                                            'cid' => $cat['id'],
                                            'clink' => $clink,
                                            'ccount' => $ccount];
                                    }
                                }
                            }
                            $catitems[] = ['catlabel' => $label,
                                'catid' => $savecid,
                                'catlink' => $link,
                                'catcount' => $count,
                                'catchildren' => $catchildren];
                        }
                        $data['cattrees'][] = ['catitems' => $catitems,
                            'catparents' => $catparents];
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
