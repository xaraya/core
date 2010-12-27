<?php

/**
 * Build array with visual tree of categories (&lt;ul&gt;&lt;li&gt;...&lt;/li&gt; style)
 * for use in view maps etc.
 *
 *  -- INPUT --
 * @param $args['cid'] The ID of the root category used for the tree
 * @param $args['eid'] optional ID to exclude from the tree (e.g. the ID of
 *                     your current category)
 *
 *  -- OUTPUT --
 * @returns array
 * @return array of array('id' => 123,
 *                        'name' => 'My Cat',
 *                        'beforetags' => '&lt;ul&gt;&lt;li&gt; ',
 *                        'aftertags' => ' &lt;/li&gt;&lt;/ul&gt;&lt;/ul&gt;')
 */
function categories_visualapi_listarray ($args)
{
    // Load User API
    if (!xarModAPILoad('categories', 'user')) return;

    // Getting categories Array
    $categories = xarMod::apiFunc
    (
     'categories',
     'user',
     'getcat',
     Array
     (
      'eid' => (isset($args['eid']))?$args['eid']:false,
      'cid' => (isset($args['cid']))?$args['cid']:false,
      'return_itself' => true,
      'getchildren' => true
     )
    );

    if ($categories === false) {// If it returned false
        $msg = xarML('Error obtaining category');
        throw new BadParameterException(null, $msg);
    }

    $startindent = 0;
    if (!empty($args['cid']) && is_numeric($args['cid'])) {
        $root = $args['cid'];
    } else {
        $root = 0;
    }
    $oldcid = 0;
    $oldindent = 0;

    $items = array();
    $itemlist = array();
    foreach ($categories as $category)
    {
        $itemlist[] = $category['cid'];
        $items[$category['cid']] = array();
        $items[$category['cid']]['id'] = $category['cid'];
        $items[$category['cid']]['name'] = $category['name'];
        $items[$category['cid']]['image'] = $category['image'];
        $items[$category['cid']]['left'] = $category['left'];
        $items[$category['cid']]['beforetags'] = '';
        $items[$category['cid']]['aftertags'] = '';
// TODO: build icon table instead of text list if there are images...
        if ($category['cid'] == $root) {
            $startindent = $category['indentation'];
        } else {
            if ($category['indentation'] > $oldindent) {
                for ($i=$oldindent;$i<$category['indentation'];$i++) {
                    $items[$category['cid']]['beforetags'] .= '<ul>';
                }
            } elseif ($category['indentation'] < $oldindent && $oldcid > 0) {
                for ($i=$category['indentation'];$i<$oldindent;$i++) {
                    $items[$oldcid]['aftertags'] .= '</ul>';
                }
            }
            $items[$category['cid']]['beforetags'] .= '<li> ';
            $items[$category['cid']]['aftertags'] .= ' </li>';
        }
        $oldindent = $category['indentation'];
        $oldcid = $category['cid'];
    }
    unset($categories);
    if ($oldcid > 0 && $oldindent > $startindent) {
        for ($i=$startindent;$i<$oldindent;$i++) {
            $items[$oldcid]['aftertags'] .= '</ul>';
        }
    }

    $list_data = Array ();
    foreach ($itemlist as $cid) {
        $list_data[] = $items[$cid];
    }
    unset($items);

    return $list_data;
}

?>
