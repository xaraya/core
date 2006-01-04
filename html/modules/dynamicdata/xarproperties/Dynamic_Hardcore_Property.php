<?php
/**
 * Dynamic HTMLArea Property
 *
 * Utilizes JavaScript based WYSIWYG Editor, HTMLArea
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * handle textarea property
 *
 * @package dynamicdata
 */
class Dynamic_Hardcore_Property extends Dynamic_Property
{
    public $rows = 8;
    public $cols = 50;
    public $wrap = 'soft';

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
    // TODO: allowable HTML ?
        $this->value = $value;
        return true;
    }

//    function showInput($name = '', $value = null, $rows = 8, $cols = 50, $wrap = 'soft', $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);

        if (!isset($name) )
        {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? $value : $this->value;
        $data['tabindex'] = !empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        //$data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        //$data['size']     = !empty($size) ? $size : $this->size;

        $template="hardcore";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);
        
    }

    function showOutput($args = array())
    {
         extract($args);
        if (isset($value)) {
            return xarVarPrepHTMLDisplay($value);
        } else {
            return xarVarPrepHTMLDisplay($this->value);
        }
    }


    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args['rows'] = 20;
        $args['cols'] = 80;
         $baseInfo = array(
                            'id'         => 204,
                            'name'       => 'hardcore',
                            'label'      => 'Harcore GUI Editor',
                            'format'     => '5',
                            'validation' => '',
                            'source'     => '',
                            'dependancies' => 'hardcore/webeditor/webeditor.js',
                            'requiresmodule' => '',
                            'aliases'        => '',
                            'args' => serialize( $args ),
                            // ...
                           );
        return $baseInfo;
     }

}
?>
