<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base;

use Xaraya\Modules\UserApiClass;

/**
 * Handle the base user API
 *
 * @method mixed browseFiles(array $args = []) Browse for files and directories (recursion supported).
 * @method mixed checklink(array $args = []) Check the status of some URL
 * @method mixed extractlinks(array $args = []) Extract a list of links from some HTML content (cfr. getfile and checklink) - Note: This is definitely not meant as an exhaustive link extractor
 * @method mixed getOutputBuffer(array $args = []) Get output buffer(s) (e.g. before trying to send back some file or image)
 * @method mixed getfavicon(array $args = []) Gets a file from the Internet - Returns the favicon (if any) from a given url - When no icon is found, an empty one is returned, defined in this function
 * @method mixed getfile(array $args = []) Gets a file from the Internet - Returns the content of the file (possibly cached). Not intended for large files.
 * @method mixed newcurl(array $args = []) Return a new xarCurl object.
 * @method mixed pager(array $args = []) Wrapper for xarTplPager::getPager() (see modules/base/class/pager.php) - Used by the base-pager template tag - Returns a pager based on url, startnum, itemsperpage and totalitems - Usage, eg <xar:pager startnum="1" itemsperpage="10" total="30"/>
 * @method mixed timesince(array $args = []) Returns a fomatted string of two of years/months/weeks/days/hours/minutes since a given time (unix timestamp).
 * @extends UserApiClass<Module>
 */
class UserApi extends UserApiClass
{
    /**
     * Summary of getmenulinks
     * @param array<mixed> $args
     * @return mixed
     */
    public function getmenulinks(array $args = [])
    {
        return [];
    }
}
