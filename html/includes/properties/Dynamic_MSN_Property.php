<?php
/**
 * Dynamic MSN Messenger Property
 *
 * @package dynamicdata
 * @subpackage properties
 */

/**
 * include the base class
 *
 */
include_once "includes/properties/Dynamic_URLIcon_Property.php";

/**
 * handle MSN property
 *
 * @package dynamicdata
 */
class Dynamic_MSN_Property extends Dynamic_URLIcon_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // cfr. pnVarValidate in pnLegacy.php
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui'; // TODO: verify this !
            if (preg_match($regexp,$value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('MSN Messenger');
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
        $data['showoutput']=false;

        if (!empty($value)) {
// TODO: what's the link to use for MSN Messenger ??
            $link = "TODO: what's the link for MSN ?".$value;
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
        $data['link']     = $link;
        $data['tabindex'] = !empty($tabindex) ? $tabindex=$tabindex : '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;

/*       return '<input type="text"'.
               ' name="' . $name . '"' .
               ' value="'. (isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value)) . '"' .
               ' size="'. (!empty($size) ? $size : $this->size) . '"' .
               ' maxlength="'. (!empty($maxlength) ? $maxlength : $this->maxlength) . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'"' : '') .
               ' />' .
               (!empty($link) ? ' [ <a href="'.xarVarPrepForDisplay($link).'" target="preview">'.xarML('check').'</a> ]' : '') .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');

*/

      $fileName="modules/dynamicdata/xartemplates/includes/dynamic-msn_property.xd";
       return xarTplFile($fileName, $data);    }

    function showOutput($args = array())
    {
	    	extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        $data=array();
        $data['showoutput']=true;
        if (!empty($value)) {

            $link = "TODO: what's the link for MSN ?".$value;
            if (!empty($this->icon)) {
                $data['image']=xarVarPrepForDisplay($this->icon);
                $data['link']=$link;
/*          return '<a href="'.xarVarPrepForDisplay($link).'"><img src="'.xarVarPrepForDisplay($this->icon).'" alt="'.xarML('MSN').'"/></a>';
*/
            }
        }
       $fileName="modules/dynamicdata/xartemplates/includes/dynamic-msn_property.xd";
       return xarTplFile($fileName, $data);
/*     return '';
*/
    }
}

?>
