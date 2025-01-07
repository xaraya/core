<?php
/**
 * Retrieve a list of itemtypes of this module
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Utility function to retrieve the list of itemtypes of this module (if any).
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return array<mixed> the itemtypes of this module and their description *
 */
function dynamicdata_userapi_getitemtypes(array $args = [], $context = null)
{
    // use module urls here
    $args['linktype'] ??= 'user';
    $args['linkfunc'] ??= 'view';
    $userapi = xarMod::getAPI('dynamicdata');
    $userapi->setContext($context);
    $itemtypes = $userapi->getItemTypes($args);
    return $itemtypes;
}
