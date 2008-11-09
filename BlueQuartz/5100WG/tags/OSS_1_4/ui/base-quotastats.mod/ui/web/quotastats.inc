<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// This draws teh user/group quota tables
// I'm pretty sure I horked this wrt multiple filesystems

function user_quota_table($factory)
{
    $fileName = "/var/local/user_quotas.dat";
    $whatIsThis = "userQuotas";
    $entity = "user";
    $all = "allUsers";

    quota_table($factory, $fileName, $whatIsThis, $entity, $all);
}

function group_quota_table($factory)
{
    $fileName = "/var/local/group_quotas.dat";
    $whatIsThis = "groupQuotas";
    $entity = "group";
    $all = "allGroups";

    quota_table($factory, $fileName, $whatIsThis, $entity, $all);
}

function quota_table($factory, $fileName, $whatIsThis, $entity, $all)
{
    $page = $factory->getPage();

    $block = $factory->getScrollList(
	$whatIsThis,
	array($entity, "blocks", "quota", "pct"),
	array(0, 1, 2, 3)
    );

    $statFile = @fopen($fileName, "r");
    if ($statFile) {
        $fileSystem = chop(fgets($statFile, 256));

        while (!feof($statFile)) {
            $totalUsed = 0;
            $totalMax = 0;

            fgets($statFile, 256);    // burn a line

            $line = fgets($statFile, 256);
            while (!feof($statFile) && (substr($line, 0, 2) != '--')) {
                $token = split("\|", $line);
                for ($i=0; $i < count($token); $i++) {
                    $token[$i] = trim($token[$i]);
                }

                $totalMax += ($token[2] > 0) ? $token[2] : 0;
                $pct = ($token[2] > 0)?round($token[1] / $token[2] * 100):0;

		$tmp = "";
                if ($token[2] < 0) {
		    $tmp = $factory->getLabel("unlimitedQuota", false);
		} else {
		    $tmp = $factory->getTextField("", $token[2], "r");
                    $totalUsed += $token[1];
		}

                $block->addEntry(array(
                    $factory->getTextField("", $token[0], "r"),
                    $factory->getTextField("", $token[1], "r"),
		    $tmp,
                    $factory->getBar("", $pct, $pct . '%')));

                $line = fgets($statFile, 256);
            }

            $pct = ($totalMax > 0)?round($totalUsed / $totalMax * 100):0;

            $fileSystem = chop(fgets($statFile, 256));
        }

        $block->addEntry(array(
	    $factory->getLabel($all, false),
            $factory->getTextField("", $totalUsed, "r"),
            $factory->getTextField("", $totalMax, "r"),
            $factory->getBar("", $pct, $pct . '%')));

        fclose($statFile);
    }
    print $block->toHtml();
}
?>
