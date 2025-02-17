<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\InstancesApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\InstancesApi;
use Xaraya\Modules\Blocks\TypesApi;
use BadParameterException;
use DuplicateException;
use IDNotFoundException;
use xarBlock;
use xarDB;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * blocks instancesapi createitem function
 * @extends MethodClass<InstancesApi>
 */
class CreateitemMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create an item
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Parameter data array
     * @return int|void Returns the block id of the newly created item
     * @throws \BadParameterException
     * @throws \IDNotFoundException
     * @throws \DuplicateException
     * @see InstancesApi::createitem()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();

        if (empty($type_id) || !is_numeric($type_id)) {
            $invalid[] = 'type_id';
        }

        if (empty($name) || !is_string($name) || strlen($name) > 64 || !preg_match('!^([a-z0-9_])*$!', $name)) {
            $invalid[] = 'name';
        }

        if (!isset($title)) {
            $title = '';
        }

        if (!is_string($title) || strlen($title) > 254) {
            $invalid[] = 'title';
        }

        if (!isset($state)) {
            $state = xarBlock::BLOCK_STATE_VISIBLE;
        }
        $states = $instancesapi->getstates();

        if (!is_numeric($state) || !isset($states[$state])) {
            $invalid[] = 'state';
        }

        if (isset($content) && !is_array($content)) {
            $invalid[] = 'content';
        }

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
            $vars = [join(', ', $invalid), 'blocks', 'instancesapi', 'createitem'];
            throw new BadParameterException($vars, $msg);
        }

        if (!$type = $typesapi->getitem(['type_id' => $type_id])) {
            $msg = 'Invalid block type id "#(1)", type does not exist';
            $vars = [$type_id];
            throw new IDNotFoundException($vars, $msg);
        }

        if ($instancesapi->getitem(['name' => $name])) {
            $msg = 'A block instance named "#(1)" already exists, name must be unique';
            $vars = [$name];
            throw new DuplicateException($vars, $msg);
        }

        if (empty($content)) {
            $content = $type['type_info'];
        }

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();
        $blocks_table = $tables['block_instances'];

        $query = "INSERT INTO $blocks_table    
                  (type_id, name, title, state, content)
                  VALUES (?,?,?,?,?)";
        $bindvars = [$type_id, $name, $title, $state, serialize($content)];

        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result) {
            return;
        }

        $block_id = $dbconn->getLastId($blocks_table);
        if (empty($block_id)) {
            return;
        }

        return $block_id;
    }
}
