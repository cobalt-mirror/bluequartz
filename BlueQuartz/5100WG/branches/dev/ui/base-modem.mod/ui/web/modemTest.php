<?php
// modem test page
// try to dial in using the settings given by the user
// update continuously through connect process, so user can see what is failing
// if connected successfully check to see if the network is functional

// back to cobalt ui
include("CobaltUI.php");
include("ServerScriptHelper.php");

// initialize some vars
$init_status = array( 'Stage', 0);
$dial_status = array();
$pppd_status = array();
$dns_status = array();
$net_status = array();
$fail = 0;
$done = 0;
$stage = "init";

$helper = new ServerScriptHelper();
$ui = new CobaltUI($sessionId, "base-modem");

$ui->SetAction(array(
		'Action' => 'REF',
		'Target' => 'System',
		'Namespace' => 'Modem'
		));

$ui->Handle();

// save connection mode in case we need to reset it
if (!$requestConnMode)
	$requestConnMode = $ui->Data["connMode"];

// shutdown pppd if it is already running, so we can run it ourself
if ($ui->Data["connMode"] != 'off')
	$ui->Cce->setObject("System", array( "connMode" => 'off'), "Modem");

$ui->StartBlock("testModem");

if (!$mode) {
	// check that the serial port lock file is gone before continuing
	// otherwise we enter an infinite loop, 
	// but don't wait longer than 60 seconds
	$current_time = time();
	$end_time = $current_time + 60;
	
	while(file_exists("/var/lock/LCK.." . $ui->Data["port"])) {
		// clear cache, otherwise the file will always exist
		clearstatcache();
	
		// check to see if pppd is running
		if(!system("/bin/ps --no-headers -C pppd"))
			// lock file must be stale, go ahead because pppd will deal with it
			break;
	
		$current_time = time();
		if ($current_time > $end_time) {
			$fail = 1;
			$init_status = array();
			$ui->TextField(
					"testFail", 
					array( 
					'Access' => 'r',
					'Value' =>
						$ui->I18n->interpolate('[[base-modem.testFailMsg]]')
					)
				);
			break;
		}
	}

	if (!$fail) {
		// MPBug fixed
		// need to know our temp file names before starting test scripts, since they need
		// to be passed on when refreshing the status page
		$chatf = tempnam('/tmp/', 'chat');
		$pppdf = tempnam('/tmp/', 'pppd');
		$netf = tempnam('/tmp/', 'net');

		// run modem test, it forks pppd itself and returns
		$ret = $helper->shell("/usr/sausalito/sbin/test_modem.pl $chatf $pppdf", $junk);
	
		// check for failure, this should never happen
		if ($ret) {
			$fail = 1;
			$init_status = array();
			$ui->TextField("testFail", 
				array( 
					'Access' => 'r',
					'Value' => $ui->I18n->interpolate('[[base-modem.testFailMsg]]')
				));
		}
	
		// set our mode
		$mode = 'run';
	}
} else {
	// read in log files
	$chat = file($chatf);
	$pppd = file($pppdf);

	for($i = 0; (($stage == "init") || ($stage == "dial")) && ($i < count($chat)); $i++) {
		$chat[$i] = trim($chat[$i]);
		if ($stage == "init") {
			$init_status = check_status($chat[$i], "got it", "failed");

			if($init_status[0] == "Success")
				$stage = "dial";
			elseif($init_status[0] == "Fail") {
				$fail = 1;
				$done = 1;
				break;
			}
		} elseif ($stage == "dial") {
			$dial_status = check_status($chat[$i], "got it", "^failed \(([a-z ]+)\)|^failed");

			if($dial_status[0] == "Success")
				$stage = "connect";
			elseif($dial_status[0] == "Fail") {
				$dial_status[1] = modem_error($dial_status[1]);
				$fail = 1;
				$done = 1;
				break;
			}
		}
		
	
	}

	for($i = 0; ($stage == "connect") && ($i < count($pppd)); $i++) {
		$pppd[$i] = trim($pppd[$i]);
	
		$pppd_status = check_status($pppd[$i], "local", "hangup|timeout|failed|terminating");

		if ($pppd_status[0] == "Success")
			$stage = "test_dns";
		elseif ($pppd_status[0] == "Fail") {
			$pppd_status[1] = pppd_error($pppd_status[1]);
			$fail = 1;
			$done = 1;
			break;
		}
	}
}

