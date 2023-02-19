<?php

// Generate files dependency tree with:
//
// $ phpstan dump-deps html > developer/tools/phpstan_deps.json
//
$contents = file_get_contents('phpstan_deps.json');
$children = json_decode($contents, true);
echo "Parents: " . count($children) . "\n";

$parents = [];
foreach ($children as $parent => $dependents) {
    foreach ($dependents as $dependent) {
        if (!array_key_exists($dependent, $parents)) {
            $parents[$dependent] = [];
        }
        if (!in_array($parent, $parents[$dependent])) {
            array_push($parents[$dependent], $parent);
        }
    }
}
ksort($parents);
$directs = [];
foreach ($children as $parent => $dependents) {
    $directs[$parent] = [];
    foreach ($dependents as $dependent) {
        if (!array_key_exists($dependent, $children)) {
            array_push($directs[$parent], $dependent);
            continue;
        }
        $found = false;
        foreach ($dependents as $candidate) {
            if ($candidate == $dependent) {
                continue;
            }
            if (!array_key_exists($candidate, $children)) {
                continue;
            }
            if (in_array($dependent, $children[$candidate])) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            array_push($directs[$parent], $dependent);
        }
    }
}
$roots = [];
foreach ($parents as $child => $ancestors) {
    foreach ($ancestors as $ancestor) {
        if (!array_key_exists($ancestor, $parents)) {
            if (!in_array($ancestor, $roots)) {
                array_push($roots, $ancestor);
            }
        }
    }
}

echo "Children: " . count($parents) . "\n";
echo "Directs: " . count($directs) . "\n";
$contents = json_encode($parents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents('phpstan_revs.json', $contents);
$contents = json_encode($roots, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo $contents . "\n";
$contents = json_encode($directs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo $contents . "\n";
