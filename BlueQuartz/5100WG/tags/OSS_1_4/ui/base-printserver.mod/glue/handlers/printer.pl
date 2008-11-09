#!/usr/bin/perl -w

use lib '/usr/sausalito/perl';
use CCE;
use Sauce::Util;
use Sauce::Service;
use strict;

my $cce = new CCE;
$cce->connectfd();

my $old = $cce->event_old(); 
my $obj = $cce->event_object();
my $oid = $cce->event_oid();
my $sys = $cce->find("System");
my ($ok, $ps) = $cce->get($sys, "PrintServer");

my @oids = $cce->find("Printer", {name=>$obj->{name}});

if(scalar(@oids)-1){
	$cce->warn("[[base-printserver.printerNameExists,name=\"$obj->{name}\"]]");
	$cce->bye("FAIL");
	exit(1);
}

my $printcap_config = "/etc/printcap";
my $appleshare_config = "/etc/atalk/papd.conf";

#write printcap
Sauce::Util::replaceblock(
                $printcap_config,
                "# start printer: $oid.  DO NOT EDIT",
                printcap_printer($obj),
                "# stop printer: $oid.  DO NOT EDIT",
                0644
);

#write appleshare
Sauce::Util::replaceblock(
                $appleshare_config,
                "# start printer: $oid.  DO NOT EDIT",
                appleshare_printer($obj),
                "# stop printer: $oid.  DO NOT EDIT",
                0644
);

#restart lpd, samba and papd
if($ps->{enable}){
	system("/usr/sbin/checkpc -f");
	service_run_init("lpd", "restart", "nobg");
	if(exists $old->{name} 
	  && $old->{name} ne $obj->{name}
	  && $obj->{suspended}){
		system("/usr/sbin/lpc -P$obj->{name} stop");

	}
	if($ps->{"samba"}){
		service_run_init("smb", "restart");
	}
	if($ps->{"appleshare"}){
		service_run_init("atalk", "restart");
	}
}else{
	system("/usr/sbin/checkpc -f");
}

$cce->bye("SUCCESS");
exit(0);

sub appleshare_printer{
	my $obj = shift;
	my $ppd = $obj->{PPD} || ".ppd";

	my $str = << "_END_";
$obj->{name}:\\
	:pr=|/usr/bin/lpr -P$obj->{name}:\\
	:op=admin:\\
	:pd=$ppd:
_END_
	return $str;

}

sub printcap_printer{
	my $obj = shift;
	my $location="";
	my $str;

	if($obj->{location} eq "network"){
$location = << "END";
        :rp=$obj->{spool}
        :rm=$obj->{hostname}
END
        }else{
$location = << "END";
        :lp=/dev/usb/lp0
END
 
        }

$str = << "_END_";
$obj->{name}:
$location
        :sd=/var/spool/lpd/$obj->{OID}
	:sh
 
_END_
	return $str;
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
