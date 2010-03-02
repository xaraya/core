<?php
/**
 * HTML block
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Manage html block
 * @author Patrick Kellum
 */
sys::import('modules.base.xarblocks.html');

    class HtmlBlockAdmin extends HTMLBlock implements iBlock
    {
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
        public function modify(Array $data=array())
        {
            $data = parent::modify($data);

            // Defaults
            if (empty($data['expire'])) $data['expire'] = $this->expire;
            if (empty($data['html_content'])) $data['html_content'] = $this->html_content;

            $now = time();
            if ($data['expire'] == 0){
                $data['expirein'] = 0;
            } else {
                $soon = $data['expire'] - $now ;
                $sooner = $soon / 3600;
                $data['expirein'] =  round($sooner);
            }

            return $data;
        }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
        public function update(Array $data=array())
        {
            $data = parent::update($data);
            if (!xarVarFetch('expire', 'str:1', $args['expire'], 0, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('html_content', 'str:1', $args['html_content'], '', XARVAR_NOT_REQUIRED)) {return;}

            // Defaults
            if ($args['expire'] != 0) {
                $now = time();
                $args['expire'] = $args['expire'] + $now;
            }

            $data['content'] = $args;
            return $data;
        }
    }

?>