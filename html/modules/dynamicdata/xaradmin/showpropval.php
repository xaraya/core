<?php
/**
 * Show configuration of some property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Show configuration of some property
 * @return array
 */
function dynamicdata_admin_showpropval($args)
{
    extract($args);

    // get the property id
    if (!xarVarFetch('itemid',  'id',    $itemid, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('exit', 'isset', $exit, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirm', 'isset', $confirm, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}

    if (empty($itemid)) {
        // get the property type for sample configuration
        if (!xarVarFetch('proptype', 'isset', $proptype, NULL, XARVAR_NOT_REQUIRED)) {return;}

        // show sample configuration for some property type
        return dynamicdata_config_propval($proptype);
    }

    // get the object corresponding to this dynamic property
    $myobject = & DataObjectMaster::getObject(array('name'   => 'properties',
                                                    'itemid' => $itemid));
    if (empty($myobject)) return;

    // check security
    $module_id = $myobject->moduleid;
    $itemtype = $myobject->itemtype;
    if (!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$module_id:$itemtype:$itemid")) return;

    $newid = $myobject->getItem();

    if (empty($newid) || empty($myobject->properties['id']->value)) {
        throw new BadParameterException(null,'Invalid item id');
    }

    // check if the module+itemtype this property belongs to is hooked to the uploads module
    /* FIXME: can we do without this hardwiring? Comment out for now
    $module_id = $myobject->properties['module_id']->value;
    $itemtype = $myobject->properties['itemtype']->value;
    $modinfo = xarModGetInfo($module_id);
    if (xarModIsHooked('uploads', $modinfo['name'], $itemtype)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    */

    $data = array();
    // get a new property of the right type
    $data['type'] = $myobject->properties['type']->value;
    $id = $myobject->properties['configuration']->id;

    $data['name']       = 'dd_'.$id;
    // pass the actual id for the property here
    $data['id']         = $id;
    // pass the original invalid value here
    $data['invalid']    = !empty($invalid) ? $invalid :'';
    $property =& DataPropertyMaster::getProperty($data);
    if (empty($property)) return;

    if (!empty($preview) || !empty($confirm) || !empty($exit)) {
        if (!xarVarFetch($data['name'],'isset',$configuration,NULL,XARVAR_NOT_REQUIRED)) return;

        // pass the current value as configuration rule
        $data['configuration'] = isset($configuration) ? $configuration : '';

        $isvalid = $property->updateConfiguration($data);

        if ($isvalid) {
            if (!empty($confirm) || !empty($exit)) {
                // store the updated configuration rule back in the value
                $myobject->properties['configuration']->value = $property->configuration;
                if (!xarSecConfirmAuthKey()) {
                    return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
                }

                $newid = $myobject->updateItem();
                if (empty($newid)) return;

            }
            if (!empty($exit)) {
                if (!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
                if (empty($return_url)) {
                    // return to modifyprop
                    $return_url = xarModURL('dynamicdata', 'admin', 'modifyprop',
                                            array('itemid' => $myobject->properties['objectid']->value));
                }
                xarResponse::Redirect($return_url);
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
    $data['id']         = 'dd_'.$id;
    $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
    $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
    $data['size']       = !empty($size) ? $size : 50;

    // call its showConfiguration() method and return
    $data['showval'] = $property->showConfiguration($data);
    $data['itemid'] = $itemid;
    $data['object'] =& $myobject;

    xarTplSetPageTitle(xarML('Configuration for DataProperty #(1)', $itemid));

    // Return the template variables defined in this function
    return $data;
}

/**
 * Show sample configuration for some property type
 * @return array
 */
function dynamicdata_config_propval($proptype)
{
    $data = array();
    if (empty($proptype)) {
        xarTplSetPageTitle(xarML('Sample Configuration for DataProperty Types'));
        return $data;
    }

    // get a new property of the right type
    $data['type'] = $proptype;
    $data['name'] = 'dd_' . $proptype;
    $property =& DataPropertyMaster::getProperty($data);
    if (empty($property)) {
        xarTplSetPageTitle(xarML('Sample Configuration for DataProperty Types'));
        return $data;
    }

    if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirm', 'isset', $confirm, NULL, XARVAR_DONT_SET)) {return;}
    if (!empty($preview) || !empty($confirm)) {
        if (!xarVarFetch($data['name'],'isset',$configuration,NULL,XARVAR_NOT_REQUIRED)) return;

        // pass the current value as configuration rule
        $data['configuration'] = isset($configuration) ? $configuration : '';

        $isvalid = $property->updateConfiguration($data);

        if ($isvalid) {
            $data['configuration'] = $property->configuration;
/*
// CHECKME: allow updating the default configuration for a property type someday ? See
//          also CHECKME in class/properties/master.php DataPropertyMaster::getProperty()
            if (!empty($confirm)) {
                if (!xarSecConfirmAuthKey()) {
                    return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
                }
// TODO: we need some method in PropertyRegistration to update a property type ;-)

// TODO: we need some way to avoid overwriting this whenever we flush property types
            }
*/
        } else {
            $data['invalid'] = $property->invalid;
        }

    // pass the current value as configuration rule
    } elseif (!empty($property->configuration)) {
        $data['configuration'] = $property->configuration;

    } else {
        $data['configuration'] = null;
    }

    // pass the id for the input field here
    $data['id']         = 'dd_'.$proptype;
    $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
    $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
    $data['size']       = !empty($size) ? $size : 50;

    // call its showConfiguration() method and return
    $data['showval'] = $property->showConfiguration($data);
    $data['proptype'] = $proptype;
    $data['propinfo'] =& $property;

    xarTplSetPageTitle(xarML('Sample Configuration for DataProperty Type #(1)', $proptype));

    return $data;
}

?>
