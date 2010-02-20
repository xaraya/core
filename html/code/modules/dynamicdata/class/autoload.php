<?php
/**
 * Dynamic Data Autoload
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Dynamic Data Autoload - moved this from xarAutoload::autoload_todo because it has lots :-)
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
        case 'dataobjectdescriptor':
            sys::import('modules.dynamicdata.class.objects.descriptor');
            return;
        case 'dataobjectlinks':
            sys::import('modules.dynamicdata.class.objects.links');
            return;
/* if we remove all sys::import from the classes someday ? :-)
        case 'idataobject':
        case 'idataobjectlist':
            sys::import('modules.dynamicdata.class.objects.interfaces');
            return;
*/

        case 'dataproperty':
            sys::import('modules.dynamicdata.class.properties.base');
            return;
        case 'datapropertymaster':
            sys::import('modules.dynamicdata.class.properties.master');
            return;
/* if we remove all sys::import from the classes someday ? :-)
        case 'idataproperty':
            sys::import('modules.dynamicdata.class.properties.interfaces');
            return;
*/

        case 'flattabledatastore':
            sys::import('xaraya.datastores.sql.flattable');
            return;
        case 'variabletabledatastore':
            sys::import('xaraya.datastores.sql.variabletable');
            return;
        case 'modulevariablesdatastore':
            sys::import('xaraya.datastores.sql.modulevariables');
            return;
        case 'dummydatastore':
            sys::import('xaraya.datastores.dummy');
            return;
        case 'hookdatastore':
            sys::import('xaraya.datastores.hook');
            return;
        case 'datastorelinks':
            sys::import('modules.dynamicdata.class.datastores.links');
            return;
/* if we remove all sys::import from the classes someday ? :-)
        case 'iddobject':
        case 'ibasicdatastore':
        case 'iordereddatastore':
        case 'isqldatastore':
            sys::import('xaraya.datastores.interface');
            return;
*/
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
