<?php
/**
 * Handle Group list property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/*
 * Handle Group list property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
include_once "modules/base/xarproperties/Dynamic_URLIcon_Property.php";

class Dynamic_ICQ_Property extends Dynamic_URLIcon_Property
{
    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
    }
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_numeric($value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('ICQ Number');
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
        if (!empty($value)) {
            $link = 'http://wwp.icq.com/scripts/search.dll?to=' . $value;
        } else {
            $link = '';
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['link']     = $link;
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;

        $template="";
        return xarTplProperty('roles', 'icq', 'showinput', $data);

    }

    function showOutput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        // TODO: use redirect function here ?
        if (!empty($value) && !empty($this->icon)) {
        // TODO: check this ICQ stuff
        //<jojodee> Passing the whole lot to the template !
        //The data is there for anyone that wants to use the vars themselves in the template.
        $link = '<script language="JavaScript" type="text/javascript"><!--
if ( navigator.userAgent.toLowerCase().indexOf(\'mozilla\') != -1 && navigator.userAgent.indexOf(\'5.\') == -1 )
    document.write(\' <a href="http://wwp.icq.com/scripts/search.dll?to='.xarVarPrepForDisplay($value).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="ICQ Number" title="ICQ Number" alt=""/></a>\');
else
    document.write(\'<a href="http://wwp.icq.com/scripts/search.dll?to='.xarVarPrepForDisplay($value).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="ICQ Number" title="ICQ Number" alt=""/></a><a href="http://wwp.icq.com/'.xarVarPrepForDisplay($value).'#pager"><img src="http://web.icq.com/whitepages/online?icq='.xarVarPrepForDisplay($value).'&amp;img=5" width="18" height="18" alt=""/></a>\');
//--></script><noscript><a href="http://wwp.icq.com/scripts/search.dll?to='.xarVarPrepForDisplay($value).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="ICQ Number" title="ICQ Number" border="0" /></a></noscript>';

        } else {
            $link ='';
        }

        $data['value']= $this->value;
        $data['icon'] = xarVarPrepForDisplay($this->icon);
        $data['name'] = $this->name;
        $data['id']   = $this->id;
        $data['link'] = $link;

        $template="";
        return xarTplProperty('roles', 'icq', 'showoutput', $data);
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
                              'id'         => 28,
                              'name'       => 'icq',
                              'label'      => 'ICQ Number',
                              'format'     => '28',
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
