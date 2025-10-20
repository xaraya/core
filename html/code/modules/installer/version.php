<?php

/**
 * Version information for the Installer module
 *
 * @package modules\installer
 * @subpackage installer
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/200.html
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */

namespace Xaraya\Modules\Installer;

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
            'name' => 'Xaraya Installer',
            'id' => '200',
            'version' => '2.8.1',
            'displayname' => 'Installer',
            'description' => 'Install and customize Xaraya.',
            'displaydescription' => 'Install and customize Xaraya.',
            'credits' => '',
            'help' => '',
            'changelog' => '',
            'license' => '',
            'official' => true,
            'author' => 'Paul Rosania, Johnny Robeson',
            'contact' => 'http://www.xaraya.com/',
            'admin' => false,
            'user' => false,
            'class' => 'Core Admin',
            'category' => 'System',
            'twigtemplates' => false,
        ];
    }
}
