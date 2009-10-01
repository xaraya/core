<?php
/**
 * Dynamic Data Autoload
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Dynamic Data Autoload - moved this from xarAutoload::autoload_todo because it has lots :-)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata
 */
function dynamicdata_autoload($class)
{
    static $classpathlist = array();

    $class = strtolower($class);

    // Some predefined classes
    switch ($class)
    {
        case 'dataobject':
            sys::import('modules.dynamicdata.class.objects.base');
            return;
        case 'dataobjectlist':
            sys::import('modules.dynamicdata.class.objects.list');
            return;
        case 'dataobjectmaster':
            sys::import('modules.dynamicdata.class.objects.master');
            return;

        case 'dataproperty':
            sys::import('modules.dynamicdata.class.properties.base');
            return;
        case 'datapropertymaster':
            sys::import('modules.dynamicdata.class.properties.master');
            return;

        case 'flattabledatastore':
            sys::import('xaraya.datastores.sql.flattable');
            return;
        case 'variabletabledatastore':
            sys::import('xaraya.datastores.sql.variabletable');
            return;
        case 'dummydatastore':
            sys::import('xaraya.datastores.dummy');
            return;

        default:
            break;
    }

    // We still haven't found it, so look at the properties now

    if (empty($classpathlist)) {
        // add all known property classes we might be looking for
        sys::import('modules.dynamicdata.class.properties.registration');
        $proptypes = PropertyRegistration::Retrieve();
        foreach ($proptypes as $proptype) {
            $name = strtolower($proptype['class']);
            // add sys::code() here to get the full path for module properties
            $classpathlist[$name] = sys::code() . $proptype['filepath'];
        }

        // add some more typical classes we might be looking for
        // ...
    }

    if (isset($classpathlist[$class]) && file_exists($classpathlist[$class])) {
        include_once($classpathlist[$class]);
        return;
    }

    return false;
}

/**
 * Register this function for autoload on import !?
 */
if (class_exists('xarAutoload')) {
    xarAutoload::registerFunction('dynamicdata_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
?>
