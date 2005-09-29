/**
 * Checkbox Mask Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
 */

include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class to handle check box property
 *
 * @package dynamicdata
 */
class Dynamic_CheckboxMask_Property extends Dynamic_Select_Property
{
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
                        'id'         => 1114,
                        'name'       => 'checkboxmask',
                        'label'      => 'Checkbox Mask',
                        'format'     => '1114',
                        'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                        // ...
                       );
        return $baseInfo;
    }
     
     
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

        if( is_array($value) )
        {
            $this->value = maskImplode ( $value);
        } else {
            $this->value = $value;
        }

        return true;
    }
     
    function showInput($args = array())
    {
        extract($args);
        $data=array();

        if (!isset($value)) 
        {
            $data['value'] = $this->value;
        } else {
            $data['value'] = $value;
        }
        
        if ( !is_array($data['value']) && is_string($data['value']) )
        {
            $data['value'] = maskExplode( $data['value'] );
        }
        
        if (!isset($options) || count($options) == 0) 
        {
            $this->getOptions();
            $options = array();            
            foreach( $this->options as $key => $option )
            {
                $option['checked'] = in_array($option['id'],$data['value']);
                $options[$key] = $option;
            }
            $data['options'] = $options;
            
        }
        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
            $data['name'] = $name;
        }
        if (empty($id)) {
            $data['id'] = $data['name'];
        } else {
            $data['id']= $id;
        }

        $data['tabindex'] =!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '';
        $data['invalid']  =!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '';


        $template="";
        return xarTplProperty('base', 'checkboxmask', 'showinput', $data);
        //return $out;
    }

    function showOutput($args = array())
    {
        extract($args);
        
        if (!isset($value)) 
        {
            $value = $this->value;
        }
        
        if( !is_array($value) )
        {
            $value = maskExplode($value);
        }
        
        $this->getOptions();
        $numOptionsSelected=0;
        $options = array();            
        foreach( $this->options as $key => $option )
        {
            $option['checked'] = in_array($option['id'],$value);
            $options[$key] = $option;
            if( $option['checked'] )
            {
                $numOptionsSelected++;
            }
        }

        $data=array();
        $data['options'] = $options;
        $data['numOptionsSelected'] = $numOptionsSelected;

        $template="";
        return xarTplProperty('base', 'checkboxmask', 'showoutput', $data);
    }
     
}

function maskImplode ( $anArray )
{
    $output = '';
    if( is_array( $anArray ) )
    {
        foreach( $anArray as $entry )
        {
            $output .= $entry;
        }
    }
    return $output;
}

function maskExplode ( $aString )
{
    return explode(',',substr(chunk_split($aString, 1, ','), 0, -1));
}
?>