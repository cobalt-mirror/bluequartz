##---------------------------------------------------------------------------##
##  File:
##	@(#) mhmsgextbody.pl 1.1 99/09/28 23:17:50
##  Author:
##      Earl Hood       mhonarc@pobox.com
##  Description:
##	Library defines routine to filter message/external-body parts to
##	HTML for MHonArc.
##	Filter routine can be registered with the following:
##          <MIMEFILTERS>
##          message/external-body;m2h_msg_extbody::filter;mhmsgextbody.pl
##          </MIMEFILTERS>
##---------------------------------------------------------------------------##
##    MHonArc -- Internet mail-to-HTML converter
##    Copyright (C) 1999	Earl Hood, mhonarc@pobox.com
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

package m2h_msg_extbody;

##---------------------------------------------------------------------------##
##	message/external-body filter for MHonArc.
##	The following filter arguments are recognized ($args):
##
##	local-file	Support local-file access-type.  This option
##			is best used for internal local mail archives
##			where it is known that readers will have
##			direct access to the file.
##
sub filter {
    local($header, *fields, *data, $isdecode, $args) = @_;

    # grab content-type
    my $ctype = (split(/$readmail::FieldSep/o, $fields{'content-type'}))[0];
    return ''  unless $ctype =~ /\S/;

    # parse argument string
    my $b_lfile = $args =~ /\blocal-file\b/i;

    my $ret = "";
    my $parms = readmail::MAILparse_parameter_str($ctype, 1);
    my $access_type = lc $parms->{'access-type'}{'value'};
       $access_type =~ s/\s//g;
    my $cdesc = $fields{'content-description'} || "";

    local(%dfields, %dfl2o);
    $data =~ s/\A\s+//;
    my $dheader = readmail::MAILread_header(*data, *dfields, *dfl2o);
    my $dctype  = $dfields{'content-type'} || "";
    my $dcte 	= $dfields{'content-transfer-encoding'} || "";
    my $dmd5 	= $dfields{'content-md5'} || "";
    my $size 	= $parms->{'size'}{'value'} || "";
    my $perms 	= $parms->{'permission'}{'value'} || "";
    my $expires	= $parms->{'expiration'}{'value'} || "";
    my $name	= $parms->{'name'}{'value'} || "";

    ATYPE: {
	## FTP, TFTP, ANON-FTP
	if ( $access_type eq 'ftp' ||
	     $access_type eq 'anon-ftp' ||
	     $access_type eq 'tftp' ) {
	    my $site 	 = $parms->{'site'}{'value'};
	    my $dir 	 = $parms->{'directory'}{'value'} || "";
	       $dir	 = '/'.$dir  unless $dir =~ m|^/| || $dir eq "";
	    my $mode 	 = $parms->{'mode'}{'value'} || "";
	    my $proto	 = $access_type eq 'tftp' ? 'tftp' : 'ftp';
	    my $url	 = "$proto://" .
			   mhonarc::urlize($site) .
			   $dir . '/' .
			   mhonarc::urlize($name);
	    $ret	 = '<dl><dt>';
	    $ret	.= qq|<a href="$url">$cdesc</a><br>\n|
			    if $cdesc;
	    $ret	.= qq|<a href="$url">&lt;$url&gt;</a></dt><dd>\n|;
	    $ret	.= qq|Content-type: <tt>$dctype</tt><br>\n|
			    if $dctype;
	    $ret	.= qq|MD5: <tt>$dmd5</tt><br>\n|
			    if $dmd5;
	    $ret	.= qq|Size: $size bytes<br>\n|
			    if $size;
	    $ret	.= qq|Transfer-mode: <tt>$mode</tt><br>\n|
			    if $mode;
	    $ret	.= qq|Username/password may be required.<br>\n|
			    if $access_type eq 'ftp';
	    $ret	.= "</dd></dl>\n";
	    last ATYPE;
	}

	## Local file
	if ($access_type eq 'local-file') {
	    last ATYPE  unless $b_lfile;
	    my $site 	 = $parms->{'site'}{'value'} || "";
	    my $url	 = mhonarc::urlize("file://$name");
	    $ret	 = '<dl><dt>';
	    $ret	.= qq|<a href="$url">$cdesc</a><br>\n|  if $cdesc;
	    $ret	.= qq|<a href="$url">&lt;$url&gt;</a></dt><dd>\n|;
	    $ret	.= qq|Content-type: <tt>$dctype</tt><br>\n|
			    if $dctype;
	    $ret	.= qq|MD5: <tt>$dmd5</tt><br>\n|
			    if $dmd5;
	    $ret	.= qq|Size: $size bytes<br>\n|  	if $size;
	    $ret	.= qq|File accessible from the following domain: | .
			   qq|$site<br>\n|  if $site;
	    $ret	.= "</dd></dl>\n";
	    last ATYPE;
	}

	## Mail server
	if ($access_type eq 'mail-server') {
	    # not supported
	    last ATYPE;
	}

	## URL
	if ($access_type eq 'url') {
	    my $url 	 = $parms->{'url'}{'value'};
	       $url =~ s/\s+//g;
	    $ret	 = '<dl><dt>';
	    $ret	.= qq|<a href="$url">$cdesc</a><br>\n|  if $cdesc;
	    $ret	.= qq|<a href="$url">&lt;$url&gt;</a></dt><dd>\n|;
	    $ret	.= qq|Content-type: <tt>$dctype</tt><br>\n|
			    if $dctype;
	    $ret	.= qq|MD5: <tt>$dmd5</tt><br>\n|
			    if $dmd5;
	    $ret	.= qq|Size: $size bytes<br>\n|  	if $size;
	    $ret	.= "</dd></dl>\n";
	    last ATYPE;
	}

	last ATYPE;
    }

    ($ret);
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
