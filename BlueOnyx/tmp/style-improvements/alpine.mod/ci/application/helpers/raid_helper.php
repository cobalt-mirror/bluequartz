<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx Drives Helper
 *
 * This provides some core functions for the RAID Active Monitor
 *
 */

function fast_raid_check ($cce, $helper) {

    $array_health = array();
    $array_fail = array();

    if (file_exists( "/proc/mdstat" )) {

      $CI =& get_instance();
      $sessionId = $CI->input->cookie('sessionId');
      $ret = $helper->shell("/bin/cat /proc/mdstat", $mdstat, "root", $sessionId);

      //$ret = $helper->shell("/bin/cat /root/raid/raid_rebuild_after_fail", $mdstat, "root", $sessionId);
      //$ret = $helper->shell("/bin/cat /root/raid/raid_disk_fail", $mdstat, "root", $sessionId);
      //$ret = $helper->shell("/bin/cat /root/raid/raid_ok", $mdstat, "root", $sessionId);
      //$ret = $helper->shell("/bin/cat /root/raid/raid_sync", $mdstat, "root", $sessionId);

      $mdstat = explode("\n", $mdstat);
      $mdstat = preg_replace('/\s+/', '', $mdstat);

      foreach ($mdstat as $line) {
        if (preg_match('/^Personalities(.*)/', $line, $matches)) { continue; }
        if ((preg_match('/\[U\_\]/', $line, $matches)) || (preg_match('/\[_U\]/', $line, $matches))) {
            $array_fail[$part] = array("fail" => 'diskfail');
        }      
        if ((preg_match('/check=(.*)%/', $line, $matches)) || (preg_match('/recovery=(.*)%/', $line, $matches))) {
          $percent = $matches[1];
          unset($array_fail[$part]);
        }
        if (preg_match('/^(.*):/', $line, $matches)) {
          $part = $matches[1];
        }
        if (preg_match('/finish=(.*)minspeed/', $line, $matches)) {
          $eta = $matches[1];
        }
        if (preg_match('/DELAYED/', $line, $matches)) {
          $eta = 'delayed';
          $percent = '0.0';
          unset($array_fail[$part]);
        }
        if ((isset($percent)) && (isset($part)) && (isset($eta))) {
          if (($percent != '') && ($part != '') && ($eta != '')) {
            $array_health[$part] = array("percent" => $percent, "eta" => $eta);
            $part = '';
            $percent = '';
            $eta = '';
          }
        }
      }
    }
    return array($array_health, $array_fail);
}

