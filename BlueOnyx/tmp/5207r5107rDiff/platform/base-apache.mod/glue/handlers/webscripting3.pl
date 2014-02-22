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
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 