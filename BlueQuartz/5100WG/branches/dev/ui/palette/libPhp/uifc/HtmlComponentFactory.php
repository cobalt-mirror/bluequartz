<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: HtmlComponentFactory.php 201 2003-07-18 19:11:07Z will $

// description:
// This class represents a factory that manufactures HtmlComponent objects. It
// is designed to simplify the task of instantiating and initializing these
// objects. This factory handles the most common cases of how the objects are
// instantiated and initialized. This factory may not be able to handle rare
// cases. In these cases, users can instantiate the objects using constructors
// instead or fine tune the manufactured objects with accessor methods.
//
// applicability:
// Most pages that use UIFC can use this factory to manufacture objects. It is
// a handy tool, but it is not required.
//
// usage:
// Simply use the getHtmlComponentFactory() method of the ServerScriptHelper
// class to get a factory object. Alternatively, you can use the constructor to
// instantiate one. The form fields manufactured use <formFieldId>_invalid as
// i18n message ID for the message that says the form field is invalid and
// <formFieldId>_empty as the ID for the message that says the form field is
// empty. If these messages are not defined, default strings are used. These
// strings use <formFieldId> as message ID for the form field name and
// <formFieldId>_rule as message ID for the message that tells the validation
// rule of the form field. Labels manufactured use <labelId> as i18n message ID for
// the label text and <labelId>_help as ID for the description text.

global $isHtmlComponentFactoryDefined;
if($isHtmlComponentFactoryDefined)
  return;
$isHtmlComponentFactoryDefined = true;

class HtmlComponentFactory {
  //
  // private variables
  //

  var $i18n;
  var $page;
  var $errors;

  //
  // public methods
  //

  // description: constructor
  // param: stylist: a Stylist object
  // param: i18n: an I18n object
  // param: formAction: the action for a form in string
  function HtmlComponentFactory($stylist, $i18n, $formAction) {
    $this->i18n = $i18n;
    /* start off with no errors */
    $this->errors = array();
    include_once("uifc/Page.php");
    $this->page = new Page($stylist, $i18n, $formAction);
  }

  function processErrors($errors = array()) {
    $this->errors = $errors;
  }

  function getI18n() {
	return $this->i18n;
  }

  // description: manufacture a AddButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a AddButton object
  function getAddButton($action, $help = false) {
    include_once("uifc/AddButton.php");
    return new AddButton($this->page, $action, $help);
  }

  // description: manufacture a BackButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a BackButton object
  function getBackButton($action) {
    include_once("uifc/BackButton.php");
    return new BackButton($this->page, $action);
  }

  // description: manufacture a Bar object
  // param: id: id of the object
  // param: value: percentage
  // param: label: a label in string. Optional
  // returns: a Bar object
  function getBar($id, $value, $label = "") {
    include_once("uifc/Bar.php");
    $bar = new Bar($this->page, $id, $value);
    $bar->setLabel($label);
    return $bar;
  }

  // description: manufacture a Vertical Bar object
  // param: id: id of the object
  // param: value: percentage
  // param: label: a label in string. Optional
  // returns: a Bar object
  function getVerticalBar($id, $value, $label = "") {
    include_once("uifc/Bar.php");
    $bar = new Bar($this->page, $id, $value);
    $bar->setVertical();
    $bar->setLabel($label);
    return $bar;
  }

  // description: manufacture a Boolean object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a Boolean object
  function getBoolean($id, $value = false, $access = "rw")
  {
        include_once("uifc/Boolean.php");
        $boolean = new Boolean($this->page, $id, $value);
        $boolean->setAccess($access);
        return $boolean;
  }

  // description: manufacture a Button object
  // param: action: the string used within HREF attribute of the A tag
  // param: labelId: a message ID for i18n for the label
  // returns: a Button object
  function getButton($action, $labelId) {
    include_once("uifc/Button.php");
    include_once("uifc/Label.php");
    return new Button($this->page, $action, $this->getLabel($labelId), new Label($this->page, $this->i18n->get($labelId), $this->i18n->get($labelId."_disabledHelp")));
  }

