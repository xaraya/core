<?php
/**
 * Dynamicdata Module
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for this module's classes
 * @deprecated 2.4.1 use composer autoload instead
 */
function dynamicdata_classes_autoload($class)
{
    $class = strtolower($class);

    $class_array = [
        // Events
        // Controllers

        'dataobject'                => 'modules.dynamicdata.class.objects.base',
        'dataobjectlist'            => 'modules.dynamicdata.class.objects.list',
        'dataobjectmaster'          => 'modules.dynamicdata.class.objects.master',
        'dataobjectdescriptor'      => 'modules.dynamicdata.class.objects.descriptor',
        'virtualobjectdescriptor'   => 'modules.dynamicdata.class.objects.virtual',
        'dataobjectlinks'           => 'modules.dynamicdata.class.objects.links',
        'idataobject'               => 'modules.dynamicdata.class.objects.interfaces',

        'dataproperty'              => 'modules.dynamicdata.class.properties.base',
        'datapropertymaster'        => 'modules.dynamicdata.class.properties.master',
        'idataproperty'             => 'modules.dynamicdata.class.properties.interfaces',
        'propertyregistration'      => 'modules.dynamicdata.class.properties.registration',

        'relationaldatastore'       => 'xaraya.datastores.sql.relational',
        'variabletabledatastore'    => 'xaraya.datastores.sql.variabletable',
        'modulevariablesdatastore'  => 'xaraya.datastores.sql.modulevariables',
        'dummydatastore'            => 'xaraya.datastores.virtual',
        'hookdatastore'             => 'xaraya.datastores.hook',
        'functiondatastore'         => 'xaraya.datastores.function',
        'datastorelinks'            => 'modules.dynamicdata.class.datastores.links',

    ];

    if (isset($class_array[$class])) {
        sys::import($class_array[$class]);
        return true;
    }
    return false;

    // We still haven't found it, so look at the properties now
    /**
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
     */
}

/**
 * Register this function for autoload on import
 */
if (class_exists('xarAutoload')) {
    xarAutoload::registerFunction('dynamicdata_classes_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
