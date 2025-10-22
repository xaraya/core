<?php

/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 * @author Jim McDonald, Fl�vio Botelho <nuncanada@xaraya.com>, mikespub <postnuke@mikespub.net>
 */

namespace Xaraya\Modules\Categories;

class Tables
{
    /**
     * Specifies module tables namees
     *
     * @author  Jim McDonald, Flavio Botelho <nuncanada@xaraya.com>
     * @author  mikespub <postnuke@mikespub.net>
     * @return array<mixed> Table information
     */
    public function __invoke(string $prefix = 'xar')
    {
        // Initialise table array
        $xartable = [];

        // Set the table name
        $xartable['categories'] = $prefix . '_categories';
        $xartable['categories_linkage'] = $prefix . '_categories_linkage';
        $xartable['categories_basecategories'] = $prefix . '_categories_basecategories';
        return $xartable;
    }
}
