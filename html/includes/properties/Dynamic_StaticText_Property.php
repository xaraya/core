<?php
/**
 * File: $Id$
 *
 * Dynamic Data Static Text Property
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
 * handle static text property
 *
 * @package dynamicdata
 *
 */
class Dynamic_StaticText_Property extends Dynamic_Property
{
    function validateValue($value = null)
    {
        if (isset($value) && $value != $this->value) {
            $this->invalid = xarML('static text');
            $this->value = null;
            return false;
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);

/*        return (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/      $data=array();

        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['name']     = $name;
        $data['id']       = $id;


        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        $template="static";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
    }
     // default showOutput() from Dynamic_Property
    function showOutput($args = array())
    {
        extract($args);
        if (isset($value)) {
            return xarVarPrepForDisplay($value);
        } else {
            return xarVarPrepForDisplay($this->value);
        }

        $data=array();

        $data['value'] = $value;
        
        $template="static";
        return xarTplModule('dynamicdata', 'user', 'showoutput', $data ,$template);

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
                              'id'         => 1,
                              'name'       => 'static',
                              'label'      => 'Static Text',
                              'format'     => '1',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>