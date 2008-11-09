// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: EmailAliasList.js 3 2003-07-17 15:19:15Z will $

//
// public functions
//

function EmailAliasList_changeHandler(element) {
  var textArea = element.textArea;

  var entries = top.code.textArea_getEntries(textArea);
  for(var i = 0; i < entries.length; i++)
    if(!top.code.EmailAlias_isEmailAliasValid(entries[i])) {
      top.code.error_invalidElement(textArea, top.code.string_substitute(element.invalidMessage, "[[VAR.invalidValue]]", entries[i]));
      return false;
    }

  return true;
}

function EmailAlias_isEmailAliasValid(alias) {
	for(var i = 0; i < alias.length; i++) {
		var ch = alias.charAt(i);
	
		// first character cannot be dot
		if (i == 0 && ch == '.')
			return false;
		
		if (!top.code.string_isLowercaseAlpha(ch) &&
			!top.code.string_isNumeric(ch) &&
			ch != '-' && ch != '_' && ch != '.')
			return false;
	}

	return true;
}
