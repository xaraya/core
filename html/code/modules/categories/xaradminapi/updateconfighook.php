<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Update configuration for a module - hook for ('module','updateconfig','API')
 * Needs $extrainfo['cids'] from arguments, or 'cids' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return array Returns data array.
 */
function categories_adminapi_updateconfighook($args)
{
    sys::import('modules.dynamicdata.class.properties.master');
    $picker = DataPropertyMaster::getProperty(array('name' => 'categorypicker'));
    $picker->checkInput('basecid');

    extract($args);
    return $extrainfo;
}

?>