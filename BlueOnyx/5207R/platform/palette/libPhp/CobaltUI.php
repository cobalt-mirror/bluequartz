<?php
// Author: Harris Vaegan-Lloyd.
// $Id: CobaltUI.php 828 2006-07-19 16:20:24Z shibuya $
//
// This class puts a layer above the rest of UIFC/CceClient and I18n to
// ease UI page development as much as possible.
//
// The assumption is that each CCE object will have page devoted to it. And
// each attribute will have one form item.

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");
include_once("uifc/Label.php");

class CobaltUI 
{
    // Objects we need to do our job.
    var $Cce;
    var $I18n;
    var $I18nPallette;
    var $Domain;
    var $Stylist;
    var $Helper;

    // Information on what we are currently doing.
    // Unfortunately do to underlying restriction of UIFC only one object
    // per page, (Or one form depepnding on how you look at it);
    var $Page; // Our current page.
    var $Block; // Out current block.
    var $Blocks; // Out blocks stack.
    var $CurrentView; // name of current page of PagedBlock object.
    var $Scripts;  // scriptlets to embed in the page

    var $Action; // What the user has requested this form to do. Delete, modify
                 // or add.
    var $Target; // Current object we are working on.
    var $Namespace; // Current namespace we are working in.
    var $Data; // Current data.
               // At the beggining of the transaction it is what we are
               // trying to get into cced. After the actuate stage its what
               // we suceeded in getting in.
    var $Errors; // CCE errors accumulated so far.
    var $BadData; // BADDATA messages accumulated thus far.

    var $AfterHeaders; // Html to be placed after headers
    var $Vars; // The Criteria variables used for the find
    public $Language;

    function CobaltUI ($sessionId , $domain = "none") 
    {
        $Helper = new ServerScriptHelper($sessionId);
        $this->Helper =& $Helper;
        $this->Cce =& $Helper->getCceClient();
        $this->Domain = $domain;
        $this->I18n =& $Helper->getI18n($domain, $HTTP_ACCEPT_LANGUGE);
        $this->Language = $HTTP_ACCEPT_LANGUGE;
        if (!$this->I18n) 
        {
            print "<hr><b>ERROR: no i18n object for $domain</b><hr>\n";
        }
      
        $this->I18nPalette =& $Helper->getI18n("palette", $HTTP_ACCEPT_LANGUGE);
        $this->Stylist =& $Helper->getStylist();

        // Init out array variables.
        $this->Data = array();
        $this->Scripts = array();
        $this->Blocks = array();
        $this->Errors = array();
        $this->BadData = array();

        $this->_getUIFC("Page");
        $this->Page = new Page($this->Stylist, $this->I18nPalette, getenv("REQUEST_URI"));
        $this->CurrentView = false;
    }

    // Here is where we tell the form what it lives for.
    // Action...
    //   AAS  = Will modify if passed an OID and create if passed a string.
    //          ( Add And Set ). This makes it possible to write one page for
    //          adding and modifying objects. Remember to set approriate
    //          attributes read only for modifying in you form.
    //   SET = Modifying it''s target.
    //   ADD = Creating a new object.
    //   DEL = Deleting it''s target.
    //   REF = Refresh the display of the page (ie. switch views)
    //
    // If using AAS the StartPage the method would be..
    // $Ui->..("AAS", ($HTTP_POST_VARS["_OID"]||"Class"),"Ns");
    //
    // Target...
    //   <classname> = Some randomn classname, just operate on the firt one
    //                 of this class found (e.g. "System")
    //   <oid>       = Operate on this OID.
    //
    // Namespace
    //   <ns> = What namespace to operate in, blank for none.
    //
    // This actually just sets a hidden HTTP variables/
    // Forms which add/delete/modify all at once or some combination
    // can do their work by different submit buttons modifying the value of
    // the hdden variables
    //
    // All linked in with start page because of ordering weirdness.

    function SetAction($vars) 
    {
        return $this->_SetAction($vars);
    }

    function _SetAction($vars) 
    {
        $this->Action = $vars["Action"];
        $this->Target = $vars["Target"];
        $this->Namespace = $vars["Namespace"];
        $this->Parent = $vars["Parent"];
        $this->Debug = $vars["Debug"];
        $this->Vars = ($vars["Vars"] ? $vars["Vars"] : array());
        return true;
    }

