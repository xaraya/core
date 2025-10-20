<?php

/**
 * Version information for the Base module
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author John Robeson
 * @author Greg Allan
*/

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */

namespace Xaraya\Modules\Base;

class Version
{
    /**
     * Get module version information
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'name' => 'Base',
            'id' => '68',
            'displayname' => 'Base',
            'version' => '2.8.1',
            'description' => 'Home Page',
            'displaydescription' => 'Home Page',
            'credits' => 'xardocs/credits.txt',
            'help' => 'xardocs/help.txt',
            'changelog' => 'xardocs/changelog.txt',
            'license' => 'xardocs/license.txt',
            'official' => true,
            'author' => 'John Robeson, Greg Allan',
            'contact' => 'http://www.xaraya.com/',
            'admin' => true,
            'user' => true,
            'class' => 'Core Admin',
            'category' => 'System',
            'twigtemplates' => true,
        ];
    }
}
