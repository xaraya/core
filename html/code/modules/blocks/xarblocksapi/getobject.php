<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * 
 * Gets an object from the blocks API
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @staticvar array $loaded Keeps track of clases that have been loaded
 * @param array $args Parameter data array
 * @return object Object to be returned
 * @throws BadParameterException
 * @throws FileNotFoundException
 * @throws ClassNotFoundException
 * @throws FunctionNotFoundException
 */
function blocks_blocksapi_getobject(Array $args=array())
{
        // must have a valid type
        if (empty($args['type']) || !is_string($args['type']))
            $invalid[] = 'type';
        // if we have a module, make sure it's valid
        if (!empty($args['module']) && !is_string($args['module']))
            $invalid[] = 'module';
        
        if (isset($args['block_method']) && !is_string($args['block_method']))
            $invalid[] = 'block_method';
        
        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = array(join(', ', $invalid), 'blocks', 'blocksapi', 'getobject');
            throw new BadParameterException($vars, $msg);        
        }
        
        // keep track of classes we've already loaded 
        static $loaded = array();        
        $key = !empty($args['module']) ? $args['module'] . ':' . $args['type'] : $args['type'];
        if (!empty($args['block_method'])) $key .= ':' . $args['block_method'];
        if (isset($loaded[$key])) {
            if (isset($args['block_method']))
                unset($args['block_method']); 
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
        if (!empty($args['block_method'])) {
            // method specific class
            // basepath/type/method.php 
            $typepaths[] = $basepath . $args['type'] . '/' . $args['block_method'] . '.php';
            $typeclass[] = $baseclass . ucfirst($args['block_method']);
            // basepath/type_method.php (legacy)
            $typepaths[] = $basepath . $args['type'] . '_' . $args['block_method'] . '.php';
            $typeclass[] = $baseclass . ucfirst($args['block_method']);
            if ($args['block_method'] != 'display') {
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

        if (!empty($args['block_method']) && !method_exists($classname, $args['block_method']))
            throw new FunctionNotFoundException($args['block_method']);

        // Load the block language files
        if(!xarMLSLoadTranslations($typepath)) {
            // What to do here? return doesnt seem right
            return;
        }
        
        if (isset($args['block_method']))
            unset($args['block_method']);         

        $object = new $classname($args);
        
        $loaded[$key] = $classname;        
        
        return $object;     
}
?>