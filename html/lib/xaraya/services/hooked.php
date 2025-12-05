<?php

/**
 * Hooks available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarHooks;
use BadParameterException;
use EmptyParameterException;

/**
 * For documentation purposes only - available via HookedTrait
 */
interface HookedInterface extends WrapperInterface
{
    public const SLICE = 'hooked';

    public function isAttached($observer, $subject, $itemtype = null, $scope = "0");
}

/**
 * Hooks available via methods
 */
trait HookedTrait
{
    use WrapperTrait;

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     * @return bool true if the observer is attached, false otherwise (for any reason)
    **/
    public function isAttached($observer, $subject, $itemtype = null, $scope = "0")
    {
        // Argument check
        if (empty($observer)) {
            throw new EmptyParameterException('observer');
        }
        if (empty($subject)) {
            throw new EmptyParameterException('subject');
        }
        if (!empty($itemtype) && !is_numeric($itemtype)) {
            throw new BadParameterException('itemtype');
        }
        if (!empty($scope) && !is_numeric($scope) && !is_string($scope)) {
            throw new EmptyParameterException('scope');
        }
        $xar = $this->getServicesClass();

        $observer_id = $xar->mod()->getRegID($observer);
        if (empty($observer_id)) {
            return false;
        }
        $subject_id = $xar->mod()->getRegID($subject);
        if (empty($subject_id)) {
            return false;
        }

        if (empty($itemtype)) {
            $itemtype = 0;
        }
        if (empty($scope)) {
            $scope = 0;
        }

        // Get database info
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();
        if (empty($xartable['hooks'])) {
            $xar->mod()->init();
            $xartable = $xar->db()->getTables();
        }
        $htable = $xartable['hooks'];
        $query = "SELECT observer, subject, itemtype, scope
                  FROM $htable
                  WHERE observer = ? AND subject = ?";
        $bindvars = [$observer_id, $subject_id, $itemtype, $scope];
        // check if a module is hooked to all (itemtype 0) when an itemtype is specified
        if (!empty($itemtype)) {
            $query .= " AND ( itemtype = ? OR itemtype = ? )";
            $bindvars[] = 0;
        } else {
            $query .= " AND itemtype = ?";
        }
        if (!empty($scope)) {
            $query .= " AND ( scope = ? OR scope = ? )";
            $bindvars[] = '0';
        } else {
            $query .= " AND scope = ?";
        }
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return false;
        }
        if (!$result->next()) {
            return false;
        }
        return true;
    }
}

/**
 * Access xarHooks::* methods (notify, ...)
 *
 * Available methods:
 * - notify()
 * - ...
 */
class HookedService implements HookedInterface
{
    use HookedTrait;
}
