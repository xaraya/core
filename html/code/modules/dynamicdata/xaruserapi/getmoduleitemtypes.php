<?php
/**
 * Retrieve list of itemtypes of any module
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
sys::import('modules.dynamicdata.class.userapi');
/**
 * utility function to retrieve the list of item types of a module (if any)
 *
 * @uses Xaraya\DataObject\UserApi::getModuleItemTypes()
 * @todo remove this before it can propagate - too late, sorry
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return array<mixed> containing the item types and their description
 */
function dynamicdata_userapi_getmoduleitemtypes(array $args = [])
{
    extract($args);
    /** @var int $moduleid */
    // Argument checks
    if (empty($moduleid)) {
        throw new BadParameterException('moduleid');
    }
    $native ??= true;
    $extensions ??= true;

    return Xaraya\DataObject\UserApi::getModuleItemTypes($moduleid, $native, $extensions);
}
