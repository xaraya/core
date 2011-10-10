<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
function blocks_blocksapi_getobject(Array $args=array())
{
        // must have a valid type
        if (empty($args['type']) || !is_string($args['type']))
            $invalid[] = 'type';
        // if we have a module, make sure it's valid
        if (!empty($args['module']) && !is_string($args['module']))
            $invalid[] = 'module';
        
        if (isset($args['method']) && !is_string($args['method']))
            $invalid[] = 'method';
        
        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = array(join(', ', $invalid), 'blocks', 'blocksapi', 'getobject');
            throw new BadParameterException($vars, $msg);        
        }
        
        // keep track of classes we've already loaded 
        static $loaded = array();        
        $key = !empty($args['module']) ? $args['module'] . ':' . $args['type'] : $args['type'];
        if (!empty($args['method'])) $key .= ':' . $args['method'];
        if (isset($loaded[$key])) {
            if (isset($args['method']))
                unset($args['method']); 
            $classname = $loaded[$key];
            return new $classname($args);
        }      
                
        if (!empty($args['module'])) {
            // import a block type class belonging to a module
            $basepath = sys::code() . 'modules/'.$args['module'].'/xarblocks/';
            $baseclass = ucfirst($args['module']) . '_' . ucfirst($args['type']) . 'Block';
        } else {
            // import a solo block type class 
            $basepath = sys::code() . 'blocks/';
            $baseclass = ucfirst($args['type']) . 'Block';
        }
        $typepaths = array();
        $typeclass = array();      
        if (!empty($args['method'])) {
            // method specific class
            // basepath/type/method.php 
            $typepaths[] = $basepath . $args['type'] . '/' . $args['method'] . '.php';
            $typeclass[] = $baseclass . ucfirst($args['method']);
            // basepath/type_method.php (legacy)
            $typepaths[] = $basepath . $args['type'] . '_' . $args['method'] . '.php';
            $typeclass[] = $baseclass . ucfirst($args['method']);
            if ($args['method'] != 'display') {
                // admin methods class
                // basepath/type/admin.php 
                $typepaths[] = $basepath . $args['type'] . '/admin.php';
                $typeclass[] = $baseclass . 'Admin';         
                // basepath/type_admin.php (legacy)
                $typepaths[] = $basepath . $args['type'] . '_admin.php';            
                $typeclass[] = $baseclass . 'Admin';
            }            
        } 
        // base class 
        // basepath/type/type.php 
        $typepaths[] = $basepath . $args['type'] . '/' . $args['type'] . '.php';           
        $typeclass[] = $baseclass;
        // basepath/type.php (legacy)
        $typepaths[] = $basepath . $args['type'] . '.php';                              
        $typeclass[] = $baseclass;
        
        foreach ($typepaths as $i => $typepath) {
            if (!file_exists($typepath)) continue;
            include_once $typepath;
            $classname = $typeclass[$i];
            break;
        }

        if (empty($classname))
            throw new FileNotFoundException($typepath);
        
        if (!class_exists($classname) || !is_subclass_of($classname, 'BasicBlock')) 
            throw new ClassNotFoundException($classname);

        if (!empty($args['method']) && !method_exists($classname, $args['method']))
            throw new FunctionNotFoundException($args['method']);

        // Load the block language files
        if(!xarMLSLoadTranslations($typepath)) {
            // What to do here? return doesnt seem right
            return;
        }
        
        if (isset($args['method']))
            unset($args['method']);         

        $object = new $classname($args);
        
        $loaded[$key] = $classname;        
        
        return $object;     
}
?>