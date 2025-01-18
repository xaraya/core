<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 **/

namespace Xaraya\DataObject\TestGui;

use Xaraya\DataObject\TestGui;
use Xaraya\Modules\MethodClass;
use sys;

sys::import('xaraya.modules.method');

/**
 * Test handling module function in separate file with services
 * @extends MethodClass<TestGui>
 */
class TestServicesMethod extends MethodClass
{
    public function __invoke(array $args = [])
    {
        $args['method'] = __METHOD__;
        $args['return_url'] = $this->mod()->getURL('test', 'other', $args);
        return $this->mod()->prepare($args);
    }
}
