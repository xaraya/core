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
 * Handle <xar:categories-catinfo ...> template tags
 * Format : <xar:categories-catinfo module="modulename" itemtype="itemtype" itemid="itemid" base="base-cat-id"/>
 * Default module is the module in which the template tag is called.
 *
 * Example:
 * The following tag, used in the user-display[-pubtype].xd template of articles, will display the name of the
 * category for the current item that has category 59 as an ancestor:
 *
 * <xar:categories-catinfo module="articles" itemtype="$itemtype" itemid="$aid" base="59" ifempty="not known"/>
 *
 * Note: this same function both defines the PHP code to appear in a template and executes
 * the tag at runtime. The parameter 'runtime' defines the runtime mode.
 *
 * @author Jason Judge
 * @param $args array containing the form field definition of the module, type, id, base, ...
 * @returns string
 * @return empty string
 */
function categories_userapi_getcatinfotag($args)
{
    if (!empty($args['runtime'])) {
        // Runtime mode.
        // Get the categories.
        $cats = xarMod::apiFunc('categories', 'user', 'getitemcats', $args);

        // Determine whether we have selected a new template.
        if (empty($args['template'])) {
            $template = NULL;
        } else {
            $template = $args['template'];
        }

        // Return the formatted category array.
        // Pass all the arguments in too, allowing for a 'passthrough' from
        // the original theme tag.
        return xarTplModule(
            'categories', 'user', 'catinfo',
            array_merge($args, array('cats'=>$cats)), $template
        );
    }

    // This section onwards is only executed when compiling a template.

    // Set default module.
    if (empty($args['modid']) || !is_numeric($args['modid'])) {
        if (empty($args['module'])) {
            $args['module'] = xarModGetName();
        }

        if (!empty($args['module'])) {
            $args['modid'] = xarMod::getRegId($args['module']);
        }
    }

    // Return if we don't have a module.
    if (empty($args['modid'])) {
        return '';
    }

    // Loop for all theme tag arguments - they all get passed in somewhere.
    foreach($args as $name => $value) {
        switch ($name) {
            case 'modid':
            case 'itemtype':
            case 'itemid':
                // Numeric values.
                $params[] = '\'' . $name . '\'=>"' . addslashes($value) . '"';
                break;

            case 'base':
                if (strpos($value, ',') > 0) {
                    $params[] = '\'basecids\'=>array(' . $value . ')';
                } else {
                    $params[] = '\'basecid\'=>' . $value;
                }
                break;

            default:
                // Treat as a string
                $params[] = "'$name'=>'" . addslashes($value) . "'";
                break;
        } // switch
    }

    // Ensure the 'runtime' parameter is included, so it renders the category
    // details when executed from the tenplate.
    $params[] = "'runtime'=>true";

    $out = "echo xarMod::apiFunc("
        . "'categories', 'user', 'getcatinfotag', "
        . "array(" . implode(', ', $params) . ")); ";

    return $out;
}

?>
