<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx AM Detail Helper
 *
 * This provides some core functions to draw consistent tables for detail
 * pages - you should include this if you need to draw AM details pages with
 * other data.
 *
 * @package   CI Blueonyx
 * @author    Michael Stauber
 * @author    Tim Hockin (original Cobalt Networks code)
 * @copyright Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

function am_detail_block($factory, $cce, $nsname, $name = "") {
    $i18n = $factory->i18n;

    $block = "";

	$BxPage = $factory->getBxPage();

    $amobj = $cce->getObject("ActiveMonitor");
    if ($amobj == null) {
		$msg = $factory->getTextBlock("", $i18n->interpolate("[[base-am.amObjNotFound]]"), "r");

		$block .= $msg->toHtml();
		$block .= "<BR>";
    }
    else {
		$amenabled = $amobj["enabled"];

	    $stmap = array(
	        "N" => "none", 
	        "G" => "normal", 
	        "Y" => "problem", 
	        "R" => "severeProblem");

	    $colormap = array(
	        "N" => "light", 
	        "G" => $BxPage->getPrimaryColor(), 
	        "Y" => "orange", 
	        "R" => "red");

	    $iconmap = array(
	        "N" => "ui-icon-radio-on", 
	        "G" => "ui-icon-check", 
	        "Y" => "ui-icon-notice", 
	        "R" => "ui-icon-alert");

	    $descmap = array(
	        "N" => "[[base-am.amKeyGrey]]", 
	        "G" => "[[base-am.amKeyGreen]]", 
	        "Y" => "[[base-am.amKeyYellow]]", 
	        "R" => "[[base-am.amKeyRed]]");

		$nspace = $cce->get($amobj["OID"], $nsname);

		// make sure we have a good block name
		if ($name == "") {
			$name = $nspace["nameTag"];
		}

		// get the status icon
	    if (($amobj['enabled'] == '0') || (!$nspace["enabled"]) || (!$nspace["monitor"])) {
			$icon = '<button class="light tiny icon_only tooltip hover" title="' . $i18n->getHtml("[[base-am.amKeyGrey]]") . '"><div class="ui-icon ui-icon-radio-on"></div></button>';
	    } 
	    else {
			$icon = '<button class="' . $colormap[$nspace["currentState"]] . ' tiny icon_only tooltip hover" title="' . $i18n->getHtml($descmap[$nspace["currentState"]]) . '"><div class="ui-icon ' . $iconmap[$nspace["currentState"]] . '"></div></button>';
        }

		// get the current message for this service
		if (!$amenabled) {
			$imsg = $i18n->interpolate("[[base-am.amNotMonitored]]");
		}
		elseif (!$nspace["enabled"]) {
			$imsg = $i18n->interpolate("[[base-am.amNotEnabled]]");
		}
		elseif (!$nspace["monitor"]) {
			$imsg = $i18n->interpolate("[[base-am.amNotMonitored]]");
		}
		else {
			if ($nspace["currentMessage"]) {
			  if (strstr($nspace["currentMessage"], "\n")) {
			    // if it's multiline, we'll put the strings
			    // into a VerticalComposite
			    $imsg = split("\n", $nspace["currentMessage"]);
			    for ($i = 0; $i < count($imsg); $i++) {
			      $imsg[$i] = $i18n->interpolate($imsg[$i]);
			    }
			  } else {
			    $imsg = $i18n->interpolate($nspace["currentMessage"]);
			  }
			} else {
				$imsg = $i18n->interpolate("[[base-am.amNoMessage]]");
			}
		}

		if ($nspace["lastChange"]) {
			$date = $nspace["lastChange"]; 
		} else {
			$date = "";
		}

		if (is_array($imsg)) {
		  $msgfield = $factory->getVerticalCompositeFormField(array());
		  $msgfield->setAlignment("left");
		  for ($i = 0; $i < count($imsg); $i++) {
		  	$my_msg = $factory->getTextField("msg$i", $imsg[$i], "r");
		  	$my_msg->setLabelType("nolabel");
		    $msgfield->addFormField($my_msg);
		  }
		}
		else {
//		  $msgfield = $factory->getTextField("msg", $imsg, "r");
//		  $msgfield->setLabelType("nolabel");
  		  $msgfield = $factory->getRawHtml("msg", $imsg, "r");

		}
		$block .= am_detail_block_core($factory, $name, $icon, $msgfield, $date);
    }
    return $block;
}

function am_detail_block_core($factory, $name, $icon, $msgfield, $date) {
    $i18n = $factory->i18n;

	$defaultPage = "basicSettingsTab";

    // get the main block 
    $mainblock = $factory->getPagedBlock($i18n->interpolate($name), array($defaultPage));

    // add status to the block
    //$statline = $factory->getCompositeFormField(array($icon, $msgfield), "&nbsp;&nbsp;", "r");

    if (is_object($msgfield)) {
    	$outMsg = $msgfield->toHtml();
    }
    else {
    	$outMsg = $msgfield;
    }

    if (is_object($icon)) {
    	$icon = $icon->toHtml();
    }

	$statline = $factory->getHtmlField("acs", "<p>" . $icon . "&nbsp;" . $outMsg . "</p>", "r");

    // need to manually set description
    // because we're using fully qualified i18n tags since
    // the factory passed in isn't in AM domain.
    $label = $factory->getLabel("[[base-am.amClientStatus]]");
    $label->setDescription($i18n->interpolateHtml("[[base-am.amClientStatus_help]]"));

    // Funny how that goes: We have to set the Label + Description in our reserve fashion as well, or it won't stick:
	$BxPage = $factory->getPage();
	$BxPage->Label = array("label" => $i18n->interpolateHtml("[[base-am.amClientStatus]]"), "description" => $i18n->interpolateHtml("[[base-am.amClientStatus_help]]"));

    $mainblock->addFormField($statline, $label, $defaultPage);

    // add date to the block
    if ($date != "") {
        $datefield = $factory->getTimeStamp("lastchange", 
            $date, "datetime", "r");
    }
    else {
        $datefield = $factory->getTextField("lastchange", 
        $i18n->interpolateHtml("[[base-am.amNever]]"), "r");
    }

    // need to manually set description
    // because we're using fully qualified i18n tags since
    // the factory passed in isn't in AM domain.
    $label = $factory->getLabel("[[base-am.amClientLastChange]]");
    $description = "[[base-am.amClientLastChange_help]]";
    $label->setDescription($description);

	$BxPage->Label = array("label" => $i18n->interpolate("[[base-am.amClientLastChange]]"), "description" => $i18n->interpolate("[[base-am.amClientLastChange_help]]"));

    $mainblock->addFormField($datefield, $label, $defaultPage);

    // print it 
    return $mainblock->toHtml();
}

function am_back($factory) {
	$back = $factory->getBackButton("/am/amStatus", "DEMO-OVERRIDE");
	$buttonContainer = $factory->getButtonContainer("", array($back));
    return $buttonContainer->toHtml();
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