<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/* Themes administration
 * @author Marty Vance
*/

// Themes
class ThemeNotFoundException extends NotFoundExceptions
{ 
    protected $message = 'A theme is missing, the theme name could not be determined in the current context';
}

?>
