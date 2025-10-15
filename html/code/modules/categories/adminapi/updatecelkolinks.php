<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminApi;
use BadParameterException;
use CategoryWorker;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories adminapi updatecelkolinks function
 * @extends MethodClass<AdminApi>
 */
class UpdatecelkolinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Updates celko links
     * @param array<string,mixed> $args Parameter data array
     * @return bool|null Returns true on success, null on failure.
     * @throws \BadParameterException Thrown is invalid parameters have been given.
     * @see AdminApi::updatecelkolinks()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Argument check
        if (!isset($cid)) {
            $msg = $this->ml('Invalid Parameter Count');
            throw new BadParameterException(null, $msg);
        }

        //Get the information on the category and its parent
        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $cat = $worker->getcatinfo($cid);
        $catparent = $worker->getcatinfo($cat['parent_id']);

        $point_of_insertion = $catparent['right_id'];

        if ($type == 'create') {

            // Get database setup
            $dbconn = $this->db()->getConn();
            $xartable = $this->db()->getTables();
            $categoriestable = $xartable['categories'];
            $bindvars = [];
            $bindvars[1] = [];
            $bindvars[2] = [];
            $bindvars[3] = [];

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
            $bindvars[3] = [$point_of_insertion, $point_of_insertion + 1,$cid];

            for ($i = 1;$i < 4;$i++) {
                $result = $dbconn->Execute($SQLquery[$i], $bindvars[$i]);
                if (!$result) {
                    return;
                }
            }
        } elseif ($type == 'update') {
            $size = $cat['right_id'] - $cat['left_id'] + 1;
            $distance = $point_of_insertion - $cat['left_id'];

            // If necessary to move then evaluate
            if ($distance != 0) { // It�s Moving, baby!  Do the Evolution!
                if ($distance > 0) { // moving forward
                    $distance = $point_of_insertion - $cat['right_id'] - 1;
                    $deslocation_outside = -$size;
                    $between_string = ($cat['right_id'] + 1) . " AND " . ($point_of_insertion - 1);
                } else { // $distance < 0 (moving backward)
                    $deslocation_outside = $size;
                    $between_string = $point_of_insertion . " AND " . ($cat['left_id'] - 1);
                }

                // TODO: besides portability, also check performance here
                $SQLquery = "UPDATE $categoriestable SET
                           left_id = CASE
                            WHEN left_id BETWEEN " . $cat['left_id'] . " AND " . $cat['right_id'] . "
                               THEN left_id + ($distance)
                            WHEN left_id BETWEEN $between_string
                               THEN left_id + ($deslocation_outside)
                            ELSE left_id
                            END,
                          right_id = CASE
                            WHEN right_id BETWEEN " . $cat['left_id'] . " AND " . $cat['right_id'] . "
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
                if (!$result) {
                    return;
                }
            }
        }
        return true;
    }
}
