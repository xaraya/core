<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject;

use Xaraya\Modules\UserApiClass;
use sys;

sys::import('xaraya.modules.userapi');

/**
 * Handle the dynamicdata rest API
 *
 * @method mixed getHello(array $args = []) Sample REST API call supported by this module (if any)
 * @method mixed getlist(array $args = []) Get the list of REST API calls supported by this module (if any) - Parameters and requestBody fields can be specified as follows: - => ['itemtype', 'itemids']  // list of field names, each defaults to type 'string' - => ['itemtype' => 'string', 'itemids' => 'array']  // specify the field type, 'array' defaults to array of 'string' - => ['itemtype' => 'string', 'itemids' => ['integer']]  // specify the array items type as 'integer' here - => ['itemtype' => ['type' => 'string'], 'itemids' => ['type' => 'array', 'items' => ['type' => 'integer']]]  // rest
 * @method mixed postHello(array $args = []) Sample REST API call supported by this module (if any)
 * @extends UserApiClass<Module>
 */
class RestApi extends UserApiClass
{
    // ...
}
