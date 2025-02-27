<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminGui;
use Xaraya\Modules\Categories\VisualApi;
use BadParameterException;
use xarMod;
use xarModVars;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin newhook function
 * @extends MethodClass<AdminGui>
 */
class NewhookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Select categories for a new item - hook for ('item','new','GUI')
     * @param mixed $args ['objectid'] ID of the object
     * @param mixed $args ['extrainfo'] extra information
     * @return string|array|null Returns display data array on success null on failure.
     * If security checks fail an empty string is returned
     * @see AdminGui::newhook()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        ///** @var VisualApi $visualapi */
        //$visualapi = $this->visualapi();

        if (!isset($extrainfo)) {
            $extrainfo = [];
        }

        // When called via hooks, the module name may be empty, so we get it from
        // the current module
        if (empty($extrainfo['module'])) {
            $modname = $this->mod()->getName();
        } else {
            $modname = $extrainfo['module'];
        }
        $data['module'] = $modname;
        $modid = $this->mod()->getRegID($modname);

        /* ---------------------------- TODO: Remove
            if (empty($modid)) {
                $msg = $this->ml('Invalid #(1) for #(2) function #(3)() in module #(4)','module name', 'admin', 'modifyhook', 'categories');
                throw new BadParameterException(null, $msg);
            }

            if (empty($extrainfo['number_of_categories'])) {
                // get number of categories from current settings
                if (!empty($extrainfo['itemtype'])) {
                    $numcats = (int) $this->mod($modname)->getVar('number_of_categories.'.$extrainfo['itemtype']);
                } else {
                    $numcats = (int) $this->mod($modname)->getVar('number_of_categories');
                }
            } else {
                $numcats = (int) $extrainfo['number_of_categories'];
            }

            if (empty($numcats) || !is_numeric($numcats)) {
                $numcats = 0;
                // no categories to show here -> return empty output
                return '';
            }
        ------------------------------- */
        // Security check (return empty hook output if not allowed) - to be refined per cat
        if (!empty($extrainfo['itemtype'])) {
            $modtype = $extrainfo['itemtype'];
            $data['itemtype'] = $extrainfo['itemtype'];
        } else {
            $modtype = 'All';
            $data['itemtype'] = 0;
        }
        if (!$this->sec()->check('SubmitCategoryLink', 0, 'Link', "$modid:$modtype:All:All")) {
            return '';
        }

        /* ---------------------------- TODO: Remove
            if (empty($extrainfo['mastercids']) || !is_array($extrainfo['mastercids'])) {
                // try to get master cids from current settings
                if (!empty($extrainfo['itemtype'])) {
                    $cidlist = $this->mod($modname)->getVar('mastercids.'.$extrainfo['itemtype']);
                } else {
                    $cidlist = $this->mod($modname)->getVar('mastercids');
                }
                if (empty($cidlist)) {
                    $mastercids = array();
                } else {
                    $mastercids = explode(';',$cidlist);
                }
            } else {
                $mastercids = $extrainfo['mastercids'];
            }

            // used e.g. for previews of new items
            if (empty($extrainfo['cids']) || !is_array($extrainfo['cids'])) {
                if (!empty($extrainfo['new_cids']) && is_array($extrainfo['new_cids'])) {
                    $cids = $extrainfo['new_cids'];
                } else {
                    // try to get cids from input
                    $this->var()->find('new_cids', $cids, 'list:int:1:', null);
                    if (empty($cids) || !is_array($cids)) {
                        $cids = array();
                    }
                }
            } else {
                $cids = $extrainfo['cids'];
            }
            // get all valid cids
            $seencid = array();
            foreach ($cids as $cid) {
                if (empty($cid) || !is_numeric($cid)) {
                    continue;
                }
                if (empty($seencid[$cid])) {
                    $seencid[$cid] = 1;
                } else {
                    $seencid[$cid]++;
                }
            }

            $items = array();
            for ($n = 0; $n < $numcats; $n++) {
                if (!isset($mastercids[$n])) {
                    break;
                }
                $item = array();
                $item['num'] = $n + 1;
                $item['select'] = $visualapi->makeselect(array('cid' => $mastercids[$n],
                                                     'multiple' => 1,
                                                     'name_prefix' => 'new_',
                                                     'return_itself' => true,
                                                     'select_itself' => true,
                                                     'values' => &$seencid));

                $items[] = $item;
            }
            unset($item);

            $labels = array();
            if ($numcats > 1) {
                $labels['categories'] = $this->ml('Categories');
            } else {
                $labels['categories'] = $this->ml('Category');
            }

            return $this->tpl()->module('categories','admin','newhook',
                                 array('labels' => $labels,
                                       'numcats' => $numcats,
                                       'items' => $items));
        ------------------------------- */

        // check if we're previewing some new item
        $this->var()->check('preview', $data['preview']);

        return $data;
    }
}
