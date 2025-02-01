<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/

use Xaraya\Bridge\GraphQL\GraphQLHandler;
use sys;

sys::import('xaraya.bridge.graphql.handler');

/**
 * Class to handle GraphQL queries
 * @uses \sys::autoload()
 */
class xarGraphQL extends GraphQLHandler
{
}
