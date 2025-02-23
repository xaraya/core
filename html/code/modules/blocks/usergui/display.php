<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\UserGui;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\UserGui;
use Xaraya\Modules\Blocks\UserApi;
use xarMod;
use xarVar;
use sys;

sys::import('modules.blocks.method');

/**
 * blocks user display function
 * @extends MethodClass<UserGui>
 */
class DisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marcel van der Boom <mrb@hsdev.com>
     * @param array<string,mixed> $args Parameter data array.
     * @return array|void Display data array
     * @see UserGui::display()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        // Get all the available blocks
        $benum = 'enum';
        $data = [];
        foreach ($userapi->getall() as $bid => $binfo) {
            $benum .= ':' . $binfo['name'];
        }
        xarVar::fetch('name', $benum, $name);

        // Template issues a wrapped xar:block tag.
        $data['name'] = $name;
        return $data;
    }
}