    // DEPRECEATED!! Use SetAction instead..
    function StartPage ($action = false, $target = false, $ns = false) 
    {
        if ($action) 
        {
            $this->SetAction(array( "Action" => $action,
                                    "Target" => $target,
                                    "Namespace" => $ns) );
        }
    }

    // Completly generic routine to read in some http vars and make a cced
    // statement out of them.
    function Handle($data  = false) 
    {
        return $this->_Handle($data);
    }

    function _Handle($data = false) 
    {
        global $HTTP_POST_VARS;
        global $HTTP_GET_VARS;
        global $PHP_SELF;

        $post = $HTTP_POST_VARS;
        $get = $HTTP_GET_VARS;

        if  (! $data) 
        {
            if (is_array( $post )) 
            {
                $data = $post;
            } 
            else 
            {
                $data = array();
            }
            if (is_array($get)) 
            {
                $data = array_merge($data, $get);
            }
        }

        // Default to the http variables..
        $Ns = $data["_NAMESPACE"];
        $Target = $data["_TARGET"];
        $Action = $data["_ACTION"];
        $Parent = $data["_PARENT"];

        // Fall back to the specified ones..

        if (count($Ns)) 
        {
            $this->Namespace = $Ns;
        }

        if (count($Target)) 
        {
            $this->Target = $Target;
        }

        if (count($Action)) 
        {
            $this->Action = $Action;
        }

        if (count($Parent)) 
        {
            $this->Parent = $Parent;
        }

        # If we have something to do, try to do it, if it fails keep
        # old data to display back to user. Else grab the new stuff.
        if ($data["_save"]) 
        {
            if ($this->HandleSet($data)) 
            {
                // Now that we're finished we go back to our parent page.
                if ($this->Parent) 
                {
                    $this->Redirect($this->Parent);
                    // no exit code, because the exit code would flash 
                    // in the browser
                    exit;  
                } 
                else 
                {
                    $this->HandleGet();
                }
            } 
            else 
            {
                $this->Data = $data;
            }
        } 
        else 
        {
            $this->HandleGet();
        }
    }

    function HandleGet() 
    {
        return $this->_HandleGet();
    }

    function _HandleGet() 
    {
        $oid = $this->_GetTargetOid();
        if (!$oid) return 0;
        $this->Data = $this->Cce->get($oid, $this->Namespace);
        $this->addErrors($this->Cce->errors());
    }

    function HandleSet($data) 
    {
        return $this->_HandleSet($data);
    }

    function _HandleSet($data) 
    {
        $this->Data = $this->_sanitiseArray($data);

        if (!($this->Target && $this->Action)) 
        {
            return false;
        }

        if ($this->Action == "AAS") 
        {
            switch(gettype($this->Target)) 
            {
                case "integer":
                    $Action = "SET";
                case "string":
                    $Action = "ADD";
            }
        } 
        else 
        {
            $Action = $this->Action;
        }

        if ($Action == "SET") 
        {
            $oid = $this->_GetTargetOid();
            if (! $oid) return false;
            $ret = $this->Cce->set($oid, $this->Namespace, $this->Data);
        } 
        else if ($Action == "ADD") 
        {
            $ret = $this->Cce->create($this->Target, $this->Data);
        } 
        else if ($Action == "DEL") 
        {
            $oid = $this->_GetTargetOid();
            if (! $oid) return false;
            $ret = $this->Cce->destroy($oid);  
        }

        $this->addErrors($this->Cce->errors());
        if(! $ret) 
        {
            return $ret;
        } 
        else 
        {
            // commit seems to not be returning consistent info
            //return $this->Cce->commit();
            return true;
        }
    }

    function addErrors($errors) 
    {
        while ($error = array_pop($errors)) 
        {
            # Pop it on the end of our errors tag.
            $this->Errors[] = $error;
            if ($error->getKey()) 
            {
                $this->BadData[$error->getKey()] = $error;
            }
        }
    }

    /////////////////////////////////////////////////////////
    // Block control
    /////////////////////////////////////////////////////////

    function StartBlock($label = "" , $vars = array()) 
    {
        $this->_getUIFC("PagedBlock");
        $this->Block = new PagedBlock(
                                $this->Page, 
                                "blockid" . count($this->Blocks),
                                new Label($this->Page,
                                    $this->_transTag($label,"html", $vars)
                                )
                        );
        $this->CurrentView = false;
    }
  
    function InvisiBlock() 
    {
        $this->_getUIFC("PagedBlock");
        $this->Block = new PagedBlock(
                            $this->Page, 
                            'blockid99', 
                            '');
    }
  
