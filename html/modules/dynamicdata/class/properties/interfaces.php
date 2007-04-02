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
    public function parseValidation($validation = '');
    public function setValue($value);
    public function showInput(Array $args = array());
    public function showOutput(Array $args = array());
    public function showValidation(Array $args = array());
    public function updateValidation(Array $args = array());
    public function validateValue($value = null);
}

?>