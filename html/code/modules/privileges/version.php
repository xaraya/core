<?php

/**
 * Version information for the Privileges module
 *
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/* WARNING
 * Modification of this file is not supported.
 * Any modification is at your own risk and
 * may lead to inablity of the system to process
 * the file correctly, resulting in unexpected results.
 */

namespace Xaraya\Modules\Privileges;

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
            'name' => 'Privileges Adminstration',
            'id' => '1098',
            'version' => '2.4.1',
            'displayname' => 'Privileges',
            'description' => 'Modify privileges security',
            'displaydescription' => 'Modify privileges security',
            'official' => true,
            'author' => 'Marc Lutolf',
            'contact' => 'http://www.xaraya.com/',
            'admin' => true,
            'user' => false,
            'class' => 'Core Complete',
            'category' => 'Users & Groups',
            'securityschema'
             => [
                 'Privileges::' => 'name:id',
             ],
            'twigtemplates' => true,
        ];
    }
}
