<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminApi;
use Exception;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges adminapi getcomponents function
 * @extends MethodClass<AdminApi>
 */
class GetcomponentsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * getcomponents: returns all the current components of a module.
     * Returns an array of all the components that have been registered for a given module.
     * The components correspond to masks in the masks table. Each one can be used to
     * construct a privilege's xarSecurity::check.
     * They are used to populate dropdowns in displays
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['module']  module name
     * @return array of component ids and names
     * @see AdminApi::getcomponents()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($modid)) {
            $components[] = ['id' => -2,
                'name' => 'All'];
        } else {
            $module = $this->mod()->getName($modid);

            // @checkme where is getcomponents() supposed to come from?
            // Do we have the components in a file?
            try {
                sys::import('modules.' . $module . '.security');
                return getcomponents();
            } catch (Exception $e) {
            }

            $modid = $this->mod()->getID($module);
        }

        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $query = "SELECT DISTINCT component
                      FROM " . $xartable['security_instances'] . "
                      WHERE module_id = ?
                      ORDER BY component";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([$modid]);
        $iter = $result->next();
        if (null == $result->fields) {
            $result->fields = [];
        }

        $components = [];
        if (count($result->fields) == 0) {
            $components[] = ['id' => 'All', 'name' => 'All'];
            //          $components[] = array('id' => 0, 'name' => 'None');
        } else {
            $components[] = ['id' => 'All', 'name' => 'All'];
            //          $components[] = array('id' => 0,'name' => 'None');
            $ind = 2;
            while ($iter) {
                $name = $result->getString(1);
                if (($name != 'All') && ($name != 'None')) {
                    $ind = $ind + 1;
                    $components[] = [
                        'id'   => $name,
                        'name' => $name,
                    ];
                }
                $iter = $result->next();
            }
        }
        return $components;
    }
}
