<?php
// Author: Brian N. Smith, Michael Stauber
// $Id: yum.php

include_once("ServerScriptHelper.php");

function br2nl($str) {
  $str = preg_replace("/(\r\n|\n|\r)/", "", $str);
  return preg_replace("=<br */?>=i", "\n", $str);
}

$serverScriptHelper = new ServerScriptHelper();

// Only 'managePackage' should be here:
if (!$serverScriptHelper->getAllowed('managePackage')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-yum", "/base/swupdate/yum.php");
$transMethodOn="off";

if ( $_save == "1" ) {
  mt_srand((double)microtime() * 1000000);
  $y_force_update = mt_rand();

  $config = array(
    "autoupdate" => $autoupdate,
    "y_force_update" => $y_force_update,
    "yumguiEXCLUDE" => $yumguiEXCLUDE,
    "yumguiEMAIL" => $yumguiEMAIL,
    "yumguiEMAILADDY" => $yumguiEMAILADDY,
    "yumUpdateTime" => $yumUpdateTime,
    "yumUpdateSU" => $yumUpdateSU,
    "yumUpdateMO" => $yumUpdateMO,
    "yumUpdateTU" => $yumUpdateTU,
    "yumUpdateWE" => $yumUpdateWE,
    "yumUpdateTH" => $yumUpdateTH,
    "yumUpdateFR" => $yumUpdateFR,
    "yumUpdateSA" => $yumUpdateSA
    );

  $cceClient->setObject("System", $config, "yum");
  $errors = $cceClient->errors();
}
else {
    // We're not saving changes. So we set 'skiplock' to call a handler that runs a
    // chmod 444 over our files in /tmp so that this PHP page can access them:

    mt_srand((double)microtime() * 1000000);
    $skiplock = mt_rand();
    $config = array(
	"skiplock" => $skiplock
    );
    $cceClient->setObject("System", $config, "yum");
    $errors = $cceClient->errors();
}

// get settings
$systemObj = $cceClient->getObject("System",array(), "yum");

$page = $factory->getPage();

$block = $factory->getPagedBlock("yumgui_head", array("yumTitle", "Settings", "Logs"));
$block->processErrors($serverScriptHelper->getErrors());

// Settings:
if($systemObj["autoupdate"] == "On") {
        $autoupdate_choices=array("autoupdate_yes" => "On", "autoupdate_no" => "Off");
}
else {
//Strict, but safe default:
        $autoupdate_choices=array("autoupdate_no" => "Off", "autoupdate_yes" => "On");
}

// Display YUM enabler switch:
$autoupdate_select = $factory->getMultiChoice("autoupdate",array_values($autoupdate_choices));
$autoupdate_select->setSelected($autoupdate_choices[$autoupdate], true);
$block->addFormField($autoupdate_select,$factory->getLabel("autoupdate"), "Settings");

$exclude_box = $factory->getTextBlock("yumguiEXCLUDE", $systemObj["yumguiEXCLUDE"]);
$exclude_box->setHeight("5");
$exclude_box->setWidth("40");
$exclude_box->setOptional(true);

$block->addFormField(
  $exclude_box,
  $factory->getLabel("yumguiEXCLUDE"),
  "Settings"
  );

$block->addFormField(
  $factory->getBoolean("yumguiEMAIL", $systemObj["yumguiEMAIL"]),
  $factory->getLabel("yumguiEMAIL"),
  "Settings"
  );

$yumguiEMAILADDYField = $factory->getTextField("yumguiEMAILADDY", $systemObj['yumguiEMAILADDY']);
$yumguiEMAILADDYField->setOptional ('silent');
$block->addFormField(
  $yumguiEMAILADDYField,
  $factory->getLabel("yumguiEMAILADDY"),
  "Settings"
);

$time_to_update = array();
for ($i = 0; $i < 24 ; $i++ ) {
  $time_to_update []= "$i:00";
  $time_to_update []= "$i:30";
}

$yumUpdateTime= $factory->getMultiChoice("yumUpdateTime", $time_to_update);
$yumUpdateTime->setSelected($systemObj["yumUpdateTime"], true);
$block->addFormField(
  $yumUpdateTime,
  $factory->getLabel("yumUpdateTime"),
  "Settings"
);

$block->addFormField(
  $factory->getBoolean("yumUpdateSU", $systemObj["yumUpdateSU"]),
  $factory->getLabel("yumUpdateSU"),
  "Settings"
);

$block->addFormField(
  $factory->getBoolean("yumUpdateMO", $systemObj["yumUpdateMO"]),
  $factory->getLabel("yumUpdateMO"),
  "Settings"
);

$block->addFormField(
  $factory->getBoolean("yumUpdateTU", $systemObj["yumUpdateTU"]),
  $factory->getLabel("yumUpdateTU"),
  "Settings"
);

$block->addFormField(
  $factory->getBoolean("yumUpdateWE", $systemObj["yumUpdateWE"]),
  $factory->getLabel("yumUpdateWE"),
  "Settings"
);

$block->addFormField(
  $factory->getBoolean("yumUpdateTH", $systemObj["yumUpdateTH"]),
  $factory->getLabel("yumUpdateTH"),
  "Settings"
);

$block->addFormField(
  $factory->getBoolean("yumUpdateFR", $systemObj["yumUpdateFR"]),
  $factory->getLabel("yumUpdateFR"),
  "Settings"
);

$block->addFormField(
  $factory->getBoolean("yumUpdateSA", $systemObj["yumUpdateSA"]),
  $factory->getLabel("yumUpdateSA"),
  "Settings"
);

if ($_PagedBlock_selectedId_yumgui_head == "Settings") {
  $block->addButton($factory->getSaveButton($page->getSubmitAction()));
}

// Display date of last update:
if ( file_exists("/var/log/yum.log") ) {
	//$yum_last_updated = $serverScriptHelper->getFile("/tmp/yum.update-date"); // I think it's better to get the date from the logfile instead:
	$yum_last_updated = `/usr/bin/stat /var/log/yum.log |grep "Modify:"|sed 's/Modify: //g'|sed 's/\..*//g'`;
	$block->addFormField(
	$factory->getTextField("yum_last_updated", $yum_last_updated, "r"),
	$factory->getLabel("yum_last_updated"), "Available updates"
  	);
}

// Display notice that system is currently installing updates:
if ( file_exists("/tmp/yum.updating") ) {
	$yum_is_pulling_updates = "This system is currently being updated. Please check back in a couple of minutes.";
	$block->addFormField(
	$factory->getTextField("yum_is_pulling_updates", $yum_is_pulling_updates, "r"),
	$factory->getLabel("yum_is_pulling_updates"), "Available updates"
  	);
}

// Logfile viewer:
if ((file_exists("/var/log/yum.log")) && (is_readable("/var/log/yum.log"))) {
    $datei_yum = "/var/log/yum.log";
    $array_yum = file($datei_yum);
    $array_yum = array_reverse($array_yum);

    for($x=0;$x<count($array_yum);$x++){
        // Replace
        $array_yum[$x] = nl2br($array_yum[$x]); //#newline conversion
        $array_yum[$x] = br2nl($array_yum[$x]);
	if ($x < 500) {
	        $the_file_data = $the_file_data.$array_yum[$x];
	}
    }
    $tfd_num = count($the_file_data);
}
if ($tfd_num < 1) {
    $the_file_data = "Logfile appears to be empty.";
}

$box = $factory->getTextBlock("", $the_file_data);
$box->setHeight("30");
$box->setWidth("85");
$block->addFormField($box, $factory->getLabel("yumlog"), "Logs");

print $page->toHeaderHtml(); 
print $block->toHtml(); 
print "<br>";

if ((($_PagedBlock_selectedId_yumgui_head == "yumTitle") || (!$_PagedBlock_selectedId_yumgui_head)) && (!$yum_is_pulling_updates)) { 
  $yum = $factory->getScrollList("yumTitle",
    array("name", "version", "status"), array(0,1,2));
  $yum->setDefaultSortedIndex(0);
  $yum->addButton($factory->getButton("/base/swupdate/yum-check-update.php", "yumCheck"));
  if ( file_exists("/tmp/yum.check-update") ) {
    $yum_output = file_get_contents("/tmp/yum.check-update");
  }
  else {
    $yum_output = "";
  }
  $a_yum = preg_split("/\n/", $yum_output);
  $count = count($a_yum);
  $start = 0;
  for ( $i = 0; $i < $count; $i++ ) {
    if ( $a_yum[$i] == "" ) { $start = 1; }
    if ( $start == 1 AND $a_yum[$i] ) {
      $updates[] = $a_yum[$i];
    }
  }
  if(isset($updates) && count($updates) > 0 ) { 
    foreach ( $updates as $entry ) {
      $yum_update = 1;
      $entry = preg_replace("/\s+/", " ", $entry);
      $a_entry = preg_split("/ /", $entry);
      if ($a_entry[0] != "") {
    	    $yum->addEntry(array(
    	    $factory->getTextField("", $a_entry[0], "r"),
    	    $factory->getTextField("", $a_entry[1], "r"),
    	    $factory->getTextField("", $a_entry[2], "r")
    	    ));
      }
    }
  }

  $button = $factory->getButton("/base/swupdate/yum-update.php", "yumNOW");

  echo $yum->toHtml();
  echo "<br>";
  if ( $yum_update ) {
    echo $button->toHtml();
  }
}

$serverScriptHelper->destructor();
echo $page->toFooterHtml();
/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>