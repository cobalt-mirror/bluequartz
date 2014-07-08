#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: webscripting.pl
#
# This is triggered by changes to the Vsite PHP, CGI, or SSI namespaces.
# It maintains the scripting part of the vhost conf

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

&debug_msg("Init of webscripting.pl\n");

my($ok, $php) = $cce->get($cce->event_oid(), 'PHP');
($ok, my $cgi) = $cce->get($cce->event_oid(), 'CGI');
($ok, my $ssi) = $cce->get($cce->event_oid(), 'SSI');

if(not $ok)
{
    $cce->bye('FAIL', '[[base-apache.cantReadWebScripting]]');
    &debug_msg("Fail1: [[base-apache.cantReadWebScripting]]\n");
    exit(1);
}

if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{name}), 
                            *edit_vhost, $php, $cgi, $ssi, $vsite->{fqdn}))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    &debug_msg("Fail2: [[base-apache.cantEditVhost]]\n");
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

sub edit_vhost
{
    my ($in, $out, $php, $cgi, $ssi, $fqdn) = @_;

    my $script_conf = '';

    my $begin = '# BEGIN WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';
    my $end = '# END WebScripting SECTION.  DO NOT EDIT MARKS OR IN BETWEEN.';

	if ($cgi->{enabled})
	{
		$script_conf .= "AddHandler cgi-wrapper .cgi\nAddHandler cgi-wrapper .pl\n";
	}

	if ($ssi->{enabled})
	{
		$script_conf .= "AddHandler server-parsed .shtml\nAddType text/html .shtml\n";
	}

	if ($php->{enabled})
	{
                if ($php->{suPHP_enabled}) { 
                        $script_conf .= <<EOT
suPHP_Engine on
suPHP_ConfigPath /home/sites/$fqdn
suPHP_AddHandler x-httpd-suphp
AddHandler x-httpd-suphp .php
EOT
                } else { 
                        $script_conf .= "AddType application/x-httpd-php .php5\nAddType application/x-httpd-php .php4\nAddType application/x-httpd-php .php\n"; 
                } 
	}

    my $last;
    while(<$in>)
    {
        if(/^<\/VirtualHost>/i) { $last = $_; last; }

        if(/^$begin$/)
        {
            while(<$in>)
            {
                if(/^$end$/) { last; }
            }
        }
        else
        {
            print $out $_;
        }
    }

    print $out $begin, "\n";
    print $out $script_conf;
    print $out $end, "\n";
    print $out $last;

    # preserve the remainder of the config file
    while(<$in>)
    {
        print $out $_;
    }

    return 1;
}

# For debugging:
sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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