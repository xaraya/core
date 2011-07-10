<?php
/**
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1098.html
 */

    /**
     * returnPrivilege: adds or modifies a privilege coming from an external wizard .
     *
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  public
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['pid']<br/>
 *        string   $args['name']<br/>
 *        string   $args['realm']<br/>
 *        string   $args['module']<br/>
 *        string   $args['component']<br/>
 *        string   $args['instance']<br/>
 *        integer  $args['level']
    */

    function privileges_adminapi_returnprivilege(Array $args=array())
    {
        extract($args);

        if (!empty($instance) && is_array($instance)) {
            $instance = implode(':',$instance);
        }
        $instance = !empty($instance) ? $instance : "All";

        if(empty($pid)) {
            $pargs = array('name' => $name,
                           'realm' => $realm,
                           'module' => $module,
                           'module_id'=>xarMod::getID($module),
                           'component' => $component,
                           'instance' => $instance,
                           'level' => $level,
                           'parentid' => 0
                           );
            sys::import('modules.privileges.class.privilege');
            $priv = new xarPrivilege($pargs);
            if ($priv->add()) return $priv->getID();
        } else {
            sys::import('modules.privileges.class.privileges');
            $priv = xarPrivileges::getPrivilege($pid);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
            $priv->setModuleID($module);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
            if ($priv->update()) return $priv->getID();
        }
        return;
    }
?>
