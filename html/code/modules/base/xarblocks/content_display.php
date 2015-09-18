<?php
/**
 * Content Block display interface
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Display block
 *
 * Displays a Text/HTML/PHP Block
 *
 * @author Jason Judge
 */
sys::import('modules.base.xarblocks.content');
class Base_ContentBlockDisplay extends Base_ContentBlock implements iBlock
{
    /**
     * Display method
     * @param void N/A
     * @return array Returns display data array or null if not available.
     */
    function display()
    {
        $data = $this->getContent();

        // Check if the block is within its start/end period
        $now = time();
        if (
            (!empty($data['start_date']) && $data['start_date'] > $now)
            || (!empty($data['end_date']) && $data['end_date'] < $now)
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
                    $data = array_merge($result, $data);
                    $data = $data;
                } else {
                    // Structured data not returned.
                    return;
                }
            }

            if (isset($title) && is_string($title)) {
                // The PHP code can set the title of the block (my treat).
                // Just include $title='whatever';  in the block code.
                $this->setTitle($title);
            }
        }

        if ($data['content_type'] != 'data' && !empty($data['hide_empty']) && trim($data['content_text']) == '') {
            // Block is empty - hide it (but not 'data' type, as no output
            // is required for that type).
            return;
        }

        // Split the text into lines, to help rendering.
        $data['content_lines'] = explode("\n", $data['content_text']);
            
        return $data;
    }

}
?>