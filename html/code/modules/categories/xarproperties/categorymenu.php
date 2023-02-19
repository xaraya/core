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
 * @author Marc Lutolf <mfl@netspan.ch>
 */

sys::import('modules.categories.xarproperties.categorytree');

/**
 * This property displays a multitiered menu of categories
 */
class CategoryMenuProperty extends CategoryTreeProperty
{
    public $id         = 30047;
    public $name       = 'categorymenu';
    public $desc       = 'CategoryMenu';
    public $reqmodules = array('categories');

	/**
	 * Display the property for input
	 * 
	 * @param  array data An array of input parameters
	 * @return string|void     HTML markup to display the property for input on a web page
	 */
    public function showInput(Array $data = array())
    {
        if(!xarVar::fetch('activetab',    'isset', $data['activetab'],    0, xarVar::NOT_REQUIRED)) {return;}

        if (!isset($data['parent'])) $data['parent'] = 0;
        if (!isset($data['levels'])) $data['levels'] = 1;
        // Could also do this using getchildren, although then we get more data we don't really need

        if ($data['parent']) {
            $node = new CategoryTreeNode($data['parent']);
            $tree = new CategoryTree($node);
            $trees = $node->breadthfirstenumeration($data['levels']);
            $trees = array_shift($trees);
            $data['layout'] = 'tree';
        } else {
            // the top level of categories need not have a common parent
            xarMod::loadDbInfo('categories');
            $xartable =& xarDB::getTables();
            sys::import('xaraya.structures.query');
            $q = new Query('SELECT',$xartable['categories']);
            $q->addfield('id');
            $q->addfield('name');
            $q->addfield('parent_id');
            $q->eq('parent_id',$data['parent']);
            if (!$q->run()) return;
            $trees = $q->output();
            $data['layout'] = 'toplevel';
        }

        $data['tabs'] = $trees;
        return parent::showInput($data);
    }
}
