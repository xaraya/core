<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes module
 * @link http://xaraya.com/index.php/release/70.html
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
