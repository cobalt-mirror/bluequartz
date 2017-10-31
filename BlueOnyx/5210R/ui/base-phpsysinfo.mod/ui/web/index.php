<?php

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only 'serverServerDesktop' should be here
if (!$serverScriptHelper->getAllowed('serverServerDesktop')) {
  header("location: /error/forbidden.html");
  return;
}

header("location: /base/phpsysinfo/.phpsysinfo/index.php");
return;

?>
