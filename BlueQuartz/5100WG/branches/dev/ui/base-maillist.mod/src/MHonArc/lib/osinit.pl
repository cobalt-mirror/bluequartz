##---------------------------------------------------------------------------##
##  File:
##	@(#) osinit.pl 2.4 99/07/13 00:46:45
##  Author:
##      Earl Hood       mhonarc@pobox.com
##  Description:
##	A library for setting up a script based upon the OS the script
##	is running under.  The main routine defined is OSinit.  See
##	the routine for specific information.
##---------------------------------------------------------------------------##
##    MHonArc -- Internet mail-to-HTML converter
##    Copyright (C) 1995-1999	Earl Hood, mhonarc@pobox.com
##
##    This program is free software; you can redistribute it and/or modify
##    it under the terms of the GNU General Public License as published by
##    the Free Software Foundation; either version 2 of the License, or
##    (at your option) any later version.
##
##    This program is distributed in the hope that it will be useful,
##    but WITHOUT ANY WARRANTY; without even the implied warranty of
##    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
##    GNU General Public License for more details.
##
##    You should have received a copy of the GNU General Public License
##    along with this program; if not, write to the Free Software
##    Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
##    02111-1307, USA
##---------------------------------------------------------------------------##

package mhonarc;

##---------------------------------------------------------------------------##
##	OSinit() checks what operating system we are running on set
##	some global variables that can be used by the calling routine.
##	All global variables are exported to package main.
##
##	Variables set:
##
##	    $MSDOS	=> Set to 1 if running under MS-DOS/Windows
##	    $MACOS	=> Set to 1 if running under Mac
##	    $UNIX	=> Set to 1 if running under Unix
##	    $VMS	=> Set to 1 if running under VMS
##	    $DIRSEP	=> Directory separator character
##	    $DIRSEPREX	=> Directory separator character for use in
##			   regular expressions.
##	    $PATHSEP	=> Recommend path list separator
##	    $CURDIR	=> Current working directory
##	    $PROG	=> Program name with leading pathname component
##			   stripped off.
##
##	If running under a Mac and the script is a droplet, command-line
##	options will be prompted for unless $noOptions argument is
##	set to true.
##
sub OSinit {
    my($noOptions) = shift;

    ##  Check what system we are executing under
    my($tmp);
    if ($^O =~ /vms/i) {
        $MSDOS = 0;  $MACOS = 0;  $UNIX = 0;  $VMS = 1;
	$DIRSEP = '/';  $CURDIR = '.';
	$PATHSEP = ':';

    } elsif (($^O !~ /cygwin/i) &&
    	     (($^O =~ /mswin/i) ||
	      ($^O =~ /\bdos\b/i) ||
    	      (($tmp = $ENV{'COMSPEC'}) &&
	       ($tmp =~ /^[a-zA-Z]:\\/) &&
	       (-e $tmp))) ) {
        $MSDOS = 1;  $MACOS = 0;  $UNIX = 0;  $VMS = 0;
	$DIRSEP = '\\';  $CURDIR = '.';
	$PATHSEP = ';';

    } elsif (defined($MacPerl::Version)) {
        $MSDOS = 0;  $MACOS = 1;  $UNIX = 0;  $VMS = 0;
	$DIRSEP = ':';  $CURDIR = ':';
	$PATHSEP = ';';

    } else {
        $MSDOS = 0;  $MACOS = 0;  $UNIX = 1;  $VMS = 0;
	$DIRSEP = '/';  $CURDIR = '.';
	$PATHSEP = ':';
    }

    ##	Store name of program
    if ($MSDOS || $WINDOWS) {
        $DIRSEPREX = "\\\\\\/";
    } else {
        ($DIRSEPREX = $DIRSEP) =~ s/(\W)/\\$1/g;
    }
    ($PROG = $0) =~ s%.*[$DIRSEPREX]%%o;

    ##	Ask for command-line options if script is a Mac droplet
    ##		Code taken from the MacPerl FAQ
    if (!$noOptions &&
	defined($MacPerl::Version) &&
	( $MacPerl::Version =~ /Application$/ )) {

	# we're running from the app
	local( $cmdLine, @args );
	$cmdLine = &MacPerl::Ask( "Enter command line options:" );
	require "shellwords.pl";
	@args = &shellwords( $cmdLine );
	unshift( @ARGV, @args );
    }
}

##---------------------------------------------------------------------------##
##      OSis_absolute_path() returns true if a string is an absolute path
##
sub OSis_absolute_path {
 
    if ($MSDOS || $WINDOWS) {
        return $_[0] =~ /^([a-z]:)?[\\\/]/i;
    }
    if ($MACOS) {               ## Not sure about Mac
        return $_[0] =~ /^:/o;
    }
    $_[0] =~ m|^/|o;            ## Unix (fallback)
}

##---------------------------------------------------------------------------##
1;
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
