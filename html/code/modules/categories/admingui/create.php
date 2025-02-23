<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\AdminGui;
use DataObjectFactory;
use xarController;
use xarModVars;
use xarSec;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories admin create function
 * @extends MethodClass<AdminGui>
 */
class CreateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Create one or more new categories
     * @return bool|string|void Returns true on success, string on security failure
     * @see AdminGui::create()
     */
    public function __invoke(array $args = [])
    {
        // Confirm authorisation code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        $data = [];
        //Checkbox work for submit buttons too
        $this->var()->check('return_url', $data['return_url']);
        $this->var()->find('reassign', $reassign, 'checkbox', false);
        $this->var()->find('repeat', $data['repeat'], 'int:1:100', 1);
        if ($reassign) {
            xarController::redirect(xarController::URL('categories', 'admin', 'new', ['repeat' => $data['repeat']]), null, $this->getContext());
            return true;
        }

        sys::import('modules.dynamicdata.class.objects.factory');
        for ($i = 1;$i <= $data['repeat'];$i++) {
            $data['objects'][$i] = DataObjectFactory::getObject(['name' => xarModVars::get('categories', 'categoriesobject'), 'fieldprefix' => $i]);
            $isvalid = $data['objects'][$i]->checkInput();
        }

        if (!$isvalid) {
            $data['authid'] = xarSec::genAuthKey();
            $data['context'] ??= $this->getContext();
            return xarTpl::module('categories', 'admin', 'new', $data);
        }

        for ($i = 1;$i <= $data['repeat'];$i++) {
            $data['objects'][$i]->createItem();
        }

        xarController::redirect(xarController::URL('categories', 'admin', 'view'), null, $this->getContext());
        //    xarController::redirect(xarController::URL('categories','admin','new',array('repeat' => $data['repeat'])), null, $this->getContext());
        return true;
    }
}