    function SetBlockView($pagename) 
    {
        $label = new Label(
                    $this->Page,
                    $this->_transTag($pagename, "html"),
                    $this->_transTag($pagename."_help"),"html");
        
        $this->Block->addPage( "_$pagename", $label);
        $this->CurrentView = "_$pagename";
    }

    function EndBlock() 
    {
        array_push($this->Blocks, $this->Block);
        $this->CurrentView = false;
    }

    function AddButtons($parent_url = "") 
    {
        global $PHP_SELF;

        if ($parent_url) 
        {
            $cancel = $parent_url;
        } 
        else if ($this->Parent) 
        {
            $cancel = $this->Parent;
        } 
        else 
        {
            $cancel = $PHP_SELF;
        }

        $this->_getUIFC("SaveButton");
        $this->Block->addButton(
        // first api: $this->Page->getSubmitButton()
        // second api: new AddButton($this->Page, $this->Page->getSubmitAction())
        // third api: when will the madness end?
            new SaveButton($this->Page, $this->Page->getSubmitAction())
        );

        // It seems that cancel buttons are not in fashion.
        // When possible, see AddSaveButton
        $this->_getUIFC("CancelButton");
        $this->Block->addButton(
            new CancelButton($this->Page, $cancel)
        );
    }

    function AddSaveButton($parent_url = "") 
    {
        $this->_getUIFC("SaveButton");
        $this->Block->addButton(
            new SaveButton($this->Page, $this->Page->getSubmitAction())
        );
    }
  
    function AddGenericButton($target_url = "", $text_label = "") 
    {
        $this->_getUIFC("Button");
        $label = new Label(
                    $this->Page,
                    $this->_transTag($text_label,"html"),
                    $this->_transTag($text_label . "_help")
                );

        $this->Block->addButton(
            new Button($this->Page, $target_url, $label)
        );
    }

    function EndPage() 
    {
        print $this->Page->toHeaderHtml();

        print "<INPUT TYPE=\"HIDDEN\" NAME=\"_ACTION\" VALUE=\"".
            $this->Action . "\">\n";
        print "<INPUT TYPE=\"HIDDEN\" NAME=\"_TARGET\" VALUE=\"".
            $this->Target . "\">\n";
        print "<INPUT TYPE=\"HIDDEN\" NAME=\"_NAMESPACE\" VALUE=\"".
            $this->Namespace . "\">\n";
        print "<INPUT TYPE=\"HIDDEN\" NAME=\"_PARENT\" VALUE=\"".
            $this->Parent . "\">\n";

        if ($this->Blocks[0]) 
        {
            // If a PagedBlock object exists, use it to display errors.
            $this->Blocks[0]->processErrors($this->Errors);
        } 
        else 
        {
            // Inform the user of any errors that may have occured.
            print "<SCRIPT LANGUAGE=\"JAVASCRIPT\">\n";
            print $this->Helper->toErrorJavascript($this->Errors);
            print "</SCRIPT>\n";
        }

        print $this->AfterHeaders;
    
        while ($block = array_pop($this->Blocks)) 
        {
            print $block->toHtml("", $this->I18nPalette);
            print '<BR>';
        }
    
        // add embedded scripts
        while($script = array_pop($this->Scripts))
            print $script;

        print $this->Page->toFooterHtml();

        if($this->Debug ) 
        {
            phpinfo();
        }

        $this->Helper->destructor();
    }

    function Redirect($url) 
    {
        print $this->Helper->toHandlerHtml($url, $this->Errors, false);
        $this->Helper->destructor();
    }

    function Divider($labelText) 
    {
        $label = new Label(
                    $this->Page,
                    $this->_transTag($labelText),
                    "","html");
        if (!$this->CurrentView) 
        {
            $this->SetBlockView("_defaultPage");
        } 
        $this->Block->addDivider($label, $this->CurrentView);
    }

    function _ObjAdd(&$obj, $opts, $error = false) 
    {
        if(is_array($opts)) 
        {
            $obj =& $this->_ObjOpts($obj, $opts);
        }
        $i18n =& $this->I18n;
        if (!$i18n) 
        {
            print "<hr><b>_ObjAdd: bad i18n object</b><hr>\n";
        }
        $label = new Label( 
                    $this->Page,
                    $this->_transTag($obj->getId(),"html"),
                    $this->_transTag($obj->getId()."_help"),"html");
    
        if (!$this->CurrentView) 
        {
            $this->SetBlockView("_defaultPage");
        }
    
        if (!$error) 
        {
            $error =& $this->BadData[$obj->getId()];
        }
    
        // FIXME: deliberately breaking error reporting, so that error
        // reporting will be "consistent."  IMO, it's the rest of the UI
        // that should get fixed.
        $error = false;
        // end of FIXME.
    
        $this->Block->addFormField($obj, $label, $this->CurrentView, $error);
    }

