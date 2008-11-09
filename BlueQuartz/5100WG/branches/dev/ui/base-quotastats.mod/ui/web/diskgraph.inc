<?
// Copyright 2000, Cobalt Networks.  All rights reserved.
// This draws the disk usage table

function disk_usage_table($factory)
{
  $block = $factory->getScrollList
  (
    "diskUsageSummary",
    array("partition", "usedSpace", "available", "totalSpace", "pct"),
    array(1, 2, 3, 4)
  );
  $block->setColumnWidths(array(200, 50, 50, 50, 200));

  $statFile = @fopen("/var/local/disk_usage.dat", "r");
  if ($statFile)
  {
    while($line = fgets($statFile, 256))
    {
      $token = split("\|", $line);
      $bAry = array();

      array_push($bAry, $factory->getLabel(trim($token[0]), false));

      for ($i=1; $i < count($token) - 1; ++$i)
      {
        array_push($bAry, $factory->getTextField("", trim($token[$i]), "r"));
      }
      array_push($bAry, $factory->getBar("", trim($token[count($token) - 1])));
      $block->addEntry($bAry);
    }
    fclose($statFile);
  }
  print($block->toHtml());
}        
?>
