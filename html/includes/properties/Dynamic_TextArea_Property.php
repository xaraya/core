<?php
/**
 * File: $Id$
 *
 * Dynamic Data Text Area Property
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
 * handle textarea property
 *
 * @package dynamicdata
 */
class Dynamic_TextArea_Property extends Dynamic_Property
{
    var $rows = 8;
    var $cols = 50;
    var $wrap = 'soft';

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
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
/*        return '<textarea' .
               ' name="' . $name . '"' .
               ' rows="'. (!empty($rows) ? $rows : $this->rows) . '"' .
               ' cols="'. (!empty($cols) ? $cols : $this->cols) . '"' .
               ' wrap="'. (!empty($wrap) ? $wrap : $this->wrap) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               '>' . (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '</textarea>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
*/
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['rows']     = !empty($rows) ? $rows : $this->rows;
        $data['cols']     = !empty($cols) ? $cols : $this->cols; 
        $data['wrap']     = !empty($wrap) ? $wrap : $this->wrap;

        $template="textarea";
        return xarTplModule('dynamicdata', 'admin', 'showinput', $data , $template);

    }

    function showOutput($args = array())
    {
         extract($args);
         $data=array();
         
         if (isset($value)) {
            //return xarVarPrepHTMLDisplay($value);
            $data['value'] = xarVarPrepHTMLDisplay($value);
         } else {
            //return xarVarPrepHTMLDisplay($this->value);
            $data['value'] = xarVarPrepHTMLDisplay($this->value);
         }
         $template="textarea";
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
		$args['rows'] = 8;	 
		$aliases[] = array(
							'id'         => 4,
							'name'       => 'textarea_medium',
							'label'      => 'Medium Text Area',
							'format'     => '4',
							'validation' => '',
							'source'     => '',
							'dependancies' => '',
							'requiresmodule' => '',
							'args' => serialize( $args ),
							
							// ...
						   );
	 
		$args['rows'] = 20;	 
		$aliases[] = array(
                              'id'         => 5,
                              'name'       => 'textarea_large',
                              'label'      => 'Large Text Area',
                              'format'     => '5',
                              'validation' => '',
							'source'     => '',
							'dependancies' => '',
							'requiresmodule' => '',
							'args' => serialize( $args ),
							// ...
						   );

		$args['rows'] = 2;	 
	 	$baseInfo = array(
							'id'         => 3,
							'name'       => 'textarea_small',
							'label'      => 'Small Text Area',
							'format'     => '3',
							'validation' => '',
							'source'     => '',
							'dependancies' => '',
							'requiresmodule' => '',
							'aliases' => $aliases,
							'args' => serialize( $args ),
							// ...
						   );
		return $baseInfo;
	 }

}

?>
