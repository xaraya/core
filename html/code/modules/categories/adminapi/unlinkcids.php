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
use Query;

/**
 * categories adminapi unlinkcids function
 * @extends MethodClass<AdminApi>
 */
class UnlinkcidsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete all links for a specific module, itemtype and list of cids (e.g. orphan links)
     * @param mixed $args ['modid'] ID of the module
     * @param mixed $args ['itemtype'] item type
     * @param mixed $args ['cids'] array of category ids
     * @return bool|null Returns true on success, null on failure
     * @throws \BadParameterException Thrown if invalid parameters have been given.
     * @see AdminApi::unlinkcids()
     */
    public function __invoke(array $args = [])
    {
        // Get arguments from argument array
        extract($args);

        // Argument check
        if (empty($modid) || !is_numeric($modid)) {
            $msg = $this->ml('Invalid Parameter Count');
            throw new BadParameterException(null, $msg);
        }

        // By convention an itemtype 0 means "all of them"
        if (!isset($itemtype) || !is_numeric($itemtype)) {
            $itemtype = 0;
        }

        // Set up the DELETE query and run
        $xartable = $this->db()->getTables();
        $q = new Query('DELETE', $xartable['categories_linkage']);
        $q->eq('module_id', (int) $modid);
        if (!empty($itemtype)) {
            $q->eq('itemtype', (int) $itemtype);
        }
        if (!empty($cids)) {
            $q->in('category_id', $cids);
        }
        $q->run();

        return true;
    }
}
