<?php

    class BasicBlock extends DataContainer implements iBlock
    {
        protected $descriptor;

        public $html_content = "";
        public $no_cache            = 0;
        public $pageshared          = 1;
        public $usershared          = 1;
        public $cacheexpire         = null;

        public $name                = 'BlockName';
        public $module              = 'BlockModule';
        public $text_type           = null;
        public $text_type_long      = 'base';
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

        function display(Array $data=array())
        {
            if (!xarSecurityCheck('View' . $data['module'], 0, 'Block', $data['type'] . ":" . $data['name'] . ":" . "$data[bid]")) {return;}
            // Get variables from content block
            if (!is_array($data['content'])) $data['content'] = unserialize($data['content']);
            if (empty($data['content'])) $data['content'] = array();
            return $data;
        }

        public function modify(Array $data=array())
        {
            // Get current content
            if (!is_array($data['content'])) {
                $exploded = @unserialize($data['content']);
                if (is_array($exploded))
                    $data = array_merge($data,$exploded);
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
