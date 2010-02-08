/*
  Upgrade script for the table changes made in Xaraya 2.1.0
  compared to Xaraya 2.0.0
*/

/* Merging the blockgroups and blocks tables */
/* FIXME: add SQL to move blockgroup data from xar_block_groups to xar_block_instances*/
DROP TABLE xar_block_groups;