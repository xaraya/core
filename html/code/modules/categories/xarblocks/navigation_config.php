<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

sys::import('modules.categories.xarblocks.navigation');

class Categories_NavigationBlockConfig extends Categories_NavigationBlock implements iBlock
{
    /**
     * Modify Function to the Blocks Admin
     * 
     * @param void N/A
     * @return array Returns data array
     */
    public function configmodify()
    {
        $data = $this->getContent();

        $data['modules'] = array();
        $data['modules'][] = array('id' => '',
                                   'name' => xarML('Adapt dynamically to current page'));

        // List contains:
        // 0. option group for the module
        // 1. module [base1|base2]
        // 2.    module [base1]    (for itemtype 0)
        //       module [base2]
        // 3.    module:itemtype [base3|base4]
        // 4.       itemtype [base3]
        //          itemtype [base4]

        sys::import('modules.categories.class.worker');
        $worker = new CategoryWorker();
        $allcatbases = $worker->getcatbases(array('order'=>'module', 'format'=>'tree'));

        foreach($allcatbases as $modulecatbases) {
            // Module label for the option group in the list.
            $modlabel = xarML('#(1)', ucwords($modulecatbases['module']));
            $data['modules'][] = array('label' => $modlabel);
    
            $indent = '&#160;&#160;&#160;';
    
            foreach($modulecatbases['itemtypes'] as $thisitemtype => $itemtypecatbase) {
                if (!empty($itemtypecatbase['catbases'])) {
                    $catlist = '[';
                    $join = '';
                    foreach($itemtypecatbase['catbases'] as $itemtypecatbases) {
                        $catlist .= $join . $itemtypecatbases['category']['name'];
                        $join = ' | ';
                    }
                    $catlist .= ']';
    
                    //if (empty($itemtypecatbase['itemtype']['label'])) {
                    if ($thisitemtype == 0) {
                        // Default module cats at top level.
                        $indent_level = 0;
                        $itemtypelabel = '';
                    } else {
                        // Item types at one level deeper
                        $indent_level = 1;
                        $itemtypelabel = ' -&gt; ' . xarML('#(1)', $itemtypecatbase['itemtype']['label']);
                    }
    
                    // Module-Itemtype [all cats]
                    $data['modules'][] = array(
                        'id' => $modulecatbases['module'] . '.' . $thisitemtype . '.0',
                        'name' => str_repeat($indent, $indent_level) . $modlabel . $itemtypelabel . ' ' . $catlist
                    );
    
                    // Individual categories a level deeper.
                    $indent_level += 1;
    
                    // Individual base categories where there are more than one.
                    if (count($itemtypecatbase['catbases']) > 1) {
                        foreach($itemtypecatbase['catbases'] as $itemtypecatbases) {
                            $catlist = '[' . $itemtypecatbases['category']['name'] . ']';
                            if ($thisitemtype == 0) {$itemtypelabel = $modlabel;}
                            $data['modules'][] = array(
                                'id' => $modulecatbases['module'] . '.' . $thisitemtype . '.' . $itemtypecatbases['category']['cid'],
                                'name' => str_repeat($indent, $indent_level) . $itemtypelabel . ' ' . $catlist
                            );
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Updates the Block config from the Blocks Admin
     * 
     * @param array $data Parameter data array
     * @return boolean|null Returns true on success and null on failure 
     */
    public function configupdate(Array $data=array())
    {
        if(!xarVarFetch('layout',       'isset', $vars['layout'],       $this->layout, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('showcatcount', 'isset', $vars['showcatcount'], false, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('showchildren', 'isset', $vars['showchildren'], $this->showchildren, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('showempty',    'checkbox', $vars['showempty'], false, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('startmodule',  'isset', $vars['startmodule'],  $this->startmodule, XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('dynamictitle', 'checkbox', $vars['dynamictitle'],  false, XARVAR_NOT_REQUIRED)) {return;}
        
        $this->setContent($vars);
        return true;
    }

    /**
     * built-in block help/information system.
     */
    /*
    function categories_navigationblock_help()
    {
        return '';
    }
    */
}

?>