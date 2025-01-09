<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.5.6
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\UserApi;

use Xaraya\DataObject\UserApi;
use Xaraya\Modules\MethodClass;
use sys;

sys::import('xaraya.modules.method');

/**
 * Test handling module function in separate file
 */
class TestCallMethod extends MethodClass
{
    public function __invoke(array $args = [])
    {
        $args['context'] ??= $this->getContext();
        $args['handled'] = true;
        $args['parent'] = $this->parent::class;
        // call other methods from the UserApi() class via ->parent here
        assert($this->parent instanceof UserApi);
        $args['other'] = $this->parent->other();
        return $args;
    }
}
