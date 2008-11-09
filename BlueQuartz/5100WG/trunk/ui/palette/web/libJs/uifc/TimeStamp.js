// Author: Kevin K. M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: TimeStamp.js 3 2003-07-17 15:19:15Z will $

function TimeStamp_submitHandler(element){
  var year = element.yearElement;
  var month = element.monthElement;
  var day = element.dayElement;
  var hour = element.hourElement;
  var minute = element.minuteElement;
  var amPm = element.ampmElement;

  var yearval = (year == null) ? element.oyearElement.value : year.options[year.selectedIndex].value;
  var monthval = (month == null) ? element.omonthElement.value : month.options[month.selectedIndex].value;
  var dayval = (day == null) ? element.odayElement.value : day.options[day.selectedIndex].value;
  var ampmval = ((amPm == null) || (amPm.options[amPm.selectedIndex].value == 'AM')) ? 0 : 1;
  var hourval = (hour == null) ?  element.ohourElement.value : parseInt(hour.options[hour.selectedIndex].value) + 12*ampmval;
  var minuteval = (minute == null) ? element.ominuteElement.value : minute.options[minute.selectedIndex].value;
  var secondval = ((minuteval == element.ominuteElement.value) && (hourval == element.ohourElement.value)) ? element.osecondElement.value : 0;
  element.value = yearval+':'+monthval+':'+dayval+':'+hourval+':'+minuteval+':'+secondval;
  return true;
}
