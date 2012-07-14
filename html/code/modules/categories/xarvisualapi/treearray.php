<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Build array with visual tree of categories (&#160;&#160;--+ style)
 * for use in &lt;select&gt; or table display
 *
 *  -- INPUT --
 * @param $args['cid'] The ID of the root category used for the tree
 * @param $args['eid'] optional ID to exclude from the tree (e.g. the ID of
 *                     your current category)
 * @param $args['return_itself'] include the cid itself (default false)
 *
 *  -- OUTPUT --
 * @returns array
 * @return array of array('id' => 123, 'name' => '&#160;&#160;--+&#160;My Cat')
 */
function categories_visualapi_treearray ($args)
{
    if (!isset($args['maximum_depth'])) {
        $args['maximum_depth'] = null;
    }
    if (!isset($args['minimum_depth'])) {
        $args['minimum_depth'] = null;
    }

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
      'return_itself' => (isset($args['return_itself']))?$args['return_itself']:false,
      'getchildren' => true,
      'maximum_depth' => $args['maximum_depth'],
      'minimum_depth' => $args['minimum_depth']
     )
    );

    if ($categories === false) {// If it returned false
        $msg = xarML('Error obtaining category.');
        throw new BadParameterException(null, $msg);
    }

    // Outputing Location Options

    $last_indentation = 0;
    $tree_data = Array ();

    foreach ($categories as $category)
    {
        $indentation_output = "";
        for ($i=1; $i < $category['indentation']; $i++) {
           $indentation_output .= "&#160;&#160;&#160;&#160;";
        }
        if ($last_indentation < $category['indentation']) {
           $indentation_output .= "--+&#160;&#160;";
        } else {
           $indentation_output .= "&#160;&#160;&#160;+&#160;";
        }

        $last_indentation = $category['indentation'];

        $tree_data[] = Array('id'   => $category['cid'],
                             'name' => $indentation_output
                                      .xarVarPrepForDisplay($category['name']));
    }
    unset($categories);

    return $tree_data;

}

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
?>
