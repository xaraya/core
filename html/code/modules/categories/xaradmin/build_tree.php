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
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 *
 */

/**
 * Check a Celko tree
 * 
 * @param string table name
 * @param string parent ID field name in the table
 * @param string left ID field name in the table
 * @param string right ID field name in the table
 * @param string name field name in the table
 * @param string top level name
 *
 * If you are unfamiliar with the nested set model for hierarchical data ("Celko trees"), then talk to someone like Wikipedia.
 * Xaraya uses nested set models notably in categories and comments modules.
 *
 * This utility scans a database table containing nested set data and checks left, right and parent IDs of its Celko tree for consistency, and outputs any errors it finds to the screen.
 * It first tries to identify a top level entry, which by convention should be named "Root" (but you can change this) and have left_id = 1 and parent_id = 0 (this can/should not be changed).
 * Once a root entry has been identified the utility then flags any missing or wrong entries relative to the root entry.
 * There is a verbose flag (can be changed in the code, default is "false") which makes the utility output an inordinate amount of information.
 * 
 * There is also a "build" option which makes the utility repair the data as it sees fit, starting from the root entry, to reconstruct a valid Celko tree.
 * When reconstructing it will first repair the left and right IDs, and once that is done it will update the parent IDs.
 * Note that there is no guarantee this will result in the tree arrangement you expect, or even that the tree can be completely repaired.
 * For instance, the utility will add any table entries with empty ID values to what it thinks is the end of the tree.
 * If the repaired tree shows no errors (and only then) the utility will automatically update the database table.
 * Note: if you are rebuilding a table make sure the left, right and parent IDs are not indexed uniquely when the table is being updated.
 * Once the repair is done you can restore such indexes.
 * 
 * How to use it
 * Your best course of action is to run the checks and then manually fix errors in the database table where possible.
 * Try and do this iteratively, and only then run the build option.
 * If all else fails you can set all the ID values in the table to 0 and run the build.
 * This will create a flat, but valid, Celko tree. You can then use whatever code is managing the table (for instance the celkoposition property) to recreate the proper hierarchy.
 *
 * Some tips on Celko trees
 * - The left and right IDs are a continuous incrementing index, starting with 1
 * - Each index value appears exactly one time in the left and right ID fields
 * - The last index value, which is the right ID of the top level entry, is equal to twice the number of database rows
 *
 * The "natural" order of a Celko tree is by left ID
 * Assuming the natural order:
 * - if the left ID of an entry equals the left iD of the previous entry + 1, then the tree is descneding a level, with the previous entry being the parent of the current entry
 * - if the left id of an entry is equal to the right id of the previous entry + 1, then this entry stays at the same level and has the same parent as its predecessor.
 * - if the left ID of an entry is greater that the right ID + 1 of the previous, then we are going up one or more levels (the number of levels will be the diffence between the current left ID and the previous right ID + 2.
 */
 
