<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Displays a Text/HTML/PHP Block
 *
 * @author Jason Judge
 */
    sys::import('modules.base.xarblocks.content');

    class Base_ContentBlockAdmin extends Base_ContentBlock implements iBlock
    {
/**
 * Modify Function to the Blocks Admin
 * @author Jason Judge
 * @param $blockinfo array containing title,content
 */
        public function modify(Array $data=array())
        {
            $data = $this->getContent();

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