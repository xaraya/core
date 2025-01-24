<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\AdminGui;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\AdminGui;
use BadParameterException;
use DataObjectFactory;
use DataPropertyMaster;
use xarController;
use xarMod;
use xarModHooks;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata admin showpropval function
 * @extends MethodClass<AdminGui>
 */
class ShowpropvalMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show configuration of some property
     * @return array|string|bool|void data for the template display
     * @see AdminGui::showpropval()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminDynamicData')) {
            return;
        }

        extract($args);

        // get the property id
        if (!$this->var()->find('itemid', $itemid, 'id')) {
            return;
        }
        if (!$this->var()->check('exit', $exit)) {
            return;
        }
        if (!$this->var()->check('confirm', $confirm)) {
            return;
        }
        if (!$this->var()->check('preview', $preview)) {
            return;
        }

        if (empty($itemid)) {
            // get the property type for sample configuration
            if (!$this->var()->find('proptype', $proptype)) {
                return;
            }

            // show sample configuration for some property type
            return dynamicdata_config_propval($proptype);
        }

        // get the object corresponding to this dynamic property
        // set context if available in function
        $myobject = $this->data()->getObject(
            ['name'   => 'properties',
                'itemid' => $itemid]
        );
        if (empty($myobject)) {
            return;
        }

        $newid = $myobject->getItem();

        if (empty($newid) || empty($myobject->properties['id']->value)) {
            throw new BadParameterException(null, 'Invalid item id');
        }
        if (empty($myobject->properties['objectid']->value)) {
            throw new BadParameterException(null, 'Invalid object id');
        }

        // check security of the parent object
        $parentobjectid = $myobject->properties['objectid']->value;
        // set context if available in function
        $parentobject = $this->data()->getObject(['objectid' => $parentobjectid]);
        if (empty($parentobject)) {
            return;
        }
        if (!$parentobject->checkAccess('config')) {
            $msg = $this->ml('Configure #(1) is forbidden', $parentobject->label);
            return $this->ctl()->forbidden($msg);
        }
        // @todo For now, always add a reference to the parent object? - see DataPropertyMaster::addProperty()
        //unset($parentobject);

        // check if the module+itemtype this property belongs to is hooked to the uploads module
        /* FIXME: can we do without this hardwiring? Comment out for now
        $module_id = $myobject->properties['module_id']->value;
        $itemtype = $myobject->properties['itemtype']->value;
        $modinfo = xarMod::getInfo($module_id);
        if (xarModHooks::isHooked('uploads', $modinfo['name'], $itemtype)) {
            xarVar::setCached('Hooks.uploads','ishooked',1);
        }
        */

        $data = [];
        // get a new property of the right type
        $data['type'] = $myobject->properties['type']->value;
        $id = $myobject->properties['configuration']->id;

        $data['name']       = 'dd_' . $id;
        // pass the actual id for the property here
        $data['id']         = $id;
        // pass the original invalid value here
        $data['invalid']    = !empty($invalid) ? $invalid : '';
        // @todo For now, always add a reference to the parent object? - see DataPropertyMaster::addProperty()
        $data['objectref'] = $parentobject;
        $property = $this->prop()->getProperty($data);
        if (empty($property)) {
            return;
        }

        $data['propertytype'] = $this->prop()->getProperty(['type' => $data['type']]);

        if (!empty($preview) || !empty($confirm) || !empty($exit)) {
            if (!$this->var()->find($data['name'], $configuration)) {
                return;
            }

            // pass the current value as configuration rule
            $data['configuration'] = $configuration ?? '';

            $isvalid = $property->updateConfiguration($data);

            if ($isvalid) {
                if (!empty($confirm) || !empty($exit)) {
                    // store the updated configuration rule back in the value
                    $myobject->properties['configuration']->value = $property->configuration;
                    if (!$this->sec()->confirmAuthKey()) {
                        return $this->ctl()->badRequest('bad_author');
                    }

                    $newid = $myobject->updateItem();
                    if (empty($newid)) {
                        return;
                    }

                    if (empty($exit)) {
                        $return_url = $this->mod()->getURL('admin', 'showpropval', ['itemid' => $itemid]);
                        $this->ctl()->redirect($return_url);
                        return true;
                    }
                }
                if (!empty($exit)) {
                    if (!$this->var()->check('return_url', $return_url)) {
                        return;
                    }
                    if (empty($return_url)) {
                        // return to modifyprop
                        $return_url = $this->mod()->getURL(
                            'admin',
                            'modifyprop',
                            ['itemid' => $parentobjectid]
                        );
                    }
                    $this->ctl()->redirect($return_url);
                    return true;
                }
                // show preview/updated values

            } else {
                $myobject->properties['configuration']->invalid = $property->invalid;
            }

            // pass the current value as configuration rule
        } elseif (!empty($myobject->properties['configuration'])) {
            $data['configuration'] = $myobject->properties['configuration']->value;

        } else {
            $data['configuration'] = null;
        }

        // pass the id for the input field here
        $data['id']         = 'dd_' . $id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
        $data['size']       = !empty($size) ? $size : 50;

        // call its showConfiguration() method and return
        $data['showval'] = $property->showConfiguration($data);
        $data['itemid'] = $itemid;
        $data['object'] = & $myobject;

        $this->tpl()->setPageTitle($this->ml('Configuration for DataProperty #(1)', $itemid));
        $data['has_overview'] = false;
        $typename = $data['propertytype']->name;
        if (file_exists(sys::code() . 'properties/' . $typename . '/xartemplates/includes/overview.xt')) {
            $data['has_overview'] = true;
        }

        // Return the template variables defined in this function
        return $data;
    }
}
