<?php
// Author: Michael Stauber
// $Id: PieChart.php
//
// This Class works similar than the BarGraph Class, but is used
// to create pie charts instead.
//
// This new Class uses Flot instead to generate the Graphs:
//
// Flot
// Version: 0.8.3
// Link: http://www.flotcharts.org/
// Description: Charting solution, see link for full feature list.
//

global $isPieChartDefined;
if ( $isPieChartDefined )
	return;
$isPieChartDefined = true;

include_once("uifc/HtmlComponent.php");

class PieChart extends HtmlComponent {

	var $Ylabel;
	var $Xlabel;
	var $width;
	var $height;
	var $id;
	var $Label, $Description;
	var $numgraphs;
	var $graph;
	var $data_labels;
	var $xlabels;
	var $xaxisLabels;

	//
	// public methods
	//

	// constructor
	function PieChart($BxPage, $id, $data) {
		$this->BxPage = $BxPage;
		$this->id = $id;

		// Find out how many Graphs our $data contains:
		$this->numgraphs = count(array_keys($data));

		// We use the array keys as labels for our graphs.
		// So we extract the keys first:
		$this->data_labels = array_keys($data);

		// Start sane:
		$this->graph = "	var data = [\n";

		// Do a proper encoding of our Graph data:
		$endentry = count($this->data_labels);
		$entry = "0";
		foreach ($data as $key => $value) {
			// The fucking joys of PHP's localization implementation:
			// If we're in 'en_US', the dot is our delimiter. If we're
			// in 'de_DE' or others, the comma is the numeric delimiter.
			// jQuery expects dots as delimiter. So we give it dots:
			$value = preg_replace('/,/', '.', $value);

			$this->graph .= '		{ label: "' . $key . '",  data: ' . $value . ' }';
			$entry++;
			if ($entry < $endentry) {
				$this->graph .= ", \n";
			}
		}

		// End sane:
		$this->graph .= "\n	];\n";

	}

	function getId() {
		return $this->id;
	}

	function getValue() {
		// This is a dummy return so we can live in a PageBlock():
		return FALSE;
	}

	function getAccess() {
		$access = "rw";
		return $access;
	}

	function isOptional() {
		return TRUE;
	}

	function setYLabel($label) {
		$this->Ylabel = $label;
	}

	function setXLabel($label) {
		$this->Xlabel = $label;
	}

	function setSize($width = "739", $height = "450") {
		$this->width = $width . "px";
		$this->height = $height . "px";
	}

	function getWidth() {
		if (!isset($this->width)) {
			$this->width = "739px";
		}
		return $this->width;
	}

	function getHeight() {
		if (!isset($this->height)) {
			$this->height = "450px";
		}
		return $this->height;
	}

	// Sets the current label
	function setCurrentLabel($label) {
		$this->Label = $label;
	}

	// Returns the current label
	function getCurrentLabel() {
		if (!isset($this->Label)) {
		  	$this->Label = "";
		}
		return $this->Label;
	}

	// Sets the current label-description:
	function setDescription($description) {
		if (!isset($this->Description)) {
			$this->Description = "";
		}
		$this->Description = $description;
	}

	// Returns the current label-description:
	function getDescription() {
		return $this->Description;
	}

	function toHtml($style = "") {

		if (isset($this->Xlabel)) {
			$out = '
				<div class="box grid_16">
					<h2 class="box_head">' . $this->Xlabel . '</h2>
				</div>';
		}
		else {
			$out = '';
		}

		$out .= '<div id="' . $this->id . '" style="width:' . $this->getWidth() . ';height:' . $this->getHeight() . '"></div>' . "\n";
		$out .=	"<script>\n";
		$out .=	$this->graph;
		$out .=	"
			$.plot($(\"#" . $this->id . "\"), data, {
				series: {
					pie: { 
						show: true,
						combine: {
							color: \"#999\",
							threshold: 0.05
						}
					}
				},
				legend: {
					show: false
				},
			});
			</script>";

		return $out;
	}
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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