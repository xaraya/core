<?php
/**
 * Import a property type
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
 * @return array<mixed>|void empty array for the template display
 */
function dynamicdata_admin_importpropertytypes(array $args = [], $context = null)
{
    // Security
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    $args['flush'] = 'false';
    $success = xarMod::apiFunc('dynamicdata', 'admin', 'importpropertytypes', $args, $context);

    return [];
}
