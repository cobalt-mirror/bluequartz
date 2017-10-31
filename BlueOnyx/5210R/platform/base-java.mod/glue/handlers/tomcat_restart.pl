#!/usr/bin/perl -w
# $Id: tomcat_restart.pl
# Tomcat service control
# Will DeHaan <null@sun.com>

# configure here: (mostly)
my $SERVICE = "tomcat";	# name of initd script for this daemon
my $RESTART = "restart"; # restart action
my $NAMESPACE = 'Java';
my $CONF = '/etc/httpd/conf/httpd.conf';
my $MOD = '# LoadModule jk_module modules/mod_jk.so';
my $DEBUG   = 0;
# The includes load mod_jk.so, global jsp, per-site and per-servlet configs.
my @INCLUDES = ('/etc/httpd/conf.d/mod_jk.conf');

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

	system("/sbin/service ${SERVICE} start >/dev/null 2>&1");

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

	system("/sbin/service ${SERVICE} stop >/dev/null 2>&1");

}
# We're running as desired.  Restart.
elsif ($running && $obj->{enabled}) {
	system("/sbin/service ${SERVICE} restart >/dev/null 2>&1");
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
		$include{$file} = "# Include $file\n";
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
		if (/java/ && /tomcat/)
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

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 