    // Why does johnny pass by value not reference ?
    function &_ObjOpts(&$obj, $opts) 
    {
        while(list($key, $val) = each($opts)) 
        {
            $method = "set$key";
            $obj->$method($val);
        }
        return $obj;
    }

    function _sanitiseArray($data) 
    {
        $ret = array();
        $keys = array_keys($data);

        $key = current($keys);

        $kopy = $keys;

        while($key = current($keys)) 
        {
            if (substr($key,0,1) == "_") 
            {
            } 
            else if (($data[$key]=="") &&(in_array("_".$key."_repeat", $kopy))) 
            {
            } 
            else 
            {
                $ret[$key] = $data[$key];
            }
            $key = next($keys);
        }
    
        return $ret;
    }

    function &_standardUIFC($classname, $tag, $opts, $error = false) 
    {
        $this->_getUIFC($classname);
        
        $obj = new $classname(
                    $this->Page,
                    $tag,
                    $this->Data[$tag],
                    $this->_transTag($tag."_invalid","js"),
                    $this->_transTag($tag."_empty","js"));
                    
        $this->_ObjAdd($obj, $opts, $error);
        return $obj;
    }


    // Arguments:
    // tag: both the name of the cgi variable created and of the
    //      cced attribute of the current object to me modified.
    // opts: an optional hash of extra information to include.
    function &UserName($tag, $opts = false) 
    {
        return $this->_standardUIFC("UserName", $tag, $opts);
    }

    function &FullName($tag, $opts = false) 
    {
        return $this->_standardUIFC("FullName", $tag, $opts);
    }

    function &GroupName($tag, $opts = false) 
    {
        return $this->_standardUIFC("GroupName", $tag, $opts);
    }

    function &IpAddress($tag, $opts = false) 
    {
        return $this->_standardUIFC("IpAddress", $tag, $opts);
    }

    function &IpAddressList($tag, $opts = false) 
    {
        return $this->_standardUIFC("IpAddressList", $tag, $opts);
    }

    function &NetAddress($tag, $opts = false) 
    {
        return $this->_standardUIFC("NetAddress", $tag, $opts);
    }
      
    function &NetAddressList($tag, $opts = false) 
    {
        return $this->_standardUIFC("NetAddressList", $tag, $opts);
    }

    function &InetAddress($tag, $opts = false)
    {
        return $this->_standardUIFC("InetAddress", $tag, $opts);
    }

    function &InetAddressList($tag, $opts = false)
    {
        return $this->_standardUIFC("InetAddressList", $tag, $opts);
    }

    function &NetMask($tag, $opts = false)
    {
        return $this->_standardUIFC("NetMask", $tag, $opts);
    }
      
    function &MacAddress($tag, $opts = false) 
    {
        return $this->_standardUIFC("MacAddress", $tag, $opts);
    }

    function &MacAddressList($tag, $opts = false) 
    {
        return $this->_standardUIFC("MacAddressList", $tag, $opts);
    }
      
    function &TextField($tag, $opts = false) 
    {
        return $this->_standardUIFC("TextField", $tag, $opts);
    }

    function &Url($tag, $opts = false) 
    {
        return $this->_standardUIFC("Url", $tag, $opts);
    }

    function &Hidden($tag, $opts = array()) 
    {
        $obj =& $this->_standardUIFC("TextField", $tag, 
                    array_merge($opts, array('Access' => '')));
        return $obj;
    }

    function &TextBlock($tag, $opts = false) 
    {
        return $this->_standardUIFC("TextBlock", $tag, $opts);
    }

    function &Integer($tag, $min = 0, $max = 10000, $opts = false) 
    {
        // object passing problems in php means min and max need to be
        // passed in the opts array
        if(is_array($opts))
            $opts = array_merge($opts, array( 'Min' => $min, 'Max' => $max ));
        else
            $opts = array( 'Min' => $min, 'Max' => $max );

        return $this->_standardUIFC("Integer", $tag, $opts);
    }

