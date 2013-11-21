#!/usr/bin/perl -w
# $Id: webscripting.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
#
# This is triggered by changes to the Vsite PHP, CGI, or SSI namespaces.
# It maintains the scripting part of the vhost conf

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE;
$cce->connectfd();

my $vsite = $cce->event_object();

($ok, my $userwebs) = $cce->get($cce->event_oid(), 'USERWEBS');

if(not $ok)
{
    $cce->bye('FAIL', '[[base-apache.cantReadUserWebs]]');
    exit(1);
}

$documentRoot = $vsite->{basedir};
$siteNumber = $vsite->{name};

if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{name}), 
                            *edit_vhost, $userwebs))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

sub edit_vhost
{
    my ($in, $out, $userwebs) = @_;

    my $script_conf = '';

    my $begin = 'RewriteOptions inherit';
    my $end = 'Include /etc/httpd/conf/vhosts/' . $siteNumber . '.include';

	if ($userwebs->{enabled}) {
		$script_conf .= "AliasMatch ^/~([^/]+)(/(.*))?           $documentRoot/users/\$1/web/\$3\n";
	}
	else {
		$script_conf .= "#AliasMatch ^/~([^/]+)(/(.*))?          $documentRoot/users/\$1/web/\$3\n";
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
