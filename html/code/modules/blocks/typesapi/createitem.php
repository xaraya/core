<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\TypesApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\TypesApi;
use BadParameterException;
use DuplicateException;
use xarBlock;
use xarDB;
use xarMod;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks typesapi createitem function
 * @extends MethodClass<TypesApi>
 */
class CreateitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Creates an item in the API
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return bool|int|void Returns newly created id on success or falce on failure
     * @throws \BadParameterException
     * @throws \DuplicateException
     * @see TypesApi::createitem()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();

        // type name is required
        if (!isset($type) || !is_string($type) || strlen($type) > 64) {
            $invalid[] = 'type';
        }

        // module is optional
        if (!isset($module)) {
            $module = '';
        }
        if (!empty($module)) {
            $modinfo = $this->mod()->getBaseInfo($module);
            if (empty($modinfo)) {
                $invalid[] = 'module';
            } else {
                $module_id = $modinfo['systemid'];
            }
        }
        if (empty($module_id)) {
            $module_id = 0;
        }
        if (!is_numeric($module_id)) {
            $invalid[] = 'module_id';
        }

        // state is optional
        if (!isset($state)) {
            $state = xarBlock::TYPE_STATE_ACTIVE;
        }
        $states = $typesapi->getstates();
        if (!is_numeric($state) || !isset($states[$state])) {
            $invalid[] = 'state';
        }

        // everything else to store we get from the block type object

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'typesapi', 'createitem'];
            throw new BadParameterException($vars, $msg);
        }

        // check for duplicates
        if ($typesapi->getitem(['type' => $type, 'module' => $module])) {
            if (empty($module)) {
                $msg = 'Unable to create standalone block type "#(1)", type already exists';
                $vars = [$type];
            } else {
                $msg = 'Unable to create block type "#(1)" belonging to #(2) module, type already exists';
                $vars = [$type, $module];
            }
            throw new DuplicateException($vars, $msg);
        }

        // get an instance of this block type object
        $blocktype = $typesapi->getblock(['type' => $type, 'module' => $module]);

        $category = $blocktype->type_category;
        $info = serialize($blocktype->storeContent());

        // we need to save the actual $classname and $filepath for getitems()
        $classname = get_class($blocktype);
        $filepath = $blocktype->filepath;

        unset($blocktype);

        $dbconn = $this->db()->getConn();
        $tables = $this->db()->getTables();
        $types_table = $tables['block_types'];

        // we need to save the actual $classname and $filepath for getitems() - requires UPGRADE due to table change
        $query = "INSERT INTO $types_table
                  (module_id, state, type, category, info, class, filepath)
                  VALUES (?,?,?,?,?,?,?)";
        $bindvars = [$module_id, $state, $type, $category, $info, $classname, $filepath];

        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) {
            return;
        }
        $id = $dbconn->getLastId($types_table);
        if (empty($id)) {
            return;
        }
        return $id;
    }
}
