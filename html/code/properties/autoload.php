<?php
/**
 * Property Autoload
 *
 * @package properties
 * @subpackage property autoload
 * @category Xaraya Property
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Autoload function for standalone properties
 */
function xaraya_properties_autoload($class)
{
    $class = strtolower($class);

    $class_array = array(
        'codemirrorproperty'          => 'properties.codemirror.main',
        'datetimeproperty'            => 'properties.datetime.main',
        'datetimepropertyinstall'     => 'properties.datetime.install',
        'iconcheckboxproperty'        => 'properties.iconcheckbox.main',
        'iconcheckboxpropertyinstall' => 'properties.iconcheckbox.install',
        'icondropdownproperty'        => 'properties.icondropdown.main',
        'icondropdownpropertyinstall' => 'properties.icondropdown.install',
        'languagesproperty'           => 'properties.languages.main',
        'languagespropertyinstall'    => 'properties.languages.install',
        'listingproperty'             => 'properties.listing.main',
        'listingpropertyinstall'      => 'properties.listing.install',
        'pagerproperty'               => 'properties.pager.main',
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
    xarAutoload::registerFunction('xaraya_properties_autoload');
} else {
    // guess you'll have to register it yourself :-)
}
