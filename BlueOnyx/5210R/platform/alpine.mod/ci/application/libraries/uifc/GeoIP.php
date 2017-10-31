<?php
// $Id: GeoIP.php

global $isGeoIPDefined;
if($isGeoIPDefined)
  return;
$isGeoIPDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

//
// protected class variable
//

class GeoIP extends FormField {
  //
  // public methods
  //

  function recursive_array_search($needle,$haystack) {
      $result = array();
      foreach($haystack as $key=>$value) {
          $current_key=$key;
          if($needle===$value OR (is_array($value) && recursive_array_search($needle,$value) !== false)) {
              $result[] = $current_key;
          }
      }
      return $result;
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
    $this->Description = $description;
  }

  // Returns the current label-description
  function getDescription() {
    if (!isset($this->Description)) {
      $this->Description = "";
    }
    return $this->Description;
  }

  function toHtml($style = "") {
    $page =& $this->getPage();
    $i18n =& $page->getI18n();
    $form =& $page->getForm();
    $formId = $form->getId();
    $id = $this->getId();

    // GeoIP 'Continent' list:
    $geoip_continents = array(
        "EU",
        "NA",
        "SA",
        "AS",
        "OC",
        "AF",
        "AN",
        "XX"
      );

    // GeoIP 'Country' => 'Continent' association:
    // (Taken from: http://dev.maxmind.com/geoip/legacy/codes/country_continent/ )
    $geoip_database = array(
        "A1" => "XX",
        "A2" => "XX",
        "AD" => "EU",
        "AE" => "AS",
        "AF" => "AS",
        "AG" => "NA",
        "AI" => "NA",
        "AL" => "EU",
        "AM" => "AS",
        "AN" => "NA",
        "AO" => "AF",
        "AP" => "AS",
        "AQ" => "AN",
        "AR" => "SA",
        "AS" => "OC",
        "AT" => "EU",
        "AU" => "OC",
        "AW" => "NA",
        "AX" => "EU",
        "AZ" => "AS",
        "BA" => "EU",
        "BB" => "NA",
        "BD" => "AS",
        "BE" => "EU",
        "BF" => "AF",
        "BG" => "EU",
        "BH" => "AS",
        "BI" => "AF",
        "BJ" => "AF",
        "BL" => "NA",
        "BM" => "NA",
        "BN" => "AS",
        "BO" => "SA",
        "BR" => "SA",
        "BS" => "NA",
        "BT" => "AS",
        "BV" => "AN",
        "BW" => "AF",
        "BY" => "EU",
        "BZ" => "NA",
        "CA" => "NA",
        "CC" => "AS",
        "CD" => "AF",
        "CF" => "AF",
        "CG" => "AF",
        "CH" => "EU",
        "CI" => "AF",
        "CK" => "OC",
        "CL" => "SA",
        "CM" => "AF",
        "CN" => "AS",
        "CO" => "SA",
        "CR" => "NA",
        "CU" => "NA",
        "CV" => "AF",
        "CX" => "AS",
        "CY" => "AS",
        "CZ" => "EU",
        "DE" => "EU",
        "DJ" => "AF",
        "DK" => "EU",
        "DM" => "NA",
        "DO" => "NA",
        "DZ" => "AF",
        "EC" => "SA",
        "EE" => "EU",
        "EG" => "AF",
        "EH" => "AF",
        "ER" => "AF",
        "ES" => "EU",
        "ET" => "AF",
        "EU" => "EU",
        "FI" => "EU",
        "FJ" => "OC",
        "FK" => "SA",
        "FM" => "OC",
        "FO" => "EU",
        "FR" => "EU",
        "FX" => "EU",
        "GA" => "AF",
        "GB" => "EU",
        "GD" => "NA",
        "GE" => "AS",
        "GF" => "SA",
        "GG" => "EU",
        "GH" => "AF",
        "GI" => "EU",
        "GL" => "NA",
        "GM" => "AF",
        "GN" => "AF",
        "GP" => "NA",
        "GQ" => "AF",
        "GR" => "EU",
        "GS" => "AN",
        "GT" => "NA",
        "GU" => "OC",
        "GW" => "AF",
        "GY" => "SA",
        "HK" => "AS",
        "HM" => "AN",
        "HN" => "NA",
        "HR" => "EU",
        "HT" => "NA",
        "HU" => "EU",
        "ID" => "AS",
        "IE" => "EU",
        "IL" => "AS",
        "IM" => "EU",
        "IN" => "AS",
        "IO" => "AS",
        "IQ" => "AS",
        "IR" => "AS",
        "IS" => "EU",
        "IT" => "EU",
        "JE" => "EU",
        "JM" => "NA",
        "JO" => "AS",
        "JP" => "AS",
        "KE" => "AF",
        "KG" => "AS",
        "KH" => "AS",
        "KI" => "OC",
        "KM" => "AF",
        "KN" => "NA",
        "KP" => "AS",
        "KR" => "AS",
        "KW" => "AS",
        "KY" => "NA",
        "KZ" => "AS",
        "LA" => "AS",
        "LB" => "AS",
        "LC" => "NA",
        "LI" => "EU",
        "LK" => "AS",
        "LR" => "AF",
        "LS" => "AF",
        "LT" => "EU",
        "LU" => "EU",
        "LV" => "EU",
        "LY" => "AF",
        "MA" => "AF",
        "MC" => "EU",
        "MD" => "EU",
        "ME" => "EU",
        "MF" => "NA",
        "MG" => "AF",
        "MH" => "OC",
        "MK" => "EU",
        "ML" => "AF",
        "MM" => "AS",
        "MN" => "AS",
        "MO" => "AS",
        "MP" => "OC",
        "MQ" => "NA",
        "MR" => "AF",
        "MS" => "NA",
        "MT" => "EU",
        "MU" => "AF",
        "MV" => "AS",
        "MW" => "AF",
        "MX" => "NA",
        "MY" => "AS",
        "MZ" => "AF",
        "NA" => "AF",
        "NC" => "OC",
        "NE" => "AF",
        "NF" => "OC",
        "NG" => "AF",
        "NI" => "NA",
        "NL" => "EU",
        "NO" => "EU",
        "NP" => "AS",
        "NR" => "OC",
        "NU" => "OC",
        "NZ" => "OC",
        "O1" => "XX",
        "OM" => "AS",
        "PA" => "NA",
        "PE" => "SA",
        "PF" => "OC",
        "PG" => "OC",
        "PH" => "AS",
        "PK" => "AS",
        "PL" => "EU",
        "PM" => "NA",
        "PN" => "OC",
        "PR" => "NA",
        "PS" => "AS",
        "PT" => "EU",
        "PW" => "OC",
        "PY" => "SA",
        "QA" => "AS",
        "RE" => "AF",
        "RO" => "EU",
        "RS" => "EU",
        "RU" => "EU",
        "RW" => "AF",
        "SA" => "AS",
        "SB" => "OC",
        "SC" => "AF",
        "SD" => "AF",
        "SE" => "EU",
        "SG" => "AS",
        "SH" => "AF",
        "SI" => "EU",
        "SJ" => "EU",
        "SK" => "EU",
        "SL" => "AF",
        "SM" => "EU",
        "SN" => "AF",
        "SO" => "AF",
        "SR" => "SA",
        "ST" => "AF",
        "SV" => "NA",
        "SY" => "AS",
        "SZ" => "AF",
        "TC" => "NA",
        "TD" => "AF",
        "TF" => "AN",
        "TG" => "AF",
        "TH" => "AS",
        "TJ" => "AS",
        "TK" => "OC",
        "TL" => "AS",
        "TM" => "AS",
        "TN" => "AF",
        "TO" => "OC",
        "TR" => "EU",
        "TT" => "NA",
        "TV" => "OC",
        "TW" => "AS",
        "TZ" => "AF",
        "UA" => "EU",
        "UG" => "AF",
        "UM" => "OC",
        "US" => "NA",
        "UY" => "SA",
        "UZ" => "AS",
        "VA" => "EU",
        "VC" => "NA",
        "VE" => "SA",
        "VG" => "NA",
        "VI" => "NA",
        "VN" => "AS",
        "VU" => "OC",
        "WF" => "OC",
        "WS" => "OC",
        "YE" => "AS",
        "YT" => "AF",
        "ZA" => "AF",
        "ZM" => "AF",
        "ZW" => "AF"
    );

    // Check Class BXPage to see if we have a label and description for this FormField:
    if (is_array($this->page->getLabel($id))) {
      foreach ($this->page->getLabel($id) as $label => $description) {
        // We do? Tell FormFieldBuilder about it:
        $this->setCurrentLabel($label);
        $this->setDescription($description);
      }
    }
    else {
      // We have no label for this FormField:
      $this->setCurrentLabel("");
      $this->setDescription("");
    }
    $label = $this->getCurrentLabel();
    $description = $this->getDescription();

    $selected_countries = scalar_to_array($this->getValue());

    // Assemble an array with all countries per region:
    $centerpiece = '';
    $irrational_countries = array("AM" , "PM");
    foreach ($geoip_continents as $cont) {
      // Get all countries and sort them by regions:
      $regional_country_list[$cont] = GeoIP::recursive_array_search($cont, $geoip_database);

      // Start the region table:
      $centerpiece .= '
        <h2 class="box_head">' . $i18n->getWrapped($cont, "palette") . '</h2>
        <table class="static">
          <tbody>
            <tr>
        ' . "\n";

      // Walk through all regions and process the countries:
      $num = '0';
      foreach ($regional_country_list[$cont] as $numCounttry => $country) {

        // Label and Description for the countries:
        if (!in_array($country, $irrational_countries)) {
          $country_label_desc = $i18n->getWrapped(strtolower($country), "palette");
        }
        else {
          $country_label_desc = $i18n->getWrapped($country, "palette"); 
        }
        $country_label = '<label for="' . $country . '" title="' . $country_label_desc . '" class="tooltip hover uniform">' . $country . '</label>';

        // Is the checkbox ticked?
        $is_checked = '';
        if (in_array($country, $selected_countries)) {
          $is_checked = ' CHECKED';
        }

        // Create checkbox <TD>:
        $centerpiece .= '                <td>' . $country_label . '<br><input type="checkbox" name="' . $id . '[]" class="mcb-' . $id . '" id="' . $id . '-' . $cont . '-' . $country . '" value="' . $country . '"' . $is_checked . '/></td>' . "\n";

        // After 16 checkboxes insert a new row:
        $num++;
        if ($num > '15') {
          $centerpiece .= '            </tr>' . "\n" . '            <tr>' . "\n";
          $num = '0';
        }
      }

      // Finalize the region table:
      $centerpiece .= '
            </tr>
          </tbody>
        </table>
        ' . "\n";
    }

    // Insert the regional tables into the fieldset:
    $out_html = '
      <fieldset class="label_side">
              <label for="' . $id . '" title="' . $i18n->getWrapped($description) . '" class="tooltip right uniform">' . $i18n->get($label) . '</label>
              <div>' . $centerpiece . '
              </div>
      </fieldset>
    ' . "\n";

    // Out with all of it:
    return $out_html;
  }
}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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