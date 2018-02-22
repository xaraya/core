<?php
/**
 * Base Module
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's classes
 */
function base_classes_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        // Events
        'baseeventobserver'             => 'modules.base.class.eventobservers.event',
        'baseeventsubject'              => 'modules.base.class.eventsubjects.event',
        'baseserverrequestsubject'      => 'modules.base.class.eventsubjects.serverrequest',
        'basesessioncreateubject'       => 'modules.base.class.eventsubjects.sessioncreate',
        // Controllers
        'authsystemshortcontroller'     => 'modules.authsystem.controllers.short',

        'feedparser'                    => 'modules.base.class.feedParser',
        'baseitemwaitingcontentsubject' => 'modules.base.class.hooksubjectsitemwaitingcontent',
        'xartplpager'                   => 'modules.base.class.pager',
        'xarcurl'                       => 'modules.base.class.xarCurl',
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
    xarAutoload::registerFunction('base_classes_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>