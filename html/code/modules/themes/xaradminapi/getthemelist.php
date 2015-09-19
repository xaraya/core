<?php
/**
 * @package modules
 * @subpackage themes module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * 
 */
/**
 * Gets a list of themes that matches required criteria
 *
 * Supported criteria are: UserCapable, AdminCapable, Class, Category, State.
 * @author original - Marco Canini <marco@xaraya.com>,
 * @author andyv - modified
 * @param array    $args array of optional parameters<br/>
 *        string   $args['filter'] array of criteria used to filter the entire list of installed themes.<br/>
 *        integer  $args['startNum'] the start offset in the list<br/>
 *        integer  $args['numItems'] the length of the list<br/>
 *        string   $args['orderBy'] the order type of the list
 * @return array array of theme information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function themes_adminapi_getthemelist(Array $args=array())
{
    extract($args);
    static $validOrderFields = array('name' => 'themes', 'regid' => 'themes',
                                     'class' => 'infos');
    if (!isset($filter)) $filter = array();
    if (!is_array($filter)) 
        throw new BadParameterException('filter','Parameter filter must be an array.');

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
                throw new BadParameterException('orderBy',
                    'Parameter orderBy can contain only \'name\' or \'regid\' or \'class\' as items.');
        }
        $get['sort'] = $sort;
    } else {
        $get['sort'] = 'name';
    }        

    if (isset($filter['Class'])) {
        $get['class'] = $filter['Class'];
    } elseif (isset($Class)) {
        $get['class'] = $Class;
    }
    
    if (isset($filter['State'])) {
        $get['state'] = $filter['State'];
    } else {
        $get['state'] = XARTHEME_STATE_ACTIVE;
    }
    
    return xarMod::apiFunc('themes', 'admin', 'getitems', $get);

}
?>