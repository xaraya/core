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

/**
 * creates a category
 *
 *  -- INPUT --
 * @param $args['name'] the name of the category
 * @param $args['description'] the description of the category
 * @param $args['image'] the (optional) image for the category
 *
 * @param $args['catexists'] = 0 means there were no categories during insertion
 *
 * If catexists == 0 then these do not to be set:
 *
 *    @param $args['refcid'] the ID of the reference category
 *
 *    These two parameters are set in relationship with the reference category:
 *
 *       @param $args['inorout'] Where the new category should be: IN or OUT
 *       @param $args['rightorleft'] Where the new category should be: RIGHT or LEFT
 *
 *  -- OUTPUT --
 * @returns int
 * @return category ID on success, false on failure
 */
function categories_adminapi_createcat($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if ((!isset($name))        ||
        (!isset($description)))
    {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null, $msg);
    }

    if (!isset($image)) {
        $image = '';
    }

     if (isset($catexists) && ($catexists != 0))
     {
        if ((!isset($refcid))      ||
            (!isset($rightorleft)) ||
            (!isset($inorout))
           )
        {
            $msg = xarML('Invalid Parameter Count');
            throw new BadParameterException(null, $msg);
        }
    }


    // Security check
    // Has to be redone later

    if(!xarSecurityCheck('AddCategories')) return;

    if (isset($catexists) && ($catexists == 0)) {

       $n = xarMod::apiFunc('categories', 'user', 'countcats', Array());

       if ($n == 0) {
               // Editing database doesn´t need to have a great performance
            // So the 2 extras updates are OK...
            $cid = xarMod::apiFunc('categories','admin','createcatdirectly',
                Array
                (
                    'point_of_insertion' => 1,
                    'name' => $name,
                    'description' => $description,
                    'image' => $image,
                    'parent' => 0
                )
            );
       } else {
            $msg = xarML('That category already exists');
            throw new BadParameterException(null, $msg);
       }
    } else {

       // Obtain current information on the reference category
       $cat = xarMod::apiFunc('categories', 'user', 'getcatinfo', Array('cid'=>$refcid));

       if ($cat == false) {
           xarSession::setVar('errormsg', xarML('That category does not exist'));
           return false;
       }

       $right = $cat['right'];
       $left = $cat['left'];

       /* Find out where you should put the new category in */
       if (
           !($point_of_insertion =
                xarMod::apiFunc('categories','admin','find_point_of_insertion',
                   Array('inorout' => $inorout,
                           'rightorleft' => $rightorleft,
                           'right' => $right,
                           'left' => $left
                   )
               )
          )
          )
       {
           return false;
       }

        /* Find the right parent for this category */
        if (strtolower($inorout) == 'in') {
            $parent_id = $refcid;
        } else {
            $parent_id = $cat['parent'];
        }
        $cid = xarMod::apiFunc('categories','admin','createcatdirectly',
               Array
            (
                'point_of_insertion' => $point_of_insertion,
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'parent' => $parent_id
            )
        );
    }
    // Let any hooks know that we have created a new category.
    $item['module'] = 'categories';
    $item['itemtype'] = $itemtype;
    $item['itemid'] = $cid;
    xarModCallHooks('item', 'create', $cid, $item);

    return $cid;

}

?>