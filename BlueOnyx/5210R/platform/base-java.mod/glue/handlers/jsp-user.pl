#!/usr/bin/perl
# $Id: jsp-user.pl
# Jsp parser support for users
# Dependent on Vsite.Java.enabled ? all users parse .jsp : no users parse .jsp
# Will DeHaan <null@sun.com>

use lib qw(/usr/sausalito/perl);
use CCE;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_user_dir);

my $DEBUG = 0;
$DEBUG && warn $0.' '.`date`;
eval('use strict;') if ($DEBUG);

my $tomcat_conf = '/etc/tomcat/server.xml';

my $cce = new CCE;
$cce->connectfd();

my $object = $cce->event_object();
my $old = $cce->event_old();
my $new = $cce->event_new();

# Establish our user and site group
my $site = $object->{site};
$site = $old->{site} if ($old->{site});
$site = $new->{site} if ($new->{site});

# lookup vsite fqdn
my $vsite_oid = ($cce->find('Vsite', {'name' => $site}))[0];
$DEBUG && warn "Found site oid $vsite_oid corresponding to group: $site";
my ($ok, $vsite) = $cce->get($vsite_oid);
$DEBUG && warn "Found site fqdn: ".$vsite->{fqdn};

# lookup vsite Java
my ($a_ok, $jsp_vsite) = $cce->get($vsite_oid, 'Java');

# Bail if java is not enabled for this user's site
unless($jsp_vsite->{enabled})
{
	# Nothing to do!
	$DEBUG && warn "Java not enabled for siite $site";
	$cce->bye('SUCCESS');
	exit 0;
}

my $ret;
if($cce->event_is_destroy())
{
	# flush user lines from tomcat's server.xml
	$ret = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		0, $old->{name}, $site, $vsite->{fqdn}, $vsite->{volume})
		);
	# vsite ipaddr config (may not apply)
	my $ignore = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		0, $old->{name}, $site, $vsite->{ipaddr}, $vsite->{volume})
		);
        $cce->bye('SUCCESS'); # ignore return values on user delete. 
        exit 0;
}
else
{
	# new user, add line
	# flush user line from tomcat's server.xml
	$ret = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		1, $new->{name}, $site, $vsite->{fqdn}, $vsite->{volume})
		);
	# vsite ipaddr config (may not apply)
	my $ignore = (Sauce::Util::editfile(
		$tomcat_conf,
		*user_xml, 
		1, $new->{name}, $site, $vsite->{ipaddr}, $vsite->{volume})
		);
}

if ($ret)
{
        $cce->bye('SUCCESS'); # ignore return values on site delete. 
        exit 0;
} 
else
{
        $cce->bye('FAIL', '[[base-java.failedUserEnable]]');
        exit 1;
}


### Subs

sub user_xml
{
	my($in, $out, $enabled, $user, $group, $fqdn, $volume) = @_;
	$DEBUG && warn "user_xml invoked: $enabled, $user, $group, $fqdn, $volume\n";

	my $found = 0; # Track return status

	my $docbase = homedir_get_user_dir($user, $group, $volume).'/web';
	my $xml =<<EOF;
	  <Context path="/~$user/" docBase="$docbase" debug="0" reloadable="true" unpackWARs="true" autoDeploy="true"/>
EOF
	$DEBUG && warn "My XML:\n$xml";

	while(<$in>) 
	{
		if(/^\s*<Host name=\"$fqdn\".+\sSite\s+$group\s+/ ... /^\s*<\/Host>/) 
		{
			$DEBUG && warn "WITHIN Vsite $fqdn Host block: $_";

			if(/\"$docbase\"/)
			{
				$DEBUG && warn "Found existing user\n";
				print $out $_ if ($enabled);
				$found = 1;
				next;
			}
			elsif(/^\s*\<\/Host>/)
			{
				$DEBUG && warn "found end of host block";
				print $out $xml if ($enabled);
				$found = 1;
			}
			print $out $_;
		}
		else
		{
			print $out $_;
		}
	}
	return $found;
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