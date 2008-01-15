<?php
/**
 * Interfaces for Dynamic Properties:
 *
 */

interface iDataProperty
{
    public function __construct(ObjectDescriptor $descriptor);
    public function checkInput($name = '', $value = null);
    public function fetchValue($name = '');
    public static function getRegistrationInfo();
    public function getValue();
    public function parseConfiguration($validation = '');
    public function showConfiguration(Array $data = array());
    public function updateConfiguration(Array $data = array());
    public function setValue($value);
    public function showHidden(Array $args = array());
    public function showInput(Array $args = array());
    public function showLabel(Array $args = array());
//    CHECKME: public  or what?
//    public function _showPreset(Array $args = array());
    public function showOutput(Array $args = array());
    public function validateValue($value = null);
}

?>