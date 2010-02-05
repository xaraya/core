<?php

    class BasicBlock extends DataContainer implements iBlock
    {
        protected $descriptor;

        public $html_content = "";
        public $nocache             = 0;
        public $pageshared          = 1;
        public $usershared          = 1;
        public $cacheexpire         = null;

        public $name                = 'BlockName';
        public $module              = 'BlockModule';
        public $text_type           = 'Basic Block';
        public $text_type_long      = 'Parent class for blocks';
        public $func_update         = null;
        public $allow_multiple      = false;
        public $form_content        = false;    // display textarea for content in the admin UI
        public $form_refresh        = false;    // display UI for periodic refreshing of the block
        public $show_preview        = false;

        public function __construct(ObjectDescriptor $descriptor)
        {
            $descriptor->refresh($this);
            $this->descriptor = $descriptor;
        }

        public function getArgs()
        {
            return $this->descriptor->getArgs();
        }

        public function getInfo()
        {
            return $this->getPublicProperties();
        }

        public function getInit()
        {
            $result = $this->getPublicProperties();
            $skiplist = array('name', 'module', 'text_type', 'text_type_long', 'func_update', 'allow_multiple', 'form_content', 'form_refresh', 'show_preview');
            foreach ($skiplist as $propname) {
                unset($result[$propname]);
            }
            return $result;
        }

        public function display(Array $data=array())
        {
            // Get variables from content block
            if (!is_array($data['content'])) {
                if (!empty($data['content'])) {
                    $exploded = @unserialize($data['content']);
                    if (is_array($exploded)) $data = array_merge($data,$exploded);
                    $data['content'] = $exploded;
                }
            } else {
                $data = array_merge($data,$data['content']);
            }

            $access = isset($data['content']['display_access']) ? $data['content']['display_access'] : array();
            $data['allowaccess'] = false;
            
            // FIXME: remove this once all blocks have access data
            if (empty($access)) {
                try {
                    if (xarSecurityCheck('View' . $data['module'], 0, 'Block', $data['type'] . ":" . $data['name'] . ":" . "$data[bid]")) {
                        $data['allowaccess'] = true;
                    }
                } catch (Exception $e) {}
                return $data;
            }

            // Decide whether this block is displayed to the current user
            $args = array(
                'module' => $data['module'],
                'component' => 'Block',
                'instance' => $data['type'] . ":" . $data['name'] . ":" . "$data[bid]",
                'group' => $access['group'],
                'level' => $access['level'],
            );
            sys::import('modules.dynamicdata.class.properties.master');
            $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
            $data['allowaccess'] = $accessproperty->check($args);
            
            //Pass the access data along
            $data['display_access'] = $access;
            return $data;
        }

        public function modify(Array $data=array())
        {
            // Get current content
            if (!is_array($data['content'])) {
                $exploded = @unserialize($data['content']);
                if (is_array($exploded)) $data = array_merge($data,$exploded);
            } else {
                $data = array_merge($data,$data['content']);
            }
            $data['blockid'] = $data['bid'];
            return $data;
        }

        public function update(Array $data=array())
        {
            if (!is_array($data['content'])) {
                $vars = unserialize($data['content']);
            } else {
                $vars = $data['content'];
            }

            if ($this->form_refresh) {
                if (!xarVarFetch('expire', 'int', $expire, 0, XARVAR_NOT_REQUIRED)) {return;}
                if ($expire > 0) $vars['expire'] = $expire + time();
                if (!isset($vars['expire'])) $vars['expire'] = 0;
            }
            if ($this->form_content) {
                if (!xarVarFetch('text_content', 'str:1', $text_content, '', XARVAR_DONT_SET)) {return;}
                $vars['text_content'] = $text_content;
            }
            $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
            $isvalid = $accessproperty->checkInput($data['name'] . '_display');
            $vars['display_access'] = $accessproperty->value;
            $isvalid = $accessproperty->checkInput($data['name'] . '_modify');
            $vars['modify_access'] = $accessproperty->value;
            $isvalid = $accessproperty->checkInput($data['name'] . '_delete');
            $vars['delete_access'] = $accessproperty->value;

            $data['content'] = $vars;
            return $data;
        }
    }

interface iBlock
{
    public function getInfo();
    public function display(Array $data=array());
    public function modify(Array $data=array());
    public function update(Array $data=array());
}

/*public class Block extends Object
    {

        public function info()
        {
            return array('text_type' => 'HTML',
                 'text_type_long' => 'HTML',
                 'module' => 'base',
                 'func_update' => 'base_htmlblock_update',
                 'allow_multiple' => true,
                 'form_content' => false,
                 'form_refresh' => false,
                 'show_preview' => true);

        }
    }
    */
?>
