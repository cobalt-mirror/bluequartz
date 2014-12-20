#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: servlet.pl
# Servlet/Tomcat XML registration
# Will DeHaan <null@sun.com>

use strict;

use CCE;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_group_dir);

my $DEBUG = 0;
$DEBUG && warn "$0 ".`date`;

my $servlet_conf = '/etc/tomcat/web.xml';
my $server_conf = '/etc/tomcat/server.xml';

my $cce = new CCE('Domain' => 'base-java');
$cce->connectfd();

# retrieve info
my $oid = $cce->event_oid();
my $old = $cce->event_old(); # old values
my $new = $cce->event_new(); # recently changed values only
my $obj = $cce->event_object(); # composite "new" object.

$DEBUG && warn "My ($0) event oid: $oid\n";

my ($ok, $servlet) = $cce->get($cce->event_oid());
if (not $ok)
{
	$DEBUG && warn 'Could not load current servlet CCE object: '.$cce->event_oid()."\n";
	$cce->bye('FAIL');
	exit(1);
}

my($enabled, $name, $group, $user, $class);

if ($cce->event_is_destroy())
{
	$enabled = 0;
	$name = $old->{name};
	$group = $old->{group};
	$user = $old->{user};
	$class = $old->{class};
}
else
{
	$enabled = $obj->{enabled};
	$name = $obj->{name};
	$group= $obj->{group};
	$user= $obj->{user};
	$class = $obj->{class};
}

# obtain the fqdn from the site group
# FIXME: Not sure how this site query will behave on site deletion... 
my ($ret, $vsite) = $cce->get(($cce->find('Vsite', { 'name' => $group }))[0]);
my $fqdn = $vsite->{fqdn};
my $ipaddr = $vsite->{ipaddr};
my $volume = $vsite->{volume};
my $sitename = $vsite->{name};

# First edit Tomcat's web.xml config
#if (!Sauce::Util::editfile(
#	$servlet_conf,
#	*edit_xml, 
#	$enabled, $name, $class)
#	)
#{
#	$cce->bye('FAIL');
#	exit(1);
#}
# Now add the context to Tomcat's server.xml
# We do not pass the user argument unless this servlet is for a user web 
# (as opposed to a site web)
if (!Sauce::Util::editfile(
	$server_conf,
	*edit_context, 
	$enabled, $name, $fqdn, $group, undef, $volume)
	)
{
	$cce->bye('FAIL');
	exit(1);
}

#Now edit the /etc/httpd/conf/vhosts/siteXX file. The entries
#in this file are for registering our servlets with apache.
my $modjk_conf = "/etc/httpd/conf/vhosts/$sitename";
if (!Sauce::Util::editfile(
	$modjk_conf,
	*edit_modjk, 
	$enabled, $ipaddr, $fqdn, $name)
	)
{
	$cce->bye('FAIL');
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);


# Subs, less greasy than burgers but still a quick lunch #

sub edit_xml
# configure ...tomcat/conf/edit.xml
{
	my($in, $out, $enabled, $name, $class) = @_;

	my $xml;

	$DEBUG && warn "edit_xml invoked...\n";

	if ($enabled) 
	{
		# A tough call here, allowing user specified arbitrary class per-servlet.
		my $class;
		$class ="\n\t\t<servlet-class>$class\.$name<\/servlet-class>" if ($class); 

		# Slightly complex here, URL is assumed to be /servlet/<appname> 
		# unless we define a custom class, then URL is descignated as
		# /servlet/<class>/<appname>
		# FIXME: add user-specified URL?
		my $urlpatt = "<utl-pattern>\/servlet\/$name<\/url-pattern>";
		$urlpatt = "<url-pattern>\/servlet\/$class\/$name<\/url-pattern>" 
			if ($class); 

		$xml =<<EOF;
	<!-- Cobalt generated servlet integration.  Do not modify this line. $name:$class -->
	<servlet>
		<servlet-name>$name</servlet-name>$class
	</servlet>
	<servlet-mapping>
		<servlet-name>$name</servlet-name>
		$urlpatt
	</servlet-mapping>
	<!-- End Cobalt generated servlet integration. -->
EOF
	}

	$DEBUG && warn "My XML:\n$xml";

	my $found = 0;
	while(<$in>) 
	{
		if (/^\s*<\!-- Cobalt generated.+\s$name\:$class\s/ ... 
		    /^\s*<\!-- End Cobalt/) 
		{
			$DEBUG && warn "WITHIN Servlet $name config block: $_";

			if ($enabled) 
			{
				print $out $xml unless($found);
				warn "INSERT/REPLACE: $xml";
				$found = 1;
			}
		}
		elsif (/^\s*<\/web-app>/ && $enabled && !$found)
		{
			print $out $xml;
			$DEBUG && warn "APPEND: $xml";
			
			print $out $_;
			$DEBUG && warn "Out: $_";
		} 
		else
		{
			print $out $_;
			$DEBUG && warn "Out: $_";
		}
	}
	return 1;
}

