#!/usr/bin/perl -w
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# This is triggered by changes to the VirtualHost's ip, fqdn, or host.
# It maintains the scripting part of the vhost conf

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);
use Sauce::Util;

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

   # extract web settings
   # assume oject type is VirtualHost
   my $vname = $vsite->{name};

   ($vsoid) = $cce->find("Vsite", { "name" => $vsite->{name} });
   my($success, $php) = $cce->get($vsoid, "PHP");
   ($success, $cgi) = $cce->get($vsoid, "CGI");
   ($success, $ssi) = $cce->get($vsoid, "SSI");

# check extraction success
if(not $success)
{
    $cce->bye('FAIL', '[[base-apache.cantReadWebScripting]]');
    exit(1);
}

if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{name}), 
                            *edit_vhost, $php, $cgi, $ssi, $vsite->{fqdn}))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    exit(1);
}

if($php->{suPHP_enabled}){    $site_dir = "/home/sites/".$vsite->{fqdn};    #copy php.ini
    if(!-e "$site_dir/php.ini") {
        system("/bin/cp -r /etc/php.ini $site_dir/php.ini");
        system("/bin/chmod 0664 $site_dir/php.ini");
        system("/bin/chown nobody.$vsite->{name} $site_dir/php.ini");
        Sauce::Util::editfile("$site_dir/php.ini", *edit_phpini, $vsite);
    }

    # mkdir .tmp
    if(!-e "$site_dir/.tmp") {
        system("/bin/mkdir \"$site_dir/.tmp\"");
        system("chmod 2777 \"$site_dir/.tmp\"");
        system("chown nobody.$vsite->{name} \"$site_dir/.tmp\"");
    }
}

$cce->bye('SUCCESS');
exit(0);

sub edit_phpini {
       my ($in, $out, $vsite) = @_;
       while (<$in>) {
               s/^session\.save_path(.*)$/session\.save_path = \"\/home\/sites\/$vsite->{fqdn}\/\.tmp\"/g;
               print $out $_;
       }
       return 1;
}

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
		if ($php->{suPHP_enabled}) {
			$script_conf .= <<EOT
suPHP_Engine on
suPHP_ConfigPath /home/sites/$fqdn
suPHP_AddHandler php5-script
AddHandler php5-script .php
EOT
		} else {
			$script_conf .= "AddType application/x-httpd-php .php5\nAddType application/x-httpd-php .php4\nAddType application/x-httpd-php .php\n";
		}
	}

	if ($php->{enabled})
	{
		$script_conf .= "AddType application/x-httpd-php .php5\nAddType application/x-httpd-php .php4\nAddType application/x-httpd-php .php\n";
	}

    my $last;
    my $enableSSL = 0;
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
        if(/^<\/VirtualHost>/i) { $enableSSL = 1; $last = $_; last; }

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

    if ($enableSSL) {
        print $out $begin, "\n";
        print $out $script_conf;
        print $out $end, "\n";
        print $out $last;

        while(<$in>)
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
