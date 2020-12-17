<?php
/**
 * @package modules\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1.html
 */
function modules_adminapi_getitems(Array $args=array())
{
    extract($args);
    
    // Set some defaults
    if (!isset($state)) $state = xarMod::STATE_ACTIVE;
    if (!isset($include_core)) $include_core = true;
    if (!isset($sort)) $sort = 'name ASC';
        
    // Determine the table we are going to use
    $tables =& xarDB::getTables();
    $q = new Query('SELECT', $tables['modules']);
    $q->addfields("id as systemid, regid, name, directory, version, class, category, state,user_capable, admin_capable");

    if (!empty($regid)) $q->eq('regid', $regid);
    
    if (!empty($name)) {
        if (is_array($name)) {
            $q->in('name', $name);
        } else {             
            $q->eq('name', $name);
        }
    }
    
    if ($state != xarMod::STATE_ANY) {
        if ($state != xarMod::STATE_INSTALLED) {
            $q->eq('state', $state);
        } else {
            $q->ne('state', xarMod::STATE_UNINITIALISED);
            $q->lt('state', xarMod::STATE_MISSING_FROM_INACTIVE);
            $q->ne('state', xarMod::STATE_MISSING_FROM_UNINITIALISED);
        }    
    }
    
    if (!empty($modclass)) $q->eq('class', $modclass);
    if (!empty($category)) $q->eq('category', $category);
    
    if (!$include_core) {
        $coremods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules','categories');
        $q->notin('name', $coremods);
    }

    if (!empty($user_capable)) $q->eq('user_capable', (int)$user_capable);
    if (!empty($admin_capable)) $q->eq('admin_capable', (int)$admin_capable);

    if (!is_array($sort))
        $sort = strpos($sort, ',') !== false ? array_map('trim', explode(',', $sort)) : array(trim($sort));
    foreach ($sort as $pairs) {
        list($sortfield, $sortorder) = array_map('trim', array_pad(explode(' ', $pairs), 2, 'ASC'));
        if (!isset($select[$sortfield]) || isset($orderby[$sortfield])) continue;
        $orderby[$sortfield] = $select[$sortfield] . ' ' . strtoupper($sortorder);
    }
    // We just order by name for now
    $q->setorder('name', 'ASC');

    if (!empty($numitems)) {
        $q->setrowstodo($numitems);
        if (empty($startnum)) $startnum = 1;
        $q->setstartat($startnum - 1);
    }
    $q->run();

    $items = array();
    foreach ($q->output() as $item) {

        if (xarVar::isCached('Mod.Infos', $item['regid'])) {
            // Merge cached info with db info 
            $item += xarVar::getCached('Mod.Infos', $item['regid']);
        } else {
            $item['displayname'] = xarMod::getDisplayName($item['name']);
            $item['displaydescription'] = xarMod::getDisplayDescription($item['name']);
            // Shortcut for os prepared directory
            $item['osdirectory'] = xarVar::prepForOS($item['directory']);

            xarVar::setCached('Mod.BaseInfos', $item['name'], $item);            
                   
            $fileinfo = xarMod::getFileInfo($item['osdirectory']);
            if (isset($fileinfo)) {
                $item = array_merge($fileinfo, $item);
                xarVar::setCached('Mod.Infos', $item['regid'], $item);
                switch ($item['state']) {
                case xarMod::STATE_MISSING_FROM_UNINITIALISED:
                    $item['state'] = xarMod::STATE_UNINITIALISED;
                    break;
                case xarMod::STATE_MISSING_FROM_INACTIVE:
                    $item['state'] = xarMod::STATE_INACTIVE;
                    break;
                case xarMod::STATE_MISSING_FROM_ACTIVE:
                    $item['state'] = xarMod::STATE_ACTIVE;
                    break;
                case xarMod::STATE_MISSING_FROM_UPGRADED:
                    $item['state'] = xarMod::STATE_UPGRADED;
                    break;
                }
            }        
        }
        $items[] = $item;    
    }

    return $items;
}
?>