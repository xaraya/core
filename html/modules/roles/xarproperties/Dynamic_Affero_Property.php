<?php
/**
 * File: $Id$
 *
 * Dynamic Data Affero Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata properties
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_URLIcon_Property.php";

/**
 * handle MSN property
 *
 * @package dynamicdata
 */
class Dynamic_Affero_Property extends Dynamic_URLIcon_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_string($value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('Affero Name');
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        $data=array();

        if (!empty($value)) {
            $link = 'http://svcs.affero.net/user-history.php?ll=lq_members&u='.$value;
        } else {
            $link = '';
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;
        $data['link']     = xarVarPrepForDisplay($link);

        $template="";
        return xarTplProperty('roles', 'affero', 'showinput', $data);

    }

    function showOutput($args = array())
    {
         extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        $data=array();

        if (!empty($value)) {
            $link = 'http://svcs.affero.net/user-history.php?ll=lq_members&u='.$value;
            $data['link'] = xarVarPrepForDisplay($link);

            if (!empty($this->icon)) {
                $data['value']= $this->value;
                $data['icon'] = $this->icon;
                $data['name'] = $this->name;
                $data['id']   = $this->id;
                $data['image']= xarVarPrepForDisplay($this->icon);

            $template="";
            return xarTplProperty('roles', 'affero', 'showoutput', $data );
           }
        }
        return '';
    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args = array();
         $baseInfo = array(
                             'id'         => 40,
                              'name'       => 'affero',
                              'label'      => 'Affero Username',
                              'format'     => '40',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
