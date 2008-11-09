// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: info.js 201 2003-07-18 19:11:07Z will $

//
// private variables
//

var _info_frame = null;
var _info_info = "";
var _info_logInfo = new Array();
var _info_logIndex = -1;
var _info_logMaxLen = 20;
var _info_logMode = new Array();
var _info_style = new Object();
var _info_timeoutId = -1;

//
// public functions
//

function info_setFrame(frame) {
  _info_frame = frame;
}

function info_setStyle(name, value) {
  _info_style[name] = value;
}

// description: show a piece of info in the info area using a certain display mode
// param: info: a string
// param: mode: "error", "help", "status" or "wait"
function info_show(info, mode) {
  
  if(mode != null && mode != "help" && mode != "wait") {
    // log error info
    _info_logInfo[_info_logInfo.length] = info;
    _info_logMode[_info_logMode.length] = mode;
    _info_logIndex = _info_logInfo.length-1;
  }
  else
    _info_logIndex = _info_logInfo.length;

  // clean up
  var firstEntry = _info_logInfo.length-_info_logMaxLen;
  if(firstEntry > 0) {
    _info_logInfo[firstEntry-1] = void 0;
    _info_logMode[firstEntry-1] = void 0;
  }

  _info_showOnFrame(info, mode);
}

// for backwards compatibility with classic style
// this function is deprecated and will be removed in a future release
top.showInfo = top.code.showInfo;

// this function is deprecated and will be removed in a future release
function showInfo( info, period, mode ) {
  if ( mode == null || mode.length == 0 ) {
		mode = "help";
	}
	if ( info == null || info.length == 0 ) {
		top.code.info_clear();
	} else {
		top.code.info_show( info, mode );
	}
}
// end backwards compatibility code

function info_clear() {
  _info_showOnFrame("");
}

function info_lastInfo() {
  _info_logIndex--;
  _info_showOnFrame(_info_logInfo[_info_logIndex], _info_logMode[_info_logIndex]);
}

function info_nextInfo() {
  _info_logIndex++;
  _info_showOnFrame(_info_logInfo[_info_logIndex], _info_logMode[_info_logIndex]);
}

// description: event handler for the onClick event
function info_click() {
  if(_info_timeoutId != -1) {
    clearTimeout(_info_timeoutId);
    _info_timeoutId = -1;
  }
  window.status = "";
  return true;
}

// description: event handler for the onMouseOver event
function info_mouseOver(info) {
  // only show info if mouseOver event was triggered by moving cursor into the
  // activation area
  if(top.code.css_getEvent().type == "mouseup")
    return;

  _info_info = info;
  _info_timeoutId = setTimeout("info_show(_info_info, \"help\");", 666);
  window.status = "";
  return true;
}

// description: event handler for the onMouseOver event
function info_mouseOverError(info) {
  // only show info if mouseOver event was triggered by moving cursor into the
  // activation area
  if(top.code.css_getEvent().type == "mouseup")
    return;

  _info_info = info;
  _info_timeoutId = setTimeout("info_show(_info_info, \"error\");", 666);
  window.status = "";
  return true;
}

// description: event handler for the onMouseOut event
function info_mouseOut() {
  if(_info_timeoutId != -1) {
    clearTimeout(_info_timeoutId);
    _info_timeoutId = -1;
  }
  window.status = "";
  return true;
}

//
// private functions
//

