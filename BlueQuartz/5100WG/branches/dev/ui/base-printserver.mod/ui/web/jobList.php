<?php

include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-printserver");
$i18n = $serverScriptHelper->getI18n();

$page = $factory->getPage();

$backButton = $factory->getBackButton("/base/printserver/printerList.php");

$out = `lpq -P$printerName -L`;
$out=explode("\n",$out);

if(preg_match("/printing disabled/", $out[0])){
	$suspend = $factory->getButton("/base/printserver/jobSuspend.php?printerName=$printerName&currState=disabled","unsuspendPrinter"); 
}else{
	$suspend = $factory->getButton("/base/printserver/jobSuspend.php?printerName=$printerName&currState=enabled","suspendPrinter");
}

$clear = $factory->getButton("/base/printserver/jobDelete.php?printerName=$printerName&jobName=all","clearQueue");

$scrollList = $factory->getScrollList('[[base-printserver.jobList,name="'.$printerName.'"]]', array("owner", "size", "date",
"listAction"));
$scrollList->setAlignments(array("left", "left", "left", "center"));
$scrollList->setColumnWidths(array("", "", "", "1%"));

$bytes = $i18n->get("Bytes");
for($i=0; $i<count($out); $i++){
	if(preg_match("/^\d|^[a-z]/", $out[$i])){
		$job=parse($out[$i]);
	        $scrollList->addEntry(array(
        	        $factory->getTextField("", $job["Owner"], "r"),
                	$factory->getTextField("", $job["size"]." ".$bytes, "r"),
			$factory->getTextField("", $job["date"], "r"),
	                $factory->getRemoveButton(
        	                "javascript: confirmRemove('".$job["Owner"]."','".$job["ID"]."', '".$printerName."')"
                	)
	        ), "", false, -1);
			// -1 = add the entry to the end of the scrollList.
	}
}

function parse($line){
//	echo $line;
	$matches=array();
	$ret=array();
	preg_match("/^(?:\d+|\w\S+)\s+([^\s]+)\s+[^\s]+\s+[^\s]+\s+[^\s]+\s+([^\s]+)\s+([^\s]+)/", $line, $matches);
	$tmp = explode("+",$matches[1]);
	$ret["Owner"] = $tmp[0];
	$ret["ID"] = $tmp[1];
	$ret["size"] = $matches[2];
	$ret["date"]= $matches[3];

	return $ret;
}


$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
 
<SCRIPT LANGUAGE="javascript">
function confirmRemove(jobName, jobId, printerName) {
  var message = "<?php print($i18n->interpolate("[[base-printserver.removeJobConfirm]]"))?>";
  message = top.code.string_substitute(message, "[[VAR.jobName]]", jobName);
 
  if(confirm(message))
    location = "/base/printserver/jobDelete.php?printerName="+printerName+"&jobName="+jobId;
}
</SCRIPT>

<TABLE CELLSPACING="3" CELLPADDING="3">
        <TR>
                <TD><?  print($clear->toHtml()); ?></TD>
                <TD><?  print($suspend->toHtml());  ?></TD>
        </TR>
</TABLE> 
<BR>

<?php print($scrollList->toHtml()); ?>
<BR>
<?php print($backButton->toHtml()); ?>
 
<?php print($page->toFooterHtml());                                        
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

