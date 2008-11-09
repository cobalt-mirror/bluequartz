<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: TimeStamp.php 259 2004-01-03 06:28:40Z shibuya $

global $isTimeStampDefined;
if($isTimeStampDefined)
  return;
$isTimeStampDefined = true;

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class TimeStamp extends FormField {
  //
  // private variables
  //

  var $format;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the number of seconds since Epoch
  function TimeStamp(&$page, $id, $value) {
    // superclass constructor
    if (!$page) { print "<hr>TimeStamp.TimeStamp: no page<hr>\n"; }
    $this->FormField($page, $id, $value);

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


  // override superclass getValue since a TimeStamp accepts a 
  // Unix timestamp as argument, but doesn't return one.
  // It instead returns a colon delimited string 
  // "year:month:day:hour:minute:second"
  // we want get value to return the same as setvalue in a 
  // data preservation state
  function getValue() {
    $time = parent::getValue();
    if (preg_match('/(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)/',
		   $time, $matches)) {
      $unix_time = mktime($matches[4], $matches[5], $matches[6],
			  $matches[2], $matches[3], $matches[1]);
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

    // get selected values
    $selectedMonth = date("m", $value)-1;
    $selectedDay = date("d", $value)-1;
    $selectedYear = date("Y", $value);
    $selectedHour = date("H", $value);
    $selectedHour12 = date("h", $value);
    $selectedMinute = date("i", $value);
    $selectedSecond = date("s", $value);
    $selectedAmPm = (date("A", $value)=="AM")? 0 : 1;

    // hidden access
    if($access == "") 
      return $builder->makeHiddenField($id, $value);

    $page =& $this->getPage();
    $i18n =& $page->getI18n();
    $format = $this->getFormat();

    // read-only access
    if($access == "r") {
      if($format == "datetime" || $format == "date") {
	// get month string
	$monthTag = ($selectedMonth < 9) ? "0".($selectedMonth+1)."month" : ($selectedMonth+1)."month";
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

      if($format == "datetime")
	$result .= $i18n->getHtml("dateTimeFormat", "palette", array("month" => $month, "date" => $selectedDay+1, "year" => $selectedYear, "24hour" => $selectedHour, "12Hour" => ($selectedHour12 + 0), "minute" => $selectedMinute, "amPm" => $amPm));
      else if($format == "date")
	$result .= $i18n->getHtml("dateFormat", "palette", array("month" => $month, "date" => $selectedDay+1, "year" => $selectedYear));
      else
	$result .= $i18n->getHtml("timeFormat", "palette", array("hour" => $hour, "minute" => $selectedMinute, "amPm" => $amPm));

      return $result;
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
    $result .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
element.oyearElement = document.$formId.$oyearId;
element.omonthElement = document.$formId.$omonthId;
element.ohourElement = document.$formId.$ohourId;
element.ominuteElement = document.$formId.$ominuteId;
element.osecondElement = document.$formId.$osecondId;
</SCRIPT>";
    
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
	$monthLabels[$i] = $i18n->getHtml($month."month", "palette");
	$monthValues[$i] = $month;
      }
      $monthField= $builder->makeSelectField($monthId, $access, 1, 3, false, $formId, "", $monthLabels, $monthValues, array("$selectedMonth"));

      // make date select
      $dayLabels = array();
      $dayValues = array();
      for($i = 1; $i < 32; $i++) 
      {
	    $j = $i; $day = ($j < 10) ? "0".$j : $j;
	    $dayLabels[] = $i18n->getHtml("date", "palette", array("date" => $day));
	    $dayValues[] = $day;
      }
      
      $dayField = $builder->makeSelectField($dayId, $access, 1, 2, false, $formId, "", $dayLabels, $dayValues,array("$selectedDay"));

      // make year select
      $yearLabels = array();
      $yearValues = array();
      for($i = 2000; $i < 2026; $i++)
      {
	    $yearLabels[] = $i18n->getHtml("year","palette",array("year" => $i));
	    $yearValues[] = $i;
      }
      $yearField= $builder->makeSelectField($yearId, $access, 1, 4, false, $formId, "", $yearLabels, $yearValues,array($selectedYear-2000));

	//get date property
	$dateProp=$i18n->get("dateFormat","palette",array("year"=>"Y", "month"=>"M", "date"=>"D"));

	$dateAry = preg_split('//',$dateProp, -1, PREG_SPLIT_NO_EMPTY);
	$jscript="";

	for($i=0;$i<count($dateAry);$i++){
		switch($dateAry[$i]){
			case "Y":
				$result.=$yearField;
				$jscript.="element.yearElement = document.$formId.$yearId;";
				break;
			case "M":
				$result.=$monthField;
				$jscript.="element.monthElement = document.$formId.$monthId;";
				break;
			case "D":
				$result.=$dayField;
				$jscript.="element.dayElement = document.$formId.$dayId;";
				break;
			default:
				$result.=$dateAry[$i];
				break;
		}
	}

      $result .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
$jscript
</SCRIPT>";
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
      $hour24Field = $builder->makeSelectField($hourId, $access, 1, 2, false, $formId, "", $hourLabels,$hourValues,array($selectedHour+0)); 

     $hourLabels=array();
     $hourValues=array();

     for($i = 0; $i < 12; $i++){
	$hour=($i<10) ? "0".$i:$i;
	$hourLabels[$i]=$i18n->getHtml("hour","palette", array("hour"=>$i18n->getHtml($hour."00Hour","palette")));
	$hourValues[$i]=$i;
     }
     $hour12Field = $builder->makeSelectField($hourId, $access, 1, 2, false, $formId,"",$hourLabels,$hourValues,array($selectedHour12+0));


      // make minute select
      $minuteLabels = array();
      $minuteValues = array();
      for($i = 0; $i < 60; $i++) {
	$minute = ($i < 10) ? (!$i?"00":"0".$i) : $i;
	$minuteLabels[] = $i18n->getHtml("minute","palette",array("minute" => $minute));
	$minuteValues[] = $minute;
      }
      $minuteField = $builder->makeSelectField($minuteId, $access, 1, 2, false, $formId, "", $minuteLabels, $minuteValues,array($selectedMinute+0));

	//make AM/PM select
	$amPmLabels = array($i18n->get("am","palette"),$i18n->get("pm","palette"));
	$amPmValues = array("AM","PM");


	$amPmField = $builder->makeSelectField($amPmId, $access, 1, 2, false,$formId,"",$amPmLabels,$amPmValues,array($selectedAmPm));

	//get date property
	$timeProp=$i18n->get("timeFormat","palette",array("hour"=>"H", "minute"=>"M","amPm"=>"A"));

	$timeAry = preg_split('//', $timeProp, -1, PREG_SPLIT_NO_EMPTY);
	$jscript="";

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
				$jscript.="element.hourElement = document.$formId.$hourId;";
				break;
			case "M":
				$result.=$minuteField;
				$jscript.="element.minuteElement = document.$formId.$minuteId;";
				break;
			case "A":
				$result.=$amPmField;
				$jscript.="element.ampmElement = document.$formId.$amPmId;";
				break;
			default:
				$result.=$timeAry[$i];
				break;
		}
	}


      $result .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
$jscript
</SCRIPT>";
    }

    $result .= $builder->makeJavaScript($this, "", "top.code.TimeStamp_submitHandler");

    // avoid wrapping
    return "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\"><TR><TD NOWRAP>$result</TD></TR></TABLE>";
  }

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