function _info_showOnFrame(info, mode) {
  if(_info_frame == null || _info_frame.document == null)
    return;

  var doc = _info_frame.document;

  // use the right image, style, icons and masks based on mode
     var image = "";
	 var backgroundStyle = "";
	 var textStyle = "";


   if(mode == null) {
      mode = "Help";
      image = "";
   } else {
      U = mode.charAt(0);
      U = U.toUpperCase();
      mode = U + mode.slice(1);
      image = _info_style["typeIcon" + mode];
   }
   
   image = (image != "") ? "<IMG BORDER=\"0\" SRC=\""+image+"\">" : "";

   backgroundStyle = _info_style["backgroundStyle" + mode];
   textStyle = _info_style["textStyle" + mode];

   errorIcon = _info_style["errorIcon" + mode];
   lastIcon = _info_style["lastIcon" + mode];
   lastIconDisabled = _info_style["lastIconDisabled" + mode];
   nextIcon = _info_style["nextIcon" + mode];
   nextIconDisabled = _info_style["nextIconDisabled" + mode];
   revealMask = _info_style["revealMask" + mode];

  // make the buttons
  var lastInfo = "<IMG BORDER=\"0\" SRC=\""+lastIconDisabled+"\">";
  var nextInfo = "<IMG BORDER=\"0\" SRC=\""+nextIconDisabled+"\">";

  var firstEntry = _info_logInfo.length-_info_logMaxLen;
  if(firstEntry < 0)
    firstEntry = 0;

  var isButtonsShown = false;
  if(_info_logIndex > firstEntry) {
    lastInfo = "<A HREF=\"javascript: top.code.info_lastInfo()\"><IMG BORDER=\"0\" SRC=\""+lastIcon+"\"></A>";
    isButtonsShown = true;
  }
  if(_info_logIndex < _info_logInfo.length-1) {
    nextInfo = "<A HREF=\"javascript: top.code.info_nextInfo()\"><IMG BORDER=\"0\" SRC=\""+nextIcon+"\"></A>";
    isButtonsShown = true;
  }

  var buttonCells = isButtonsShown ? "<TD valign=\"top\">"+lastInfo+"</TD><TD valign=\"top\"><IMG SRC=\""+errorIcon+"\"></TD><TD valign=\"top\">"+nextInfo+"</TD>\n" : "";

  // disable animation
  var isAnimation = false;
/*
  // NN 4 on Mac is too slow for animation
  // IE 4 on Mac does not show info at all with DIVs
  var isAnimation = true;
  var name = navigator.appName;
  var version = navigator.appVersion;
  if((name.indexOf("Netscape") != -1 && version.indexOf("4.") != -1 && version.indexOf("Mac") != -1) ||
    (name.indexOf("Microsoft") != -1 && version.indexOf("4.") != -1 && version.indexOf("Mac") != -1))
    isAnimation = false;
*/

  // get info string area information
  // we need to do this before doc.open() because document.body object is
  // cleared up on IE by then on IE 5/WinNT
  var maskWidth = 50;
  var infoLeft = 30;
  var errorAreaWidth = 85;
  infoWidth = top.code.css_getWindowWidth(_info_frame)-infoLeft-errorAreaWidth;

  doc.open();
  doc.write("<HTML><BODY STYLE=\""+backgroundStyle+"\">\n");

  // draw DIVs for animation
  if(isAnimation) {
    doc.write("<DIV ID=\"infoDiv\" STYLE=\"position:absolute; left:"+infoLeft+"; width:"+infoWidth+"; visibility:hidden;\"><FONT STYLE=\""+textStyle+"\">"+info+"</FONT></DIV>\n");

    // hide mask if there is no info
    // we still need the DIV to avoid Javascript error
    var imageHeight = (info == null || info == "") ? "HEIGHT=\"5\"" : "";

    doc.write("<DIV ID=\"maskDiv\" STYLE=\"position:absolute; width:"+maskWidth+"; visibility:hidden;\"><IMG BORDER=\"0\" "+imageHeight+" NAME=\"mask\" SRC=\""+revealMask+"\"></DIV>\n");
  }

  doc.write("<TABLE WIDTH=\"99%\" BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\" VALIGN=\"TOP\">\n");
  doc.write("<TR><TD COLSPAN=\"2\"><IMG SRC=\"/libImage/spaceHolder.gif\" HEIGHT=\"5\" WIDTH=\"10\"></TD></TR>\n");

  var centerPiece = isAnimation ? "" : "<FONT STYLE=\""+textStyle+"\">"+info+"</FONT>";
  doc.write("<TR><TD VALIGN=\"top\">"+image+"</TD><TD WIDTH=\"99%\">"+centerPiece+"</TD>"+buttonCells+"</TR>\n");

  doc.write("</TABLE>\n");

  doc.write("</BODY></HTML>");
  doc.close();

  // animate?
  if(info != "" && isAnimation)
    top.code.css_reveal(doc, "infoDiv", "maskDiv", 40);
}