    function &DomainName($tag, $opts = false) 
    {
        return $this->_standardUIFC("DomainName", $tag, $opts);
    }

    function &DomainNameList($tag, $opts = false) 
    {
        return $this->_standardUIFC("DomainNameList", $tag, $opts);
    }

    function &BlanketDomainList($tag, $opts = false) 
    {
        return $this->_standardUIFC("BlanketDomainList", $tag, $opts);
    }

    function &Boolean($tag, $opts = false) 
    {
        return $this->_standardUIFC("Boolean", $tag, $opts);
    }

    function &Password($tag, $opts = false) 
    {
        // If we already have a value make it optional.
        if( $this->Data[$tag] ) 
        {
            $opts["Optional"] = true ;
        }
        $obj =& $this->_standardUIFC("Password", $tag, $opts);

        return $obj;
    }

    function &MailListName($tag, $opts = false) 
    {
        return $this->_standardUIFC("MailListName", $tag, $opts);
    }

    function &EmailAddress($tag, $opts = false) 
    {
        return $this->_standardUIFC("EmailAddress", $tag, $opts);
    }

    # ListField - add a widget for adding/removing lists of items
    # arguments:
    #   tag -- tag name
    #   opts -- list of options, yadda yadda.
    function &ListField($tag, $opts = false)
    {
        return $this->_standardUIFC("TextList", $tag, $opts);
    }
      
    function &EmailListField($tag, $opts = false)
    {
        return $this->_standardUIFC("EmailAddressList", $tag, $opts);
    }
      
    # SelectField - a pull-down select list where only one item can
    # be selected at a time.  Drat, I just realized this is
    # redundant with Alters.  Well, I'll leave it for now.
    #   tag - tag name
    #   choices - array of option tags
    #   opts - list of options, yadda yadda.
    # generates these i18n tags:
    #   $tag
    #   $tag-$choice[$i]
    function &SelectField($tag, $choices, $opts = false)
    {
        $data = $this->Data[$tag];

        // Make sure that we have the UIFC classes we need.    
        $this->_getUIFC("MultiChoice");
        $this->_getUIFC("Option");
        # danger: silly code ensues.

        $widget = new MultiChoice($this->Page, $tag);
        $anyselected = false;
        for ($i = 0; $i < count($choices); $i++) 
        {
            $label = new Label(
                        $this->Page,
                        $this->_transTag($tag . "-" . $choices[$i], "html"),
                        "");
            $option = new Option($label, $choices[$i]);
            $widget->addOption($option);
            if ($data == $choices[$i]) 
            {
                $widget->setSelected($i, true);
                $anyselected = true;
            }
        }
        $widget->setValue($data);
        if (!$anyselected) 
        {
            $widget->setSelected(0, true); # select first by default
        }
        # whew!
        
        return $this->_ObjAdd($widget, $opts);
    }
        
    # SetSelectField - add a widget to select between sets
    # arguements:
    #   tag -- tag name
    #   selname -- name of the "selected" column (presently left)
    #   unselname -- name of the "unselected" column (now right)
    #   elements -- array of all elements (selected & unselected)
    function &SetSelectField($tag, $selname, $unselname, $elements, $opts = false) {
        $sel_elems = $this->Data[$tag];
        $unsel_elems = arrayToString($elements);
        $this->_getUIFC("SetSelector");    
        $sel = new SetSelector(
                    $this->Page,
                    $tag,
                    $sel_elems, $unsel_elems,
                    $this->_transTag($tag . "_empty"));

        if ($selname) 
        {
            $sel->setValueLabel(
                new Label(
                    $this->Page,
                    $this->_transTag($selname,"html")));
        }
        if ($unselname) 
        {
            $sel->setEntriesLabel(
                new Label(
                    $this->Page,
                    $this->_transTag($unselname,"html")));
        }

        return $this->_ObjAdd($sel, $opts);
    }

