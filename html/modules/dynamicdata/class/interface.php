<?php
/**
 * Dynamic Object Interface 
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
 * Dynamic Object Interface (experimental - cfr. 'object' module)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Object_Interface
{
    var $args = array();
    var $object = null;
    var $list = null;

    // module where the main templates for the GUI reside (defaults to the object module)
    var $urlmodule = null;
    // main function handling all object method calls (to be handled by the core someday ?)
    var $func = 'main';

    function Dynamic_Object_Interface($args = array())
    {
        // set a specific GUI module for now
        if (!empty($args['urlmodule'])) {
            $this->urlmodule = $args['urlmodule'];
        }

        // get some common URL parameters
        if(!xarVarFetch('object',   'isset', $args['object'],   NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('module',   'isset', $args['module'],   NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('itemtype', 'isset', $args['itemtype'], NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('table',    'isset', $args['table'],    NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('layout',   'isset', $args['layout'],   NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('template', 'isset', $args['template'], NULL, XARVAR_DONT_SET)) {return;}

        // do not allow the table interface unless the user is an admin
        if (!empty($args['table']) && !xarSecurityCheck('AdminDynamicData')) return;

        // retrieve the object information for this object
        if (!empty($args['object'])) {
            $info = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                  array('name' => $args['object']));
            $args = array_merge($args, $info);
        } elseif (!empty($args['module']) && empty($args['moduleid'])) {
            $args['moduleid'] = xarModGetIDFromName($args['module']);
        }
        // fill in the default object variables
        $this->args = $args;
    }


    function handle($args = array())
    {
        if(!xarVarFetch('method', 'isset', $args['method'], NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('itemid', 'isset', $args['itemid'], NULL, XARVAR_DONT_SET)) {return;}
        if (empty($args['method'])) {
            if (empty($args['itemid'])) {
                $args['method'] = 'view';
            } else {
                $args['method'] = 'display';
            }
        }
// TODO: check for the presence of existing module functions to handle this if necessary
        switch ($args['method']) {
            case 'new':
            case 'create':
                return $this->object_create($args);

            case 'modify':
            case 'update':
                return $this->object_update($args);

            case 'delete':
                return $this->object_delete($args);

            case 'display':
                return $this->object_display($args);

            case 'list':
            // no distinction between admin & user view here (for now ?)
            //    return $this->object_list($args);
            case 'view':
            default:
                return $this->object_view($args);
        }
    }

// TODO: move all object_* methods to Dynamic_Object ... or not ?

    function object_create($args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) {return;}

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        if (!isset($this->object)) {
            $this->object =& Dynamic_Object_Master::getObject($this->args);
            if (empty($this->object)) return;
            if (empty($this->urlmodule)) {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->urlmodule = $modinfo['name'];
            }
        }
        if (!xarSecurityCheck('AddDynamicDataItem',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':All')) return;

        //$this->object->getItem();

        if (!empty($args['preview']) || !empty($args['confirm'])) {
            if (!xarSecConfirmAuthKey()) return;

            $isvalid = $this->object->checkInput();

            if ($isvalid && !empty($args['confirm'])) {
                $itemid = $this->object->createItem();

                if (empty($itemid)) return; // throw back

                if (!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) {return;}
                if (!empty($args['return_url'])) {
                    xarResponseRedirect($args['return_url']);
                } else {
                    xarResponseRedirect(xarModURL($this->urlmodule, 'user', $this->func,
                                                  array('object' => $this->object->name)));
                }
                // Return
                return true;
            }
        }

        $title = xarML('New #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        // call item new hooks for this item
        $item = array();
        foreach (array_keys($this->object->properties) as $name) {
            $item[$name] = $this->object->properties[$name]->value;
        }
        if (!isset($modinfo)) {
            $modinfo = xarModGetInfo($this->object->moduleid);
        }
        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $hooks = xarModCallHooks('item', 'new', $this->object->itemid, $item, $modinfo['name']);

        $this->object->viewfunc = $this->func;
        return xarTplModule($this->urlmodule,'admin','new',
                            array('object' => $this->object,
                                  'preview' => $args['preview'],
                                  'hookoutput' => $hooks),
                            $this->object->template);
    }

    function object_update($args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) {return;}

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        if (!isset($this->object)) {
            $this->object =& Dynamic_Object_Master::getObject($this->args);
            if (empty($this->object)) return;
            if (empty($this->urlmodule)) {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->urlmodule = $modinfo['name'];
            }
        }
        if (!xarSecurityCheck('EditDynamicDataItem',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':'.$this->object->itemid)) return;

        $itemid = $this->object->getItem();
        if (empty($itemid) || $itemid != $this->object->itemid) {
            $msg = xarML('Invalid itemid');
            xarErrorSet(XAR_USER_EXCEPTION, 'NOT_FOUND',
                        new DefaultUserException($msg));
            return;
        }

        if (!empty($args['preview']) || !empty($args['confirm'])) {
            if (!xarSecConfirmAuthKey()) return;

            $isvalid = $this->object->checkInput();

            if ($isvalid && !empty($args['confirm'])) {
                $itemid = $this->object->updateItem();

                if (empty($itemid)) return; // throw back

                if (!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) {return;}
                if (!empty($args['return_url'])) {
                    xarResponseRedirect($args['return_url']);
                } else {
                    xarResponseRedirect(xarModURL($this->urlmodule, 'user', $this->func,
                                                  array('object' => $this->object->name)));
                }
                // Return
                return true;
            }
        }

        $title = xarML('Modify #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        // call item new hooks for this item
        $item = array();
        foreach (array_keys($this->object->properties) as $name) {
            $item[$name] = $this->object->properties[$name]->value;
        }
        if (!isset($modinfo)) {
            $modinfo = xarModGetInfo($this->object->moduleid);
        }
        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $hooks = xarModCallHooks('item', 'modify', $this->object->itemid, $item, $modinfo['name']);

        $this->object->viewfunc = $this->func;
        return xarTplModule($this->urlmodule,'admin','modify',
                            array('object' => $this->object,
                                  'hookoutput' => $hooks),
                            $this->object->template);
    }

    function object_delete($args = array())
    {
        if(!xarVarFetch('cancel',  'isset', $args['cancel'],  NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('confirm', 'isset', $args['confirm'], NULL, XARVAR_DONT_SET)) {return;}

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        if (!isset($this->object)) {
            $this->object =& Dynamic_Object_Master::getObject($this->args);
            if (empty($this->object)) return;
            if (empty($this->urlmodule)) {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->urlmodule = $modinfo['name'];
            }
        }
        if (!empty($args['cancel'])) {
            if (!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) {return;}
            if (!empty($args['return_url'])) {
                xarResponseRedirect($args['return_url']);
            } else {
                xarResponseRedirect(xarModURL($this->urlmodule, 'user', $this->func,
                                              array('object' => $this->object->name)));
            }
            // Return
            return true;
        }
        if (!xarSecurityCheck('DeleteDynamicDataItem',1,'Item',$this->object->moduleid.':'.$this->object->itemtype.':'.$this->object->itemid)) return;

        $itemid = $this->object->getItem();
        if (empty($itemid) || $itemid != $this->object->itemid) {
            $msg = xarML('Invalid itemid');
            xarErrorSet(XAR_USER_EXCEPTION, 'NOT_FOUND',
                        new DefaultUserException($msg));
            return;
        }

        if (!empty($args['confirm'])) {
            if (!xarSecConfirmAuthKey()) return;

            $itemid = $this->object->deleteItem();

            if (empty($itemid)) return; // throw back

            if (!xarVarFetch('return_url',  'isset', $args['return_url'], NULL, XARVAR_DONT_SET)) {return;}
            if (!empty($args['return_url'])) {
                xarResponseRedirect($args['return_url']);
            } else {
                xarResponseRedirect(xarModURL($this->urlmodule, 'user', $this->func,
                                              array('object' => $this->object->name)));
            }
            // Return
            return true;
        }

        $title = xarML('Delete #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->object->viewfunc = $this->func;
        return xarTplModule($this->urlmodule,'admin','delete',
                            array('object' => $this->object),
                            $this->object->template);
    }

    function object_display($args = array())
    {
        if(!xarVarFetch('preview', 'isset', $args['preview'], NULL, XARVAR_DONT_SET)) {return;}

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        if (!isset($this->object)) {
            $this->object =& Dynamic_Object_Master::getObject($this->args);
            if (empty($this->object)) return;
            if (empty($this->urlmodule)) {
                $modinfo = xarModGetInfo($this->object->moduleid);
                $this->urlmodule = $modinfo['name'];
            }
        }
        $title = xarML('Display #(1)', $this->object->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $itemid = $this->object->getItem();
        if (empty($itemid) || $itemid != $this->object->itemid) {
            $msg = xarML('Invalid itemid');
            xarErrorSet(XAR_USER_EXCEPTION, 'NOT_FOUND',
                        new DefaultUserException($msg));
            return;
        }

        // call item display hooks for this item
        $item = array();
        foreach (array_keys($this->object->properties) as $name) {
            $item[$name] = $this->object->properties[$name]->value;
        }
        if (!isset($modinfo)) {
            $modinfo = xarModGetInfo($this->object->moduleid);
        }
        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $this->object->itemtype;
        $item['itemid'] = $this->object->itemid;
        $item['returnurl'] = xarModURL($this->urlmodule,'user',$this->func,
                                       array('object' => $this->object->name,
                                             'itemid'   => $this->object->itemid));
        $hooks = xarModCallHooks('item', 'display', $this->object->itemid, $item, $modinfo['name']);

        $this->object->viewfunc = $this->func;
        return xarTplModule($this->urlmodule,'user','display',
                            array('object' => $this->object,
                                  'hookoutput' => $hooks),
                            $this->object->template);
    }

// no longer needed
    function object_list($args = array())
    {
        if(!xarVarFetch('catid',    'isset', $args['catid'],    NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('sort',     'isset', $args['sort'],     NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) {return;}

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        if (!isset($this->list)) {
            $this->list =& Dynamic_Object_Master::getObjectList($this->args);
            if (empty($this->list)) return;
            if (empty($this->urlmodule)) {
                $modinfo = xarModGetInfo($this->list->moduleid);
                $this->urlmodule = $modinfo['name'];
            }
        }
        $title = xarML('List #(1)', $this->list->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->list->getItems();

        $this->list->viewfunc = $this->func;
        $this->list->linkfunc = $this->func;
        return xarTplModule($this->urlmodule,'admin','view',
                            array('object' => $this->list),
                            $this->list->template);
    }

    function object_view($args = array())
    {
        if(!xarVarFetch('catid',    'isset', $args['catid'],    NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('sort',     'isset', $args['sort'],     NULL, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('startnum', 'isset', $args['startnum'], NULL, XARVAR_DONT_SET)) {return;}

        if (!empty($args) && is_array($args) && count($args) > 0) {
            $this->args = array_merge($this->args, $args);
        }
        if (!isset($this->list)) {
            $this->list =& Dynamic_Object_Master::getObjectList($this->args);
            if (empty($this->list)) return;
            if (empty($this->urlmodule)) {
                $modinfo = xarModGetInfo($this->list->moduleid);
                $this->urlmodule = $modinfo['name'];
            }
        }
        $title = xarML('View #(1)', $this->list->label);
        xarTplSetPageTitle(xarVarPrepForDisplay($title));

        $this->list->getItems();

        $this->list->viewfunc = $this->func;
        $this->list->linkfunc = $this->func;
        return xarTplModule($this->urlmodule,'user','view',
                            array('object' => $this->list),
                            $this->list->template);
    }

}

?>