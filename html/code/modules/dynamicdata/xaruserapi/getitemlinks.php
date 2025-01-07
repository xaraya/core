<?php
/**
 * Pass individual item links
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
 * utility function to pass individual item links to whoever
 *
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $args['itemtype'] item type (optional)<br/>
 *        array    $args['itemids'] array of item ids to get
 * @return array<mixed> containing the itemlink(s) for the item(s).
 */
function dynamicdata_userapi_getitemlinks(array $args = [], $context = null)
{
    // use module urls here
    $args['linktype'] ??= 'user';
    $args['linkfunc'] ??= 'display';
    $userapi = xarMod::getAPI('dynamicdata');
    $userapi->setContext($context);
    $itemlinks = $userapi->getItemLinks($args);
    return $itemlinks;
}
