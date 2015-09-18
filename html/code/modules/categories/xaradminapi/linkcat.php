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

/**
 * Link items to categories
 * @param $args['cids'] Array of IDs of the category
 * @param $args['iids'] Array of IDs of the items
 * @param $args['basecids'] Array of IDs of the base category
 * @param $args['modid'] ID of the module
 * @param $args['itemtype'] item type

 * Links each cid in cids to each iid in iids

 * @param $args['clean_first'] If is set to true then any link of the item IDs
 *                             at iids will be removed before inserting the
 *                             new ones
 */

/**
 * Link items to categories or links each cid in cids to each iid in iids
 * 
 * @param $args['cids'] Array of IDs of the category
 * @param $args['iids'] Array of IDs of the items
 * @param $args['basecids'] Array of IDs of the base category
 * @param $args['modid'] ID of the module
 * @param $args['itemtype'] item type
 * @param $args['clean_first'] If is set to true then any link of the item IDs
 *                             at iids will be removed before inserting the
 *                             new ones
 * @return boolean|null Returns true on success, null on failure.
 * @throws BadParameterException Thrown if invalid parameters have been given
 */
function categories_adminapi_linkcat($args)
{
    // Argument check
    if (isset($args['clean_first']) && $args['clean_first'] == true)
    {
        $clean_first = true;
    } else {
        $clean_first = false;
    }
    
    // Do we check the validity of the categories before linking?
    $check = isset($args['check']) ? $args['check'] : true;
    
    if (
        (!isset($args['cids'])) ||
        (!isset($args['iids'])) ||
        (!isset($args['modid']))
       )
    {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null,$msg);
    }
    $basecids = isset($args['basecids']) ? $args['basecids'] : array();
    if (isset($args['itemtype']) && is_numeric($args['itemtype'])) {
        $itemtype = $args['itemtype'];
    } else {
        $itemtype = 0;
    }
    if (!empty($itemtype)) $modtype = $itemtype;
    else $modtype = 'All';

    if ($check) {
        foreach ($args['cids'] as $cid) {
              $cidparts = explode('.',$cid);
              $cid = $cidparts[0];
            $cat = xarMod::apiFunc('categories',
                                 'user',
                                 'getcatinfo',
                                 Array
                                 (
                                  'cid' => $cid
                                 )
                                );
             if ($cat == false) {
                $msg = xarML('Unknown Category');
                throw new BadParameterException(null, $msg);
             }
        }
    }

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();
    $categorieslinkagetable = $xartable['categories_linkage'];

    if ($clean_first)
    {
        // Get current links
        $childiids = xarMod::apiFunc('categories',
                                   'user',
                                   'getlinks',
                                   array('iids' => $args['iids'],
                                         'itemtype' => $itemtype,
                                         'modid' => $args['modid'],
                                         'reverse' => 0));
        if (count($childiids) > 0) {
            // Security check
            foreach ($args['iids'] as $iid)
            {
                foreach (array_keys($childiids) as $cid)
                {
                    if(!xarSecurityCheck('EditCategoryLink',1,'Link',"$args[modid]:$modtype:$iid:$cid")) return;
                }
            }
            // Delete old links
            $bindmarkers = '?' . str_repeat(',?',count($args['iids'])-1);
            $sql = "DELETE FROM $categorieslinkagetable
                    WHERE module_id = $args[modid] AND
                          itemtype = $itemtype AND
                          item_id IN ($bindmarkers)";
            $result = $dbconn->Execute($sql,$args['iids']);
            if (!$result) return;
        } else {
            // Security check
            foreach ($args['iids'] as $iid)
            {
                if(!xarSecurityCheck('SubmitCategoryLink',1,'Link',"$args[modid]:$modtype:$iid:All")) return;
            }
        }
    }

    foreach ($args['iids'] as $iid)
    {
      sys::import('xaraya.structures.query');
      sys::import('modules.categories.class.tag');
       $i=0;
       foreach ($args['cids'] as $cid)
       {
          // Security check
          if(!xarSecurityCheck('SubmitCategoryLink',1,'Link',"$args[modid]:$modtype:$iid:$cid")) return;

          $basecid = isset($basecids[$i]) ? $basecids[$i] : 0;
          $cidparts = explode('.',$cid);
          $cid = $cidparts[0];
          $ccid = isset($cidparts[1]) ? $cidparts[1] : 0;

          // Insert the link
          $q = new Query('INSERT', $categorieslinkagetable);
          $q->addfield('category_id', $cid);
          $q->addfield('child_category_id', $ccid);
          $q->addfield('basecategory', $basecid);
          $tag = Tag($args['modid'], $itemtype, $iid, $q);
          
          if (!$q->run()) return;
          
          /*
          $sql = "INSERT INTO $categorieslinkagetable (
                    ,
                    ,
                    item_id,
                    itemtype,
                    module_id,
                    )
                  VALUES(?,?,?,?,?,?)";
          $bindvars = array(, $ccid, $iid, $itemtype, $args['modid'], $basecid);
          $result =& $dbconn->Execute($sql,$bindvars);
          if (!$result) return;
          */
          $i++;
       }
    }

    /* Don't implement for now
    // Remove the entries of these categories from the summary table
    $categorieslinkagesummarytable = $xartable['categories_linkage_summary'];
    $bindmarkers = '?' . str_repeat(',?',count($args['cids'])-1);
    $sql = "DELETE FROM $categorieslinkagesummarytable
            WHERE module_id = $args[modid] AND
                  itemtype = $itemtype AND
                  category_id IN ($bindmarkers)";
    $result = $dbconn->Execute($sql,$args['cids']);

    // Insert the entries of these categories from the summary table
    foreach ($args['cids'] as $cid)
    {
      $sql = "INSERT INTO $categorieslinkagesummarytable (
                category_id,
                item_id,
                itemtype,
                module_id,
                links)
              VALUES(?,?,?,?,?)";
      $bindvars = array($cid, $iid, $itemtype, $args['modid'], 0);
      $result =& $dbconn->Execute($sql,$bindvars);
      if (!$result) return;
    }
    */

    return true;
}

?>
