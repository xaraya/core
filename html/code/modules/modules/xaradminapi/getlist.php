<?php
/**
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/1.html
 */

/**
 * Get a list of modules that matches required criteria.
 * NOTE: this function has been superceded by modules_adminapi_getitems() function
 * which has expanded capabilites for filtering and sorting results
 *
 * Supported criteria are Mode, UserCapable, AdminCapable, Class, Category,
 * State.
 * Permitted values for Mode are XARMOD_MODE_SHARED and XARMOD_MODE_PER_SITE.
 * Permitted values for UserCapable are 0 or 1 or unset. If you specify the 1
 * value the result will contain all the installed modules that support the
 * user GUI.
 * Obviously you get the opposite result if you specify a 0 value for
 * UserCapable in filter.
 * If you don't care of UserCapable property, simply don't specify a value for
 * it.
 * The same thing is applied to the AdminCapable property.
 * Permitted values for Class and Category are the ones defined in RFC 13.
 *
 * Permitted values for State are XARMOD_STATE_ANY, XARMOD_STATE_UNINITIALISED,
 * XARMOD_STATE_INACTIVE, XARMOD_STATE_ACTIVE, XARMOD_STATE_MISSING,
 * XARMOD_STATE_UPGRADED, XARMOD_STATE_INSTALLED
 * The XARMOD_STATE_ANY means that any state is valid.
 * The default value of State is XARMOD_STATE_ACTIVE.
 * For other criteria there's no default value.
 * The orderBy parameter specifies the order by which is sorted the result
 * array, can be one of name, regid, class, category or a combination of them,
 * the default is name.
 * You can combine those fields to obtain a good ordered list simply by
 * separating them with the '/' character, i.e. if you want to order the list
 * first by class, then by category and lastly by name you pass
 * 'class/category/name' as orderBy parameter
 *
 * @author Marco Canini <marco@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        array    $args['filter'] array of criteria used to filter the entire list of installed<br/>
 *                 modules.<br/>
 *        integer  $args['startNum'] integer the start offset in the list<br/>
 *        integer  $args['numItems'] integer the length of the list<br/>
 *        string   $args['orderBy'] string the order type of the list
 * @return array array of module information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function modules_adminapi_getlist(Array $args=array())
{
    extract($args);
    static $validOrderFields = array('name' => 'mods', 'regid' => 'mods','id' => 'mods',
                                     'class' => 'mods', 'category' => 'mods');

    if (!isset($filter)) $filter = array();

    if (!is_array($filter)) throw new BadParameterException('filter','Parameter filter must be an array.');

    // build an array of arguments for getitems from the params supplied to this function
    $get = array();
    if (isset($startNum))
        $get['startnum'] = $startNum;
    if (isset($numItems))
        $get['numitems'] = $numItems;
        
    if (!empty($orderBy)) {
        $sort = array_map('trim', explode('/', $orderBy));
        foreach ($sort as $sortfield) {
            if (!array_key_exists($sortfield, $validOrderFields))
                throw new BadParameterException('orderBy');
        }
        $get['sort'] = $sort;
    } else {
        $get['sort'] = 'name';
    }
    
    if (isset($filter['UserCapable']))
        $get['user_capable'] = $filter['UserCapable'];
    if (isset($filter['AdminCapable']))
        $get['admin_capable'] = $filter['AdminCapable'];
    
    if (isset($filter['Class'])) {
        $get['modclass'] = $filter['Class'];
    } elseif (isset($class)) {
        $get['modclass'] = $class;
    }
    
    if (isset($filter['Category']))    
        $get['category'] = $filter['Category'];
    
    if (isset($filter['State'])) {
        $get['state'] = $filter['State'];
    } else {
        $get['state'] = XARMOD_STATE_ACTIVE;
    }
   
    return xarMod::apiFunc('modules', 'admin', 'getitems', $get);
}
?>