<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author John Cox
 */
sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle the StateList property
 *
 * Show a dropdown of US states
 */
class StateListProperty extends SelectProperty
{
    public $id         = 43;
    public $name       = 'statelisting';
    public $desc       = 'State Dropdown';

   function getOptions()
   {
        $options = array();
        $options[] = array('id' =>'Alabama', 'name' =>'Alabama');
        $options[] = array('id' =>'Alaska', 'name' =>'Alaska');
        $options[] = array('id' =>'Arizona', 'name' =>'Arizona');
        $options[] = array('id' =>'Arkansas', 'name' =>'Arkansas');
        $options[] = array('id' =>'California', 'name' =>'California');
        $options[] = array('id' =>'Colorado', 'name' =>'Colorado');
        $options[] = array('id' =>'Connecticut', 'name' =>'Connecticut');
        $options[] = array('id' =>'Delaware', 'name' =>'Delaware');
        $options[] = array('id' =>'District of Columbia', 'name' =>'District of Columbia');
        $options[] = array('id' =>'Florida', 'name' =>'Florida');
        $options[] = array('id' =>'Georgia', 'name' =>'Georgia');
        $options[] = array('id' =>'Hawaii', 'name' =>'Hawaii');
        $options[] = array('id' =>'Idaho', 'name' =>'Idaho');
        $options[] = array('id' =>'Illinois', 'name' =>'Illinois');
        $options[] = array('id' =>'Indiana', 'name' =>'Indiana');
        $options[] = array('id' =>'Iowa', 'name' =>'Iowa');
        $options[] = array('id' =>'Kansas', 'name' =>'Kansas');
        $options[] = array('id' =>'Kentucky', 'name' =>'Kentucky');
        $options[] = array('id' =>'Louisiana', 'name' =>'Louisiana');
        $options[] = array('id' =>'Maine', 'name' =>'Maine');
        $options[] = array('id' =>'Maryland', 'name' =>'Maryland');
        $options[] = array('id' =>'Massachusetts', 'name' =>'Massachusetts');
        $options[] = array('id' =>'Michigan', 'name' =>'Michigan');
        $options[] = array('id' =>'Minnesota', 'name' =>'Minnesota');
        $options[] = array('id' =>'Mississippi', 'name' =>'Mississippi');
        $options[] = array('id' =>'Missouri', 'name' =>'Missouri');
        $options[] = array('id' =>'Montana', 'name' =>'Montana');
        $options[] = array('id' =>'Nebraska', 'name' =>'Nebraska');
        $options[] = array('id' =>'Nevada', 'name' =>'Nevada');
        $options[] = array('id' =>'New Hampshire', 'name' =>'New Hampshire');
        $options[] = array('id' =>'New Jersey', 'name' =>'New Jersey');
        $options[] = array('id' =>'New Mexico', 'name' =>'New Mexico');
        $options[] = array('id' =>'New York', 'name' =>'New York');
        $options[] = array('id' =>'North Carolina', 'name' =>'North Carolina');
        $options[] = array('id' =>'North Dakota', 'name' =>'North Dakota');
        $options[] = array('id' =>'Ohio', 'name' =>'Ohio');
        $options[] = array('id' =>'Oklahoma', 'name' =>'Oklahoma');
        $options[] = array('id' =>'Oregon', 'name' =>'Oregon');
        $options[] = array('id' =>'Pennsylvania', 'name' =>'Pennsylvania');
        $options[] = array('id' =>'Rhode Island', 'name' =>'Rhode Island');
        $options[] = array('id' =>'South Carolina', 'name' =>'South Carolina');
        $options[] = array('id' =>'South Dakota', 'name' =>'South Dakota');
        $options[] = array('id' =>'Tennessee', 'name' =>'Tennessee');
        $options[] = array('id' =>'Texas', 'name' =>'Texas');
        $options[] = array('id' =>'Utah', 'name' =>'Utah');
        $options[] = array('id' =>'Vermont', 'name' =>'Vermont');
        $options[] = array('id' =>'Virginia', 'name' =>'Virginia');
        $options[] = array('id' =>'Washington', 'name' =>'Washington');
        $options[] = array('id' =>'West Virginia', 'name' =>'West Virginia');
        $options[] = array('id' =>'Wisconsin', 'name' =>'Wisconsin');
        $options[] = array('id' =>'Wyoming', 'name' =>'Wyoming');
        $options[] = array('id' =>'Alberta', 'name' =>'Alberta');
        $options[] = array('id' =>'British Columbia', 'name' =>'British Columbia');
        $options[] = array('id' =>'Manitoba', 'name' =>'Manitoba');
        $options[] = array('id' =>'New Brunswick', 'name' =>'New Brunswick');
        $options[] = array('id' =>'Newfoundland and Labrador', 'name' =>'Newfoundland and Labrador');
        $options[] = array('id' =>'Northwest Territories', 'name' =>'Northwest Territories');
        $options[] = array('id' =>'Nova Scotia', 'name' =>'Nova Scotia');
        $options[] = array('id' =>'Nunavut', 'name' =>'Nunavut');
        $options[] = array('id' =>'Ontario', 'name' =>'Ontario');
        $options[] = array('id' =>'Prince Edward Island', 'name' =>'Prince Edward Island');
        $options[] = array('id' =>'Quebec', 'name' =>'Quebec');
        $options[] = array('id' =>'Saskatchewan', 'name' =>'Saskatchewan');
        $options[] = array('id' =>'Yukon Territory', 'name' =>'Yukon Territory');
        $options[] = array('id' =>'Australian Capital Territory', 'name' =>'Australian Capital Territory');
        $options[] = array('id' =>'New South Wales', 'name' =>'New South Wales');
        $options[] = array('id' =>'Northern Territory', 'name' =>'Northern Territory');
        $options[] = array('id' =>'Queensland', 'name' =>'Queensland');
        $options[] = array('id' =>'South Australia', 'name' =>'South Australia');
        $options[] = array('id' =>'Tasmania', 'name' =>'Tasmania');
        $options[] = array('id' =>'Victoria', 'name' =>'Victoria');
        $options[] = array('id' =>'Western Australia', 'name' =>'Western Australia');
        $options[] = array('id' =>'Other', 'name' =>'Other');
        return $options;
    }
}

?>
