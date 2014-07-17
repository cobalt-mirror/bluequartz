<?php
// Author: Kevin K.M. Chiu
// $Id: TimeStamp.php

global $isTimeStampDefined;
if($isTimeStampDefined)
  return;
$isTimeStampDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class TimeStamp extends FormField {
  //
  // private variables
  //

  var $format;
  var $Label, $Description;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the number of seconds since Epoch
  function TimeStamp(&$page, $id, $value, $i18n) {

    // Set up $i18n:
    $this->i18n = $i18n;

    // superclass constructor
    if (!$page) { print "<hr>TimeStamp.TimeStamp: no page<hr>\n"; }
    $this->FormField($page, $id, $value, $this->i18n, "number", "", "");

    // set defaults
    $this->format = "datetime";
  }

  // description: get the format of the time stamp
  // returns: can be "date", "time" or "datetime"
  // see: setFormat()
  function getFormat() {
    return $this->format;
  }

  // description: set the format of the time stamp
  // param: format: can be "date", "time" or "datetime"
  // see: getFormat()
  function setFormat($format){
    $this->format = strToLower($format);
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

  // override superclass getValue since a TimeStamp accepts a 
  // Unix timestamp as argument, but doesn't return one.
  // It instead returns a colon delimited string 
  // "year:month:day:hour:minute:second"
  // we want get value to return the same as setvalue in a 
  // data preservation state
  function getValue() {
    $time = parent::getValue();
    // Input Format:  Year:Month:Day:Hour:Minute:Second
    if (preg_match('/(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)/', $time, $matches)) {
      //                     Hour         Minute     Second        Month         Day          Year
      $unix_time = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
      return $unix_time;
    } else {
      return $time;
    }
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $value = $this->getValue();

    $access = $this->getAccess();

    $builder = new FormFieldBuilder();

    // We use the TZ as defined in php.ini, which is now set to the system
    // TZ through the GUI and corresponding handlers and constructors: 
    $selectedMonth = date("m", $value);
    $selectedDay = date("d", $value);
    $selectedYear = date("Y", $value);
    $selectedHour = date("H", $value);
    $selectedHour12 = date("h", $value);
    $selectedMinute = date("i", $value);
    $selectedSecond = date("s", $value);
    $selectedAmPm = date("A", $value);

    if ($selectedAmPm == "") { $selectedAmPm = "AM"; }

    // hidden access
    if($access == "") {
      return $builder->makeHiddenField($id, $value);
    }

    $page =& $this->getPage();
    $i18n =& $page->getI18n();
    $format = $this->getFormat();

    // read-only access
    if($access == "r") {
      if($format == "datetime" || $format == "date") {

        // Cheesily strip leading zeroes.  
        $selectedMonth  = preg_replace('/^0/', '', $selectedMonth);

      	// get month string
      	$monthTag = ($selectedMonth < 10) ? "0".($selectedMonth)."month" : ($selectedMonth)."month";
      	$month = $i18n->getHtml($monthTag, "palette");
      }

      if($format == "datetime" || $format == "time") {
      	// get hour string
      	$hour = $i18n->getHtml($selectedHour."00Hour", "palette");

      	// get am/pm string
      	$amPmTag = ($selectedHour < 12) ? "am" : "pm";
      	$amPm = $i18n->getHtml($amPmTag, "palette");
      }

      $result = $builder->makeHiddenField($id, $value);

      if($format == "datetime") {
        $result .= $i18n->get("dateTimeFormat", "palette", array("month" => $month, "date" => $selectedDay+1, "year" => $selectedYear, "24hour" => $selectedHour, "12Hour" => ($selectedHour12 + 0), "minute" => $selectedMinute, "amPm" => $amPm));
      }
      elseif($format == "date") {
        $result .= $i18n->get("dateFormat", "palette", array("month" => $month, "date" => $selectedDay+1, "year" => $selectedYear));
      }
      else {
        $result .= $i18n->get("timeFormat", "palette", array("hour" => $hour, "minute" => $selectedMinute, "amPm" => $amPm));
      }

      if (isset($page->BXLabel[$id])) {
        $LabelArray = array_keys($page->BXLabel[$id]);
        $HtmlLabel = $i18n->getHtml($LabelArray[0]);
        $DescArray = array_values($page->BXLabel[$id]);
        $HtmlDesc = $i18n->getHtml($DescArray[0]);
      }

      $out_html = '

              <fieldset class="label_side top">
                      <label for="' . $id . '" title="' . $HtmlDesc . '" class="tooltip right uniform">' . $HtmlLabel . '<span></span></label>
                      <div>
                          <p>' . $result . '</p>
                      </div>
              </fieldset>
              ';
      return $out_html;

    }

    $form =& $page->getForm();
    $formId = $form->getId();

    // make hidden field
    $result = $builder->makeHiddenField($id, $value);

    // preserve old values 
    $oyearId = "_" . $id . "_oyear";
    $omonthId = "_" . $id . "_omonth";
    $ohourId = "_" . $id . "_ohour";
    $ominuteId = "_" . $id . "_ominute";
    $osecondId= "_" . $id . "_osecond";
    $result .= $builder->makeHiddenField($oyearId, $selectedYear+0);
    $result .= $builder->makeHiddenField($omonthId, $selectedMonth+0);
    $result .= $builder->makeHiddenField($ohourId, $selectedHour+0);
    $result .= $builder->makeHiddenField($ominuteId, $selectedMinute+0);
    $result .= $builder->makeHiddenField($osecondId, $selectedSecond+0);
    
    // take care of date
    if($format == "date" || $format == "datetime" ) {
      // make IDs
      $yearId = "_".$id."_year";
      $monthId = "_".$id."_month";
      $dayId = "_".$id."_day";

      // make month select
      $monthLabels = array();
      $monthValues = array();
      for($i = 0; $i < 12; $i++) {
      	$j = $i + 1; $month = ($j < 10) ? "0".$j : $j;
      	$monthLabels[$i] = $i18n->get($month."month", "palette");
      	$monthValues[$i] = $month;
      }
      $monthField= $builder->makeNakedSelectField($monthId, $access, $this->i18n, 1, 3, false, $formId, "", $monthLabels, $monthValues, array("$selectedMonth"));

      // make date select
      $dayLabels = array();
      $dayValues = array();
      for($i = 1; $i < 32; $i++) {
  	    $j = $i; $day = ($j < 10) ? "0".$j : $j;
  	    $dayLabels[] = $i18n->getHtml("date", "palette", array("date" => $day));
  	    $dayValues[] = $day;
      }
      
      $dayField = $builder->makeNakedSelectField($dayId, $access, $this->i18n, 1, 2, false, $formId, "", $dayLabels, $dayValues,array("$selectedDay"));

      // make year select
      $yearLabels = array();
      $yearValues = array();
      for($i = 2010; $i < 2036; $i++) {
  	    $yearLabels[] = $i18n->getHtml("year","palette",array("year" => $i));
  	    $yearValues[] = $i;
      }
      $yearField= $builder->makeNakedSelectField($yearId, $access, $this->i18n, 1, 4, false, $formId, "", $yearLabels, $yearValues,array($selectedYear));

      //get date property
      $dateProp=$i18n->get("dateFormat","palette",array("year"=>"Y", "month"=>"M", "date"=>"D"));

      $dateAry = preg_split('//',$dateProp, -1, PREG_SPLIT_NO_EMPTY);

    	for($i=0;$i<count($dateAry);$i++){
    		switch($dateAry[$i]){
    			case "Y":
    				$result.=$yearField;
    				break;
    			case "M":
    				$result.=$monthField;
    				break;
    			case "D":
    				$result.=$dayField;
    				break;
    			default:
    				$result.=$dateAry[$i];
    				break;
    		}
    	}
    }

    // take care of time
    if($format == "time" || $format == "datetime") {
      // make IDs
      $hourId = "_".$id."_hour";  
      $minuteId = "_".$id."_minute";
      $amPmId= "_".$id."_amPm";

      // make hour select
      $hourLabels = array();
      $hourValues = array();
      for($i = 0; $i < 24; $i++) {
      	$hour = ($i < 10) ? (!$i?"00":"0".$i) : $i;
        //	$amPmTag = ($i < 12) ? "am" : "pm";
      	$hourLabels[$i] = $i18n->getHtml("hour", "palette", array("hour" => $i18n->getHtml($hour."00Hour", "palette")));
      	$hourValues[$i] = $i;
      }
      $hour24Field = $builder->makeNakedSelectField($hourId, $access, $this->i18n, 1, 2, false, $formId, "", $hourLabels,$hourValues,array($selectedHour+0)); 

      $hourLabels=array();
      $hourValues=array();

      for($i = 0; $i < 12; $i++){
        $hour=($i<10) ? "0".$i:$i;
        $hourLabels[$i]=$i18n->getHtml("hour","palette", array("hour"=>$i18n->getHtml($hour."00Hour","palette")));
        $hourValues[$i]=$i;
      }
      $hour12Field = $builder->makeNakedSelectField($hourId, $access, $this->i18n, 1, 2, false, $formId,"",$hourLabels,$hourValues,array($selectedHour12+0));

      // make minute select
      $minuteLabels = array();
      $minuteValues = array();
      for($i = 0; $i < 60; $i++) {
      	$minute = ($i < 10) ? (!$i?"00":"0".$i) : $i;
      	$minuteLabels[] = $i18n->getHtml("minute","palette",array("minute" => $minute));
      	$minuteValues[] = $minute;
      }
      $minuteField = $builder->makeNakedSelectField($minuteId, $access, $this->i18n, 1, 2, false, $formId, "", $minuteLabels, $minuteValues,array($selectedMinute+0));

    	//make AM/PM select
    	$amPmLabels = array($i18n->get("am","palette"),$i18n->get("pm","palette"));
    	$amPmValues = array("AM","PM");

    	$amPmField = $builder->makeNakedSelectField($amPmId, $access, $this->i18n, 1, 2, false,$formId,"",$amPmLabels,$amPmValues,array($selectedAmPm));

    	//get date property
    	$timeProp=$i18n->get("timeFormat","palette",array("hour"=>"H", "minute"=>"M","amPm"=>"A"));

    	$timeAry = preg_split('//', $timeProp, -1, PREG_SPLIT_NO_EMPTY);

    	$amPmBool=0;

    	if(preg_match("/A/",$timeProp)){
    		$amPmBool=1;
    	}

    	if ($format == 'datetime') {
    		$result .= '<BR>';
    	}
    	for($i=0;$i<count($timeAry);$i++){
    		switch($timeAry[$i]){
    			case "H":
    				$result.=($amPmBool)?$hour12Field : $hour24Field;
    				break;
    			case "M":
    				$result.=$minuteField;
    				break;
    			case "A":
    				$result.=$amPmField;
    				break;
    			default:
    				$result.=$timeAry[$i];
    				break;
    		}
    	}
    }

    if (isset($page->BXLabel[$id])) {
      $LabelArray = array_keys($page->BXLabel[$id]);
      $HtmlLabel = $i18n->getHtml($LabelArray[0]);
      $DescArray = array_values($page->BXLabel[$id]);
      $HtmlDesc = $i18n->getHtml($DescArray[0]);
    }

    if (!isset($HtmlLabel)) {
      $HtmlLabel = $this->getCurrentLabel();
    }

    if (!isset($HtmlDesc)) {
      $HtmlDesc = $this->getDescription();
    }

    $out_html = '

            <fieldset class="label_side top">
                    <label for="' . $id . '" title="' . $HtmlDesc . '" class="tooltip right uniform">' . $HtmlLabel . '<span></span></label>
                    <div>
                        ' . $result . '
                    </div>
            </fieldset>
            ';
    return $out_html;

  }

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