<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/* Themes administration
 * @author Marty Vance
*/

// Tags
class TagRegistrationException extends RegistrationExceptions
{ 
    protected $message = 'The tag "#(1)" is not properly registered';
}

// Themes
class ThemeNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'A theme is missing, the theme name could not be determined in the current context';
}

?>
