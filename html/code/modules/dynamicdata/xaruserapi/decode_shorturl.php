<?php
/**
 * Decode short URLS
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 * @param array    $params array of optional parameters<br/>
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function dynamicdata_userapi_decode_shorturl($params)
{
    static $objectcache = array();

    if (count($objectcache) == 0) {
        $objects = xarMod::apiFunc('dynamicdata','user','getobjects');
        foreach ($objects as $object) {
            $objectcache[$object['name']] = array('module_id'    => $object['moduleid'],
                                                  'itemtype' => $object['itemtype']);
        }
    }

    $args = array();

    $module = 'dynamicdata';

    // Check if we're dealing with an alias here
    if ($params[0] != $module) {
        $alias = xarModGetAlias($params[0]);
        // yup, looks like it
        if ($module == $alias) {
            if (isset($objectcache[$params[0]])) {
                $args['module_id'] = $objectcache[$params[0]]['module_id'];
                $args['itemtype'] = $objectcache[$params[0]]['itemtype'];
            } else {
                // we don't know this one...
                return;
            }
        } else {
            // we don't know this one...
            return;
        }
    }

    if (empty($params[1]) || preg_match('/^index/i',$params[1])) {
        if (count($args) > 0) {
            return array('view', $args);
        } else {
            return array('main', $args);
        }

    } elseif (preg_match('/^c(_?[0-9 +-]+)/',$params[1],$matches)) {
        $catid = $matches[1];
        $args['catid'] = $catid;
        return array('view', $args);

    } elseif (preg_match('/^(\d+)/',$params[1],$matches)) {
        $itemid = $matches[1];
        $args['itemid'] = $itemid;
        return array('display', $args);

    } elseif (isset($objectcache[$params[1]])) {
        $args['module_id'] = $objectcache[$params[1]]['module_id'];
        $args['itemtype'] = $objectcache[$params[1]]['itemtype'];
        if (empty($params[2]) || preg_match('/^index/i',$params[2])) {
            return array('view', $args);
        } elseif (preg_match('/^c(_?[0-9 +-]+)/',$params[2],$matches)) {
            $catid = $matches[1];
            $args['catid'] = $catid;
            return array('view', $args);
        } elseif (preg_match('/^(\d+)/',$params[2],$matches)) {
            $itemid = $matches[1];
            $args['itemid'] = $itemid;
            return array('display', $args);
        } else {
            // we don't know this one...
        }

    } else {
        // we don't know this one...
    }

    // default : return nothing -> no short URL

}

?>
