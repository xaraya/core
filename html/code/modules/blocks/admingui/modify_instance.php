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
use Xaraya\Modules\Blocks\InstancesApi;
use Xaraya\Modules\Blocks\TypesApi;
use Xaraya\Modules\Blocks\UserApi;
use Xaraya\Modules\Blocks\BlocksApi;
use AccessProperty;
use DataPropertyMaster;
use EmptyParameterException;
use Exception;
use FileNotFoundException;
use FunctionNotFoundException;
use IDNotFoundException;
use xarBlock;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
use xarServer;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks admin modify_instance function
 * @extends MethodClass<AdminGui>
 */
class ModifyInstanceMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify a block instance
     * @author Jim McDonald
     * @author Paul Rosania
     * @return array|string|void data for the template display
     * @see AdminGui::modifyInstance()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var BlocksApi $blocksapi */
        $blocksapi = $this->blocksapi();
        /**
         * Pending
         *
         * @TODO Need to sperate this out to API calls.
         */
        if (!$this->sec()->checkAccess('ManageBlocks')) {
            return;
        }

        $this->var()->check(
            'block_id',
            $block_id,
            'int:1:',
            null
        );

        if (!isset($block_id)) {
            $msg = 'Missing #(1) for #(2) module #(3) function #(4)()';
            $vars = ['block_id', 'blocks', 'admin', 'modify_instance'];
            throw new EmptyParameterException($vars, $msg);
        }

        $blockinfo = $instancesapi->getitem(['block_id' => $block_id]);

        if (!$blockinfo) {
            $msg = 'Block instance id "#(1)" does not exist';
            $vars = [$block_id];
            throw new IDNotFoundException($vars, $msg);
        }

        $data = [];

        // determine the interface, method and phase
        $this->var()->find('interface', $interface, 'pre:trim:lower:str:1:', 'display');
        $this->var()->find('block_method', $method, 'pre:trim:lower:str:1:', null);
        $this->var()->find('phase', $phase, 'pre:trim:lower:str:1:', 'display');

        // admin access is needed for some operations
        $isadmin = $this->sec()->check('', 0, 'Block', "$blockinfo[type]:$blockinfo[name]:$blockinfo[block_id]", $blockinfo['module'], '', 0, 800);

        // show the status warning if the type isn't active
        if ($blockinfo['type_state'] != xarBlock::TYPE_STATE_ACTIVE) {
            $interface = 'display';
            $method = 'status';
            $phase = 'display';
        } else {
            /** @var AccessProperty $accessproperty */
            $accessproperty = $this->prop()->getProperty(['name' => 'access']);
            // check modify access
            if ($isadmin) {
                $canmodify = true;
            } else {
                $args = [
                    'module' => $blockinfo['module'],
                    'component' => 'Block',
                    'instance' => "$blockinfo[type]:$blockinfo[name]:$blockinfo[block_id]",
                    'group' => $blockinfo['content']['modify_access']['group'],
                    'level' => $blockinfo['content']['modify_access']['level'],
                ];
                $canmodify = $accessproperty->check($args);
            }
            switch ($interface) {
                case 'display':
                    if (empty($method)) {
                        $method = 'info';
                    }
                    $phase = 'display';
                    break;
                case 'caching':
                case 'access':
                    if (!$isadmin) {
                        return $this->ctl()->badRequest('no_privileges');
                    }
                    $method = $interface;
                    // no break
                case 'config':
                default:
                    if (!$canmodify) {
                        return $this->ctl()->badRequest('no_privileges');
                    }
                    if (empty($method)) {
                        $method = $interface;
                    }
                    break;

            }
            // get the block object and load the interface
            $block = xarBlock::getObject($blockinfo, $interface);
            // set context if available in gui function
            $block->setContext($this->getContext());

            $block_groups = $instancesapi->getitems(['type_category' => 'group',]);

            // if block group, get instances attached to it
            if ($block->type_category == 'group') {
                $group_instances = $block->getInstances();
                if (!empty($group_instances)) {
                    $data['group_instances'] = $instancesapi->getitems(['block_id' => $group_instances]);
                }
            }
            // else, get groups instance is attached to
            else {
                $instance_groups = $block->getGroups();
                if (!empty($instance_groups)) {
                    $data['instance_groups'] = $instancesapi->getitems(['type_category' => 'group', 'block_id' => array_keys($instance_groups)]);
                }
            }

        }

        $instance_states = $instancesapi->getstates();
        $type_states = $typesapi->getstates();

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
                            if ($isadmin) {
                                $this->var()->find('instance_name', $name, 'pre:trim:str:1:', '');
                                $this->var()->find('instance_title', $title, 'pre:trim:str:0:', '');
                                $this->var()->find('instance_state', $state, 'int:0:4', null);
                                $this->var()->find('instance_expire', $expire, 'pre:trim:str:0:20', 0);
                                $this->var()->find('instance_expire_reset', $expire_reset, 'checkbox', false);
                                $this->var()->find('instance_box_template', $box_template, 'pre:trim:str:0:', '');
                                $this->var()->find('instance_block_template', $block_template, 'pre:trim:str:0:', '');
                                $this->var()->find('instance_groups', $groups, 'array', []);
                                $this->var()->find('instance_attachgroup', $attachgroup, 'int:1:', null);

                                if (empty($name) || strlen($name) > 64) {
                                    $invalid['name'] = $this->ml('Name must be a string between 1 and 64 characters long');
                                } elseif (!preg_match('!^([a-z0-9_])*$!', $name)) {
                                    $invalid['name'] = $this->ml('Name can only contain the characters [a-z0-9_]');
                                } elseif ($name != $blockinfo['name']) {
                                    $check = $instancesapi->getitem(['name' => $name]);
                                    if ($check && $check['block_id'] != $blockinfo['block_id']) {
                                        $invalid['name'] = $this->ml('A block instance named "#(1)" already exists', $name);
                                    }
                                }

                                if (!empty($title) && strlen($title) > 254) {
                                    $invalid['title'] = $this->ml('Title must be a string no more than 254 characters long');
                                }


                                if (!isset($instance_states[$state])) {
                                    $invalid['state'] = $this->ml('Unknown block instance state');
                                }

                                if (!empty($box_template) && strlen($box_template) > 127) {
                                    $invalid['templates'] = $this->ml('Template must be a string no more than 127 characters long');
                                }
                                if (!empty($block_template) && strlen($block_template) > 127) {
                                    $invalid['templates'] = $this->ml('Template must be a string no more than 127 characters long');
                                }

                                $blockinfo_groups = [];
                                if (!empty($groups)) {
                                    foreach ($groups as $group_id => $tpls) {
                                        if (!isset($block_groups[$group_id])) {
                                            continue;
                                        }
                                        if (!is_string($tpls['box_template'])) {
                                            $tpls['box_template'] = '';
                                            $badtemplates = true;
                                        } elseif (strlen($tpls['box_template']) > 127) {
                                            $badtemplates = true;
                                        }
                                        if (!is_string($tpls['block_template'])) {
                                            $tpls['block_template'] = '';
                                            $badtemplates = true;
                                        } elseif (strlen($tpls['block_template']) > 127) {
                                            $badtemplates = true;
                                        }
                                        $blockinfo_groups[$group_id] = $tpls;
                                    }
                                    if (!empty($badtemplates)) {
                                        $invalid['templates'] = $this->ml('Template must be a string no more than 127 characters long');
                                    }
                                }

                                if (!empty($attachgroup)) {
                                    if (!isset($block_groups[$attachgroup])) {
                                        $invalid['attachgroup'] = $this->ml('Specified block group does not exist');
                                    } elseif (isset($blockinfo_groups[$attachgroup])) {
                                        $invalid['attachgroup'] = $this->ml('Instance is already a member of #(1) group', $blockinfo_groups[$attachgroup]['name']);
                                    }
                                    if (!empty($invalid['attachgroup'])) {
                                        $attachgroup = null;
                                    }
                                }

                                $blockinfo['name'] = $name;
                                $blockinfo['title'] = $title;
                                $blockinfo['state'] = $state;
                            }

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
                                    $invalid['update'] = $this->ml('Failed updating block instance configuration');
                                }
                            } else {
                                $invalid['check'] = $this->ml('Failed validating block instance form input');
                            }

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

                                if ($isadmin) {
                                    if (!empty($expire) && !$expire_reset) {
                                        // convert expire time from dd:hh:mm:ss format to an integer
                                        $expire = $userapi->convertseconds(['direction' => 'to', 'starttime' => $expire, 'countdays' => true]);
                                        // block expires in now + expire time
                                        $expire += time();
                                        $block->setExpire($expire);
                                    } elseif ($expire_reset) {
                                        $block->setExpire(0);
                                    }
                                    $block->setBoxTemplate($box_template);
                                    $block->setBlockTemplate($block_template);
                                    if (!empty($attachgroup)) {
                                        $blockinfo_groups[$attachgroup] = [
                                            'box_template' => '', 'block_template' => '',
                                        ];
                                    }
                                    $old_groups = $block->getGroups();
                                    foreach ($blockinfo_groups as $group_id => $tpls) {
                                        if (!empty($tpls['detach'])) {
                                            $block->detachGroup($group_id);
                                            $group_block = $blocksapi->getblock($block_groups[$group_id]);
                                            $group_block->detachInstance($blockinfo['block_id']);
                                        } else {
                                            $block->attachGroup($group_id, $tpls['box_template'], $tpls['block_template']);
                                            if (isset($old_groups[$group_id])) {
                                                continue;
                                            }
                                            $group_block = $blocksapi->getblock($block_groups[$group_id]);
                                            $group_block->attachInstance($blockinfo['block_id']);
                                        }
                                        $group_update = [
                                            'block_id' => $group_id,
                                            'content' => $group_block->storeContent(),
                                        ];
                                        if (!$instancesapi->updateitem($group_update)) {
                                            return;
                                        }
                                        unset($group_block, $group_update);
                                    }

                                }
                            } else {
                                // invalid data, pass what we got back to the form...
                                if ($isadmin) {
                                    $blockinfo['attachgroup'] = $attachgroup;
                                }
                            }

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
                                        $invalid['update'] = $this->ml('Failed updating block instance configuration');
                                    }
                                }
                            } else {
                                $invalid['check'] = $this->ml('Failed validating block instance form input');
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
                    break;
                case 'caching':

                    $this->var()->find(
                        'instance_nocache',
                        $nocache,
                        'checkbox',
                        false
                    );
                    $this->var()->find(
                        'instance_pageshared',
                        $pageshared,
                        'checkbox',
                        false
                    );
                    $this->var()->find(
                        'instance_usershared',
                        $usershared,
                        'int:0:2',
                        0
                    );
                    $this->var()->find(
                        'instance_cacheexpire',
                        $cacheexpire,
                        'str:1:',
                        null
                    );

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
                                $invalid['update'] = $this->ml('Failed updating block instance caching configuration');
                            }
                        }
                    } else {
                        $invalid['check'] = $this->ml('Failed validating block instance caching form input');
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
                                $invalid['update'] = $this->ml('Failed updating block instance access configuration');
                            }
                        }
                    } else {
                        $invalid['check'] = $this->ml('Failed validating block instance access form input');
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
                        $isvalid = $accessproperty->checkInput('instance_display_access');
                        $block->setAccess('display', $accessproperty->getValue());
                        $isvalid = $accessproperty->checkInput('instance_modify_access');
                        $block->setAccess('modify', $accessproperty->getValue());
                        $isvalid = $accessproperty->checkInput('instance_delete_access');
                        $block->setAccess('delete', $accessproperty->getValue());
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
            $blockinfo['content'] = $block->storeContent();
            // valid input, go ahead and update the block instance info
            if (empty($invalid)) {

                if (!$instancesapi->updateitem($blockinfo)) {
                    return;
                }

                $this->var()->find(
                    'return_url',
                    $return_url,
                    'pre:trim:str:1:',
                    ''
                );
                if (empty($return_url)) {
                    $return_url = $this->ctl()->getModuleURL(
                        'blocks',
                        'admin',
                        'modify_instance',
                        [
                            'block_id' => $blockinfo['block_id'],
                            'interface' => $interface,
                            'block_method' => $method,
                        ]
                    );
                }
                $this->ctl()->redirect($return_url);
            }
            // failed to validate, pass the invalid messages back to the form
            $data['invalid'] = $invalid;
        }

        // handle display phase
        switch ($interface) {
            case 'display':
                switch ($method) {
                    case 'info':
                        // $blockinfo already gives us most of what we need
                        // get params that can be set in block tag attributes
                        // @todo: this should be a method of the basicblock/blocktype class
                        // @todo: have the method return better definitions (data type hint, validation)
                        $block_params = [];
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
                                $block_params[$k] = [
                                    'attribute' => $k,
                                    'datatype' => $datatype,
                                    'default' => $value,
                                ];
                            }
                        }
                        $data['block_params'] = $block_params;

                        // show additional info if supplied by block type
                        if (xarBlock::hasMethod($block, 'info', true)) {
                            $data['block_output'] = xarBlock::guiMethod($block, 'info');
                        }

                        break;
                    case 'preview':
                        // show using preview method if supplied by block type...
                        if (xarBlock::hasMethod($block, 'preview', true)) {
                            $data['block_output'] = xarBlock::guiMethod($block, 'preview');
                        }
                        // or using display method otherwise...
                        else {
                            $data['block_output'] = xarBlock::guiMethod($block, 'display');
                        }
                        break;

                    case 'help':
                        // show help info if supplied by block type
                        if (xarBlock::hasMethod($block, 'help', true)) {
                            $data['block_output'] = xarBlock::guiMethod($block, 'help');
                        }
                        break;
                    case 'status':

                        break;
                    default:
                        // show custom info if supplied by block type
                        if (xarBlock::hasMethod($block, $method, true)) {
                            $data['block_output'] = xarBlock::guiMethod($block, $method);
                        }
                        break;
                }
                break;
            case 'config':
                switch ($method) {
                    case 'config':
                        try {
                            $data['block_output'] = xarBlock::guiMethod($block, 'configmodify', 'config-' . $block->type);
                        } catch (FunctionNotFoundException $e) {
                            try {
                                $data['block_output'] = xarBlock::guiMethod($block, 'modify');
                            } catch (FunctionNotFoundException $f) {
                                $data['block_output'] = '';
                            } catch (FileNotFoundException $f) {
                                $data['block_output'] = '';
                            } catch (Exception $f) {
                                throw $f;
                            }
                        } catch (Exception $e) {
                            throw $e;
                        }
                        if ($isadmin) {
                            if (!empty($blockinfo['content']['expire'])) {
                                $now = time();
                                $soon = $blockinfo['content']['expire'] - $now ;
                                $blockinfo['expirein'] = $soon;
                                if ($now > $blockinfo['content']['expire'] &&
                                    $blockinfo['content']['expire'] != 0) {
                                    $blockinfo['expire'] = 0;
                                } else {
                                    $blockinfo['expire'] = $blockinfo['content']['expire'];
                                }
                            } else {
                                $blockinfo['expire'] = 0;
                                $blockinfo['expirein'] = 0;
                            }
                            $groups = [];
                            if (!empty($blockinfo['content']['instance_groups'])) {
                                foreach ($block_groups as $group_id => $group) {
                                    if (!isset($blockinfo['content']['instance_groups'][$group_id])) {
                                        continue;
                                    }
                                    $group += $blockinfo['content']['instance_groups'][$group_id];
                                    $group['detach'] = !empty($group['detach']);
                                    $groups[$group_id] = $group;
                                }
                            }
                            $blockinfo['groups'] = $groups;
                            $group_options = [];
                            foreach ($block_groups as $id => $group) {
                                if (isset($groups[$id])) {
                                    continue;
                                }
                                $group_options[$id] = [
                                    'id' => $id,
                                    'name' => $group['name'],
                                ];
                            }
                            $data['group_options'] = $group_options;
                            if (!isset($blockinfo['attachgroup'])) {
                                $blockinfo['attachgroup'] = null;
                            }
                        }
                        break;
                    default:
                        // show custom configuration supplied by block type
                        $modify_method = $method . 'modify';
                        $data['block_output'] = xarBlock::guiMethod($block, $modify_method, $method . '-' . $block->type);
                        break;
                }
                break;
            case 'caching':
                // convert expire time to hh:mm:ss format for display
                if (!empty($blockinfo['content']['cacheexpire'])) {
                    $blockinfo['content']['cacheexpire'] = $userapi->convertseconds(['direction' => 'from', 'starttime' => $blockinfo['content']['cacheexpire']]);
                }
                $data['usershared_options'] = [
                    ['id' => 0, 'name' => $this->ml('No Sharing')],
                    ['id' => 1, 'name' => $this->ml('Group Members')],
                    ['id' => 2, 'name' => $this->ml('All Users')],
                ];
                break;
            case 'access':
                // nothing special...
                break;
            case 'export':
                $instancefields = ['block_id', 'type_id', 'type', 'name', 'title', 'state', 'content'];
                $xml = '';
                $xml .= '<block name="' . $blockinfo['name'] . '">' . "\n";
                foreach ($blockinfo as $key => $value) {

                    // Only pass the fields we want
                    if (!in_array($key, $instancefields)) {
                        continue;
                    }

                    if (is_array($value)) {/*
                            foreach ($value as $k => $v) {
                                $v = $this->var()->prep($v);
                                $value[$k] = $v;
                            }*/
                        $xml .= "  <$key>";
                        $xml .= base64_encode(serialize($value));
                        $xml .= "</$key>\n";
                    } else {
                        $xml .= "  <$key>";
                        $xml .= base64_encode($value);
                        $xml .= "</$key>\n";
                    }
                }
                $xml .= "</block>";
                $data['xml'] = & $xml;
                break;
            default:
                if (empty($method)) {
                    $method = $interface;
                }
                // show custom configuration supplied by block type
                $modify_method = $method . 'modify';
                $data['block_output'] = xarBlock::guiMethod($block, $modify_method, $method . '-' . $block->type);

                break;
        }

        $data['block'] = $blockinfo;
        $data['interface'] = $interface;
        $data['method'] = $method;
        $data['isadmin'] = $isadmin;
        $data['instance_states'] = $instance_states;
        $data['type_states'] = $type_states;
        $interfaces = [];
        $interfaces[] = [
            'url' => $this->ctl()->getCurrentURL(['interface' => 'display', 'block_method' => null]),
            'label' => $this->ml('Info'),
            'title' => $this->ml('Display information about this block type'),
            'active' => ($interface == 'display' && $method == 'info'),
        ];
        if ($interface != 'display' || $method != 'status') {
            if ($canmodify) {
                $interfaces[] = [
                    'url' => $this->ctl()->getCurrentURL(['interface' => 'config', 'block_method' => null]),
                    'label' => $this->ml('Config'),
                    'title' => $this->ml('Modify default configuration for this block type'),
                    'active' => ($interface == 'config'),
                ];
            }
            if ($isadmin) {
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
            if ($isadmin) {
                $interfaces[] = [
                    'url' => $this->ctl()->getCurrentURL(['interface' => 'export', 'block_method' => null]),
                    'label' => $this->ml('Export'),
                    'title' => $this->ml('Export the data of this block to a XML file'),
                    'active' => ($interface == 'export'),
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
