<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 */
/**
 * @return array<mixed>|bool|void data for the template display
 */

    function privileges_user_errors()
    {
        $data = [];
        if(!xarVar::fetch('layout',   'isset', $data['layout']   , 'default', xarVar::DONT_SET)) {return;}
        if(!xarVar::fetch('redirecturl',   'isset', $data['redirecturl']   , 'local_halt', xarVar::DONT_SET)) {return;}
        if (!xarUser::isLoggedIn()) {
            return $data;
        } else {
            if ($data['redirecturl'] == 'local_halt') {
            	return $data;
            } else {
				xarController::redirect($data['redirecturl']);
				return true;
            }
        }
    }
