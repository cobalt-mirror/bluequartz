#!/usr/bin/perl -w
 
use lib '/usr/sausalito/perl';
use Sauce::Service;
use Sauce::Util;
use CCE;
use strict;

my $cce = new CCE (Namespace=>"PrintServer");
$cce->connectfd();

############
# Define some useful stuff...
#############
my $obj = $cce->event_object();
my $old = $cce->event_old();
my $sys = $cce->find("System");
my ($ok, $samba) = $cce->get($sys, "WinShare");
my ($ok2, $ashare) = $cce->get($sys, "AppleShare");
 
my $func = sub {
        my($in,$out,$searchfor,$replacewith)=@_;
        while(<$in>){
                s/$searchfor/$replacewith/i;
                print $out $_;
        }
	return 1;
};

########
# Toggle LPD on or off
#########
service_toggle_init("lpd", $obj->{enable});

#######
# Appleshare stuff
########
if(changed("appleshare") || changed("enable")){
	if($obj->{enable} && $obj->{appleshare}){
		#service_run_init("atalk", "stop");
		`/etc/rc.d/init.d/atalk stop`;
		#sleep(5);
		Sauce::Util::editfile(
			"/etc/atalk/netatalk.conf",
			$func,
			"PAPD_RUN\s*=\s*no",
			"PAPD_RUN=yes"
		);
       		if($ashare->{enabled}){
	       		service_run_init("atalk", "start");
			#`/etc/rc.d/init.d/atalk start &`;
        	}else{
			$cce->set($sys, "AppleShare", {enabled=>1});
		}
	}elsif(!$obj->{enable} || !$obj->{appleshare}){
		service_run_init("atalk", "stop", "nobg") if $ashare->{enabled};
		sleep(5);
		Sauce::Util::editfile(
			"/etc/atalk/netatalk.conf",
			$func,
			"PAPD_RUN\s*=\s*yes",
			"PAPD_RUN=no"
		);
		service_run_init("atalk", "start") if $ashare->{enabled};
	}
}

#########
# Samba stuff
##########
my $a=0;
if($obj->{samba} && $obj->{enable}){
	$a=1;
}
my $str = <<"END";
[printers]
        available = $a
        path = /var/spool/samba
        print ok = yes
        printing = lprng
        guest ok = no
        printcap name = /etc/printcap
        print command =      /usr/bin/lpr  -P\%p -r \%s
        lpq command   =      /usr/bin/lpq  -P\%p
        lprm command  =      /usr/bin/lprm -P\%p \%j
        lppause command =    /usr/sbin/lpc hold \%p \%j
        lpresume command =   /usr/sbin/lpc release \%p \%j
        queuepause command = /usr/sbin/lpc  -P\%p stop
        queueresume command = /usr/sbin/lpc -P\%p start
END

if(changed("samba") || changed("enable")){
	Sauce::Util::replaceblock(
		"/etc/samba/smb.conf",
		";;start printers section.  DO NOT EDIT THIS LINE",
		$str,
		";;end printers section.  DO NOT EDIT THIS LINE",
		0644
	);


	if($obj->{enable} && $obj->{samba}){
		if($samba->{enabled}){
			service_run_init("smb", "restart");
		}else{
			$cce->set($sys, "WinShare", {enabled=>1});
		}
	}else{
		if($samba->{enabled}){
                	service_run_init("smb", "restart");
	        }
	}
}

#done
$cce->bye("SUCCESS");
exit(0);

sub changed{
	my $key = shift;

	return ($old->{$key} && !$obj->{$key}) ||
		($obj->{$key} && !$old->{$key});

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
