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
function blocks_blocksapi_getinfo(Array $args=array())
{
        // must have at least type or instance 
        if (empty($args['instance']) && empty($args['type'])) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = array('type or instance','blocks', 'blocksapi', 'getinfo');
            throw new EmptyParameterException($vars, $msg);
        }
        
        $filter = array();
        if (isset($args['instance'])) {
            // filter by instance (solo, or module)
            $apitype = 'instances';
            // can be either block instance id or name
            if (is_numeric($args['instance'])) {
                $filter['block_id'] = $args['instance'];
            } elseif (is_string($args['instance'])) {
                $filter['name'] = $args['instance'];
            } else {
                $invalid[] = 'instance';
            }
            // optionally filter on instance state
            if (isset($args['state'])) {
                if (!empty($args['state']) && is_string($args['state'])) 
                    $args['state'] = array($args['state']);
                if (!empty($args['state']) && is_array($args['state'])) {
                    $filter['state'] = $args['state'];
                } else {
                    $invalid[] = 'state';
                }
            }
        } else {
            // filter by type (standalone block - solo, or module)
            $apitype = 'types';
            if (is_string($args['type'])) {
                $filter['type'] = $args['type'];
                if (isset($args['module'])) {
                    if (is_string($args['module'])) {
                        $filter['module'] = $args['module'];
                    } else {
                        $invalid[] = 'module';
                    }
                }
            }
        }
        // optionally filter on type state
        if (isset($args['type_state'])) {
            if (!empty($args['type_state']) && is_string($args['type_state'])) 
                $args['type_state'] = array($args['type_state']);
            if (!empty($args['type_state']) && is_array($args['type_state'])) {
                $filter['type_state'] = $args['type_state'];
            } else {
                $invalid[] = 'type_state';
            }
        }
        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = array(join(', ', $invalid), 'blocks', 'blocksapi', 'getinfo');
            throw new BadParameterException($vars, $msg);             
        }
        
        // set a cache key based on filter params
        $key = md5(serialize($filter));
        // see if we cached it already
        if (xarVarIsCached('Block.Info', $key)) {
            $blockinfo = xarVarGetCached('Block.Info', $key);
        } else {
            // call the types or instances api (both return the same data set)
            $blockinfo = xarMod::apiFunc('blocks', $apitype, 'getitem', $filter);
            // cache it
            xarVarSetCached('Block.Info', $key, $blockinfo);
        }
        // were we supplied with instance or type and/or module params to a non-existent block/type?
        if (empty($blockinfo))
            throw new DataNotFoundException();
        
        // from this point on we have an array of blockinfo suitable for use by xarBlock::render()

        // We're now safe to apply any over-rides (usually from block tag)
        // title over-ride             
        if (!empty($args['title']))
            $blockinfo['title'] = $args['title'];
        
        if (!isset($args['instance'])) {
            $content = $blockinfo['type_info'];
        } else {
            $content = $blockinfo['content'];
        }

        // caching over-rides
        if (isset($args['nocache']))
            $content['nocache'] = (bool) $args['nocache'];
        if (isset($args['pageshared']))
            $content['pageshared'] = $args['pageshared'];
        if (isset($args['usershared']))
            $content['usershared'] = $args['usershared'];
        if (isset($args['cacheexpire']))
            $content['cacheexpire'] = $args['cacheexpire'];
        
        // template over-rides from block tag 
        if (isset($args['template'])) {
            if (strpos($args['template'], ';') !== false) {
                list($box_template, $block_template) = explode(';', $args['template']);
            } else {
                $box_template = $args['template'];
            }
        } 
        // template over-ride from blockgroup tag        
        elseif (isset($args['box_template'])) {
            $box_template = $args['box_template'];
        }
        if (!empty($box_template))
            $content['box_template'] = $box_template;
        if (!empty($block_template))
            $content['block_template'] = $block_template;

        
        // content over-rides (block type specific params) 
        
        
        $blockinfo['content'] = $content;
        
        return $blockinfo;
}
?>