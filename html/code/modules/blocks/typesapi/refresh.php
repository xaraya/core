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
use Xaraya\Modules\Blocks\BlocksApi;
use Exception;
use FileNotFoundException;
use xarBlock;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * blocks typesapi refresh function
 * @extends MethodClass<TypesApi>
 */
class RefreshMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @staticvar boolean $runonce
     * @param array<string,mixed> $args Parameter data array
     * @return bool|void True on success, false on failure
     * @see TypesApi::refresh()
     */
    public function __invoke(array $args = [])
    {
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        /** @var BlocksApi $blocksapi */
        $blocksapi = $this->blocksapi();
        // only need to run this once
        static $runonce = false;
        if ($runonce && empty($args['refresh'])) {
            return true;
        }

        // first get a list of block type files for all available modules
        $files = $typesapi->getfiles();

        // no $classname with possible namespace here, and no re-use of what else typesapi getfiles()
        // found in any of the API calls below
        foreach ($files as $file) {
            if (isset($args['module']) && $file['module'] != $args['module']) {
                continue;
            }
            // nothing fancy here, if a type file exists, see if we have an entry for it in the db
            if (!$typesapi->getitem([
                'type' => $file['type'],
                'module' => $file['module'],
            ])) {
                // no entry in the db, create one now
                if (!$typesapi->createitem([
                    'type' => $file['type'],
                    'module' => $file['module'],
                ])) {
                    return;
                }
            }
        }

        // now get the list of all block types in the db
        $types = $typesapi->getitems();

        foreach ($types as $type) {
            if (isset($args['module']) && $type['module'] != $args['module']) {
                continue;
            }
            $update = [];
            // if the block belongs to a module, check the module is active
            if (!empty($type['module']) && !xarMod::isAvailable($type['module'])) {
                $state = xarBlock::TYPE_STATE_MOD_UNAVAILABLE;
            } else {
                try {
                    // check the block can be instantiated
                    $block = $blocksapi->getobject($type);
                    $state = xarBlock::TYPE_STATE_ACTIVE;
                    if ($block->type_category != $type['type_category']) {
                        $update['type_category'] = $block->type_category;
                    }
                    $type_info = [];
                    if ($block->text_type != $type['type_info']['text_type']) {
                        $type_info['text_type'] = $block->text_type;
                    }
                    if ($block->text_type_long != $type['type_info']['text_type_long']) {
                        $type_info['text_type_long'] = $block->text_type_long;
                    }
                    if ($block->author != $type['type_info']['author']) {
                        $type_info['author'] = $block->author;
                    }
                    if ($block->contact != $type['type_info']['contact']) {
                        $type_info['contact'] = $block->contact;
                    }
                    if ($block->credits != $type['type_info']['credits']) {
                        $type_info['credits'] = $block->credits;
                    }
                    if ($block->license != $type['type_info']['license']) {
                        $type_info['license'] = $block->license;
                    }
                    if (!empty($type_info)) {
                        $type_info += $type['type_info'];
                        $update['type_info'] = $type_info;
                    } else {
                        unset($type_info);
                    }
                    // we need to save the actual $classname and $filepath for getitems() - requires UPGRADE due to table change
                    $classname = get_class($block);
                    if ($classname != $type['classname'] ?? '') {
                        $update['classname'] = $classname;
                    }
                    if ($block->filepath != $type['filepath'] ?? '') {
                        $update['filepath'] = $block->filepath;
                    }

                } catch (FileNotFoundException $e) {
                    $state = xarBlock::TYPE_STATE_MISSING;
                } catch (Exception $e) {
                    $state = xarBlock::TYPE_STATE_ERROR;
                }

            }
            if ($state != $type['type_state']) {
                $update['type_state'] = $state;
            }

            if (!empty($update)) {
                $update['type_id'] = $type['type_id'];
                if (!$typesapi->updateitem($update)) {
                    return;
                }
            }
            unset($block, $state, $update);

        }
        unset($files, $types);
        $runonce = true;
        return true;
    }
}
