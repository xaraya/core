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
        'FILE_NOT_EXIST' => array(
            'title' => xarML('File does not exist'),
            'short' => xarML('An operation requires a file that cannot be found.'),
            'long' => xarML('The file may be missing, or its name may have changed.')),
        'CANNOT_CONTINUE' => array(
            'title' => xarML('Operation Halted'),
            'short' => xarML('This operation cannot be completed under the present circumstances.'),
            'long' => xarML('This operation was stopped because, although it is not forbidden, it cannot be completed in this context.')),
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
        'NO_PRIVILEGES' => array(
            'title' => xarML('Privileges Error'),
            'short' => xarML('You do not have privileges for this operation.'),
            'long' => xarML('The operation you are attempting requires privileges that you do not have. Contact the systems/site administrator to request access.')),
        'NOT_LOGGED_IN' => array(
            'title' => xarML('Not logged in'),
            'short' => xarML('You are attempting an operation that is not allowed for the Anonymous user.'),
            'long' => xarML('An operation was encountered that requires the user to be logged in. If you are currently logged in please report this as a bug.')),
        'NOT_FOUND' => array(
            'title' => xarML('Item not found'),
            'short' => xarML('An unexpected empty result occurred.'),
            'long' => xarML('An operation that should have returned a result instead came up empty.')),
        'WRONG_VERSION' => array(
            'title' => xarML('Wrong version'),
            'short' => xarML('The application version supplied is wrong.'))
    );
?>