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
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin modifyconfighook function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfighookMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify configuration for a module - hook for ('module','modifyconfig','GUI')
     * @param mixed $args ['objectid'] ID of the object
     * @param mixed $args ['extrainfo'] extra information
     * @return string Returns display string
     * @throws \BadParameterException Thrown if modid was not found
     * @see AdminGui::modifyconfighook()
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

        $modid = $this->mod()->getRegID($modname);
        if (empty($modid)) {
            $msg = $this->ml('Invalid #(1) for #(2) function #(3)() in module #(4)', 'module name', 'admin', 'modifyconfighook', 'categories');
            throw new BadParameterException(null, $msg);
        }

        /* ----------------------- TODO Remove
            // see what we have to show here
            if (empty($extrainfo['number_of_categories'])) {
                // try to get number of categories from current settings
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
            }

            if (empty($extrainfo['mastercids']) || !is_array($extrainfo['mastercids'])) {
                // try to get cids from current settings
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
            // get all valid master cids for this module
            // Note : a module might have the same master cid twice (just in case...)
            $cleancids = array();
            foreach ($mastercids as $cid) {
                if (empty($cid) || !is_numeric($cid)) {
                    continue;
                }
                // preserve order of root categories if possible - do not use this for multi-select !
                $cleancids[] = $cid;
            }

            $items = array();
            for ($n = 0; $n < $numcats; $n++) {
                $item = array();
                $item['num'] = $n + 1;
                // preserve order of root categories if possible - do not use this for multi-select !
                if (isset($cleancids[$n])) {
                    $seencid = array($cleancids[$n] => 1);
                } else {
                    $seencid = array();
                }
                // TODO: improve memory usage
                // limit to some reasonable depth for now
                $item['select'] = $visualapi->makeselect(array('values' => &$seencid,
                                                     'name_prefix' => 'config_',
                                                     'maximum_depth' => 4,
                                                     'show_edit' => true));
                $items[] = $item;
            }
            unset($item);
        -----------------------------------*/
        if ($this->sec()->checkAccess('AddCategories', 0)) {
            $newcat = $this->ml('new');
        } else {
            $newcat = '';
        }

        $data = [];
        $data['newcat'] = $newcat;
        //    $data['numcats'] = $numcats;
        //    $data['items'] = $items;
        $data['modname'] = $modname;
        $data['itemtype'] = $extrainfo['itemtype'];

        $data['context'] ??= $this->getContext();
        return $this->tpl()->module('categories', 'admin', 'modifyconfighook', $data);
    }
}
