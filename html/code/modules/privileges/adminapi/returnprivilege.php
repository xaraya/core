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
use xarPrivilege;
use xarPrivileges;

/**
 * privileges adminapi returnprivilege function
 * @extends MethodClass<AdminApi>
 */
class ReturnprivilegeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * returnPrivilege: adds or modifies a privilege coming from an external wizard .
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['pid']<br/>
     * string   $args['name']<br/>
     * string   $args['realm']<br/>
     * string   $args['module']<br/>
     * string   $args['component']<br/>
     * string   $args['instance']<br/>
     * integer  $args['level']
     * @see AdminApi::returnprivilege()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (!empty($instance) && is_array($instance)) {
            $instance = implode(':', $instance);
        }
        $instance = !empty($instance) ? $instance : "All";

        if (empty($pid)) {
            $pargs = ['name' => $name,
                'realm' => $realm,
                'module' => $module,
                'module_id' => $this->mod()->getID($module),
                'component' => $component,
                'instance' => $instance,
                'level' => $level,
                'parentid' => 0,
            ];
            $priv = new xarPrivilege($pargs);
            if ($priv->add()) {
                return $priv->getID();
            }
        } else {
            $priv = xarPrivileges::getPrivilege($pid);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
            $priv->setModuleID($module);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
            if ($priv->update()) {
                return $priv->getID();
            }
        }
        return;
    }
}
