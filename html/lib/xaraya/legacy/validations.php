<?php
/**
 * @package core
 * @subpackage legacy
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * 
 */

    // Legacy validations by dataproperty
    
    function dropdown($validation)
    {
        $options = array();
        try {
            foreach($validation as $id => $name) {
                array_push($options, array('id' => $id, 'name' => $name));
            }
        } catch (Exception $s) {
            $options = array();
        }
        return $options;
    }
?>