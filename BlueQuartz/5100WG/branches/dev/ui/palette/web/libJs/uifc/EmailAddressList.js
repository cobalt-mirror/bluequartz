// Author: Kevin K.M. Chiu, Mike Waychison
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: EmailAddressList.js 201 2003-07-18 19:11:07Z will $

//
// public functions
//

function EmailAddressList_changeHandler(element) {
  var textArea = element.textArea;

  var entries = top.code.textArea_getEntries(textArea);
  for(var i = 0; i < entries.length; i++)
    if(!top.code.EmailAddress_isEmailAddressValid(entries[i])) {
      top.code.error_invalidElement(textArea, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", entries[i]));
      return false;
    }

  return true;
}

function EmailAddressListSingleLine_changeHandler(element) {
  if (element.value != "") {
    var text = element.value;
    var emails = text.split(",");
    var email;
    for (var i=0; i< emails.length;i++) {
      email = top.code.string_trim(emails[i]);

      // use the email address part if the string is in full format
      extracted = top.code.EmailAddress_getAddressFromFull(email);
      if(extracted != "")
	email = extracted;

      if(!top.code.EmailAddress_isEmailAddressValid(email)) {
	// email addy is invalid!
	top.code.error_invalidElement(text, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", emails[i]));
	return false;
      } 
    }
  } 
  return true;
}

function EmailAddressListSingleLine_submitHandler (element) {
  if(!element.isOptional && element.value == "") {
    top.code.error_invalidElement(element, element.emptyMessage);
    return false;
  } else if (element.value != "") {
    var text = element.value;
    var emails = text.split(",");
    for (var i = 0; i<emails.length; i++ )
      emails[i] = top.code.string_trim(emails[i]);
    element.form[element.postField].value = top.code.arrayPacker_arrayToString(emails);   
    
    emails = text.split(",");
    element.form[element.postField.concat("_full")].value = top.code.arrayPacker_arrayToString(emails);  
  } else {
	element.form[element.postField].value = "";
	element.form[element.postField.concat("_full")].value = "";
  }
  return true;
}