function categories_admin_build_tree()
{
    // Security Check
    if (!xarSecurityCheck('AdminCategories')) return;

    $verbose = false;
    
    if(!xarVarFetch('table',     'str',  $data['table'],      'xar_categories', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('parent_id', 'str',  $data['parent_id'],  'parent_id',      XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('left_id',   'str',  $data['left_id'],    'left_id',        XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('right_id',  'str',  $data['right_id'],   'right_id',       XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('name',      'str',  $data['name'],       'name',           XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('root_name', 'str',  $data['root_name'],  'Root',           XARVAR_NOT_REQUIRED)) {return;}

    // Buttons
    if(!xarVarFetch('check',  'isset',  $data['check'],   null,       XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('build',  'isset',  $data['build'],   null,       XARVAR_NOT_REQUIRED)) {return;}

    $data['message_warning'] = array();
    $data['message_error'] = array();
    $data['message_success'] = array();
    $data['message_info'] = array();

# --------------------------------------------------------
#
# Check that we have all the data we need
#
    if (empty($data['table']))     $data['message_warning'][] = xarML('Missing table');
    if (empty($data['parent_id'])) $data['message_warning'][] = xarML('Missing parent ID');
    if (empty($data['left_id']))   $data['message_warning'][] = xarML('Missing left ID');
    if (empty($data['right_id']))  $data['message_warning'][] = xarML('Missing right ID');
    if (empty($data['name']))      $data['message_warning'][] = xarML('Missing name field');
    $isvalid = empty($data['message_warning']);
    
    if (!$isvalid) return $data;
   
# --------------------------------------------------------
#
# Read the table
# We do everything in memory for now
# TODO: this is not scalable
#
    sys::import('xaraya.structures.query');
    $q = new Query('SELECT', $data['table']);
    $q->addfield('id');
    $q->addfield($data['parent_id']);
    $q->addfield($data['left_id']);
    $q->addfield($data['right_id']);
    $q->addfield($data['name']);
    if (!$q->run()) {
        $data['message_error'][] = xarML('Could not read the table #(1), $table');
        return $data;
    }
    $all_rows = $q->output();
    
# --------------------------------------------------------
#
# Check the data, no changes here
#
    if (isset($data['check'])) {

# --------------------------------------------------------
#
# Perform some sanity checks
#
        $ids = array();
        $left_nulls = 0;
        $right_nulls = 0;
        $parent_nulls = 0;
        $parent_ids = array();
        foreach ($all_rows as $key => $row) {
            // Rearrange the ids in a single array
            // Pick out any null values here
            if ($row[$data['left_id']] == null) {
                $left_nulls++;
            } else {
                $ids[] = (int)$row[$data['left_id']];
            }
            if ($row[$data['right_id']] == null) {
                $right_nulls++;
            } else {
                $ids[] = (int)$row[$data['right_id']];
            }
            if ($row[$data['parent_id']] == null) {
                $parent_nulls++;
            } else {
                $parent_ids[] = (int)$row[$data['parent_id']];
            }

            // Find the top level
            if ((int)$row[$data['left_id']] == 1) {
                if (isset($row[$data['name']])) {
                    $data['message_info'][] = xarML('The top level is #(1) (ID #(2))', $row[$data['name']], $row['id']);
                } else {
                    $data['message_info'][] = xarML('The top level is ID #(1)', $row['id']);
                }
            }
        }
        $data['message_info'][] = xarML('Sanity checks OK');
        $data['message_info'][] = '--------------------------------------------';
        $data['message_info'][] = xarML('Checking uniqueness of values');
        $unique_values = count($ids);
        $data['message_info'][] = xarML('Found #(1) unique values', $unique_values);
    
        // Display duplicate values
        $duplicates = $left_nulls + $right_nulls;
        $counts = array_count_values($ids);

        foreach ($counts as $key => $value) {
            if ($value != 1) {
                $data['message_error'][] = xarML('Found #(1) instances of left ID #(2) ', $value, $key);
                $duplicates += $value - 1;
            }
        }
        if ($left_nulls != 0) $data['message_error'][] = xarML('Found #(1) instances of left ID NULL ', $left_nulls);
        if ($right_nulls != 0) $data['message_error'][] = xarML('Found #(1) instances of right ID NULL ', $right_nulls);
        if ($parent_nulls != 0) $data['message_error'][] = xarML('Found #(1) instances of parent ID NULL ', $parent_nulls);
    
        sort($ids);
        $keys = array_flip($ids);
        $index = 0;
        $id_count = count($ids) + $left_nulls + $right_nulls;
        for ($i=1;$i<=$id_count;$i++) {
            $index++;
            if (!isset($keys[$i])) {
                $data['message_error'][] = xarML('Did not find an index value #(1)', $i);
            }
        }

        // Sort the rows by left_id
        foreach ($all_rows as $key => $row) {
            $left_id[$key]  = $row[$data['left_id']];
        }
        array_multisort($left_id, SORT_ASC, $all_rows);

        // Check the top level
        $top_level = $all_rows[0];
        if (($top_level[$data['left_id']] != 1) || ($top_level[$data['right_id']] != 2 * count($all_rows)) || ($top_level[$data['parent_id']] != 0)) {
            $data['message_error'][] = xarML('The top level entry (ID #(1)) is incorrect', $top_level['id']);
        } else {
            $data['message_info'][] = xarML('The top level entry (ID #(1)) is correct', $top_level['id']);
        }
    
# --------------------------------------------------------
#
# Now check each row
#
        // These values are starter values corresponding to a notional entry above the root entry
        $this_id = 0;
        $this_parent_id = -1;
        $this_left_id = 0;
        $this_right_id = -1;
    
        // We will need a stack to hold the parent IDs
        $parent_stack = new Stack();
        $id_stack = new Stack();
        $left_stack = new Stack();
        $right_stack = new Stack();
        $right_sequence_stack = new Stack();

        foreach ($all_rows as $key => $row) {
            $id_stack->push($row['id']);
            $parent_stack->push($row[$data['parent_id']]);
            $left_stack->push($row[$data['left_id']]);
            $right_stack->push($row[$data['right_id']]);

            $previous_id        = $this_id;
            $previous_left_id   = $this_left_id;
            $previous_right_id  = $this_right_id;
            $previous_parent_id = $this_parent_id;
            $this_id        = $id_stack->peek();
            $this_left_id   = $left_stack->peek();
            $this_right_id  = $right_stack->peek();
            $this_parent_id = $parent_stack->peek();

                    if ($verbose) $data['message_error'][] = xarML('    Previous Right ID: #(1)', $previous_right_id);
            // No children: don't need this entry on the stack
            if ($this_right_id == $this_left_id + 1) {
                $right_stack->pop();
                $parent_stack->pop();
            }
            //Sanity check for each entry
            if ($this_left_id > $this_right_id) {
                $data['message_error'][] = xarML('    Bad IDs at enty ID #(3): Left (#(1)) is greater than right ID (#(2))', $this_right_id, $this_right_id, $this_id);
            }
            
            // We're already past this bad right ID, throw it away
            while (($right_sequence_stack->peek() != null) && ($this_left_id > $right_sequence_stack->peek())) {
                $right_sequence_stack->pop();
            }
            // Check for bad right IDs
            if (($right_sequence_stack->peek() != null) && ($this_right_id > $right_sequence_stack->peek())) {
                $data['message_error'][] = xarML('    Bad Right ID at enty ID #(3): #(1) when looking for #(2)', $this_right_id, $right_sequence_stack->peek(), $this_id);
            }
            
            // This entry has children, add its right ID to the list for checking
            if ($this_right_id > $this_left_id + 1) {
                $right_sequence_stack->push($this_right_id);
            }

            if ($verbose) $data['message_error'][] = xarML('    Running ID: #(1)', $this_id);
            if ($verbose) $data['message_error'][] = xarML('    Running Parent ID: #(1)', $this_parent_id);
            if ($verbose) $data['message_error'][] = xarML('    Running Left ID: #(1)', $this_left_id);
            if ($verbose) $data['message_error'][] = xarML('    Running Right ID: #(1)<br/>', $this_right_id);

            if ($this_left_id == $previous_left_id + 1) {

                // This row is a child of the previous row
                // The parent should therefore be the previous row's ID

                if ($previous_id != $this_parent_id) {
                    $data['message_error'][] = xarML('The parent of ID #(3) is #(1), but should be #(2)', $this_parent_id, $previous_id, $row['id']);
                }
        
            } elseif ($this_left_id == $previous_right_id + 1) {

                // This row is on the same level as the last
                // It should have the the same parent as the last row
                if ($previous_parent_id != $this_parent_id) {
                    $data['message_error'][] = xarML('The parent of ID #(3) is #(1), but should be #(2)', $this_parent_id, $previous_parent_id, $this_id);
                    if ($verbose) $data['message_error'][] = xarML('    ID: #(1)', $this_id);
                    if ($verbose) $data['message_error'][] = xarML('    Parent ID: #(1)', $this_parent_id);
                    if ($verbose) $data['message_error'][] = xarML('    Left ID: #(1)', $this_left_id);
                    if ($verbose) $data['message_error'][] = xarML('    Right ID: #(1)', $this_right_id);
                    if ($verbose) $data['message_error'][] = xarML('    Previous Right ID: #(1)', $previous_right_id);
                }
        
            } elseif ($this_right_id <= $this_left_id) {
                // Corrupted case
                $data['message_error'][] = xarML('The right ID must be bigger that the left ID');
                $data['message_error'][] = xarML('    This ID: #(1)', $this_id);
                $data['message_error'][] = xarML('    This Parent ID: #(1)', $this_parent_id);
                $data['message_error'][] = xarML('    This Left ID: #(1)', $this_left_id);
                $data['message_error'][] = xarML('    This Right ID: #(1)<br/>', $this_right_id);
            } else {
            /*
                // Something went wrong
                $data['message_error'][] = xarML('Something went wrong');
                $data['message_error'][] = xarML('    Previous ID: #(1)', $previous_id);
                $data['message_error'][] = xarML('    Previous Parent ID: #(1)', $previous_parent_id);
                $data['message_error'][] = xarML('    Previous Left ID: #(1)', $previous_left_id);
                $data['message_error'][] = xarML('    Previous Right ID: #(1)', $previous_right_id);
                $data['message_error'][] = xarML('    This ID: #(1)', $this_id);
                $data['message_error'][] = xarML('    This Parent ID: #(1)', $this_parent_id);
                $data['message_error'][] = xarML('    This Left ID: #(1)', $this_left_id);
                $data['message_error'][] = xarML('    This Right ID: #(1)<br/>', $this_right_id);
            */
            }
        
            if ($this_left_id == $this_right_id - 1) {
                // Now check whether we have to close out close out any entries
                // This happens when we have reached the last entry in a given level
                $this_temp_right_id = $this_right_id;
                $close_out = true;
                while ($close_out) {
                    $stack_right_id = $right_stack->peek();
                    if ($stack_right_id == $this_temp_right_id + 1) {
                        // Yes, the stashed right_id is the parent of this one
                        // We are closing out, so remove the stashed values and adjust the right_id
                        if ($verbose) $data['message_error'][] = xarML('    Popping right ID: #(1)', $right_stack->pop());
                        if ($verbose) $data['message_error'][] = xarML('    Popping parent ID: #(1)', $parent_stack->pop());
                        $right_stack->pop();
                        $parent_stack->pop();
                        $this_temp_right_id++;
                    } else {
                        $close_out = false;
                    }
                }
            }
            if ($verbose) $data['message_error'][] = xarML('The right stack top element: #(1)', $right_stack->peek());
            if ($verbose) $data['message_error'][] = xarML('The parent stack top element: #(1)', $parent_stack->peek());
        }
    
        // If everything ran correctly we should now have empty stacks
        if ($right_stack->peek() != null) {
            $stack = array();
            while($right_stack->peek() != null) {
                $stack[] = $right_stack->pop();
            }
            $data['message_error'][] = xarML('The right stack elements: #(1)', implode(',', $stack));
        }
        if ($parent_stack->peek() != null) {
            $stack = array();
            while($parent_stack->peek() != null) {
                $stack[] = $parent_stack->pop();
            }
            $data['message_error'][] = xarML('The parent stack elements: #(1)', implode(',', $stack));
        }

        $data['message_success'][] = xarML('Number of rows: #(1)', count($all_rows));
        $data['message_success'][] = xarML('Number of indices: #(1)', $id_count);
        if (empty($data['message_error'])) $data['message_success'][] = xarML('The Celko indices are correct');

    } elseif (isset($data['build'])) {       
        
# --------------------------------------------------------
#
# Prepare the data: validations and other checks
#
        // The indices should all be integers
        foreach ($all_rows as $key => $row) {
            $all_rows[$key]['id']               = (int)$all_rows[$key]['id'];
            $all_rows[$key][$data['left_id']]   = (int)$all_rows[$key][$data['left_id']];
            $all_rows[$key][$data['right_id']]  = (int)$all_rows[$key][$data['right_id']];
            $all_rows[$key][$data['parent_id']] = (int)$all_rows[$key][$data['parent_id']];
        }

        // Check for a root entry
        $found = false;
        $root_entry = array();
        $root_key = 0;
        foreach($all_rows as $key => $row) {
            if ($row[$data['name']] == $data['root_name']) {
                $found = true;
                $root_entry = $row;
                $root_key = $key;
                break;
            }
        }
        if (!$found) {
            $data['message_error'][] = xarML('Did not find a root entry named: #(1)', $data['root_name']);
        } else {
            $data['message_success'][] = xarML('The top level entry ID is: #(1)', $root_entry['id']);
        }
        
        // Begin construction
        // Force the root entry values for left, right nd parent
        $all_rows[$root_key][$data['left_id']] = 1;
        $all_rows[$root_key][$data['right_id']] = 2*count($all_rows);
        $all_rows[$root_key][$data['parent_id']] = 0;
        
        // Sort the data by left ID
        foreach ($all_rows as $key => $row) {
            $left[$key]  = (int)$row[$data['left_id']];
        }
        array_multisort($left, SORT_ASC, $all_rows);
        
# --------------------------------------------------------
#
# Update the left and right IDs
#
        $id_stack = array();
        $right_stack = array();

        $current_index = 1;
        $increment = 0;
        foreach ($all_rows as $key => $row) {
            
            // Ignore the root entry
            if ($row[$data['name']] == $data['root_name']) continue;
            
            // Increment the current index
            $current_index++;
            
            // Empty values indicate the entry was added in an odd way. Set to 0 and process like any other
            if (empty($all_rows[$key][$data['left_id']]))   $all_rows[$key][$data['left_id']] = 0;
            if (empty($all_rows[$key][$data['right_id']]))  $all_rows[$key][$data['right_id']] = 0;
            if (empty($all_rows[$key][$data['parent_id']])) $all_rows[$key][$data['parent_id']] = 0;
                       
            // If both left and right IDs are the same, adjust the latter by 1
            if ($all_rows[$key][$data['right_id']] == $all_rows[$key][$data['left_id']]) {
                $all_rows[$key][$data['right_id']]++;
            }
            
            // If this left ID is not what the current index expects, then adjust both left and right IDs
            if ($row[$data['left_id']] != $current_index) {
                $increment = $current_index - $row[$data['left_id']];
                $all_rows[$key][$data['left_id']] += $increment;
                $all_rows[$key][$data['right_id']] += $increment;
            }
            if ($all_rows[$key][$data['right_id']] == $all_rows[$key][$data['left_id']] + 1) {
                // This branch has no leaves
                $current_index++;

            } else {
                // This branch has leaves, store its ID and right ID
                array_unshift($id_stack, $key);
                array_unshift($right_stack, $all_rows[$key][$data['right_id']]);
            }
            
            // Check the stack of unclosed right IDs to see if any of these are now closing
            $closing_increment = 0;
            foreach ($right_stack as $k => $v) {
                //Ignore the current entry we may have just put on the stack
                if ($all_rows[$key][$data['right_id']] == $v) continue;
                $temp_right = $v;
                // Bail if the stack is empty
                // Check the stashed value against the current index
                if ($temp_right == $current_index + 1) {
                    // Yes, the stashed right ID is the parent of this one
                    // We are closing out, so remove the stashed values and adjust the right_id
                    unset($id_stack[$k]);
                    unset($right_stack[$k]);
                    // Update the current index for the next round
                    $current_index++;
                } elseif ($temp_right < $current_index) {
                    $closing_increment++;
                    // Oops, we already passed the expected closing index. Close it out and adjust its value
                    $temp_id = $id_stack[$k];
                    $current_index = $current_index + $closing_increment;
                    $all_rows[$temp_id][$data['right_id']] = $current_index;
                    // Throw away the stored value
                    // Update the current index for the next round
                    unset($id_stack[$k]);
                    unset($right_stack[$k]);
                }
            }
        }
        
# --------------------------------------------------------
#
# Clean up any stragglers left in the stack
#
        foreach ($right_stack as $k => $v) {
            $temp_right = $v;
            if ($temp_right != $current_index) {
                $temp_id = $id_stack[$k];
                $current_index++;
                $all_rows[$temp_id][$data['right_id']] = $current_index;
                unset($id_stack[$k]);
                unset($right_stack[$k]);
            }
        }
        
# --------------------------------------------------------
#
# Deal with bad right IDs by swapping them
#
        $right_sequence_stack = new Stack();
        $id_sequence_stack = new Stack();
        foreach ($all_rows as $key => $row) {
            $this_left_id = $row[$data['left_id']];
            $this_right_id = $row[$data['right_id']];
            // Check for bad right IDs
            if (($right_sequence_stack->peek() != null) && ($this_right_id > $right_sequence_stack->peek())) {
                $that_id = $id_sequence_stack->peek();
                $this_id = $key;
                $that_right_id = $right_sequence_stack->peek();
                $all_rows[$that_id][$data['right_id']] = $this_right_id;
                $all_rows[$this_id][$data['right_id']] = $that_right_id;
                $right_sequence_stack->pop();
                $id_sequence_stack->pop();
            }
            
            // This entry has children, add its right ID to the list for checking
            if ($this_right_id > $this_left_id + 1) {
                $right_sequence_stack->push($this_right_id);
                $id_sequence_stack->push($key);
            }
            // We're already past this bad right ID, throw it away
            if ($this_left_id > $right_sequence_stack->peek()) {
                $right_sequence_stack->pop();
                $id_sequence_stack->pop();
            }
        }

# --------------------------------------------------------
#
# Check that we have exactly one of each ID as expected
#
        $ids = array();
        foreach ($all_rows as $row) {
            $ids[] = $row[$data['left_id']];
            $ids[] = $row[$data['right_id']];
        }

        $no_errors = true;
        sort($ids);
        $keys = array_flip($ids);
        $index = 0;
        $id_count = count($ids);
        for ($i=1;$i<=$id_count;$i++) {
            $index++;
            if (!isset($keys[$i])) {
                $data['message_error'][] = xarML('Did not find a left/right value #(1)', $i);
                $no_errors = false;
            }
        }

# --------------------------------------------------------
#
# Now build the parent ID links
#
        // The top link is to the root entry
        $current_parent = 0;
        $right_stack = new Stack();
        $parent_stack = new Stack();
        $parent_stack ->push($current_parent);
        foreach ($all_rows as $key => $row) {
            
            // Get the current parent
            $current_parent = $parent_stack->peek();
            
            // The parent of any entry is defned as the current parent
            $all_rows[$key][$data['parent_id']] = $current_parent;
            
            if ($all_rows[$key][$data['right_id']] == $all_rows[$key][$data['left_id']] + 1) {
                // This branch has no leaves
                // Nothing to do
            } else {
                // This branch has leaves, the next entry will be a child
                // Store the parent ID and right ID
                $parent_stack->push($all_rows[$key]['id']);
                $right_stack->push($all_rows[$key]['id']);
            }
            
            // Now check for closing right IDs
            $this_right_id = $all_rows[$key][$data['right_id']];
            $close_out = true;
            foreach ($right_stack as $k => $v) {
                $temp_right = $right_stack->peek();
                if ($temp_right == $this_right_id + 1) {
                    // Yes, the stashed right_id is the parent of this one
                    // We are closing out, so remove the stashed values and adjust the right_id
                    $right_stack->pop();
                    $parent_stack->pop();
                    $this_right_id++;
                } else {
                    $close_out = false;
                }
            }
        }
        foreach ($all_rows as $row) {
            var_dump($row);
            echo "<br/>";
        }

# --------------------------------------------------------
#
# Now build the parent ID links
#
        if ($no_errors) {
            sys::import('xaraya.structures.query');
            $q = new Query('UPDATE', $data['table']);
            foreach ($all_rows as $row) {
                $q->addfield($data['left_id'], $row[$data['left_id']]);
                $q->addfield($data['right_id'], $row[$data['right_id']]);
                $q->addfield($data['parent_id'], $row[$data['parent_id']]);
                $q->eq('id', $row['id']);
                $q->run();
                $q->clearconditions();
            }
        }
    }
    return $data;
}

?>