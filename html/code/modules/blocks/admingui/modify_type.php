<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\AdminGui;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\AdminGui;
use Xaraya\Modules\Blocks\TypesApi;
use Xaraya\Modules\Blocks\UserApi;
use EmptyParameterException;
use Exception;
use FileNotFoundException;
use FunctionNotFoundException;
use IDNotFoundException;
use xarBlock;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin modify_type function
 * @extends MethodClass<AdminGui>
 */
class ModifyTypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Chris Powis <crisp@xaraya.com>
     * @param array<string,mixed> $args Optional parameter array
     * @return array|string|void Display data array
     * @throws \EmptyParameterException
     * @throws \IDNotFoundException
     * @throws \FunctionNotFoundException
     * @see AdminGui::modifyType()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!$this->sec()->checkAccess('ManageBlocks')) {
            return;
        }

        $this->var()->check(
            'type_id',
            $type_id,
            'int:1:',
            null
        );

        if (!isset($type_id)) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['type_id', 'blocks', 'admin', 'modify_type'];
            throw new EmptyParameterException($vars, $msg);
        }

        if (!$typesapi->refresh()) {
            return;
        }

        $type = $typesapi->getitem(['type_id' => $type_id]);

        if (!$type) {
            $msg = 'Block type id "#(1)" does not exist';
            $vars = [$type_id];
            throw new IDNotFoundException($vars, $msg);
        }

        $data = [];

        // determine the interface, method and phase
        $this->var()->find(
            'interface',
            $interface,
            'pre:trim:lower:str:1:',
            'display'
        );
        $this->var()->find(
            'block_method',
            $method,
            'pre:trim:lower:str:1:',
            null
        );
        $this->var()->find(
            'phase',
            $phase,
            'pre:trim:lower:str:1:',
            'display'
        );

        // show the status warning if the type isn't active
        if ($type['type_state'] != xarBlock::TYPE_STATE_ACTIVE) {
            $interface = 'display';
            $method = 'status';
            $phase = 'display';
        } else {
            // admins only beyond the display interface methods
            if ($interface != 'display') {
                if (!$this->sec()->checkAccess('AdminBlocks')) {
                    return;
                }
            }
            // get the block object and load the interface
            $block = xarBlock::getObject($type, $interface);
            // set context if available in gui function
            $block->setContext($this->getContext());
        }

        // handle update phase
        if ($phase == 'update') {
            $invalid = [];
            switch ($interface) {
                case 'display':
                    $invalid['phase'] = $this->ml('Update phase not supported in display interface');
                    // fall through to display phase
                    $phase = 'display';
                    break;
                case 'config':
                    if (empty($method)) {
                        $method = 'config';
                    }
                    switch ($method) {
                        case 'config':
                            // if the block type supplied a validation method, use it
                            if (xarBlock::hasMethod($block, 'configcheck', true)) {
                                $isvalid = $block->configcheck();
                            } elseif (xarBlock::hasMethod($block, 'checkmodify', true)) {
                                $isvalid = $block->checkmodify();
                            } else {
                                $isvalid = true;
                            }
                            // attempt to update the block type configuration
                            if ($isvalid) {
                                if (xarBlock::hasMethod($block, 'configupdate', true)) {
                                    $result = $block->configupdate();
                                } elseif (xarBlock::hasMethod($block, 'update', true)) {
                                    $result = $block->update();
                                }
                                if (isset($result) && $result == false) {
                                    $invalid['update'] = $this->ml('Failed updating block type configuration');
                                }
                            } else {
                                $invalid['check'] = $this->ml('Failed validating block type form input');
                            }
                            // fetch block subsystem configuration
                            $this->var()->find('type_block_template', $block_template, 'pre:trim:str:1:127', null);
                            $this->var()->find('type_box_template', $box_template, 'pre:trim:str:1:127', null);
                            // update block configuration
                            if (empty($invalid)) {
                                if (!$this->sec()->confirmAuthKey()) {
                                    return $this->ctl()->badRequest('bad_author');
                                }
                                if (isset($result) && is_array($result)) {
                                    if (!empty($result['content'])) {
                                        $block->setContent($result['content']);
                                    }
                                    if (!empty($result['return_url'])) {
                                        $return_url = $result['return_url'];
                                    }
                                }
                                $block->setBlockTemplate($block_template);
                                $block->setBoxTemplate($box_template);
                            }
                            // fall through
                            break;
                        default:
                            // block type supplied a custom config interface method
                            $check_method = $method . 'check';
                            $isvalid = xarBlock::hasMethod($block, $check_method, true)
                                ? $block->$check_method() : true;
                            if ($isvalid) {
                                $update_method = $method . 'update';
                                if (xarBlock::hasMethod($block, $update_method, true)) {
                                    $result = $block->$update_method();
                                    if (empty($result)) {
                                        $invalid['update'] = $this->ml('Failed updating block type configuration');
                                    }
                                }
                            } else {
                                $invalid['check'] = $this->ml('Failed validating block type form input');
                            }
                            // update block configuration
                            if (empty($invalid)) {
                                if (!$this->sec()->confirmAuthKey()) {
                                    return $this->ctl()->badRequest('bad_author');
                                }
                                if (!empty($result) && is_array($result)) {
                                    if (!empty($result['content'])) {
                                        $block->setContent($result['content']);
                                    }
                                    if (!empty($result['return_url'])) {
                                        $return_url = $result['return_url'];
                                    }
                                }
                            }
                            // fall through
                            break;
                    }

                    break;
                case 'caching':
                    $this->var()->find('type_nocache', $nocache, 'checkbox', false);
                    $this->var()->find('type_pageshared', $pageshared, 'checkbox', false);
                    $this->var()->find('type_usershared', $usershared, 'int:0:2', 0);
                    $this->var()->find('type_cacheexpire', $cacheexpire, 'str:1:', null);

                    // convert cacheexpire from hh:mm:ss format to an integer
                    if (!empty($cacheexpire)) {
                        $cacheexpire = $userapi->convertseconds(['direction' => 'to', 'starttime' => $cacheexpire]);
                    }

                    // block type may supply additional caching configuration
                    $check_method = 'cachingcheck';
                    $isvalid = xarBlock::hasMethod($block, $check_method, true) ? $block->$check_method() : true;
                    if ($isvalid) {
                        $update_method = 'cachingupdate';
                        if (xarBlock::hasMethod($block, $update_method, true)) {
                            $result = $block->$update_method();
                            if (empty($result)) {
                                $invalid['update'] = $this->ml('Failed updating block type caching configuration');
                            }
                        }
                    } else {
                        $invalid['check'] = $this->ml('Failed validating block type caching form input');
                    }

                    // update block configuration
                    if (empty($invalid)) {
                        if (!$this->sec()->confirmAuthKey()) {
                            return $this->ctl()->badRequest('bad_author');
                        }
                        if (!empty($result) && is_array($result)) {
                            if (!empty($result['content'])) {
                                $block->setContent($result['content']);
                            }
                            if (!empty($result['return_url'])) {
                                $return_url = $result['return_url'];
                            }
                        }
                        $block->setNoCache($nocache);
                        $block->setPageShared($pageshared);
                        $block->setUserShared($usershared);
                        $block->setCacheExpire($cacheexpire);
                    }

                    break;
                case 'access':
                    // block type may supply additional access configuration
                    $check_method = 'accesscheck';
                    $isvalid = xarBlock::hasMethod($block, $check_method, true) ? $block->$check_method() : true;
                    if ($isvalid) {
                        $update_method = 'accessupdate';
                        if (xarBlock::hasMethod($block, $update_method, true)) {
                            $result = $block->$update_method();
                            if (empty($result)) {
                                $invalid['update'] = $this->ml('Failed updating block type caching configuration');
                            }
                        }
                    } else {
                        $invalid['check'] = $this->ml('Failed validating block type caching form input');
                    }

                    // update block configuration
                    if (empty($invalid)) {
                        if (!$this->sec()->confirmAuthKey()) {
                            return $this->ctl()->badRequest('bad_author');
                        }
                        if (!empty($result) && is_array($result)) {
                            if (!empty($result['content'])) {
                                $block->setContent($result['content']);
                            }
                            if (!empty($result['return_url'])) {
                                $return_url = $result['return_url'];
                            }
                        }
                        $accessproperty = $this->prop()->getProperty(['name' => 'access']);
                        $isvalid = $accessproperty->checkInput('type_add_access');
                        $block->setAccess('add', $accessproperty->value);
                    }
                    break;
                default:
                    if (empty($method)) {
                        $method = $interface;
                    }
                    // block type may supply additional interfaces and methods
                    $check_method = $method . 'check';
                    $isvalid = xarBlock::hasMethod($block, $check_method, true) ? $block->$check_method() : true;
                    if ($isvalid) {
                        $update_method = $method . 'update';
                        if (xarBlock::hasMethod($block, $update_method, true)) {
                            $result = $block->$update_method();
                            if (empty($result)) {
                                $invalid['update'] = $this->ml('Failed updating block type caching configuration');
                            }
                        }
                    } else {
                        $invalid['check'] = $this->ml('Failed validating block type caching form input');
                    }
                    // update block configuration
                    if (empty($invalid)) {
                        if (!$this->sec()->confirmAuthKey()) {
                            return $this->ctl()->badRequest('bad_author');
                        }
                        if (!empty($result) && is_array($result)) {
                            if (!empty($result['content'])) {
                                $block->setContent($result['content']);
                            }
                            if (!empty($result['return_url'])) {
                                $return_url = $result['return_url'];
                            }
                        }
                    }

                    break;
            }
            $type['type_info'] = $block->storeContent();
            // valid input, go ahead and update the block type info
            if (empty($invalid)) {

                if (!$typesapi->updateitem($type)) {
                    return;
                }

                $this->var()->find('return_url', $return_url, 'pre:trim:str:1:', '');
                if (empty($return_url)) {
                    $return_url = $this->ctl()->getModuleURL(
                        'blocks',
                        'admin',
                        'modify_type',
                        [
                            'type_id' => $type['type_id'],
                            'interface' => $interface,
                            'block_method' => $method,
                        ]
                    );
                }
                $this->ctl()->redirect($return_url);
                return true;
            }
            $data['invalid'] = $invalid;

        }


        // handle display phase
        switch ($interface) {
            case 'display':
                if (empty($method)) {
                    $method = 'info';
                }
                switch ($method) {
                    case 'info':
                        // $type already gives us most of what we need
                        // get params that can be set in block tag attributes
                        // @todo: this should be a method of the basicblock/blocktype class
                        // @todo: have the method return better definitions (data type hint, validation)
                        $type_params = [];
                        $content = $block->getContent();
                        if (!empty($content)) {
                            foreach ($content as $k => $v) {
                                $datatype = gettype($v);
                                switch ($datatype) {
                                    case 'string':
                                        $value = '"' . $v . '"';
                                        break;
                                    case 'float':
                                    case 'double':
                                    case 'integer':
                                    case 'NULL':
                                        $value = $v;
                                        break;
                                    case 'boolean':
                                        $value = $v ? '1' : '0';
                                        break;
                                    default:
                                        continue 2;
                                }
                                $type_params[$k] = [
                                    'attribute' => $k,
                                    'datatype' => $datatype,
                                    'default' => $value,
                                ];
                            }
                        }
                        $data['type_params'] = $type_params;

                        // show additional info if supplied by block type
                        if (xarBlock::hasMethod($block, 'info', true)) {
                            $data['type_output'] = xarBlock::guiMethod($block, 'info');
                        }

                        break;
                    case 'preview':
                        // show using preview method if supplied by block type...
                        if (xarBlock::hasMethod($block, 'preview', true)) {
                            $data['type_output'] = xarBlock::guiMethod($block, 'preview');
                        }
                        // or using display method otherwise...
                        else {
                            $data['type_output'] = xarBlock::guiMethod($block, 'display');
                        }

                        break;
                    case 'help':
                        // show help info if supplied by block type
                        if (xarBlock::hasMethod($block, 'help', true)) {
                            $data['type_output'] = xarBlock::guiMethod($block, 'help');
                        }
                        break;
                    case 'status':

                        break;
                    default:
                        // show custom info if supplied by block type
                        if (xarBlock::hasMethod($block, $method, true)) {
                            $data['type_output'] = xarBlock::guiMethod($block, $method);
                        }
                        break;
                }
                break;
            case 'config':
                if (empty($method)) {
                    $method = 'config';
                }
                switch ($method) {
                    case 'config':
                        try {
                            $data['type_output'] = xarBlock::guiMethod($block, 'configmodify', 'config-' . $block->type);
                        } catch (FunctionNotFoundException $e) {
                            try {
                                $data['type_output'] = xarBlock::guiMethod($block, 'modify');
                            } catch (FunctionNotFoundException $f) {
                                $data['type_output'] = '';
                            } catch (FileNotFoundException $f) {
                                $data['type_output'] = '';
                            } catch (Exception $f) {
                                throw $f;
                            }
                        } catch (Exception $e) {
                            throw $e;
                        }
                        break;
                    default:
                        // show custom configuration supplied by block type
                        $modify_method = $method . 'modify';
                        $data['type_output'] = xarBlock::guiMethod($block, $modify_method, $method . '-' . $block->type);
                        break;
                }
                break;
            case 'caching':
                // convert expire time to hh:mm:ss format for display
                if (!empty($type['type_info']['cacheexpire'])) {
                    $type['type_info']['cacheexpire'] = $userapi->convertseconds(['direction' => 'from', 'starttime' => $type['type_info']['cacheexpire']]);
                }

                $data['usershared_options'] = [
                    ['id' => 0, 'name' => $this->ml('No Sharing')],
                    ['id' => 1, 'name' => $this->ml('Group Members')],
                    ['id' => 2, 'name' => $this->ml('All Users')],
                ];
                // show additional caching info if supplied by block type
                if (xarBlock::hasMethod($block, 'cachingmodify', true)) {
                    $data['type_output'] = xarBlock::guiMethod($block, 'cachingmodify', 'caching-' . $block->type);
                }

                break;
            case 'access':
                // show additional access info if supplied by block type
                if (xarBlock::hasMethod($block, 'accessmodify', true)) {
                    $data['type_output'] = xarBlock::guiMethod($block, 'accessmodify', 'access-' . $block->type);
                }
                break;
            default:
                // block type may supply a custom interface and methods
                if (empty($method)) {
                    $method = $interface;
                }
                $modify_method = $method . 'modify';
                $data['type_output'] = xarBlock::guiMethod($block, $modify_method, $method . '-' . $block->type);
                break;
        }

        $data['type'] = $type;
        $data['interface'] = $interface;
        $data['method'] = $method;
        $data['type_states'] = $typesapi->getstates();
        $interfaces = [];
        $interfaces[] = [
            'url' => $this->ctl()->getCurrentURL(['interface' => 'display', 'block_method' => null]),
            'label' => $this->ml('Info'),
            'title' => $this->ml('Display information about this block type'),
            'active' => ($interface == 'display' && $method == 'info'),
        ];
        if ($interface != 'display' || $method != 'status') {
            if ($this->sec()->checkAccess('AdminBlocks', 0)) {
                $interfaces[] = [
                    'url' => $this->ctl()->getCurrentURL(['interface' => 'config', 'block_method' => null]),
                    'label' => $this->ml('Config'),
                    'title' => $this->ml('Modify default configuration for this block type'),
                    'active' => ($interface == 'config'),
                ];
                $interfaces[] = [
                    'url' => $this->ctl()->getCurrentURL(['interface' => 'caching', 'block_method' => null]),
                    'label' => $this->ml('Caching'),
                    'title' => $this->ml('Modify default caching configuration for this block type'),
                    'active' => ($interface == 'caching'),
                ];
                $interfaces[] = [
                    'url' => $this->ctl()->getCurrentURL(['interface' => 'access', 'block_method' => null]),
                    'label' => $this->ml('Access'),
                    'title' => $this->ml('Modify default access configuration for this block type'),
                    'active' => ($interface == 'access'),
                ];
            }
            if ($block->show_preview) {
                $interfaces[] = [
                    'url' => $this->ctl()->getCurrentURL(['interface' => 'display', 'block_method' => 'preview']),
                    'label' => $this->ml('Preview'),
                    'title' => $this->ml('Show a preview of this block type'),
                    'active' => ($interface == 'display' && $method == 'preview'),
                ];
            }
            if ($block->show_help) {
                $interfaces[] = [
                    'url' => $this->ctl()->getCurrentURL(['interface' => 'display', 'block_method' => 'help']),
                    'label' => $this->ml('Help'),
                    'title' => $this->ml('View block type help information'),
                    'active' => ($interface == 'display' && $method == 'help'),
                ];
            }
        }
        $data['interfaces'] = $interfaces;

        return $data;
    }
}
