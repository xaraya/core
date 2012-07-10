<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

function categories_adminapi_createcatdirectly($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((!isset($name))        ||
        (!isset($description)) ||
        (!isset($point_of_insertion)))
    {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null, $msg);
    }

    if (!isset($image)) {
        $image = '';
    }
    if (!isset($parent)) {
        $parent = 0;
    }

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $categoriestable = $xartable['categories'];
    $bindvars = array();
    $bindvars[1] = array();
    $bindvars[2] = array();
    $bindvars[3] = array();

    // Get next ID in table
    $nextId = $dbconn->GenId($categoriestable);

    /* Opening space for the new node */
    $SQLquery[1] = "UPDATE $categoriestable
                    SET right_id = right_id + 2
                    WHERE right_id >= ?";
    $bindvars[1][] = $point_of_insertion;

    $SQLquery[2] = "UPDATE $categoriestable
                    SET left_id = left_id + 2
                    WHERE left_id >= ?";
    $bindvars[2][] = $point_of_insertion;
    // Both can be transformed into just one SQL-statement, but i dont know if every database is SQL-92 compliant(?)

    $nextID = $dbconn->GenId($categoriestable);

    $SQLquery[3] = "INSERT INTO $categoriestable (
                                id,
                                name,
                                description,
                                image,
                                parent_id,
                                child_object,
                                left_id,
                                right_id)
                         VALUES (?,?,?,?,?,?,?,?)";
    $bindvars[3] = array($nextID, $name, $description, $image, $parent, $child_object, $point_of_insertion, $point_of_insertion + 1);

    for ($i=1;$i<4;$i++)
    {
        $result = $dbconn->Execute($SQLquery[$i],$bindvars[$i]);
        if (!$result) return;
    }


    // Call create hooks for categories, hitcount etc.
    $cid = $dbconn->PO_Insert_ID($categoriestable, 'id');

    //Hopefully Hooks will work-out better these args in the near future
    $args['module'] = 'categories';
    $args['itemtype'] = 2;
    $args['itemid'] = $cid;
    xarModCallHooks('item', 'create', $cid, $args);

    // Get cid to return
    return $cid;
}

?>
