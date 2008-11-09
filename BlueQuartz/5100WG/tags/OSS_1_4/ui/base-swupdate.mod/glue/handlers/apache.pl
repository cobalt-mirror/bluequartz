#!/usr/bin/perl -w -I/usr/sausalito/perl

# Copyright 2000, Cobalt Networks.  All rights reserved.
# $Id: apache.pl 3 2003-07-17 15:19:15Z will $
# author: mchan@cobalt.com


use CCE;
use Sauce::Util;
use Sauce::Service;

my $apache_conf = "/etc/admserv/conf/httpd.conf";
my $apache_init = "/etc/rc.d/init.d/admserv";

my $cce = new CCE();
$cce->connectfd();

my $obj = $cce->event_object();
my $old = $cce->event_old();

if ((! (-e $apache_conf) || (! (-e $apache_init)))) {
  $cce->warn("[[base-swupdate.apacheNotInstalled]]");
}

# Edit httpd.conf here
Sauce::Util::editfile($apache_conf, \&update_apache_conf, 
		      $obj->{location}, $old->{location}) 
	and $cce->warn("[[base-swupdate.errorWritingConfFile]]");

Sauce::Service::service_run_init('admserv', 'reload');
$cce->bye("SUCCESS");
exit 0;

#
# update_apache_conf()
# 
# function for editing httpd.conf
sub update_apache_conf {
  my ($fin, $fout, $location, $old) = @_;
  
  $location =~ /^http:\/\/(.*)/;
  my $domain = $1;
  my $inrequests;
  
  while (<$fin>) {
    print $fout $_;
    if (/^ProxyRequests/) {
      $inrequests = 1;
      last;
    }
  }

  print $fout "ProxyPass /proxyURL/$domain $location\n" if $domain;
  # print everything else out. wipe out old stuff.
  while (<$fin>) {
	next if (/^ProxyPass/ and /$old$/);
        print $fout $_;
  }
  return 0;
}
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
