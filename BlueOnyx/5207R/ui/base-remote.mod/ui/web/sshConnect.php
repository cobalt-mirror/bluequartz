<?php
	// Author: Brian N. Smith
	// Copyright 2006, NuOnce Networks, Inc. All rights reserved.
	// $Id: ssh.php,v 1.0 2006/07/15 14:35:00 Exp $

	include_once("ServerScriptHelper.php");

	$serverScriptHelper = new ServerScriptHelper();
	$cceClient = $serverScriptHelper->getCceClient();
	$i18n = $serverScriptHelper->getI18n("base-remote");

	if (!$serverScriptHelper->getAllowed('adminUser')) {
		header("location: /error/forbidden.html");
		return;
	}

	$factory = $serverScriptHelper->getHtmlComponentFactory("base-remote", "/base/remote/sshConnect.php");

	$page = $factory->getPage();

	$block = $factory->getPagedBlock("header");
	$block->processErrors($serverScriptHelper->getErrors());
	$block->setColumnWidths(array('0',"100%"));

	$string = $i18n->interpolateHtml("[[base-remote.about]]");

        $block->addFormField(
            $factory->getTextList("", $string, 'r'),
            $factory->getLabel(" ")
        );

	$block->addButton($factory->getButton("/base/remote/sshConnect.php", "connect"));

	echo $page->toHeaderHtml();
	echo $block->toHtml();
?>
<APPLET CODE="com.mindbright.application.MindTerm.class" ARCHIVE="mindterm.jar" WIDTH=0 HEIGHT=0\>    
  <PARAM NAME="cabinets" VALUE="mindterm.cab">    
  <PARAM NAME="debug" value="false">
  <PARAM NAME="sepframe" value="true">    
  <PARAM NAME="allow-new-server" value="false">
  <PARAM NAME="exit-on-logout" value="true">   
  <PARAM NAME="quiet" VALUE="false">
  <PARAM NAME="term-type" VALUE="xterm-color">
  <PARAM NAME="bg-color" VALUE="black">
  <PARAM NAME="fg-color" VALUE="187,187,187">
  <PARAM NAME="font-size" VALUE="14">
</APPLET>
<?php
	echo $page->toFooterHtml();

	$serverScriptHelper->destructor();
?>
