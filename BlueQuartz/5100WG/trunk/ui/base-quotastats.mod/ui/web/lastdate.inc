<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// This draws the last run date table

if ($refresh)
{
  $serverScriptHelper->fork("/usr/sausalito/sbin/get_quota_stats.pl", "root");
}

$i18n = $factory->getI18n();
$dateFile = @fopen("/var/local/quotastats_run.dat", "r");
if ($dateFile)
{
  $lastDate = $factory->getTimeStamp("lastCheck",
               trim(fgets($dateFile, 256)), "datetime", "r");
  fclose($dateFile);
}

if ($lastDate == '')
{
  $lastDate = $factory->getTextField("", $i18n->get("notRunYet"), "r");
}

$select = $factory->getMultiButton("selectUsage", array
(
  "/base/quotastats/diskusage.php",
  "/base/quotastats/userquotas.php",
  "/base/quotastats/groupquotas.php"
), array("usageSummary", "usageUser", "usageGroup"));

$button = $factory->getButton("javascript:confirmUpdateStats()", "updateNow");

print "<TABLE><TR><TD>" . $select->toHtml() . "</TD><TD>" . $button->toHtml() . "</TD></TR></TABLE><BR>\n";

$block = $factory->getPagedBlock("diskQuotaStats");
$block->addFormField($lastDate, $factory->getLabel("lastCollectionDate"));
print $block->toHtml() . "<br>\n";
?>
<SCRIPT LANGUAGE="javascript">
function confirmUpdateStats()
{
  if (confirm("<?php print $i18n->get("askUpdateNowConfirmation"); ?>"))
  {
    location = "<?php print "$PHP_SELF?refresh=1"; ?>"
  }
}
</SCRIPT>
