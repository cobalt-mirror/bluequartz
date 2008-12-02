#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: etcresolv.pl,v 1.4 2001/07/14 05:38:42 mpashniak Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# updates /etc/resolve
#
# depends on:
#		System.dns  (formated as a :-delimited list of DNS servers)
#		System.domainname

use strict;
use Sauce::Config;
use Sauce::Util;
use CCE;

my $cce = new CCE;
$cce->connectfd();

# get system and network object ids:
my ($system_oid) = $cce->find("System");

# get system object:
my ($ok, $obj) = $cce->get($system_oid);
if (!$ok) { 
	# FIXME: fail
}

# get list of dns servers
my @dns = CCE->scalar_to_array($obj->{dns});
my $dom = $obj->{domainname};

# target file
my $fileName = '/etc/resolv.conf';

my $etchosts = <<EOT ;
# /etc/resolv.conf
# Auto-generated file.  Keep your customizations at the bottom of this file.
EOT
my $dns;
foreach $dns (@dns) {
	$etchosts .= "nameserver $dns\n";
};
$etchosts .= <<EOT ;
search $dom
domain $dom
#END of auto-generated code.  Customize beneath this line.
EOT

# update file
{
  my $fn = sub {
    my ($fin, $fout) = (shift,shift);
    my ($text) = (shift);
    print $fout $text;
    my $flag = 0;
    while (defined($_ = <$fin>)) {
    	if ($flag) { print $fout $_; }
    	else { if (m/^#END/) { $flag = 1; } }
    }
    return 1;
  };
  Sauce::Util::editfile($fileName, $fn, $etchosts );
};

# always make sure permission is right
Sauce::Util::chmodfile(0644, $fileName);

$cce->bye('SUCCESS');
exit(0);

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
