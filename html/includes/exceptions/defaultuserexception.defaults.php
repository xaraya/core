<?php
/**
 * File: $Id$
 *
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

    $this->defaults = array(
        'ALREADY_EXISTS' => array(
            'title' => xarML('Block type already exists'),
            'short' => xarML('An attempt was made to register a block type in a module that already exists.')),
        'BAD_DATA' => array(
            'title' => xarML('Bad Data'),
            'short' => xarML('The data provided was bad.'),
            'long' => xarML('The value provided during this operation could not be validated, or was not accepted for other reasons.')),
        'DUPLICATE_DATA' => array(
            'title' => xarML('Duplicate Data'),
            'short' => xarML('The data provided was a duplicate.'),
            'long' => xarML('A unique value was expected during this operation, but the value provided is a duplicate of an existing value.')),
        'FORBIDDEN_OPERATION' => array(
            'title' => xarML('Forbidden Operation'),
            'short' => xarML('The operation you are attempting is not allowed in the current circumstances.'),
            'long' => xarML("You may have clicked on the browser's back or refresh button and reattempted an operation that may not be repeated, or your browser may not have cookies enabled.")),
        'LOGIN_ERROR' => array(
            'title' => xarML('Login error'),
            'short' => xarML('A problem was encountered during the login process.'),
            'long' => xarML('No further information is available.')),
        'MISSING_DATA' => array(
            'title' => xarML('Missing Data'),
            'short' => xarML('The data is incomplete.'),
            'long' => xarML('A value was expected during this operation, but none was found.')),
        'MULTIPLE_INSTANCES' => array(
            'title' => xarML('Multiple instances'),
            'short' => xarML('A module contains more than one instance of the same block type.')),
        'WRONG_VERSION' => array(
            'title' => xarML('Wrong version'),
            'short' => xarML('The application version supplied is wrong.'))
    );
?>
