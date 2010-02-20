<?php
/**
 * Interfaces for Dynamic Properties
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

interface iDataProperty
{
    public function __construct(ObjectDescriptor $descriptor);
    public function checkInput($name = '', $value = null);
    public function fetchValue($name = '');
    public static function getRegistrationInfo();
    public function getValue();
    public function parseConfiguration($configuration = '');
    public function showConfiguration(Array $data = array());
    public function updateConfiguration(Array $data = array());
    public function setValue($value=null);
    public function showHidden(Array $args = array());
    public function showInput(Array $args = array());
    public function showLabel(Array $args = array());
//    CHECKME: public  or what?
//    public function _showPreset(Array $args = array());
    public function showOutput(Array $args = array());
    public function validateValue($value = null);
}

?>
