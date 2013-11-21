<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2008, Stauber Multimedia Design. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-mysql");

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-mysql", "/base/mysql/mysqlusersHandler.php");
$transMethodOn="off";

// get settings
$systemObj = $cceClient->getObject("System",array(),"MYSQLUSERS_DEFAULTS");

$page = $factory->getPage();

$block = $factory->getPagedBlock("mysql_head", array('MySQL_TAB_ONE', 'MySQL_TAB_TWO'));
$block->processErrors($serverScriptHelper->getErrors());

// Force Update of CODB:
mt_srand((double)microtime() * 1000000);
$zufall = mt_rand();
$force_update_Field = $factory->getTextField("force_update", $zufall);
$force_update_Field->setOptional ('silent');
$block->addFormField(
    $force_update_Field,
    $factory->getLabel("force_update"),
  "hidden"
);

// Add MySQL userdb switch:
$udb_switch = $systemObj['solsitemysql'];
$block->addFormField(
  $factory->getBoolean("udb_switch", $udb_switch),
  $factory->getLabel("udb_switch"),
  'MySQL_TAB_ONE'
);

// Add divider:
$block->addDivider($factory->getLabel("DIVIDER_ZERO", false), 'MySQL_TAB_ONE');

$my_TEXT = $i18n->interpolate("[[base-mysql.MySQL_Info_Text]]");
$block->addFormField(
  $factory->getTextField("mysql_default_privileges", $my_TEXT, 'r'),
  $factory->getLabel(" "), 
  'MySQL_TAB_ONE' 
);

// Add divider:
$block->addDivider($factory->getLabel("DIVIDER_ONE", false), 'MySQL_TAB_ONE');

$SELECT = $systemObj['SELECT'];
$block->addFormField(
  $factory->getBoolean("SELECT", $SELECT),
  $factory->getLabel("SELECT"),
  'MySQL_TAB_ONE'
);
$INSERT = $systemObj['INSERT'];
$block->addFormField(
  $factory->getBoolean("INSERT", $INSERT),
  $factory->getLabel("INSERT"),
  'MySQL_TAB_ONE'
);
$UPDATE = $systemObj['UPDATE'];
$block->addFormField(
  $factory->getBoolean("UPDATE", $UPDATE),
  $factory->getLabel("UPDATE"),
  'MySQL_TAB_ONE'
);
$DELETE = $systemObj['DELETE'];
$block->addFormField(
  $factory->getBoolean("DELETE", $DELETE),
  $factory->getLabel("DELETE"),
  'MySQL_TAB_ONE'
);
$FILE = $systemObj['FILE'];
$block->addFormField(
  $factory->getBoolean("FILE", $FILE, "r"),
  $factory->getLabel("FILE"),
  'MySQL_TAB_ONEx'
);

// Add divider:
$block->addDivider($factory->getLabel("DIVIDER_TWO", false), 'MySQL_TAB_ONE');

$CREATE = $systemObj['CREATE'];
$block->addFormField(
  $factory->getBoolean("CREATE", $CREATE),
  $factory->getLabel("CREATE"),
  'MySQL_TAB_ONE'
);
$ALTER = $systemObj['ALTER'];
$block->addFormField(
  $factory->getBoolean("ALTER", $ALTER),
  $factory->getLabel("ALTER"),
  'MySQL_TAB_ONE'
);
$INDEX = $systemObj['INDEX'];
$block->addFormField(
  $factory->getBoolean("INDEX", $INDEX),
  $factory->getLabel("INDEX"),
  'MySQL_TAB_ONE'
);
$DROP = $systemObj['DROP'];
$block->addFormField(
  $factory->getBoolean("DROP", $DROP),
  $factory->getLabel("DROP"),
  'MySQL_TAB_ONE'
);
$TEMPORARY = $systemObj['TEMPORARY'];
$block->addFormField(
  $factory->getBoolean("TEMPORARY", $TEMPORARY),
  $factory->getLabel("TEMPORARY"),
  'MySQL_TAB_ONE'
);

// Add divider:
$block->addDivider($factory->getLabel("DIVIDER_THREE", false), 'MySQL_TAB_ONE');

$mysql_so = '/usr/lib/mysql/libmysqlclient.so.15.0.0';
if (file_exists($mysql_so)) {
	$access = 'rw';
}
else {
	$access = 'r';
}

