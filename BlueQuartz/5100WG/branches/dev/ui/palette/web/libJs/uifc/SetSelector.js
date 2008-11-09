// Author: Jonathan Mayer, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: SetSelector.js 201 2003-07-18 19:11:07Z will $

//
// public functions
//

function SetSelector_submitHandler(element) {
  var valueElement = element.valueElement;

  if(!element.isOptional && top.code.select_getLength(valueElement, element.emptyLabel) == 0) {
    top.code.error_invalidElement(element, element.emptyMessage);
    return false;
  }

  if(top.code.select_getLength(valueElement, element.emptyLabel) ==0) {
    element.value = "";
    return true;
  }

  var values = new Array();
  for(var i = 0; i < valueElement.options.length; i++)
    values[i] = valueElement.options[i].value;

  element.value = top.code.arrayPacker_arrayToString(values);

  return true;
}

function SetSelector_moveItem(element, action) {
  var fromSelect = (action == "add") ? element.entriesElement : element.valueElement;
  var toSelect = (action == "add") ? element.valueElement : element.entriesElement;

  if(top.code.select_getLength(fromSelect, element.emptyLabel) == 0)
    return;

  var fromOptions = fromSelect.options;
  var toOptions = toSelect.options;

  // unselect everything
  toOptions.selectedIndex = -1;

  // copy options
//  for(var i = fromOptions.length-1; i >= 0; i--) {
  for(var i = 0; i < fromOptions.length; i++) {
    var option = fromOptions[i];
    if(option.selected)
      top.code.select_addOption(toSelect, element.emptyLabel, element.parentDocument, option.text, option.value, false, true);
  }

  // remove options
  // from tail to head because the array shrinks
  for(var i = fromOptions.length-1; i >= 0; i--)
    if(fromSelect.options[i].selected)
      top.code.select_removeOption(fromSelect, element.emptyLabel, element.parentDocument, i);
}

function SetSelector_orderItem(select, direction) {
  var options = select.options;

  // nothing to order?
  if(options.length == 0)
    return;

  var isStuck = false;

  var border = (direction == "up") ? 0 : options.length-1;

  for(var i = 0; i < options.length; i++) {
    // walk from beginning or the end?
    var index = (direction == "up") ? i : options.length-i-1;

    if(options[index].selected)
      if(isStuck || index == border)
	// if last option is stuck or option is at the border, this option is stuck
	isStuck = true;
      else {
	var toIndex = (direction == "up") ? index-1 : index+1;

	// move the option
	var tmpText = options[toIndex].text;
	var tmpValue = options[toIndex].value;
	options[toIndex].text = options[index].text;
	options[toIndex].value = options[index].value;
	options[index].text = tmpText;
	options[index].value = tmpValue;

	// select the moved option
	options[index].selected = false;
	options[toIndex].selected = true;

	isStuck = false;
      }
    else
      isStuck = false;
  }
}

function SetSelector_setButtons(element) {
  if(top.code.select_countSelected(element.entriesElement, element.emptyLabel) > 0)
    element.addButton.src = element.addButton.url;
  else
    element.addButton.src = element.addButton.disabledUrl;

  if(top.code.select_countSelected(element.valueElement, element.emptyLabel) > 0) {
    element.removeButton.src = element.removeButton.url;
    if(element.upButton != null && element.valueElement.options.length > 1)
      element.upButton.src = element.upButton.url;
    if(element.downButton != null && element.valueElement.options.length > 1)
      element.downButton.src = element.downButton.url;
  }
  else {
    element.removeButton.src = element.removeButton.disabledUrl;
    if(element.upButton != null)
      element.upButton.src = element.upButton.disabledUrl;
    if(element.downButton != null)
      element.downButton.src = element.downButton.disabledUrl;
  }
}
