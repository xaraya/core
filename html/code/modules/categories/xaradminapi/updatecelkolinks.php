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
 */

function categories_adminapi_updatecelkolinks($args)
{
    extract($args);

    // Argument check
    if (!isset($cid)) {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterExcepton(null,$msg);
    }

    $cat = xarMod::apiFunc('categories', 'user', 'getcatinfo', array('cid'=>$cid));
    $catparent = xarMod::apiFunc('categories', 'user', 'getcatinfo', array('cid'=>$cat['parent']));
    $point_of_insertion = $catparent['right'];

    if ($type == 'create') {

        // Get database setup
        $dbconn = xarDB::getConn();
        $xartable =& xarDB::getTables();
        $categoriestable = $xartable['categories'];
        $bindvars = array();
        $bindvars[1] = array();
        $bindvars[2] = array();
        $bindvars[3] = array();

        /* Opening space for the new node */
        $SQLquery[1] = "UPDATE $categoriestable
                        SET right_id = right_id + 2
                        WHERE right_id >= ?";
        $bindvars[1][] = $point_of_insertion;

        $SQLquery[2] = "UPDATE $categoriestable
                        SET left_id = left_id + 2
                        WHERE left_id >= ?";
        $bindvars[2][] = $point_of_insertion;

        $SQLquery[3] = "UPDATE $categoriestable
                                    SET left_id = ?,
                                    right_id = ?
                        WHERE id >= ?";
        $bindvars[3] = array($point_of_insertion, $point_of_insertion + 1,$cid);

        for ($i=1;$i<4;$i++)
        {
            $result = $dbconn->Execute($SQLquery[$i],$bindvars[$i]);
            if (!$result) return;
        }
    } elseif ($type == 'update') {
       $size = $cat['right'] - $cat['left'] + 1;
       $distance = $point_of_insertion - $cat['left'];

       // If necessary to move then evaluate
       if ($distance != 0) { // It´s Moving, baby!  Do the Evolution!
          if ($distance > 0)
          { // moving forward
              $distance = $point_of_insertion - $cat['right'] - 1;
              $deslocation_outside = -$size;
              $between_string = ($cat['right'] + 1)." AND ".($point_of_insertion - 1);
          }
          else
          { // $distance < 0 (moving backward)
              $deslocation_outside = $size;
              $between_string = $point_of_insertion." AND ".($cat['left'] - 1);
          }

          // TODO: besided portability, also check performance here
          $SQLquery = "UPDATE $categoriestable SET
                       left_id = CASE
                        WHEN left_id BETWEEN ".$cat['left']." AND ".$cat['right']."
                           THEN left_id + ($distance)
                        WHEN left_id BETWEEN $between_string
                           THEN left_id + ($deslocation_outside)
                        ELSE left_id
                        END,
                      right_id = CASE
                        WHEN right_id BETWEEN ".$cat['left']." AND ".$cat['right']."
                           THEN right_id + ($distance)
                        WHEN right_id BETWEEN $between_string
                           THEN right_id + ($deslocation_outside)
                        ELSE right_id
                        END
                     ";
                     // This seems SQL-92 standard... Its a good test to see if
                     // the databases we are supporting are complying with it. This can be
                     // broken down in 3 simple UPDATES which shouldnt be a problem with any database

            $result = $dbconn->Execute($SQLquery);
            if (!$result) return;
        }
    }
    return true;
}

?>
