#!/usr/bin/perl -w
# $Id: tomcat_restart.pl,v 1.7.2.2 2002/03/01 04:47:06 uzi Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Tomcat service control
# Will DeHaan <null@sun.com>

# configure here: (mostly)
my $SERVICE = "tomcat.init";	# name of initd script for this daemon
my $RESTART = "restart"; # restart action
my $NAMESPACE = 'Java';
my $CONF = '/etc/httpd/conf/httpd.conf';
my $MOD = '# LoadModule jk_module modules/mod_jk.so';
my $DEBUG   = 0;
# The includes load mod_jk.so, global jsp, per-site and per-servlet configs.
my @INCLUDES = ('/usr/java/jakarta-tomcat/conf/mod_jk.conf-auto');

$DEBUG && warn "$0 invoked ".`date`;

use lib qw( /usr/sausalito/perl );
use Sauce::Util;
use CCE;
$cce = new CCE;
$cce->connectfd();

my($sysoid) = ($cce->find('System'))[0];
my ($ok, $obj) = $cce->get($sysoid, $NAMESPACE);

# fix chkconfig information:
if ($obj->{enabled}) {
	Sauce::Service::service_set_init($SERVICE, 'on', '345');
} else {
	Sauce::Service::service_set_init($SERVICE, 'off', '345');
}

# check to see if the service is presently running;
my $running = &tomcat_pstest();

$DEBUG && warn "Tomcat running prior to config? $running\n";

# we aren't running, but should be 
if (!$running && $obj->{enabled}) {

	# Verify/Create Apache's jakarta-tomcat includes
	$DEBUG && warn "$0 editing file $CONF\n";
	my $ret = Sauce::Util::editfile(
		$CONF,
		*_local_edit,
		1,
		$MOD,
		@INCLUDES);

	system("/etc/rc.d/init.d/${SERVICE} start >/dev/null 2>&1");

}
# We are running, but shoudn't
elsif ($running && !$obj->{enabled}) {
    
    #Destroy any web wars installed for all sites since java is being
    #shutdown for the system wide.
    my $site; 
    foreach $site ($cce->find('Vsite')) {
        my ($ok, $vsite) = $cce->get($site);
        my $sitename = $vsite->{name};
        my $modjk_conf = "/etc/httpd/conf/vhosts/$sitename";
        Sauce::Util::editfile(
        	    $modjk_conf,
	            *delete_jkmounts
	           );
    }

	# Remove jakarta-tomcat includes (mod_jk.so) from Apache
	$DEBUG && warn "$0 editing file $CONF\n";
	my $ret = Sauce::Util::editfile(
		$CONF,
		*_local_edit,
		0,
		$MOD,
		@INCLUDES);

	system("/etc/rc.d/init.d/${SERVICE} stop >/dev/null 2>&1");

}
# We're running as desired.  Restart.
elsif ($running && $obj->{enabled}) {
	system("/etc/rc.d/init.d/${SERVICE} restart >/dev/null 2>&1");
}

# retest whether the daemon is running
sleep 2; # wait for forked init
$running = &tomcat_pstest();
$DEBUG && warn 'Tomcat running after config? '.$running."\n";

# report the did-not-start error, if necessary:
if ($obj->{enabled} && !$running) {
	$cce->warn("[[base-java.${SERVICE}-did-not-start]]");
	$cce->bye("FAIL");
	exit 1;
} else {
	$cce->bye("SUCCESS");
	exit 0;
}



# Subs 

sub _local_edit
{
	$DEBUG && warn "$0 _local_edit invoked...\n";
	my $in = shift;
	my $out = shift;
	my $add = shift;
	my $mod = shift;
	my @files = @_;

	my ($file, %found, %include, %context);
	foreach $file (@files)
	{
		$include{$file} = "Include $file\n";
		# we'll use the $found{$context{foo}} hash to track instances
	}

    my @existing_includes = ();
    # We collect the existing includes in @existing_includes with the
    #purpose of adding the mod_jk.conf-auto ahead of any such includes.

	while (<$in>)
	{
		if (/^\s*Include\s(\S+)$/) 
		{
			my $conflet = $1;
			$DEBUG && warn "Found httpd.conf include file $conflet in $_";
			$DEBUG && warn "$conflet defined config: ".$include{$conflet};

			if($include{$conflet})
			{
				push @existing_includes, ($_) if ($add && !$found{$conflet});
				$found{$conflet} = 1;
			}
            else 
            {
				push @existing_includes, ($_);
            }
            $DEBUG && warn "so far collected: @existing_includes";
            next;
		}
		elsif (/^\s*$mod/)
		{
            $DEBUG && warn "collecting $_ in list: @existing_includes";
			push @existing_includes, ($_) if ($add && !$found{$mod}); 
			$found{$mod} = 1;
			next;
		}

		# just pass through everything else
		print $out $_;
	}

	foreach $file (@files)
	{
		if ($add && !$found{$file})
		{
			$DEBUG && warn 'Appending: '.$include{$file};
			print $out $include{$file};
			$found{$file} = 1; # uniquifies repeated arguments
		}
	}

	if ($add && !$found{$mod})
	{
		print $out $mod."\n";
	}

    #now we add the filtered @existing_includes. Please note that
    #this list is already filtered; anything need to be out
    #is already removed.
	foreach $file (@existing_includes)
    {
        $DEBUG && warn "now adding the rest: @existing_includes";
		print $out $file;
    }

	return 1;
}

sub tomcat_pstest
{
	my $running = 0;
	open(PS, "/bin/ps axwww|") || die "Process list command '/bin/ps' unavailable: $!";
	while(<PS>)
	{
		if (/jdk.+\/java\s/ && /conf\/tomcat.policy/)
		{
			$running = 1;
			last;
		}
	}
	close(PS);

	return $running;
}


sub delete_jkmounts
#for JkMount entries from the /etc/httpd/conf/vhosts/siteX file
{

    my($in, $out) = @_;
    
    my $entry_found = 0;

    while(<$in>) 
    {
        if(/^\s*JkMount/) 
        {
            $DEBUG && warn "found a JkMount: $_\n";
        }
        else
        {
            print $out $_;
        }
    }

    return 1;
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