$GRANT = $systemObj['GRANT'];
$block->addFormField(
  $factory->getBoolean("GRANT", $GRANT, $access),
  $factory->getLabel("GRANT"),
  'MySQL_TAB_ONE'
);
$REFERENCE = $systemObj['REFERENCE'];
$block->addFormField(
  $factory->getBoolean("REFERENCE", $REFERENCE, $access),
  $factory->getLabel("REFERENCE"),
  'MySQL_TAB_ONE'
);
$LOCK = $systemObj['LOCK'];
$block->addFormField(
  $factory->getBoolean("LOCK", $LOCK, $access),
  $factory->getLabel("LOCK"),
  'MySQL_TAB_ONE'
);
$CREATE_VIEW = $systemObj['CREATE_VIEW'];
$block->addFormField(
  $factory->getBoolean("CREATE_VIEW", $CREATE_VIEW, $access),
  $factory->getLabel("CREATE_VIEW"),
  'MySQL_TAB_ONE'
);
$SHOW_VIEW = $systemObj['SHOW_VIEW'];
$block->addFormField(
  $factory->getBoolean("SHOW_VIEW", $SHOW_VIEW, $access),
  $factory->getLabel("SHOW_VIEW"),
  'MySQL_TAB_ONE'
);
$CREATE_ROUTINE = $systemObj['CREATE_ROUTINE'];
$block->addFormField(
  $factory->getBoolean("CREATE_ROUTINE", $CREATE_ROUTINE, $access),
  $factory->getLabel("CREATE_ROUTINE"),
  'MySQL_TAB_ONE'
);
$ALTER_ROUTINE = $systemObj['ALTER_ROUTINE'];
$block->addFormField(
  $factory->getBoolean("ALTER_ROUTINE", $ALTER_ROUTINE, $access),
  $factory->getLabel("ALTER_ROUTINE"),
  'MySQL_TAB_ONE'
);
$EXECUTE = $systemObj['EXECUTE'];
$block->addFormField(
  $factory->getBoolean("EXECUTE", $EXECUTE, $access),
  $factory->getLabel("EXECUTE"),
  'MySQL_TAB_ONE'
);

// Add divider:
$block->addDivider($factory->getLabel("DIVIDER_FOUR", false), 'MySQL_TAB_ONE');

$MAX_QUERIES_PER_HOUR = $systemObj['MAX_QUERIES_PER_HOUR'];
$block->addFormField(
  $factory->getTextField("MAX_QUERIES_PER_HOUR", $MAX_QUERIES_PER_HOUR),
  $factory->getLabel("MAX_QUERIES_PER_HOUR"),
  'MySQL_TAB_ONE'
);
$MAX_CONNECTIONS_PER_HOUR = $systemObj['MAX_CONNECTIONS_PER_HOUR'];
$block->addFormField(
  $factory->getTextField("MAX_CONNECTIONS_PER_HOUR", $MAX_CONNECTIONS_PER_HOUR),
  $factory->getLabel("MAX_CONNECTIONS_PER_HOUR"),
  'MySQL_TAB_ONE'
);
$MAX_UPDATES_PER_HOUR = $systemObj['MAX_UPDATES_PER_HOUR'];
$block->addFormField(
  $factory->getTextField("MAX_UPDATES_PER_HOUR", $MAX_UPDATES_PER_HOUR),
  $factory->getLabel("MAX_UPDATES_PER_HOUR"),
  'MySQL_TAB_ONE'
);

// Review my.cnf:
$datei_zwo = "/etc/my.cnf";
$array_zwo = file($datei_zwo);
for($x=0;$x<count($array_zwo);$x++){
	### Replace
        $array_zwo[$x] = nl2br($array_zwo[$x]); //#newline conversion
        $array_zwo[$x] = preg_replace('/\s\s+/', '', $array_zwo[$x]); //#strip spaces
	### Replace end
	$array_zwo[$x] = br2nl($array_zwo[$x]);
	$the_file_data = $the_file_data.$array_zwo[$x];
}

// Add my.cnf editor block:
$GLOBALS["_FormField_height"] = 40;
$GLOBALS["_FormField_width"] = 90;

$block->addFormField(
  $factory->getTextBlock("my_cnf", $the_file_data),
  $factory->getLabel("my_cnf"),
  'MySQL_TAB_TWO'
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
<?php print($block->toHtml()); ?>
<?php print($page->toFooterHtml()); ?>

<?php
function br2nl($str) {
   $str = preg_replace("/(\r\n|\n|\r)/", "", $str);
   return preg_replace("=<br */?>=i", "\n", $str);
}
?>
