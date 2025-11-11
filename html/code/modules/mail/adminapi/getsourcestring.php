<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminApi;
use FileNotFoundException;

/**
 * mail adminapi getsourcestring function
 * @extends MethodClass<AdminApi>
 */
class GetsourcestringMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Psspl : Added API function to read the contents of template files (.xt) as plain text
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @see AdminApi::getsourcestring()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        $sourceFileName = $adminapi->getsourcefilename($args);
        if (!file_exists($sourceFileName)) {
            throw new FileNotFoundException($sourceFileName);
        }
        $string = '';
        $fd = fopen($sourceFileName, 'r');
        while (!feof($fd)) {
            $line = fgets($fd, 1024);
            $string .= $line;
        }
        $message = $string;
        fclose($fd);
        $message = str_replace(
            '<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">',
            '',
            $message
        );
        $message = str_replace(
            '</xar:template>',
            '',
            $message
        );
        return $message;
    }
}
