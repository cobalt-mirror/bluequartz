// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Number.js 3 2003-07-17 15:19:15Z will $

function Number_submitHandler(element) {
  element.value = _Number_convertSeparators(element.value, element.decimalSeparator, element.internalDecimalSeparator, element.thousandsSeparator);
  return true;
}

function Number_changeHandler(element) {
  var value = element.value;

  value = _Number_convertSeparators(value, element.decimalSeparator, element.internalDecimalSeparator, element.thousandsSeparator);

  if(!Number_isNumberValid(value, element.min, element.max)) {
    top.code.error_invalidElement(element, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", element.value));
    return false;
  }

  return true;
}

function Number_isNumberValid(number, min, max) {
  if(number.length == 0)
    return true;

  var value = parseFloat(number);
  if(value.toString() != number)
    return false;

  if(value < min)
    return false;

  if(value > max)
    return false;

  return true;
}

//
// private functions
//

// description: convert decimal and thousands separators for i18n
// param: number: the number to convert
// param: decimalSeparator: decimal separator to convert from
// param: internalDecimalSeparator: decimal separator to convert to
// param: thousandsSeparator: thousands separator to remove
// returns: a converted number string
function _Number_convertSeparators(number, decimalSeparator, internalDecimalSeparator, thousandsSeparator) {
  // strip all the thousands separators
  number = string_substitute(number, thousandsSeparator, "");

  // convert decimal separators into internal representation
  number = string_substitute(number, decimalSeparator, internalDecimalSeparator);

  return number;
}