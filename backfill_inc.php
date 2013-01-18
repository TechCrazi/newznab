<?php
/*
This script will backfill your newznab installation slowly and steadily
Tigggger
*/
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/releases.php");
require_once(FS_ROOT."/../../www/lib/sphinx.php");
require_once(FS_ROOT."/../../www/lib/backfill.php");

//this is the maximum days the script will go up to, change it to go higher
$backfill_target = 200;

//see what the lowest backfilled group is
$db = new Db;
$sql = "SELECT backfill_target FROM groups WHERE active=1 ORDER BY backfill_target DESC LIMIT 1";
$res= $db->query($sql);
$highest = $res[0]['backfill_target'];

//increase the backfill days by 1 for all active groups less that current highest days
//this will allow groups to catch up so retention is same for all of them
$sql = "UPDATE groups SET backfill_target=backfill_target+1 WHERE active=1 AND backfill_target < '$backfill_target' AND backfill_target <= '$highest'";
$res= $db->query($sql);

//get binaries
$backfill = new Backfill();
$backfill->backfillAllGroups();

//get number of releases to process, and keep running until completed
$sql = "select COUNT(*) AS ToDo from releases r left join category c on c.ID = r.categoryID where (r.passwordstatus between -6 and -1) or (r.haspreview = -1 and c.disablepreview = 0)";
$res= $db->query($sql);
while ($res[0]['ToDo'] > 0) {
  $releases = new Releases;
	$sphinx = new Sphinx();
	$releases->processReleases();
	$sphinx->update();
}
?>
