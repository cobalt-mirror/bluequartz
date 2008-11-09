<?
/**
  * Utility Library for File and Related Functions
  *
  * These functions are intended to simplify working with files in the context
  * of CCE sessions.  They are designed to be small and fast and use 
  * procedural code for speed.
  *
  * $Id: file.php,v 1.1.2.1 2001/12/06 02:04:47 jcheng Exp $
  *
  * @author  Kevin K.M. Chiu, Eric Braswell
  * @version $Revision: 1.1.2.1 $
  * @copyright  Copyright 2001 Sun Microsystems, Inc. All Rights Reserved.
  * @access public
  */  

  /**
    * Returns the contents of a file using the unix permissions
    * granted to the current CCE user
    * 
    * @param string $filename  The filename of the file to be opened
    * @return string the contents of the file
    * @access public
    */
  function file_get($filename) {
    $rv = file_shell("/bin/ls -s --block-size=1 $filename", $ls);
    if (!$rv) {
      ereg("^([0-9]+)[[:space:]]", $ls, $regs);
      $size = $regs[1];
      $fh = file_popen("/bin/cat $filename");
      $ret = fread($fh, $size);
    }
    pclose($fh);
    return $ret;
  }
  
  /**
    * Writes the given data into the given file as the currently logged
    * in user.  The current user becomes the owner, 'users' is the group
    * and the permissions are 0600.
    * 
    * @param string $filename  The filename of the file to write
    * @param string $data Data to be written to the file
    * @todo This needs error checking
    * @access public
    */  
  function file_put($filename, $data) {
    $fh = file_popen("/usr/sausalito/sbin/writeFile.pl $filename", "w");
    fwrite($fh, $data);
    pclose($fh);
  }

  // description: allows one to execute a program as
  //   the currently logged in user
  // param: program: A string containing program to execute, including 
  //   path and any arguments
  // param: output variable that picks up the output sent by the program
  // param: the user to run this program as (defaults to the currently
  //   logged in user 
  // returns: 0 an success, errno on error
  function file_shell($cmd, &$output, $runas="") {
    // call ccewrap
    //$cmd = escapeShellCmd($cmd);	
    putenv("CCE_SESSIONID=". $_SESSION_ID);
    putenv("CCE_USERNAME=". $_LOGIN_NAME);
    putenv("CCE_REQUESTUSER=". $runas);
    putenv("PERL_BADLANG=0");

    if ($this->isMonterey) {
      exec("$cmd", $array, $ret);
    } else {
      exec("/usr/sausalito/bin/ccewrap $cmd", $array, $ret);
    }
      
    // prepare return
    while (list($key,$val)=each($array)) 
      $output .= "$val\n";	

    // clean up
    putenv("CCE_SESSIONID=");
    putenv("CCE_USERNAME=");
    putenv("CCE_REQUESTUSER=");

    return $ret;
  }

  // description: allows one to fork a program as
  //   the currently logged in user.  Notice that NO interaction between the 
  //   called program and the caller can be made.
  // param: program: A string containing program to execute, including 
  //   path and any arguments
  // param: the user this program should run as.  Defaults to the currently
  //   logged in user
  // returns: 0 an success, errno on error
  function file_fork($cmd, $runas = "") {
    file_shell("$cmd >/dev/null 2>&1 < /dev/null &", $out, $runas);
  }

  // description: opens a read-only stream wrapped by CCE
  // param: program: A string containing the program to execute, including
  //   the path and any arguments
  // param: mode: The mode to use in this popen
  // returns: a file handle to be read from
  function file_popen($cmd, $mode = "r") {
    putenv("CCE_SESSIONID=". $_SESSION_ID);
    putenv("CCE_USERNAME=". $_LOGIN_NAME);

    if ($this->isMonterey) {
      $handle = popen("$cmd", $mode);
    } else {
      $handle = popen("/usr/sausalito/bin/ccewrap $cmd", $mode);
    }
    putenv("CCE_SESSIONID=");
    putenv("CCE_USERNAME=");
    return $handle;
  }


?>/*
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