function raid_table($factory, $cce, $helper) {

    $CI =& get_instance();

    // Load AM Detail Helper:
    $CI->load->helper('amdetail');

    $i18n = $factory->i18n;

    // this info is always up to date, 
    // since we're get it directly from raid_amdetails.pl
    $date = time();

    $name = "raid_title";

    // get RAID config
    $oid = $cce->find('System');
    if (!$oid) {
        // not found
    }
    $raid = $cce->get($oid[0], 'RAID');
    $level = $raid['level'];
    $disks = $raid['disks'];

    // if raid is not setup, then try to figure out what the default is
    if ($level === '') {
        if ($disks == 1) {
                $level = 0;
        }
        elseif ($disks == 2) {
                $level = 1;
        }
        elseif ($disks == 3) {
                $level = 1;
        }
        elseif ($disks == 4) {
                $level = 10;
        }
        else {
                $level = 0;
        }
    }

    if ($disks == 0) {
      $config_string = "[[base-raid.raid_disabled]]";
    } else {
      $config_string = "[[base-raid.config_raid$level,numdisks=$disks,plural=\"" . 
      ($disks == 1 ? '' : 's') . "\"]]";  
    }

    if (!is_file("/proc/mdstat")) {
      $state = "noraid";
    }
    else {
      // Not working relieable as raid_amdetails.pl is partially blind these days. We fix that later:
      //$sessionId = $CI->input->cookie('sessionId');
      //$ret = $helper->shell("/usr/sausalito/swatch/bin/raid_amdetails.pl", $raidState, "root", $sessionId);
      //$raidState = explode("\n", $raidState);
      //$state = array_shift($raidState);

      list($array_health, $array_fail) = fast_raid_check($cce, $helper);
      if ((count($array_fail) > 0) && (count($array_health) == 0)) {
        $state = "fail";
      }
      elseif ((count($array_fail) == 0) && (count($array_health) > 0)) {
        $state = "syncing";
      }
      elseif ((count($array_fail) == 0) && (count($array_health) == 0)) {
        $state = "raidOK";
      }
      else {
        // Both are >1:
        $state = "syncing";
      }
    }

    switch ($state) {
    	case "noraid": 
    		$stateMessage = "raid_disabled";
    		$stateBall = "none";
    		$refresh = 900;
    		break;
    	case "raidOK":
    		$stateMessage = "raid_working";
    		$stateBall = "normal";
    		$refresh = 900;
    		break;
    	case "syncing": 
    		$stateMessage = "raid_sync_in_progress";
    		$stateBall = "problem";
    		$refresh = 30;
    		break;
      case "incomplete":
        $stateMessage = "raid_sync_incomplete";
    		$stateBall = "problem";
    		$refresh = 30;
    		break;
    	case "fail":
    		if ($level == '0') {
    			$stateMessage = "raid0_failure";
    		}
    		else {
    			$stateMessage = "raid_failure";
    		}
    		$stateBall = "severeProblem";
    		$refresh = 45;
    		break;
    	default:
  	  	$stateMessage = " ";
    		$stateBall = "none";
    		$refresh = 60;
    		break;
      }

    $refresh = $refresh * 1000; # convert to milliseconds
    $stateMessage = ($stateMessage)? "[[base-raid.$stateMessage]]": "";

    $msg = "";

    if ($state == "syncing") {
      list($array_health, $array_fail) = fast_raid_check($cce, $helper);
      if (count($array_health) > 0) {
        $stateMessage = "raid_sync_in_progress";
        $stateBall = "problem";
        $msg = $factory->getVerticalCompositeFormField(array(), "", "r");
        // We have an array snc'ing or waiting for a sync.
        foreach ($array_health as $key => $value) {
          $syncing_array = $key;
          foreach ($value as $skey => $svalue) {
            if ($skey == "percent") {
              $percent = $svalue;
            }
            if ($skey == "eta") {
              $eta = $svalue;
            }
          }
          $sync_complete = $percent; 
          if ($eta == "delayed") {
              $timeMessage = $sync_complete . " % " . $i18n->interpolate("[[base-raid.raid_completed]]") . ", " . $i18n->interpolate("[[base-raid.raid_sync_delayed]]");
          }
          else if ($eta < 1) {
              $timeMessage = $sync_complete . " % " . $i18n->interpolate("[[base-raid.raid_completed]]") . ", " . $i18n->interpolate("[[base-raid.raid_less_than_one_remaining]]");
          }
          else {
              $eta = floor($eta);
              $timeMessage = $sync_complete . " % " . $i18n->interpolate("[[base-raid.raid_completed]]") . ", " . $eta . " " . $i18n->interpolate("[[base-raid.raid_minutes_remaining]]");
          }

          //$progressBar = $factory->getBar($part, $percent, $sync_complete);
          $progressBar = $factory->getBar($syncing_array, floor($percent), $timeMessage);
          $progressBar->setBarText($timeMessage);
          $progressBar->setLabel($syncing_array);
          $msg->addFormField($progressBar);
        }
      }
    }
    else {
      $msg = $factory->getVerticalCompositeFormField(array(), "", "r");
      if (is_file("/proc/mdstat")) {
        $msg->addFormField($factory->getTextField("config", $i18n->get($config_string), "R"));
      }
      $msg->addFormField($factory->getTextField("one", $i18n->get($stateMessage), "R"));
      $msg->setAlignment("left");
    }
    $icon = $factory->getStatusSignal($stateBall);

    $stmap = array(
        "none" => "none", 
        "normal" => "normal", 
        "problem" => "problem", 
        "severeProblem" => "severeProblem");

      $colormap = array(
          "none" => "light", 
          "normal" => "blue", 
          "problem" => "orange", 
          "severeProblem" => "red");

      $iconmap = array(
          "none" => "ui-icon-radio-on", 
          "normal" => "ui-icon-check", 
          "problem" => "ui-icon-notice", 
          "severeProblem" => "ui-icon-alert");

      $icTMmap = array(
          "none" => "raid_disabled", 
          "normal" => "raid_working", 
          "problem" => "integrityProblem", 
          "severeProblem" => "integritySevereProblem");

    $icon = '<button class="' . $colormap[$stateBall] . ' tiny icon_only tooltip hover" title="' . $i18n->getWrapped($stateMessage) . '"><div class="ui-icon ' . $iconmap[$stateBall] . '"></div></button> ' . $i18n->getHtml($icTMmap[$stateBall]);

    return am_detail_block_core($factory, $name, $icon, $msg, $date);
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>