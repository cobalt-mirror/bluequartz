##---------------------------------------------------------------------------##
##  File:
##      @(#) mhsingle.pl 1.5 99/08/04 23:39:52
##  Author:
##      Earl Hood       mhonarc@pobox.com
##  Description:
##      Routines for converting a single message to HTML
##---------------------------------------------------------------------------##
##    MHonArc -- Internet mail-to-HTML converter
##    Copyright (C) 1995-1999   Earl Hood, mhonarc@pobox.com
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

##---------------------------------------------------------------------------
##	Routine to perform conversion of a single mail message to
##	HTML.
##
sub single {
    local($mhead,$index,$from,$date,$sub,$header,$handle,$mesg,
	  $template,$filename,%fields);

    ## Prevent any verbose output
    $QUIET = 1;

    ## See where input is coming from
    if ($ARGV[0]) {
	($handle = &file_open($ARGV[0])) ||
	    die("ERROR: Unable to open $ARGV[0]\n");
	$filename = $ARGV[0];
    } else {
	$handle = $MhaStdin;
    }

    ## Read header
    ($index,$from,$date,$sub,$header) =
	&read_mail_header($handle, *mhead, *fields);

    ($From{$index},$Date{$index},$Subject{$index}) = ($from,$date,$sub);
    $MsgHead{$index} = $mhead;

    ## Read rest of message
    $Message{$index} = &read_mail_body($handle, $index, $header, *fields);

    ## Set index list structures for replace_li_var()
    @MListOrder = &sort_messages();
    %Index2MLoc = ();
    @Index2MLoc{@MListOrder} = (0 .. $#MListOrder);

    ## Output mail
    if ($DoArchive) {
	&output_mail($index, 1, 0);
    }

    close($handle)  unless -t $handle;
}

##---------------------------------------------------------------------------
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
