<?php
/**
 * Mail Module
 *
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's classes
 */
function mail_classes_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        // Events
        // Controllers

        'phpmailer'            => 'modules.mail.class.phpmailer',
        'xarmailparser'        => 'modules.mail.class.decode',     // Not used
        'mail_mimedecode'      => 'modules.mail.class.mimeDecode', // Not used
        // FIXME: this will not work
        'smtp'                 => 'modules.mail.class.class.smtp',
    );
    
    if (isset($class_array[$class])) {
        sys::import($class_array[$class]);
        return true;
    }
    
    return false;
}

/**
 * Register this function for autoload on import
 */
if (class_exists('xarAutoload')) {
    xarAutoload::registerFunction('mail_classes_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
