// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: select.js 3 2003-07-17 15:19:15Z will $

// description:
// This is a library for Select objects

//
// public functions
//

function select_addOption(element, emptyLabel, parentDocument, text, value, defaultSelected, selected) {
  if(value == "" || value == null)
    return;

  var options = element.options;

  // clean up
  if(select_getLength(element, emptyLabel) == 0)
    options.length = 0;

  // use createElement if available because IE 5.X on Windows does not allow
  // options created outside the document of the select element to be added
  // to it
  // cannot just do parentDocument.createElement == null to see if this
  // function is available because IE4 barfs
  var name = navigator.appName;
  var version = navigator.appVersion;

  // if not IE 5.X or 6.X on windows
  if(	name.indexOf("Microsoft") == -1 ||
	version.indexOf("Win") == -1 &&
	(version.indexOf("MSIE 5") == -1 || version.indexOf("MSIE 6") == -1)
  ) {
    options[options.length] = new Option(text, value, defaultSelected, selected)
  } else {
    var option = parentDocument.createElement("OPTION");
    option.text = text;
    option.value = value;
    option.defaultSelected = defaultSelected;
    option.selected = selected;
    options[options.length] = option;
  }
}

function select_getLength(element, emptyLabel) {
  if(element.options.length == 0)
    return 0;

  if(element.options.length == 1
    && element.options[0].text == emptyLabel)
    return 0;

  return element.length;
}

function select_countSelected(element, emptyLabel) {
  if(select_getLength(element, emptyLabel) == 0)
    return 0;

  var options = element.options;
  var count = 0;
  for(var i = 0; i < options.length; i++)
    if(options[i].selected)
      count++;

  return count;
}

function select_removeOption(element, emptyLabel, parentDocument, index) {
  // do nothing if out of range
  if(index < 0 || index >= select_getLength(element, emptyLabel))
    return;

  element.options[index] = null;

  if(select_getLength(element, emptyLabel) == 0) {
    select_addOption(element, emptyLabel, parentDocument, emptyLabel, emptyLabel, false, false);
    element.selectedIndex = -1;
  }
}
