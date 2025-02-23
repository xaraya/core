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
use xarModVars;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * base user main function
 * @extends MethodClass<UserGui>
 */
class MainMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * The main user interface function of this module.
     * This function is the default function, and is called whenever the module is
     * initiated without defining arguments.
     * The function displays the module's main entry page, or redirects to different page if the admin has defined one.
     * @author Paul Rosania
     * @author Marc Lutolf
     * @param array<string,mixed> $args Optional parameters
     * @return mixed output display string
     * @see UserGui::main()
     */
    public function __invoke(array $args = [])
    {
        // Security Check
        if (!$this->sec()->checkAccess('ViewBase')) {
            return;
        }

        /* fetch some optional 'page' argument or parameter */
        extract($args);
        $this->var()->find('page', $page, 'str', '');
        if (!empty($page)) {
            $this->tpl()->setPageTitle($page);
            /* Cache the custom page name so it is accessible elsewhere */
            $this->var()->setCached('Base.pages', 'page', $page);
        } else {
            $pageTemplate = $this->mod()->getVar('AlternatePageTemplateName');
            if ($this->mod()->getVar('UseAlternatePageTemplate') != '' &&
                $pageTemplate != '') {
                $this->tpl()->setPageTemplateName($pageTemplate);
            }
            $this->tpl()->setPageTitle($this->ml('Welcome'));
        }
        /**
         * if you want to include different pages in your user-main template,
         * return an array of template variables
         */
        // return ['page' => $page];
        /**
         * if you want to use different user-main-<page> templates,
         * call $this->tpl()->module() yourself and pass along the context
         */
        $data = [];
        // Pass along the context for $this->tpl()->module() if needed
        $data['context'] = $this->getContext();
        return $this->tpl()->module('base', 'user', 'main', $data, $page);
    }
}
