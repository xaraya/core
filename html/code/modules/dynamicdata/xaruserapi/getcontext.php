<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
/**
 * get an array of context data for a module using dynamicdata
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string   $module  name of the module dynamicdata is working for
 * @return array<mixed> of data
 */
function dynamicdata_userapi_getcontext($args = ['module' => 'dynamicdata'], $context = null)
{
    // @todo use incoming $context here too?
    extract($args);
    /** @var ?string $module */
    $module ??= 'dynamicdata';
    $context = xarSession::getVar('ddcontext.' . $module);
    $context['tplmodule'] = $module;
    return $context;
}
