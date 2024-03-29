<?php
/**
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
 * Update hooks when migrating module items
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args
 * with
 *     array $args['from'] the module id and itemtype for the original items
 *     array $args['to'] the module id and itemtype for the new items
 *     array $args['hookmap'] the hook mapping
 *     array $args['itemids'] the list of old => new itemids
 *     bool $args['debug'] don't actually update anything :-)
 * @return mixed true or debug string on success, null on failure
 * @throws BadParameterException
 */
function dynamicdata_utilapi_updatehooks(array $args = [], $context = null)
{
    extract($args);

    $invalid = [];
    /** @var array<string, mixed> $from */
    if (empty($from)) {
        $invalid[] = 'from array';
    } else {
        if (empty($from['module']) || !is_numeric($from['module'])) {
            $invalid[] = 'from module';
        }
        if (!isset($from['itemtype']) || !is_numeric($from['itemtype'])) {
            $invalid[] = 'from itemtype';
        }
    }
    /** @var array<string, mixed> $to */
    if (empty($to)) {
        $invalid[] = 'to array';
    } else {
        if (empty($to['module']) || !is_numeric($to['module'])) {
            $invalid[] = 'to module';
        }
        if (!isset($to['itemtype']) || !is_numeric($to['itemtype'])) {
            $invalid[] = 'to itemtype';
        }
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = [join(', ', $invalid), 'admin', 'updatehooks', 'DynamicData'];
        throw new BadParameterException($vars, $msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurity::check('AdminDynamicData')) {
        return;
    }

    /** @var array<int, int> $itemids */
    /** @var array<string, mixed> $hookmap */
    if (empty($itemids) || empty($hookmap)) {
        // nothing to do here
        if (!empty($debug)) {
            $debug .= xarML('No hooks to update') . "\n";
            return $debug;
        } else {
            return true;
        }
    }

    $dbconn = xarDB::getConn();
    foreach ($hookmap as $fromhook => $tohook) {
        if (empty($fromhook) || empty($tohook)) {
            continue;
        }
        if ($fromhook != $tohook) {
            continue;
        } // no moving of hooked content atm
        $table = '';
        $modfield = '';
        $typefield = '';
        $idfield = '';
        switch ($tohook) {
            case 'categories':
                // load table definitions et al.
                xarMod::apiLoad('categories', 'user');
                $xartable =  xarDB::getTables();
                if (empty($xartable['categories_linkage'])) {
                    break;
                }
                $table = $xartable['categories_linkage'];
                $modfield = 'xar_modid';
                $typefield = 'xar_itemtype';
                $idfield = 'xar_iid';
                break;

            case 'changelog':
            case 'hitcount':
            case 'keywords':
            case 'ratings':
            case 'xlink':
                // load table definitions et al.
                xarMod::apiLoad($tohook, 'user');
                $xartable =  xarDB::getTables();
                if (empty($xartable[$tohook])) {
                    break;
                }
                $table = $xartable[$tohook];
                $modfield = 'xar_moduleid';
                $typefield = 'xar_itemtype';
                $idfield = 'xar_itemid';
                break;

            case 'comments':
                // load table definitions et al.
                xarMod::apiLoad('comments', 'user');
                $xartable =  xarDB::getTables();
                if (empty($xartable['comments'])) {
                    break;
                }
                $table = $xartable['comments'];
                $modfield = 'xar_modid';
                $typefield = 'xar_itemtype';
                $idfield = 'xar_objectid';
                break;

            case 'dynamicdata':
                // already done via field mapping
                break;

            case 'polls':
                // load table definitions et al.
                xarMod::apiLoad('polls', 'user');
                $xartable =  xarDB::getTables();
                if (empty($xartable['polls'])) {
                    break;
                }
                $table = $xartable['polls'];
                // Note: assuming fixed column names here (version 1.4.0)
                $modfield = 'xar_modid';
                $typefield = 'xar_itemtype';
                $idfield = 'xar_itemid';
                break;

            case 'subitems':
                // TODO: retrieve old/new subitems objects from subitems_ddobjects, then
                //       copy DD from old to new object, and update id in subitems_ddids
                break;

            case 'uploads':
                // load table definitions et al.
                xarMod::apiLoad('uploads', 'user');
                $xartable =  xarDB::getTables();
                if (empty($xartable['file_associations'])) {
                    break;
                }
                $table = $xartable['file_associations'];
                $modfield = 'xar_modid';
                $typefield = 'xar_itemtype';
                $idfield = 'xar_objectid';
                break;

            case 'workflow':
                // not possible to migrate this without knowing the processes,
                // and especially what kind of information they store about items
                break;

            default:
                break;
        }
        if (empty($table)) {
            continue;
        }
        // if the itemids didn't change
        if (array_keys($itemids) == array_values($itemids)) {
            if ($from['module'] == $to['module'] && $from['itemtype'] == $to['itemtype']) {
                continue;
            }
            $bindvars = [];
            $set = [];
            if ($from['module'] != $to['module']) {
                $bindvars[] = (int) $to['module'];
                $set[] = $modfield . ' = ?';
            }
            if ($from['itemtype'] != $to['itemtype']) {
                $bindvars[] = (int) $to['itemtype'];
                $set[] = $typefield . ' = ?';
            }
            $bindvars[] = (int) $from['module'];
            $bindvars[] = (int) $from['itemtype'];
            $markers = 0;
            foreach (array_keys($itemids) as $itemid) {
                if (empty($itemid)) {
                    continue;
                }
                $bindvars[] = (int) $itemid;
                $markers++;
            }
            $bindmarkers = '?' . str_repeat(',?', $markers - 1);
            $query = "UPDATE $table
                         SET " . join(', ', $set) . "
                       WHERE $modfield = ?
                         AND $typefield = ?
                         AND $idfield IN ($bindmarkers)";
            if (empty($debug)) {
                $dbconn->Execute($query, $bindvars);
            } else {
                $debug .= xarML(
                    'Updating hook #(1) from #(2) to #(3) for items #(4)',
                    $tohook,
                    "$from[module]:$from[itemtype]",
                    "$to[module]:$to[itemtype]",
                    join(',', array_keys($itemids))
                );
                $debug .= "\n";
            }
            continue;
        }
        // if the itemids changed too
        try {
            $dbconn->begin();
            foreach ($itemids as $itemid => $newid) {
                if (empty($itemid) || empty($newid)) {
                    continue;
                }
                if ($from['module'] == $to['module'] && $from['itemtype'] == $to['itemtype'] && $itemid == $newid) {
                    // nothing changes for hooks
                    continue;
                }
                $bindvars = [];
                $set = [];
                if ($from['module'] != $to['module']) {
                    $bindvars[] = (int) $to['module'];
                    $set[] = $modfield . ' = ?';
                }
                if ($from['itemtype'] != $to['itemtype']) {
                    $bindvars[] = (int) $to['itemtype'];
                    $set[] = $typefield . ' = ?';
                }
                if ($itemid != $newid) {
                    $bindvars[] = (int) $newid;
                    $set[] = $idfield . ' = ?';
                }
                $bindvars[] = (int) $from['module'];
                $bindvars[] = (int) $from['itemtype'];
                $bindvars[] = (int) $itemid;
                $query = "UPDATE $table SET " . join(', ', $set) . " WHERE $modfield = ?  AND $typefield = ? AND $idfield = ?";
                $dbconn->Execute($query, $bindvars);

                if (!empty($debug)) {
                    $debug .= xarML(
                        'Updating hook #(1) from #(2) to #(3)',
                        $tohook,
                        "$from[module]:$from[itemtype]:$itemid",
                        "$to[module]:$to[itemtype]:$newid"
                    );
                    $debug .= "\n";
                }
            }
            $dbconn->commit();
        } catch(SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
    }

    if (!empty($debug)) {
        return $debug;
    } else {
        return true;
    }
}
