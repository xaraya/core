<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Michel Dalle <mikespub@xaraya.com>
 */

/**
 * get category by name
 *
 * @param $args['name'] name of the category to retrieve
 * @param $args['return_itself'] =Boolean= return the cid itself (default true)
 * @param $args['getchildren'] =Boolean= get children of category (default false)
 * @param $args['getparents'] =Boolean= get parents of category (default false)
 * @returns array
 * @return array of category info arrays, false on failure
 */
function categories_userapi_getcatbyname($args)
{
    // Extract arguments
    extract($args);

    // Argument validation
    if (!isset($name) && !is_string($name)) {
        $msg = xarML('Invalid name for #(1) function #(2)() in module #(3)',
                     'userapi', 'getcatbyname', 'category');
        throw new BadParameterException(null, $msg);
    }

    // Check for optional arguments
    if (!isset($return_itself)) {
        $return_itself = true;
    }
    if (!isset($getchildren)) {
        $getchildren = false;
    }
    if (!isset($getparents)) {
        $getparents = false;
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $categoriestable = $xartable['categories'];

    $SQLquery = "SELECT id
                 FROM $categoriestable
                 WHERE name = ?";
    $bindvars = array($name);
    $result = $dbconn->Execute($SQLquery,$bindvars);
    if (!$result) return;

    // Check for no rows found
    if ($result->EOF) {
        $result->Close();
        //$msg = xarML('This category does not exist');
        //throw new BadParameterException(null, $msg);
    }

    // Obtain the owner information from the result set
    list($cid) = $result->fields;

    // Close result set
    $result->Close();

    // Get category information
    $category = xarMod::apiFunc('categories',
                              'user',
                              'getcat',
                              Array('cid' => $cid,
                                    'return_itself' => $return_itself,
                                    'getparents' => $getparents,
                                    'getchildren' => $getchildren));

    return $category;
}

?>