    # Arguments:
    # Tag = Tag name
    # data = An array of data to be used as values.
    # disp = An array of strings to translate for tag labels.
    # opts = Usual opts arg.
    function &Alters ($tag, $data, $labels = false, $opts = false) 
    {
        $i = 0; // Multichoice object sucks. need to keep an index to
                // select options.

        $this->_getUIFC("MultiChoice");
        $this->_getUIFC("Option");
        $obj = new MultiChoice($this->Page, $tag);

        $anyselected = false;
        while ($val =& current($data)) 
        {
            if(is_array($labels)) 
            {
                $label =& current($labels);
                next($labels);
            } 
            else 
            {
                $label =& $val;
            }

            $labelObj = new Label(
                            $this->Page, 
                            $this->_transTag(
                                $label, 
                                "html", 
                                array("opt_val" => $val))
                        );
            $option = new Option($labelObj, $val);
            $obj->addOption($option);
   
            if ($this->Data[$tag] == $val) 
            {
                // Sets the last object to selected.
                $anyselected = true;
                $obj->setSelected($i, true);
            }
            next($data);
            $i++;
        }
        
        if (!$anyselected) 
        {
            $obj->setSelected(0, true);
        }

        $this->_ObjAdd($obj, $opts);
        return $obj;
    }

    function _GetTargetOid() 
    {
        if(is_numeric($this->Target)) 
        {
            // just in case
            settype($this->Target, "integer");
            return $this->Target;
        }

        $oids = $this->Cce->find($this->Target, $this->Vars);
        $this->addErrors($this->Cce->errors());

        // FIXME: There should be a better way to get an error to the
        // user than faking a CCE error. *sigh*
        if($oids[0] < 1 || $oids[0] == undef) 
        {
            array_push($this->Errors,
                 new CceError('',0,'',
                    "[[palette.noInstances,class=".
                    $this->Target . "]]"));
            return 0;
        }

        if(count($oids) > 1) 
        {
            array_push(
                $this->Errors, 
                new CceError('', 0, '',
                    "[[palette.too_many_instances" . $this->Target . "]]"));
            return 0;
        }
        return $oids[0];
    }

    function _transTag($message, $quoting = "js", $vars = array()) 
    {
        while(list($key,$val) = each($vars)) 
        {
            $message = $message .",$key=$val";
        }

        $message = "[[" . $this->Domain . ".$message]]";

        if ($quoting == "js") 
        {
            // $trans = $this->I18n->interpolateJs($message);
            $trans = strtr($this->I18n->interpolate($message), "'", "´");
        } 
        else if ($quoting == "html") 
        {

            // On 5106R our pulldown needs to be trimmed down to just the language
            // identifiers if we're on Japanese:
            $is_legacy = exec("cat /etc/build|grep 5106R|wc -l");
            $encoding = $this->I18nPalette->getProperty("encoding", "palette");
            if (($encoding == "EUC-JP") && ($is_legacy == "1")) {
                $trans = I18n::Utf8Encode($this->I18n->interpolate($message));
            }
            elseif ($is_legacy == "1") {
                $trans = $this->I18n->interpolate($message);
            }
            else {
                $trans = $this->I18n->interpolateHtml($message);
            }
        } 
        else 
        {
            $trans = $this->I18n->interpolate($message);
        }

        return $trans;
    }

    function _getUIFC($class) 
    {
        if(! $this->Included[$class]) 
        {
            include_once("uifc/$class.php");
            $this->Included[$class] = 1;
        }
    }

    // report_errors
    //  $errors -- array of raw error hashes
    //  $mapping -- associative array that maps object property
    //      names to form field names.      
    function report_errors($mapping) 
    {
        $errors = $this->Cce->errors();
        for ($i = 0; $i < count($errors); $i++) 
        {
            $err =& $errors[$i];

            if ($err->getKey() && $mapping[$err->getKey()]) 
            {
                // add internationalized string for field name
                $istr = "[[" . $this->Domain . "." 
                        . $mapping[$err->getKey()] . "]]";
                $err->setVar("field", $istr);
                $err->setVar("key", $istr);
            }
       
            // FIXME: undo these changes!
            /* the old, correct way:
             * if ($err->getKey() && $mapping[$err->getKey()]) 
             * {
             *      $this->BadData[$mapping[$err->getKey()]] = $err;
             * } 
             * else 
             * {
             *      array_push($this->Errors, $err);
             * }
             */
            // the consistent, but incorrect way: (thanks to PR6508)
            array_push($this->Errors, $err);
        }
    }

    /*
     *   Javascript function(s)
     *   Add a script to be embedded in a page generated by CobaltUI
     */
    function AddScript($code, $lang = "JAVASCRIPT") 
    {
        $script = "
<SCRIPT \"$lang\">
$code
</SCRIPT>
";
    
        array_push($this->Scripts, $script);
    }

    /*
     *   Append to html code that is placed after all headers
     *   useful when adding a MultiButton item
     */
    function AppendAfterHeaders($html) 
    {
        $this->AfterHeaders .= $html;
    }
};
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
