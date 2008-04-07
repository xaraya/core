<?php

    sys::import('xaraya.structures.containers.blocks.basicblock');

    class PHPBlock extends BasicBlock
    {
        public $name                = 'PHPBlock';
        public $module              = 'base';
        public $text_type           = 'PHP';
        public $text_type_long      = 'PHP Script';
        public $allow_multiple      = true;
        public $form_content        = true;
        public $show_preview        = true;

        public function display(Array $blockinfo=array())
        {
            // Security Check
            if(!xarSecurityCheck('ViewBaseBlocks',0,'Block',"php:$blockinfo[title]:$blockinfo[bid]")) return;

            ob_start();
            print eval($blockinfo['content']);
            $blockinfo['content'] = ob_get_contents();
            ob_end_clean();

            if (empty($blockinfo['content'])){
                $blockinfo['content'] = xarML('Content is empty');
            }

            return $blockinfo;
        }
    }
?>