  // description: manufacture a CancelButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a CancelButton object
  function getCancelButton($action) {
    include_once("uifc/CancelButton.php");
    return new CancelButton($this->page, $action);
  }

  // description: manufacture a CompositeFormField object
  // param: formFields: an array of FormField objects. Optional
  // param: delimiter: a string to delimit the form fields. Optional
  // param: access: access of the object. Optional
  // returns: a CompositeFormField object
  function getCompositeFormField($formFields = array(), $delimiter = null, $access = "rw") {
    include_once("uifc/CompositeFormField.php");
    $compositeFormField = new CompositeFormField();

    for($i = 0; $i < count($formFields); $i++)
      $compositeFormField->addFormField($formFields[$i]);

    if($delimiter != null)
      $compositeFormField->setDelimiter($delimiter);

    $compositeFormField->setAccess($access);
    return $compositeFormField;
  }

  // description: manufacture a VerticalCompositeFormField object
  // param: formFields: an array of FormField objects. Optional
  // param: delimiter: a string to delimit the form fields. Optional
  // param: access: access of the object. Optional
  // returns: a VerticalCompositeFormField object
  function getVerticalCompositeFormField($formFields = array(), $delimiter = null, $access = "rw") {
    include_once("uifc/VerticalCompositeFormField.php");
    $vertCompositeFormField = new VerticalCompositeFormField();

    for($i = 0; $i < count($formFields); $i++)
      $vertCompositeFormField->addFormField($formFields[$i]);

    if($delimiter != null)
      $vertCompositeFormField->setDelimiter($delimiter);

    $vertCompositeFormField->setAccess($access);
    return $vertCompositeFormField;
  }

  // description: manufacture an CountryName object
  // param: id: id of the object
  // param: value: selected value of the object
  // param: access: access of the object. Optional
  // returns: an CountryName object
  function getCountryName($id, $value, $access = "rw") {
    include_once("uifc/CountryName.php");
    $country = new CountryName($this->page, $id, $value);
    $country->setAccess($access);
    return $country;
  } 

  // description: manufacture a DetailButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a DetailButton object
  function getDetailButton($action) {
    include_once("uifc/DetailButton.php");
    return new DetailButton($this->page, $action);
  }

  // description: manufacture an ImageButton object
  // param: action: the string used within HREF attribute of the A tag
  // param: image: image for the button
  // param: label: label for alt-text
  // param: description: string for help message
  function getImageButton($action, $image, $label = "", $descr = "") {
    include_once("uifc/ImageButton.php");
    return new ImageButton($this->page, $action, $image, $label, $descr);
  }