// network test
if (($stage == 'test_dns') && ($mode == 'run')) {
	$helper->fork("/usr/sausalito/sbin/test_net.pl $netf");
	$mode = 'test_net';
	$dns_status = array("Stage", 0);
} elseif ($mode == 'test_net') {
	// reset for network test
	$stage = 'test_dns';
	$dns_status = array("Stage", 0);

	// read in log file
	$net = file($netf);

	if($net[0]) {
		$dns_status = check_status($net[0], "dns ok", "failed");

		// even if dns test fails go to network test stage
		if($dns_status[0] != "Stage")
			$stage = 'test_net';
	}

	if ($stage == 'test_net') {
		$net_status = array("Stage", 0);

		if ($net[1]) {
			$net_status = check_status($net[1], "network ok", "failed");

			if($net_status[0] == "Success")
				$done = 1;
			elseif($net_status[0] == "Fail"){
				$fail = 1;
				$done = 1;
			}
		}
	}
}

// now create text fields displaying current status of each stage
// if status is known
if ($init_status)
	display_status($ui, "initModem", $init_status, array( "init" => $ui->Data["initStr"] ));

if ($dial_status)
	display_status($ui, "dialModem", $dial_status, array( "phone" => $ui->Data["phone"] ));

if ($pppd_status)
	display_status($ui, "pppdAuth", $pppd_status);

if ($dns_status)
	display_status($ui, "dnsTest", $dns_status);

if ($net_status)
	display_status($ui, "netTest", $net_status);

// finish off the page
if (!$fail && !$done)
	$ui->AddScript("setTimeout(\"location.replace('/base/modem/modemTest.php?mode=$mode&requestConnMode=$requestConnMode&chatf=$chatf&pppdf=$pppdf&netf=$netf')\", 5000);");
else {
	if (!$fail)
		// kill pppd
		system('/usr/bin/killall pppd');
	else
		// set connection mode to off
		$requestConnMode = 'off';
	
	$ui->AddGenericButton('/base/network/wan.php', 'back');
	
	// set connection mode
	$ui->Cce->setObject("System", 
			array( "connMode" => $requestConnMode ),
			"Modem");
}

$ui->EndBlock();
$ui->EndPage();

function check_status($line, $success_string, $fail_re) {
	if (eregi($fail_re, $line, $regs))
		if($regs[1])	
			return array("Fail", $regs[1]);
		else
			return array("Fail", $line);

	if (eregi("$success_string", $line))
		return array("Success", 0);
	else
		return array("Stage", 0);
}

function display_status(&$ui, $stage, $status, $other_vars = array()) {
	$vars = array_merge(array( "errmsg" => $status[1]), $other_vars);

	$message = "[[base-modem." . $stage . $status[0] . "]]";

	$ui->Data[$stage] = $ui->I18n->interpolate($message, $vars);
	$ui->TextField($stage, 
			array(
				'Access' => 'r'
			)
		);
}

// process the error message returned, so that the errors can be localized
function modem_error($errmsg) {
	// make sure error message is in lower case
	$errmsg = strtolower($errmsg);

	// return proper i18n tag for error message
	// these should be enough to be descriptive for dial failure
	switch($errmsg) {
		case 'no dialtone':
		case 'no dial tone':
			return '[[base-modem.noDialtone]]';
			break;
		case 'busy':
			return '[[base-modem.busy]]';
			break;
		case 'no carrier':
			return '[[base-modem.noCarrier]]';
			break;
		case 'waiting':
			return '[[base-modem.waiting]]';
			break;
		default:
			return '[[base-modem.unknownError]]';
	}

}

function pppd_error($errmsg) {
	if (eregi("timeout", $errmsg))
		return '[[base-modem.noResponse]]';
	elseif (eregi("authentication failed", $errmsg))
		return '[[base-modem.authFailure]]';
	elseif (eregi("hangup", $errmsg))
		return '[[base-modem.remoteTerm]]';
	else
		return '[[base-modem.unknownError]]';
}


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

