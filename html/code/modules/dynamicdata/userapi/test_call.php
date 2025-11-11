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

namespace Xaraya\Modules\DynamicData\UserApi;

use Xaraya\Modules\DynamicData\UserApi;
use Xaraya\Modules\DynamicData\MethodClass;

/**
 * Test handling module function in separate file
 * @extends MethodClass<UserApi>
 */
class TestCallMethod extends MethodClass
{
    /**
     * Summary of __invoke
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     * @see UserApi::testCall()
     */
    public function __invoke(array $args = [])
    {
        $args['context'] ??= $this->getContext();
        $args['handled'] = true;
        $args['parent'] = $this->getParent()::class;
        // call other methods from the UserApi() class via ->getParent() here
        assert($this->getParent() instanceof UserApi);
        $args['other'] = $this->getParent()->other();
        return $args;
    }
}