  // description: manufacture a DomainName object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a DomainName object
  function getDomainName($id, $value = "", $access = "rw") {
    include_once("uifc/DomainName.php");
    $domainName = new DomainName($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $domainName->setAccess($access);
    return $domainName;
  }

  // description: manufacture an DomainNameList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an DomainNameList object
  function getDomainNameList($id, $value = "", $access = "rw") {
    include_once("uifc/DomainNameList.php");
    $domainNameList = new DomainNameList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $domainNameList->setAccess($access);
    return $domainNameList;
  }

  // description: manufacture an BlanketDomainList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an BlanketDomainList object
  // just like DomainNameList but allows prefixed period chcaracters per-domain
  function getBlanketDomainList($id, $value, $access = "rw") {
    include_once("uifc/BlanketDomainList.php");
    $blanketDomainList = new BlanketDomainList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $blanketDomainList->setAccess($access);
    return $blanketDomainList;
  }

  // description: manufacture an EmailAddress object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an EmailAddress object
  function getEmailAddress($id, $value = "", $access = "rw", $remote = false) {
    include_once("uifc/EmailAddress.php");
    $emailAddress = new EmailAddress($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $emailAddress->setAccess($access);
    $emailAddress->setRemote(false);
    return $emailAddress;
  }

  // description: manufacture an EmailAddressList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an EmailAddressList object
  function getEmailAddressList($id, $value = "", $access = "rw") {
    include_once("uifc/EmailAddressList.php");
    $emailAddressList = new EmailAddressList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $emailAddressList->setAccess($access);
    return $emailAddressList;
  }

  // description: manufacture an EmailAliasList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an EmailAliasList object
  function getEmailAliasList($id, $value = "", $access = "rw") {
    include_once("uifc/EmailAliasList.php");
    $emailAliasList = new EmailAliasList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $emailAliasList->setAccess($access);
    return $emailAliasList;
  }

 // description: manufacture a FileUpload object
 // param: id: id of the object
 // param: value: the path
 // param: maxFileSize: the maximum file size allowed to upload in bytes.
 //     Optional
 // param: access: access of the object. Optional
 // returns: a FileUpload object
 function getFileUpload($id, $value = "", $maxFileSize = "", $access = "rw") {
   include_once("uifc/FileUpload.php");
   $fileUpload = new FileUpload($this->page, $id, $value, $maxFileSize, "", $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
   $fileUpload->setAccess($access);
   return $fileUpload;
 }

 // description: manufacture a MultiFileUpload object
 // param: id: id of the object
 // param: value: the path
 // param: maxFileSize: the maximum file size allowed to upload in bytes.
 //     Optional
 // param: access: access of the object. Optional
 // returns: a MultiFileUpload object
 function getMultiFileUpload($id, $value = "", $maxFileSize = "", $access = "rw") {
   include_once("uifc/MultiFileUpload.php");
   $multiFileUpload = new MultiFileUpload($this->page, $id, $value, $maxFileSize, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
   $multiFileUpload->setAccess($access);
   return $multiFileUpload;
 }

  // description: manufacture a FormFieldList object
  // param: id: id of the object
  // param: value: an ampersand "&" separated list of default values
  // param: formFieldClass: the name of the form field class that form the list
  // param: access: access of the object. Optional
  // returns: a FormFieldList object
  function getFormFieldList($id, $value, $formFieldClass, $access = "rw") {
    include_once("uifc/FormFieldList.php");
    include_once("uifc/$formFieldClass.php");
    $formFieldList = new FormFieldList($this->page, $id, $value, $formFieldClass, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $formFieldList->setAccess($access);
    return $formFieldList;
  }

  // description: manufacture a FullName object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a FullName object
  function getFullName($id, $value = "", $access = "rw") {
    include_once("uifc/FullName.php");
    $fullName = new FullName($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $fullName->setAccess($access);
    return $fullName;
  }

  // description: manufacture a GroupName object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a GroupName object
  function getGroupName($id, $value = "", $access = "rw") {
    include_once("uifc/GroupName.php");
    $groupName = new GroupName($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $groupName->setAccess($access);
    return $groupName;
  }

  // description: manufacture an ImageLabel object
  // param: name: name of the label. It is also the message ID for I18n
  // param: image: an URL of an image
  // param: isDescribed: true if description exists, false otherwise
  //     optional. true by default
  // param: nameI18nVars: a hash of variables for name I18n. optional
  // param: descriptionI18nVars: a hash of variables for description I18n. optional
  // returns: an ImageLabel object
  function getImageLabel($name, $image, $isDescribed = true, $nameI18nVars = array(), $descriptionI18nVars = array()) {
    $description = "";
    if($isDescribed)
      $description = $this->i18n->get($name."_help", "", $descriptionI18nVars);

    include_once("uifc/ImageLabel.php");
    return new ImageLabel($this->page, $image, $this->i18n->get($name, "", $nameI18nVars), $description);
  }

  // description: manufacture an Integer object
  // param: id: id of the object
  // param: value: value of the object
  // param: min: the minimum acceptable value. Optional
  // param: max: the maximum acceptable value. Optional
  // param: access: access of the object. Optional
  // returns: an Integer object
  function getInteger($id, $value = "", $min = "", $max = "", $access = "rw") {
    include_once("uifc/Integer.php");
    $integer = new Integer($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));

    if(!($min === ""))
      $integer->setMin($min);

    if(!($max === ""))
      $integer->setMax($max);

    $integer->setAccess($access);
    return $integer;
  }

  // description: manufacture an IpAddress object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an IpAddress object
  function getIpAddress($id, $value = "", $access = "rw") {
    include_once("uifc/IpAddress.php");
    $ipAddress = new IpAddress($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $ipAddress->setAccess($access);
    return $ipAddress;
  }

  // description: manufacture an IpAddressList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an IpAddressList object
  function getIpAddressList($id, $value, $access = "rw") {
    include_once("uifc/IpAddressList.php");
    $ipAddressList = new IpAddressList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $ipAddressList->setAccess($access);
    return $ipAddressList;
  }

  // description: manufacture a Label object
  // param: name: name of the label. It is also the message ID for I18n
  // param: isDescribed: true if description exists, false otherwise
  //     optional. true by default
  // param: nameI18nVars: a hash of variables for name I18n. optional
  // param: descriptionI18nVars: a hash of variables for description I18n. optional
  // returns: a Label object
  function getLabel($name, $isDescribed = true, $nameI18nVars = array(), $descriptionI18nVars = array()) {
    $description = "";
    if($isDescribed)
      $description = $this->i18n->getJs($name."_help", "", $descriptionI18nVars);

    include_once("uifc/Label.php");
    return new Label($this->page, $this->i18n->get($name, "", $nameI18nVars), $description);
  }

  // description: manufacture an Locale object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an Locale object
  function getLocale($id, $value = "", $access = "rw") {
    include_once("uifc/Locale.php");
    $ipAddress = new Locale($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $ipAddress->setAccess($access);
    return $ipAddress;
  }

  // description: manufacture a MacAddress object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a MacAddress object
  function getMacAddress($id, $value = "", $access = "rw") {
    include_once("uifc/MacAddress.php");
    $macAddress = new MacAddress($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $macAddress->setAccess($access);
    return $macAddress;
  }

  // description: manufacture an MailListName object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an MailListName object
  function getMailListName($id, $value, $access = "rw") {
    include_once("uifc/MailListName.php");
    $mailListName = new MailListName($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $mailListName->setAccess($access);
    return $mailListName;
  }

  // description: manufacture a ModifyButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a ModifyButton object
  function getModifyButton($action) {
    include_once("uifc/ModifyButton.php");
    return new ModifyButton($this->page, $action);
  }

  // description: manufacture a MultiButton object
  // param: textId: i18n ID of the text of the object
  // param: actions: an array of actions
  // param: actionTextIds: an array of i18n ID of the text for the actions
  // returns: a MultiButton object
  function getMultiButton($textId, $actions = array(), $actionTextIds = array()) {
    include_once("uifc/MultiButton.php");
    $multiButton = new MultiButton($this->page, $this->i18n->get($textId));
    for($i = 0; $i < count($actions); $i++)
      $multiButton->addAction($actions[$i], $this->i18n->get($actionTextIds[$i]));
    return $multiButton;
  }

  // description: manufacture a MultiChoice object
  // param: id: id of the object
  // param: optionValues: an array of values of option objects
  // param: selectedValues: an array of selected values
  // param: access: access of the object. Optional
  // returns: a MultiChoice object
  function getMultiChoice($id, $optionValues = array(), $selectedValues = array(), $access = "rw", $sh="top.code.MultiChoice_submitHandlerOption") {
    include_once("uifc/MultiChoice.php");
    $multiChoice = new MultiChoice($this->page, $id, $sh);
    for($i = 0; $i < count($optionValues); $i++)
      $multiChoice->addOption(
	$this->getOption($optionValues[$i], 
	  in_array($optionValues[$i], $selectedValues) )
      );
    $multiChoice->setAccess($access);
    return $multiChoice;
  }

  // description: manufacture a NetAddress object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a NetAddress object
  function getNetAddress($id, $value, $access = "rw") {
    include_once("uifc/NetAddress.php");
    $netAddress = new NetAddress($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $netAddress->setAccess($access);
    return $netAddress;
  }

  // description: manufacture a NetAddressList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a NetAddressList object
  function getNetAddressList($id, $value, $access = "rw") {
    include_once("uifc/NetAddressList.php");
    $netAddressList = new NetAddressList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $netAddressList->setAccess($access);
    return $netAddressList;
  }

  // description: manufacture an Number object
  // param: id: id of the object
  // param: value: value of the object
  // param: min: the minimum acceptable value. Optional
  // param: max: the maximum acceptable value. Optional
  // param: access: access of the object. Optional
  // returns: an Number object
  function getNumber($id, $value = "", $min = "", $max = "", $access = "rw") {
    include_once("uifc/Number.php");
    $number = new Number($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));

    if(!($min === ""))
      $number->setMin($min);

    if(!($max === ""))
      $number->setMax($max);

    $number->setAccess($access);
    return $number;
  }

  // description: manufacture an Option object
  // param: value: value of the object
  // param: isSelected: true if selected, false otherwise. Optional
  // returns: an Option object
  function getOption($value, $isSelected = false) {
    include_once("uifc/Option.php");
    // echo "<li> getOption: $value = $isSelected";
    return new Option($this->getLabel($value, false), $value, $isSelected);
  }

  // description: manufacture a PagedBlock object
  // param: id: id of the object
  // param: pageIds: an array of page IDs. optional
  // returns: a PagedBlock object
  function getPagedBlock($id, $pageIds = "") {
    include_once("uifc/PagedBlock.php");
    $block = new PagedBlock($this->page, $id, $this->getLabel($id, false));

    /* have the pagedBlock process any error fields */
    $block->processErrors($this->errors);

    for($i = 0; $i < count($pageIds); $i++) {
      $pageId = $pageIds[$i];
      $block->addPage($pageId, $this->getLabel($pageId));
    }

    return $block;
  }

  // description: manufacture a Password object
  // param: id: id of the object
  // param: value: value of the object
  // param: isConfirm: true to confirm password, false otherwise
  //     Optional and true by default
  // param: access: access of the object. Optional
  // returns: a Password object
  function getPassword($id, $value = "", $isConfirm = true, $access = "rw") {
    include_once("uifc/Password.php");
    $password = new Password($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $password->setConfirm($isConfirm);
    $password->setAccess($access);
    return $password;
  }

  // description: manufacture a PlainBlock object
  // param: labelId: a message ID for i18n for the label
  // returns: a PlainBlock object
  function getPlainBlock($labelId) {
    include_once("uifc/PlainBlock.php");
    return new PlainBlock($this->page, $this->getLabel($labelId, false));
  }

  function getIntRange($id, $value = "", $access = "rw") {
	include_once("uifc/IntRange.php");
	$pr = new IntRange($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
	$pr->setAccess($access);
	return $pr;
  }

  // description: manufacture a Page object
  // returns: a Page object
  function getPage() {
    return $this->page;
  }

  // description: manufacture a RemoveButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a RemoveButton object
  function getRemoveButton($action) {
    include_once("uifc/RemoveButton.php");
    return new RemoveButton($this->page, $action);
  }

  // description: manufacture a SaveButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a SaveButton object
  function getSaveButton($action) {
    include_once("uifc/SaveButton.php");
    return new SaveButton($this->page, $action);
  }

  // description: manufacture a ScrollList object
  // param: id: the identifier in string
  // param: labelIds: an array of label IDs in string
  // param: sortables: an array of indexes of the sortable components. Optional
  // returns: a ScrollList object
  function getScrollList($id, $labelIds, $sortables = array()) {
    include_once("uifc/ScrollList.php");
    $entryLabels = array();
    for($i = 0; $i < count($labelIds); $i++)
      $entryLabels[] = $this->getLabel($labelIds[$i]);
    $scrolllist = new ScrollList($this->page, $id, $this->getLabel($id, false), $entryLabels, $sortables);
    $scrolllist->processErrors($this->errors);
    return $scrolllist;
  }

  // description: manufacture a SetSelector object
  // param: id: id of the object
  // param: value: an ampersand "&" separated list for the value set
  // param: entries: an ampersand "&" separated list for the entry set
  // param: valueLabelId: a label ID for the value field. Optional
  // param: entriesLabelId: a label ID for the entries field. Optional
  // param: access: access of the object. Optional
  // returns: a SetSelector object
  function getSetSelector($id, $value, $entries, $valueLabelId = "", $entriesLabelId = "", $access = "rw", $valueVals="",$entriesVals="") {
    include_once("uifc/SetSelector.php");
    $setSelector = new SetSelector($this->page, $id, $value, $entries, $this->_getEmptyMessage($id), $valueVals,$entriesVals);

    if($valueLabelId != "")
      $setSelector->setValueLabel($this->getLabel($valueLabelId, false));
    if($entriesLabelId != "")
      $setSelector->setEntriesLabel($this->getLabel($entriesLabelId, false));

    $setSelector->setAccess($access);
    return $setSelector;
  }

  // description: manufacture a SimpleBlock object
  // param: labelId: a message ID for i18n for the label
  // returns: a SimpleBlock object
  function getSimpleBlock($labelId) {
    include_once("uifc/SimpleBlock.php");
    return new SimpleBlock($this->page, $this->getLabel($labelId, false));
  }
  
  // description: manufacture a  SimpleText object (well, duh!)
  // param: text: the text you want to output
  // param: style: a style object
  // returns: a SimpleText object
  function getSimpleText($text, $style = "") {
    include_once("uifc/SimpleText.php");
    return new SimpleText($text, $style);
  }
 
  // description: manufacture a SnmpCommunity object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a SnmpCommunity object
  function getSnmpCommunity($id, $value, $access = "rw") {
    include_once("uifc/SnmpCommunity.php");
    $snmpCommunity = new SnmpCommunity($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $snmpCommunity->setAccess($access);
    return $snmpCommunity;
  }

  // description: manufacture a StatusSignal object
  // param: status: the status string
  // param: url: the url to which to link. Optional
  // returns: a Label object
  function getStatusSignal($status, $url = "") {
    include_once("uifc/StatusSignal.php");
    return new StatusSignal($this->page, $status, $url);
  }

  // description: manufacture a TextBlock object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a TextBlock object
  function getTextBlock($id, $value = "", $access = "rw") {
    include_once("uifc/TextBlock.php");
    $textBlock = new TextBlock($this->page, $id, $value, $this->_getEmptyMessage($id));
    $textBlock->setAccess($access);
    return $textBlock;
  }

  // description: manufacture a TextField object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a TextField object
  function getTextField($id, $value = "", $access = "rw") {
    include_once("uifc/TextField.php");
    $textField = new TextField($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $textField->setAccess($access);
    return $textField;
  }

  // description: manufacture an TextList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a TextList object
  function getTextList($id, $value = "", $access = "rw") {
    include_once("uifc/TextList.php");
    $textList = new TextList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $textList->setAccess($access);
    return $textList;
  }

  // description: manufacture a TimeStamp object
  // param: id: id of the object
  // param: value: value of the object
  // param: format: can be "date","time" or "datetime". Optional
  // param: access: access of the object. Optional
  // returns: a TimeStamp object
  function getTimeStamp($id, $value, $format = "", $access = "rw") {
    include_once("uifc/TimeStamp.php");
    $timeStamp = new TimeStamp($this->page, $id, $value);
    if($format != "")
      $timeStamp->setFormat($format);
    $timeStamp->setAccess($access);
    return $timeStamp;
  }


  // description: manufacture a TimeZone object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: a TimeZone object
  function getTimeZone($id, $value, $access = "rw") {
    include_once("uifc/TimeZone.php");
    $timeZone = new TimeZone($this->page, $id, $value);
    $timeZone->setAccess($access);
    return $timeZone;
  }

  // description: manufacture a UninstallButton object
  // param: action: the string used within HREF attribute of the A tag
  // returns: a UninstallButton object
  function getUninstallButton($action) {
    include_once("uifc/UninstallButton.php");
    return new UninstallButton($this->page, $action);
  }

  // description: manufacture an Url object
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the URL
  // param: label: label of the object. Optional
  //     If not supplied, i18n is used fetch it using id as message ID
  // param: target: target attribute of the A tag. Optional
  // param: access: access of the object. Optional
  // returns: an Url object
  function getUrl($id, $value = "", $label = "", $target = "", $access = "rw") {
    include_once("uifc/Url.php");
    $label = ($label == "") ? $this->i18n->get($id) : $label;
    $url = new Url($this->page, $id, $value, $label, $target, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $url->setAccess($access);
    return $url;
  }

  // description: manufacture an UrlList object
  // param: id: id of the object
  // param: value: value of the object
  // param: labels: an array of label strings. Optional
  // param: targets: an array of TARGET attribute strings for A tags. Optional
  // param: access: access of the object. Optional
  // returns: an UrlList object
  function getUrlList($id, $value, $labels = array(), $targets = array(), $access = "rw") {
    include_once("uifc/UrlList.php");
    $urlList = new UrlList($this->page, $id, $value, $labels, $targets, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $urlList->setAccess($access);
    return $urlList;
  }

  // description: manufacture an UserName object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an UserName object
  function getUserName($id, $value = "", $access = "rw") {
    include_once("uifc/UserName.php");
    $userName = new UserName($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $userName->setAccess($access);
    return $userName;
  }

  // description: manufacture an UserNameList object
  // param: id: id of the object
  // param: value: value of the object
  // param: access: access of the object. Optional
  // returns: an UserNameList object
  function getUserNameList($id, $value, $access = "rw") {
    include_once("uifc/UserNameList.php");
    $userNameList = new UserNameList($this->page, $id, $value, $this->_getInvalidMessage($id), $this->_getEmptyMessage($id));
    $userNameList->setAccess($access);
    return $userNameList;
  }

  //
  // private methods
  //

  function _getEmptyMessage($id) {
    $tag = $id."_empty";
    $message = $this->i18n->get($tag);

    // message not found?
    if($message == $tag) {
      // find rule
      $ruleTag = $id."_rule";
      $rule = $this->i18n->get($ruleTag);
      if($rule == $ruleTag)
	$rule = "";

      $message = $this->i18n->get("defaultEmptyMessage", "palette", array(
	"name" => $this->i18n->get($id),
	"rule" => $rule
      ));
    }

    return $message;
  }

  function _getInvalidMessage($id) {
    $tag = $id."_invalid";
    $message = $this->i18n->getJs($tag);

    // message not found?
    if($message == $tag) {
      // find rule
      $ruleTag = $id."_rule";
      $rule = $this->i18n->get($ruleTag);
      if($rule == $ruleTag)
	$rule = "";

      $message = $this->i18n->get("defaultInvalidMessage", "palette", array(
	"name" => $this->i18n->get($id),
	"rule" => $rule
      ));
    }

    return $message;
  }
}
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>

