<?php
/**
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */

/**
 * Gets a list of themes that matches required criteria.
 * NOTE: this function has been superceded by themes_adminapi_getitems() function
 * which has expanded capabilites for filtering and sorting results
 *
 * Supported criteria are UserCapable, AdminCapable, Class, Category, State.
 *
 * Permitted values for UserCapable are 0 or 1 or unset. If you specify the 1
 * value the result will contain all the installed themes that support the
 * user GUI.
 * Obviously you get the opposite result if you specify a 0 value for
 * UserCapable in filter.
 * If you don't care of UserCapable property, simply don't specify a value for
 * it.
 * The same thing is applied to the AdminCapable property.
 * Permitted values for Class and Category are the ones defined in the proper
 * RFC.
 * Permitted values for State are XARTHEME_STATE_ANY, XARTHEME_STATE_UNINITIALISED,
 * XARTHEME_STATE_INACTIVE, XARTHEME_STATE_ACTIVE, XARTHEME_STATE_MISSING,
 * XARTHEME_STATE_UPGRADED.
 * The XARTHEME_STATE_ANY means that any state is valid.
 * The default value of State is XARTHEME_STATE_ACTIVE.
 * For other criteria there's no default value.
 * The orderBy parameter specifies the order by which is sorted the result
 * array, can be one of name, regid, class, category or a combination of them,
 * the default is name.
 * You can combine those fields to obtain a good ordered list simply by
 * separating them with the '/' character, i.e. if you want to order the list
 * first by class, then by category and lastly by name you pass
 * 'class/category/name' as orderBy parameter
 *
 * @author Marco Canini <marco.canini@postnuke.com>
 *        array    $args['filter'] array of criteria used to filter the entire list of installed<br/>
 *                 themes.<br/>
 *        integer  $args['startNum'] the start offset in the list<br/>
 *        integer  $args['numItems'] the length of the list<br/>
 *        string   $args['orderBy'] the order type of the list
 * @return array of theme information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function themes_adminapi_getlist($filter = array(), $startNum = NULL, $numItems = NULL, $orderBy = 'name')
{
    if (!is_array($filter)) 
        throw new BadParameterException('filter','Parameter filter must be an array.');
    // this function is identical to getthemelist
    // the only difference is default state here is any instead of active 
    if (!isset($filter['State']))
        $filter['State'] = XARTHEME_STATE_ANY;
    
    $get = array(
        'filter' => $filter,
        'startNum' => $startNum,
        'numItems' => $numItems,
        'orderBy' => $orderBy,
    );
    
    return xarMod::apiFunc('themes', 'admin', 'getthemelist', $get);

}
?>