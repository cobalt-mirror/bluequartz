// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: textArea.js 201 2003-07-18 19:11:07Z will $

//
// public functions
//

function textArea_getEntries(element) {
  var value = element.value;

  // replace carriage returns and newlines by commas
  var replaced = "";
  var isComma = false;
  for(var i = 0; i < value.length; i++) {
    var ch = value.charAt(i);

    if(ch == "\n" || ch == "\r") {
      // make sure commas don't stick together
      if(!isComma)
	replaced += ",";
      isComma = true;
    }
    else {
      replaced += ch;
      isComma = false;
    }
  }
  value = replaced;

  // remove trailing comma
  if(value.charAt(value.length-1) == ",")
    value = value.substring(0, value.length-1);

  // return empty array if there are no entries
  if(value.length == 0)
    return new Array();

  return value.split(",");
}

function textArea_reformat(element) {
  var text = element.value;
  var newText = "";

  // break the lines
  var delimiters = ", \r\n\t";
  var lastCh = ",";
  for(var i = 0; i < text.length; i++) {
    var ch = text.charAt(i);

    // if ch is not a delimiter
    if(delimiters.indexOf(ch) == -1)
        newText += ch;
    else
      // if last ch is not a delimiter
      if(delimiters.indexOf(lastCh) == -1)
	// change to line break
        newText += "\n";

    lastCh = ch;
  }

  element.value = newText;
}
