<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Displays a Text/HTML/PHP Block
 *
 * @author Jason Judge
 */
    sys::import('xaraya.structures.containers.blocks.basicblock');

    class ContentBlock extends BasicBlock implements iBlock
    {
        public $name                = 'ContentBlock';
        public $module              = 'base';
        public $text_type           = 'Content';
        public $text_type_long      = 'Generic Content Block';
        public $allow_multiple      = true;
        public $show_preview        = true;

        public $nocache             = 1;
        public $expire              = 0;

        public $content_text        = '';
        public $content_type        = 'text';
        public $hide_empty          = true;
        public $custom_format       = '';
        public $hide_errors         = true;
        public $start_date          = '';
        public $end_date            = '';

        public $func_update         = 'base_contentblock_update';
        public $notes               = "content_type can be 'text', 'html', 'php' or 'data'";

/**
 * Display func.
 * @param $blockinfo array
 * @returns $blockinfo array
 */
 
        function display(Array $data=array())
        {
            $data = parent::display($data);
            if (empty($data)) return;

            // Check if the block is within its start/end period
            $now = time();
            if (
                (!empty($vars['start_date']) && $vars['start_date'] > $now)
                || (!empty($vars['end_date']) && $vars['end_date'] < $now)
            ) {
                // Not yet started.
                return;
            }
    
            // Special preparation for each content type.
            if ($data['content_type'] == 'text') {
            // Nothing special

            } elseif ($data['content_type'] == 'php' || $data['content_type'] == 'data') {
                // Execute the PHP code.
                ob_start();
                if (!empty($data['hide_errors'])) {
                    $result = @eval($data['content_text']);
                } else {
                    $result = eval($data['content_text']);
                }
                $data['content_text'] = ob_get_contents();
                ob_end_clean();

                if ($result === false && !empty($data['hide_errors'])) {
                    // If the PHP code returns a boolean 'false', then the block
                    // will not be displayed. This allows the code in a PHP block
                    // to suppress its own output completely.
                    // Note: only works if PHP errors are hidden, since a PHP
                    // error will also return a false.
                    return;
                }

                // If the format is 'data' then an array can be returned, that
                // gets merged with the content for the output template.
                if ($data['content_type'] == 'data') {
                    if (is_array($result)) {
                        $data = array_merge($result, $vars);
                        $vars = $data;
                    } else {
                        // Structured data not returned.
                        return;
                    }
                }

                if (isset($title) && is_string($title)) {
                    // The PHP code can set the title of the block (my treat).
                    // Just include $title='whatever';  in the block code.
                    $data['title'] = $title;
                }
            }

            if ($data['content_type'] != 'data' && !empty($data['hide_empty']) && trim($data['content_text']) == '') {
                // Block is empty - hide it (but not 'data' type, as no output
                // is required for that type).
                return;
            }

            // Split the text into lines, to help rendering.
            $data['content']['content_lines'] = explode("\n", $data['content_text']);

            return $data;
        }

/**
 * Modify Function to the Blocks Admin
 * @author Jason Judge
 * @param $blockinfo array containing title,content
 */
        public function modify(Array $data=array())
        {
            $data = parent::modify($data);

            // Defaults
            if (!isset($data['content_text'])) $data['content_text'] = $this->content_text;

            if (empty($data['expire'])) $data['expire'] = $this->expire;
            if (empty($data['html_content'])) $data['html_content'] = $this->html_content;

            // Drop-down list defining content type.
            $content_types = array();
            $content_types[] = array('value' => 'text', 'label' => xarML('Text'));
            $content_types[] = array('value' => 'html', 'label' => xarML('HTML'));
            $content_types[] = array('value' => 'php', 'label' => xarML('PHP (echo capture)'));
            $content_types[] = array('value' => 'data', 'label' => xarML('PHP (template data)'));
            $data['content_types'] = $content_types;

            return $data;
        }

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
        public function update(Array $data=array())
        {
            $data = parent::update($data);

            if (xarVarFetch('content_type', 'pre:lower:passthru:enum:text:html:php:custom:data', $content_type, 'text', XARVAR_NOT_REQUIRED)) {
                $args['content_type'] = $content_type;
            }

            // TODO: check the flags that allow a posted value to override the existing value.
            if (xarVarFetch('content_text', 'str:1', $content_text, '', XARVAR_NOT_REQUIRED)) {
                $args['content_text'] = $content_text;
            }

            if (xarVarFetch('hide_errors', 'checkbox', $hide_errors, false, XARVAR_NOT_REQUIRED)) {
                $args['hide_errors'] = $hide_errors;
            }

            if (xarVarFetch('hide_empty', 'checkbox', $hide_empty, false, XARVAR_NOT_REQUIRED)) {
                $args['hide_empty'] = $hide_empty;
            }

            if (xarVarFetch('custom_format', 'pre:lower:ftoken:str:0:20', $custom_format, '', XARVAR_NOT_REQUIRED)) {
                $args['custom_format'] = $custom_format;
            }

            if (xarVarFetch('start_date', 'str', $start_date, '0', XARVAR_NOT_REQUIRED)) {
                // Convert the start date into a datetime format.
                // TODO: is this the way we should be converting dates from the calendar property?
                if (!empty($start_date)) {
                    $args['start_date'] = strtotime($start_date);
                } else {
                    $args['start_date'] = '';
                }
            }

            if (xarVarFetch('end_date', 'str', $end_date, '0', XARVAR_NOT_REQUIRED)) {
                // Convert the end date into a datetime format.
                // TODO: is this the way we should be converting dates from the calendar property?
                if (!empty($end_date)) {
                    $args['end_date'] = strtotime($end_date);
                } else {
                    $args['end_date'] = '';
                }
            }

            $data['content'] = $args;
            return $data;
        }

    }

?>