sub edit_context
# configure ...tomcat/conf/server.xml
{
	my($in, $out, $enabled, $app, $fqdn, $group, $user, $volume) = @_;
	$app =~ s/^\\//g; # strip leading slash

	my $sitebase = homedir_get_group_dir($group, $volume);
	my $found = 0;
	my $xml;

	if($user) 
	{
		$xml =<<EOF;
	  <Context path="/~$user/" docBase="$sitebase/users/$user/web/$app" reloadable="true" debug="0"/>
EOF
	}
	else
	{
		$xml =<<EOF;
	  <Context path="/$app" docBase="$sitebase/web/$app" reloadable="true" debug="0"/>
EOF
	}

	$DEBUG && warn "My XML:\n$xml";

	while(<$in>) 
	{
		if(/^\s*<Host\s+name=\"$fqdn\"/i ... /^\s*<\/Host>/i) 
		{
			$DEBUG && warn "WITHIN Vsite $fqdn Host block: $_";

			if(/Context\s+path=\"\/$app\"\s+/)
			{
				$found = 1;
				print $out $_ if ($enabled);
				$DEBUG && warn "XML OUTPUT: $_";
			}
			elsif(!$found && /^\s*<\/Host>/i)
			{
				print $out $xml if ($enabled);
				print $out $_;
				$found = 1; # remember for return status
			}
			else
			{
				print $out $_;
			}
		}
		else
		{
			print $out $_;
		}
	}
	return $found;
}


sub edit_modjk
#for editing the /etc/httpd/conf/vhosts/siteX file
{

    my($in, $out, $enabled, $ip_addr, $server_name, $war_name) = @_;
    
    my $host_found = 0;
    my $entry_found = 0;

    my $new_entry;
    $new_entry = <<EOF
JkMount /$war_name/* ajp13
JkMount /$war_name/*.jsp ajp13
JkMount /$war_name/servlet/* ajp13
EOF
;

    while(<$in>) 
    {
        if(/^\s*<VirtualHost\s+$ip_addr/i ... /^\s*<\/VirtualHost>/i) 
        {
            $DEBUG && warn "WITHIN VirtualHost $ip_addr block: $_";

            if(m{JkMount\s+/$war_name/servlet.*} || 
               m{JkMount\s+/$war_name/\*\.jsp.*} 
              )
            {
                $entry_found = 1;
                print $out $_ if ($enabled);
                $DEBUG && warn "XML OUTPUT: $_";
            }
            elsif(!$entry_found && /^\s*<\/VirtualHost>/i)
            {
                print $out $new_entry if ($enabled);
                print $out $_;
                $entry_found = 1; # remember for return status
            }
            else
            {
                print $out $_;
            }
    
            $host_found = 1;
        }       
        else
        {
            print $out $_;
        }
    }

    if (!$host_found) {
        my $new_block = <<EOF
<VirtualHost $ip_addr>
ServerName $server_name
$new_entry</VirtualHost>
EOF
;
        print $out $new_block;
        $entry_found = 1; # remember for return status
    }

    return $entry_found;
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