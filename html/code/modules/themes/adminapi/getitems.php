<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\AdminApi;
use xarTheme;
use sys;

sys::import('xaraya.modules.method');

/**
 * themes adminapi getitems function
 * @extends MethodClass<AdminApi>
 */
class GetitemsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\themes
     * @subpackage themes
     * @copyright see the html/credits.html file in this release
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/70.html
     * @see AdminApi::getitems()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!isset($state)) {
            $state = xarTheme::STATE_ACTIVE;
        }

        if (!isset($class)) {
            $class = 3;
        } // any

        if (!isset($sort)) {
            $sort = 'name ASC';
        }

        // Determine the tables we are going to use
        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $themes_table = $tables['themes'];

        $select   = [];
        $where    = [];
        $orderby  = [];
        $bindvars = [];

        $select['regid']         = 'themes.regid';
        $select['name']          = 'themes.name';
        $select['directory']     = 'themes.directory';
        $select['state']         = 'themes.state';
        $select['class']         = 'themes.class';
        $select['configuration'] = 'themes.configuration';

        if (isset($name)) {
            $where[] = 'themes.name = ?';
            $bindvars[] = $name;
        }

        if (isset($regid)) {
            $where[] = 'themes.regid = ?';
            $bindvars[] = $regid;
        }

        if ($state != xarTheme::STATE_ANY) {
            if ($state != xarTheme::STATE_INSTALLED) {
                $where[] = 'themes.state = ?';
                $bindvars[] = $state;
            } else {
                $where[] = 'themes.state != ? AND themes.state < ? AND themes.state != ?';
                $bindvars[] = xarTheme::STATE_UNINITIALISED;
                $bindvars[] = xarTheme::STATE_MISSING_FROM_INACTIVE;
                $bindvars[] = xarTheme::STATE_MISSING_FROM_UNINITIALISED;
            }
        }

        if (isset($class) && $class != 3) {
            $where[] = 'themes.class = ?';
            $bindvars[] = $class;
        }

        if (!is_array($sort)) {
            $sort = strpos($sort, ',') !== false ? array_map('trim', explode(',', $sort)) : [trim($sort)];
        }
        foreach ($sort as $pairs) {
            [$sortfield, $sortorder] = array_map('trim', array_pad(explode(' ', $pairs), 2, 'ASC'));
            if (!isset($select[$sortfield]) || isset($orderby[$sortfield])) {
                continue;
            }
            $orderby[$sortfield] = $select[$sortfield] . ' ' . strtoupper($sortorder);
        }

        $query = "SELECT " . join(',', $select);
        $query .= " FROM $themes_table themes";
        if (!empty($where)) {
            $query .= " WHERE " . join(' AND ', $where);
        }
        if (!empty($orderby)) {
            $query .= " ORDER BY " . join(',', $orderby);
        }

        $stmt = $dbconn->prepareStatement($query);
        if (!empty($numitems)) {
            $stmt->setLimit($numitems);
            if (empty($startnum)) {
                $startnum = 1;
            }
            $stmt->setOffset($startnum - 1);
        }
        $result = $stmt->executeQuery($bindvars);

        $items = [];
        while ($result->next()) {
            $item = [];
            foreach (array_keys($select) as $field) {
                $item[$field] = array_shift($result->fields);
            }

            if ($this->mem()->has('Theme.Infos', $item['regid'])) {
                // merge cached info with db info
                $item += $this->mem()->get('Theme.Infos', $item['regid']);
            } else {
                $item['displayname'] = $item['name'];
                // Shortcut for os prepared directory
                $item['osdirectory'] = $this->prep()->path($item['directory']);

                $this->mem()->set('Theme.BaseInfos', $item['name'], $item);

                $fileinfo = xarTheme::getFileInfo($item['osdirectory']);
                if (!empty($fileinfo)) {
                    $item = array_merge($fileinfo, $item);
                    $this->mem()->set('Theme.Infos', $item['regid'], $item);
                    switch ($item['state']) {
                        case xarTheme::STATE_MISSING_FROM_UNINITIALISED:
                            $item['state'] = xarTheme::STATE_UNINITIALISED;
                            break;
                        case xarTheme::STATE_MISSING_FROM_INACTIVE:
                            $item['state'] = xarTheme::STATE_INACTIVE;
                            break;
                        case xarTheme::STATE_MISSING_FROM_ACTIVE:
                            $item['state'] = xarTheme::STATE_ACTIVE;
                            break;
                        case xarTheme::STATE_MISSING_FROM_UPGRADED:
                            $item['state'] = xarTheme::STATE_UPGRADED;
                            break;
                    }
                } else {
                    // There was an entry in the database which was not in the file system,
                    // @CHECKME: do we really want to do this here?
                    // <chris/> I think not, this is handled by refresh and managed in list UI
                    /*
                    // This functionality was present in getlist api function
                    // remove the entry from the database
                    $adminapi->remove(array('regid' => $item['regid']));
                    continue;
                    */
                    // <chris/> instead, let's apply the correct state and pass through
                    // This functionality was present in getthemelist api
                    // Following changes were applied by <andyv> on 21st May 2003
                    // as per the patch by Garrett Hunter
                    // Credits: Garrett Hunter <Garrett.Hunter@Verizon.net>
                    switch ($item['state']) {
                        case xarTheme::STATE_UNINITIALISED:
                            $item['state'] = xarTheme::STATE_MISSING_FROM_UNINITIALISED;
                            break;
                        case xarTheme::STATE_INACTIVE:
                            $item['state'] = xarTheme::STATE_MISSING_FROM_INACTIVE;
                            break;
                        case xarTheme::STATE_ACTIVE:
                            $item['state'] = xarTheme::STATE_MISSING_FROM_ACTIVE;
                            break;
                        case xarTheme::STATE_UPGRADED:
                            $item['state'] = xarTheme::STATE_MISSING_FROM_UPGRADED;
                            break;
                    }
                    //$item['class'] = "";
                    $item['version'] = "&#160;";

                }
            }

            $items[] = $item;
        }
        $result->close();

        return $items;
    }
}
