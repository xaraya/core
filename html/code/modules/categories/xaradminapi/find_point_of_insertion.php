<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Find the correct point of insertion for a node in Celko�s model for
 * hierarchical SQL Trees.
 *
 * @param string $args['inorout'] Where the new category should be: IN or OUT
 * @param string $args['rightorleft'] Where the new category should be: RIGHT or LEFT
 * @param type $args['right'] The right value of the reference category
 * @param type $args['left'] The left value of the reference category
 * @return type Returns the point of insertion value
 * @throws BadParameterException Thrown if parameters contain invalid values
 */
function categories_adminapi_find_point_of_insertion($args)
{

    extract($args);

    // Switch chosen over ifs for easiness of comprehession of the code
    $rightorleft = strtolower ($rightorleft);
    $inorout = strtolower ($inorout);

    switch($rightorleft) {
       case "right":
           $point_of_insertion = $right;

           switch($inorout) {
              case "out":
                 $point_of_insertion++;
              break;

              case "in":
              break;

              default:
                $msg = xarML('Valid values: IN or OUT');
                throw new BadParameterException(null, $msg);
           }

       break;
       case "left":
           $point_of_insertion = $left;
           switch($inorout) {
              case "out":
              break;

              case "in":
                 $point_of_insertion++;
              break;

              default:
                $msg = xarML('Valid values: IN or OUT');
                throw new BadParameterException(null, $msg);
           }
       break;
       default:
        $msg = xarML('Valid values: RIGHT or LEFT');
        throw new BadParameterException(null, $msg);
    }
    return $point_of_insertion;
}

?>
