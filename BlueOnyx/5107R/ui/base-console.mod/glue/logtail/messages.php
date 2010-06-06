<?
// logtail.php

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$helper = new ServerScriptHelper($sessionId);

// Only adminUser should be here
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cmd = "/usr/sausalito/ui/web/base/console/.logtail/messages.pl";
exec("$cmd 2>&1", $output);
foreach($output as $outputline) {
    echo ("$outputline\n");
}
?>
