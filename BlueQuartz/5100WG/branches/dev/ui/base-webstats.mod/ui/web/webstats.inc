<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// Apparently: graphType = [v,h] and containerType = [b,l]

if ($maxLines == 0)
{
  $maxLines = 200;  // change at your leisure
}

if (! $graphType)
{
  $graphType = 'h';
}

if (! $containerType)
{
  $containerType = 'l';
}

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-webstats",
                                                        $PHP_SELF);
$i18n = $factory->getI18n();
$page = $factory->getPage();

if ($resetStats)
{
  $serverScriptHelper->shell("/usr/sausalito/sbin/reset_web_stats", $output, "root");
}

print $page->toHeaderHtml();

$linkTextArray = array
(
  "webtotals",
  "webbyip",
  "webbyfile",
  "webbyref",
  "webbyhour",
  "webbydate"
);

$t = split('\/', $SCRIPT_FILENAME);
$r = split('\.', $t[count($t) - 1]);
$headerTitle = $r[0] . '_header';
$linkActionArray = array();
for ($i=0; $i < count($linkTextArray); $i++)
{
  array_push($linkActionArray, $linkTextArray[$i] . '.php');
}

$linkButton = $factory->getMultiButton("selectStat", $linkActionArray, $linkTextArray);
$button = $factory->getButton("javascript:confirmResetStats()", "resetStats");

print "<TABLE BORDER=\"0\"><TR><TD>".$linkButton->toHtml()."</TD><TD>".$button->toHtml()."</TD></TR></TABLE>";
print "\n<br>\n";

$statFile = @fopen($fileName, "r");
if (! $statFile)
{
  print $i18n->get($headerTitle) . " : " . $i18n->get("noStats") . '<br>';
}
else
{
  if ($graphType == 'h')
  {
    if ($containerType == 'l')
      $detailList = $factory->getScrollList($headerTitle, $headerArray);
    else
      $block = $factory->getPagedBlock($headerTitle);
  }
  else
  {
    $labelAry = array();
    $pctAry   = array();
    $numAry   = array();
  }
  $line = fgets($statFile, 256);
  $lineCount = 0;
  while (!feof($statFile) && ($lineCount < $maxLines))
  {
    ++$lineCount;
    $token = split("\|", $line);
    for ($i=0; $i < count($token); $i++)
    {
      $token[$i] = trim($token[$i]);
    }
    if ($graphType == 'h')
    {
      if ($containerType == 'l')
      {
        $detailList->addEntry(getDetailLine($factory, $token));
      }
      else
      {
	  // if the second field is null, just say 'not available'
	  if($token[1] == "")
	  {
	    $block->addFormField(
			$factory->getTextField($lineCount,"notAvailable", "r"),
			$factory->getLabel("notAvailable")
	    );
	  }

	// the first three lines of the file are timestamps.  duh.
        if ($lineCount <= 3)
        {
	    $block->addFormField(
			$factory->getTimeStamp(
				$lineCount,$token[1],"datetime", "r"
			),
			$factory->getLabel($token[0])
	    );
        }
	else
	{
	    $block->addFormField(
			$factory->getTextField($token[0], $token[1], "r"),
			$factory->getLabel($token[0])
	    );
	}
      }
    }
    else
    {
      $res = getDetailLine($factory, $token);
      array_push($labelAry, $res[0]);
      array_push($pctAry,   $res[1]);
      array_push($numAry,   $res[2]);
    }
    $line = fgets($statFile, 256);
  }
  fclose($statFile);

  if ($graphType == 'h')
  {
    if ($containerType == 'l')
      print $detailList->toHtml();
    else
      print $block->toHtml();
  }
  else
  {
    $pctGraph = $factory->getScrollList($headerTitle, $labelAry);
    $colAry = array();
    for ($i=0; $i < count($pctAry); $i++)
    {
      array_push($colAry,
                 $factory->getVerticalBar("", $pctAry[$i], $numAry[$i]));
    }
    $pctGraph->addEntry($colAry);
    // $colAry = array();
    // for ($i=0; $i < count($pctAry); $i++)
    // {
    //   array_push($colAry, 
    //              $factory->getTextField("", $numAry[$i], 'r'));
    // }
    // $pctGraph->addEntry($colAry);
    $pctGraph->setEntryCountHidden(true);
    print $pctGraph->toHtml();
  }
}
?>
<SCRIPT LANGUAGE="javascript">
function confirmResetStats()
{
  if (confirm("<?php print $i18n->get("askResetConfirmation"); ?>"))
  {
    location = "<?php print "$PHP_SELF?resetStats=1"; ?>"
  }
}
</SCRIPT>
<?php
print $page->toFooterHtml();
?>
