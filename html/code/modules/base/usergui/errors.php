<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\UserGui;
use sys;

sys::import('xaraya.modules.method');

/**
 * base user errors function
 * @extends MethodClass<UserGui>
 */
class ErrorsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Entry point for custom error messages
     * Use this for redirecting pages from other applications or within Xaraya
     * @author Marc Lutolf <mfl@netspan.ch>
     * @see UserGui::errors()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        $this->var()->check('errortype', $errortype, 'str', '');
        switch ($errortype) {
            case 'forbidden':
                $this->var()->check('message', $msg, 'str', '');
                $this->var()->check('template', $template, 'str', null);
                return $this->ctl()->forbidden($msg, $template);
            case 'exception':
            case 'systemerror':
            case 'systeminfo':
            case 'usererror':
            case 'notfound':
            default:
                $this->var()->check('message', $msg, 'str', '');
                $this->var()->check('template', $template, 'str', null);
                return $this->ctl()->notFound($msg, $template);
        }
    }
}
