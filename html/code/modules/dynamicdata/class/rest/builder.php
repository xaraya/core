<?php

/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
 *
 * @author mikespub <mikespub@xaraya.com>
 */

use Xaraya\Bridge\RestAPI\RestAPIBuilder;
use sys;

sys::import('xaraya.bridge.restapi.builder');

/**
 * Class to build DataObject REST API
 * @deprecated 2.6.2 use RestAPIBuilder() instead
 */
class DataObjectRESTBuilder extends RestAPIBuilder {}
