<?

include("./drives.inc");
include("ServerScriptHelper.php");
include("./raid.inc");
include("base/am/imagelib.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$htmlFactory = $serverScriptHelper->getHtmlComponentFactory("base-raid");
$page = $htmlFactory->getPage();
$i18n = $htmlFactory->i18n;

print($page->toHeaderHtml());

raid_table($htmlFactory, $cce, $serverScriptHelper);

// get info
$status = drive_status($cce, $serverScriptHelper);

// find overall worst status
$worst = 'N';
$severity = array('N' => 0, 'G' => 1, 'Y' => 2, 'R' => 3);
foreach (array_keys($status) as $drive) {
  $state = $status[$drive]['state'];
  $worst = ($severity[$state] > $severity[$worst]) ? $state : $worst;
  $syncing = $syncing ? $syncing : in_array('[[base-raid.drive_syncing]]', $status[$drive]['msgs']);
}

// set up strings for per drive status block
switch ($worst) {
 case 'G':
   $string = "[[base-raid.drives_ok]]";
   $ball = 'normal';
   break;
 case 'Y':
   $string = $syncing ? "[[base-raid.drives_syncing_problem]]" : "[[base-raid.drives_problem]]";
   $ball = 'problem';
   break;
 case 'R':
   $string = "[[base-raid.drives_severe_problem]]";
   $ball = 'severeProblem';
   break;
 case 'N':
   $string = "[[base-raid.drives_no_info]]";
   $ball = 'none';
   break;
 default:
   error_log("should never happen");
   break;
}

$name = "per_drive_title";
$icon = $htmlFactory->getStatusSignal($ball);
$msg = $htmlFactory->getTextField("per_disk", $i18n->get($string), "r");


// find last changed time
$time=time();

// finally. print per-drive-status block
print("<BR>");
am_detail_block_core($htmlFactory, $name, $icon, $msg, $time);

// create and print image
$images_config = get_images_config();

$drives_config = $images_config['am_drives'];

$render_info = get_render_info($status, $drives_config);

$name = "map1";

print image_map($name, $render_info);
print "<BR>";
// need to put random data in the URL otherwise Netscape caches the URL
print '<IMG border=0 usemap="#map1" SRC="image.php?random=' . time() . '&mydata=' . rawurlencode(serialize($status)) . "\">";
print("<BR>");

am_back($htmlFactory);
print($page->toFooterHtml());
$serverScriptHelper->destructor();
exit;
/*
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

