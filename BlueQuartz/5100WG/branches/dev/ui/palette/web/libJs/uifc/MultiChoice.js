// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: MultiChoice.js 201 2003-07-18 19:11:07Z will $

function MultiChoice_submitHandler(element) {
  var checkboxes = element.checkboxes;

  // get all the values of checked checkboxes
  var checkboxValues = new Array();
  for(var i = 0; i < checkboxes.length; i++)
    if(checkboxes[i].checked)
      checkboxValues[checkboxValues.length] = checkboxes[i].value;

  // save the value
  element.value = top.code.arrayPacker_arrayToString(checkboxValues);

  return true;
}

function MultiChoice_submitHandlerOption(element) {
  var childFields = element.childFields;

  // only checkbox and radio buttons use this function
  if(!element.checked)
    // if the option is not selected,
    // all form fields associated with that option are optional
    for(var i = 0; i < childFields.length; i++) {
      childFields[i].isOptionalOriginal = childFields[i].isOptional;
      childFields[i].isOptional = true;
    }
  else
    // revert to original if necessary
    for(var i = 0; i < childFields.length; i++)
      if(childFields[i].isOptionalOriginal != null)
	childFields[i].isOptional = childFields[i].isOptionalOriginal;

  return true;
}