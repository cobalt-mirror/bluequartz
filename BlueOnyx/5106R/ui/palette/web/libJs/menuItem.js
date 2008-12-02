// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: menuItem.js 3 2003-07-17 15:19:15Z will $

//
// public functions
//

// constructor for menu item
function mItem_Item(id, name, description, type, url, expanded, visible, window, imageOff, imageOn) {
  this.id = id;
  this.name = name;
  this.description = description;
  this.type = type;
  this.url = url;
  this.expanded = expanded;
  this.visible = visible;
  this.window = window;
  this.imageOff = imageOff;
  this.imageOn = imageOn;
  

  this.parents = new Array();
  this.subItems = new Array();

  this.addItem = _mItem_addItem;
  this.delItem = _mItem_delItem;
  this.getItem = _mItem_getItem;
  this.getItems = _mItem_getItems;
  this.getDescription = _mItem_getDescription;
  this.setDescription = _mItem_setDescription;
  this.isExpanded = _mItem_isExpanded;
  this.setExpanded = _mItem_setExpanded;
  this.getId = _mItem_getId;
  this.getName = _mItem_getName;
  this.setName = _mItem_setName;
  this.addParent = _mItem_addParent;
  this.getParents = _mItem_getParents;
  this.getType = _mItem_getType;
  this.setType = _mItem_setType;
  this.getUrl = _mItem_getUrl;
  this.setUrl = _mItem_setUrl;
  this.isVisible = _mItem_isVisible;
  this.setVisible = _mItem_setVisible;
  this.getWindow = _mItem_getWindow;
  this.setWindow = _mItem_setWindow;
  this.getImageOff = _mItem_getImageOff;
  this.setImageOff = _mItem_setImageOff;
  this.getImageOn = _mItem_getImageOn;
  this.setImageOn = _mItem_setImageOn;
}


//
// private functions
//

// description: get a child
// param: id: the ID of the item in string
// param: isRecursive: find grandchildren level and onwards, too
// returns: a mItem object or null if the child cannot be found
function _mItem_getItem(id, isRecursive) {
  var items = this.getItems();
  for(var i = 0; i < items.length; i++)
    if(items[i].getId() == id)
      return items[i];

  if(isRecursive) {
    for(var i = 0; i < items.length; i++) {
      // recursive call
      var found = items[i].getItem(id, isRecursive);
      if(found != null)
	return found;
    }
  }

  return null;
}

// description: gets all the children
// returns: Array of mItem
function _mItem_getItems() {
  return this.subItems;
}

// description: add a child to the menuItem and add a parent to the child accordingly
// param: item: a mItem object
function _mItem_addItem(item) {
  this.subItems[this.subItems.length] = item;
  item.addParent(this);
}

// description: delete a child from the menuItem
// param: id: the ID of the item in string
// returns: true if success, false otherwise
function _mItem_delItem(id) {
  var found = false;
  var result = new Array();
  var items = this.subItems;

  for(var i = 0; i < items.length; i++)
    if(items[i].getId() != id)
      result[result.length] = items[i];
    else
      found = true;

  this.subItems = result;

  return found;
}

function _mItem_getDescription() {
  return this.description;
}

function _mItem_setDescription(description) {
  this.description = description;
}

function _mItem_isExpanded() {
  return this.expanded;
}

// description: set the expanded flag
// param: expanded: true indicates this item is expanded, false otherwise
function _mItem_setExpanded(expanded) {
  this.expanded = expanded;
}

function _mItem_getId() {
  return this.id;
}

function _mItem_getName() {
  return this.name;
}

function _mItem_setName(name) {
  this.name = name;
}

function _mItem_addParent(parent) {
  this.parents[this.parents.length] = parent;
}

function _mItem_getParents() {
  return this.parents;
}

function _mItem_getType() {
  return this.type;
}

function _mItem_setType(type) {
  this.type = type;
}

function _mItem_getUrl() {
  return this.url;
}

function _mItem_setUrl(url) {
  this.url = url;
}

function _mItem_isVisible() {
  return this.visible;
}

// description: set the visibility
// param: visible: true if visible, false otherwise
// param: isRecursive: true to set visiblity for all members down the
//     hierarchy, false otherwise
function _mItem_setVisible(visible, isRecursive) {
  this.visible = visible;

  if(isRecursive) {
    var children = this.getItems();
    for(var i = 0; i < children.length; i++)
      children[i].setVisible(visible, true);
  }
}

function _mItem_getWindow() {
  return this.window;
}

function _mItem_setWindow(window) {
  this.window = window;
}

function _mItem_getImageOff() {
  return this.imageOff;
}

function _mItem_setImageOff(imageOff) {
  this.imageOff = imageOff;
}

function _mItem_getImageOn() {
  return this.imageOn;
}

function _mItem_setImageOn(imageOn) {
  this.imageOn = imageOn;
}

