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
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        $data = [];
        //Checkbox work for submit buttons too
        $this->var()->check('return_url', $data['return_url']);
        $this->var()->find('reassign', $reassign, 'checkbox', false);
        $this->var()->find('repeat', $data['repeat'], 'int:1:100', 1);
        if ($reassign) {
            $this->ctl()->redirect($this->ctl()->getModuleURL('categories', 'admin', 'new', ['repeat' => $data['repeat']]));
            return true;
        }

        for ($i = 1;$i <= $data['repeat'];$i++) {
            $data['objects'][$i] = $this->data()->getObject(['name' => $this->mod()->getVar('categoriesobject'), 'fieldprefix' => $i]);
            $isvalid = $data['objects'][$i]->checkInput();
        }

        if (!$isvalid) {
            $data['authid'] = $this->sec()->genAuthKey();
            $data['context'] ??= $this->getContext();
            return $this->tpl()->module('categories', 'admin', 'new', $data);
        }

        for ($i = 1;$i <= $data['repeat'];$i++) {
            $data['objects'][$i]->createItem();
        }

        $this->ctl()->redirect($this->ctl()->getModuleURL('categories', 'admin', 'view'));
        //    $this->ctl()->redirect($this->ctl()->getModuleURL('categories','admin','new',array('repeat' => $data['repeat'])));
        return true;
    }
}
