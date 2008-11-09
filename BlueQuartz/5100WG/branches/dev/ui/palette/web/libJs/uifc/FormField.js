// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: FormField.js 201 2003-07-18 19:11:07Z will $

//
// public functions
//

function FormField_textFieldSubmitHandler(element) {
  if(!element.isOptional && element.value == "") {
    top.code.error_invalidElement(element, element.emptyMessage);
    return false;
  }

  return true;
}

function FormField_textListSubmitHandler(element) {
  var textArea = element.textArea;

  // format element 1st
  top.code.textArea_reformat(textArea);

  var textAreaValue = textArea.value;

  if(!element.isOptional && textAreaValue == "") {
    top.code.error_invalidElement(textArea, element.emptyMessage);
    return false;
  }

  var entries = top.code.textArea_getEntries(textArea);
  element.value = top.code.arrayPacker_arrayToString(entries);

  return true;
}
