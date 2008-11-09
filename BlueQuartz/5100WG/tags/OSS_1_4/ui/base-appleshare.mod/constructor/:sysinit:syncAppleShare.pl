#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/appleshare

use Sauce::Config;
use Sauce::Util;
use Sauce::Service;
use CCE;
use appleshare;
use I18n;

my $cce = new CCE;
$cce->connectuds();

my $i18n=new I18n;

my @oids = $cce->find('System');
if (not @oids) {
        $cce->bye('FAIL');
        exit 1;
}

my ($ok, $obj) = $cce->get($oids[0], 'AppleShare');
unless ($ok and $obj) {
        $cce->bye('FAIL');
        exit 1;
}

# sync up system state with cce:
my $old = Sauce::Service::service_get_init('atalk') ? 'on' : 'off';
my $new = $obj->{enabled} ? 'on' : 'off';
Sauce::Service::service_set_init('atalk', $new) unless ($new eq $old);

# max users
my %settings;
$settings{'AFPD_MAX_CLIENTS'} = $obj->{maxConnections};

# sync up guest share
($ok, $obj) = $cce->get($oids[0], 'FileShare');

$settings{'AFPD_UAMLIST'} = '"-U uams_clrtxt.so,uams_dhx.so';
$settings{'AFPD_UAMLIST'} .= ',uams_guest.so' if $obj->{guestEnabled};
$settings{'AFPD_UAMLIST'} .= "\"";
$settings{'AFPD_GUEST'} = $obj->{guestUser};
Sauce::Util::editfile(appleshare::atalk_getnetatalk,
		      *Sauce::Util::keyvalue_edit_fcn,
		      '#', '=', undef, %settings);
Sauce::Util::editfile("/etc/atalk/AppleVolumes.default",
	\&editVolumes,
	$i18n->getProperty("atalkOptions","base-appleshare")
);
$cce->bye('SUCCESS');


sub editVolumes {
	my($if,$of,$options)=@_;
	my $printed=0;
	while(<$if>){
		if(/^#/){
			print $of $_;
		}else{
			unless($printed == 1){
				$printed=1;
				print $of ":DEFAULT: options:$options\n" unless ($options eq "none" 
										|| $options eq "ERROR");
			}
			print $of $_ unless /^:DEFAULT: options:$options$/;
		}
	}
}

exit 0;
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
