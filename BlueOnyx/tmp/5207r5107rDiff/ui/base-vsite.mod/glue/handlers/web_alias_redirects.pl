#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: web_alias_redirects.pl

# This handler is run whenever 'Vsite' key 'webAliasRedirects' is set.
#
# When that happens, this script adds or removes the RewriteCond's of Web aliases
# which either allow aliases to run as sites with own URL, or which redirect to
# the main site instead.

# Debugging switch:
$DEBUG = "0";

$whatami = "handler";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;
use Sauce::Service;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_group_dir homedir_get_user_dir);
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();

    # Get our events from the event handler stack:
    $oid = $cce->event_oid();
    $obj = $cce->event_object();

    $old = $cce->event_old();
    $new = $cce->event_new();

    ($soid) = $cce->find('System');
    ($ok, $obj) = $cce->get($soid);

    # Get "System" . "Web":
    ($ok, $objWeb) = $cce->get($soid, 'Web');

    # HTTP and SSL ports:
    $httpPort = "80";
    if ($objWeb->{'httpPort'}) {
        $httpPort = $objWeb->{'httpPort'};
    }
    $sslPort = "443";
    if ($objWeb->{'sslPort'}) {
        $sslPort = $objWeb->{'sslPort'};
    }

    # Poll info about the Vsite in question:
    ($ok, $vsite) = $cce->get($oid);

    # Event is create or modify:
    if ((($cce->event_is_create()) || ($cce->event_is_modify()))) {

	# Edit the vhost container or die!:
	if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{"name"}), *edit_vhost, $vsite->{"webAliases"})) {
	    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
	    exit(1);
	}

	# Restart Apache:
	&restart_apache;
    }
}

$cce->bye('SUCCESS');
exit(0);

sub restart_apache {
    # Restarts Apache - soft restart:
    service_run_init('httpd', 'reload');
}

sub edit_vhost {
    my ($in, $out, $php, $cgi, $ssi) = @_;

    my $vhost_conf = '';

    # Build the webAlias Array:
    my $aliasRewrite, $aliasRewriteSSL;
    if (($vsite->{webAliases}) && ($vsite->{webAliasRedirects} == "0")) {
        my @webAliases = $cce->scalar_to_array($vsite->{webAliases});
        foreach my $alias (@webAliases) {
           $aliasRewrite .= "RewriteCond %{HTTP_HOST}                !^$alias(:$httpPort)?\$ [NC]\n";
        }
    }

    # Build our output string:
    my $vhost_conf =<<END;
RewriteCond %{HTTP_HOST}                !^$vsite->{ipaddr}(:$httpPort)?\$
RewriteCond %{HTTP_HOST}                !^$vsite->{fqdn}(:$httpPort)?\$ [NC]
$aliasRewrite
END

    # Do the actual editing:
    my $last;
    while(<$in>) {
        if ((/^RewriteRule/i) || (/^RewriteOptions/i)) { 
    	    # If we get to this point, we're past the area of interest.
    	    # We store it in the string $last and end the charade.
    	    $last = $_; 
    	    last; 
    	}

        if (/^RewriteCond/) {
    	    # If we find this line, we ignore it and later add our own.
    	    $found = "1";
	    next;
        }
	elsif ((/^[\r\n]/) && ($found eq "1")) {
	    # We now found the empty line smack in the middle of our area of interest and ignore it.
	    next;
	}
        else {
    	    # Anything else stays and gets printed out straight away.
            print $out $_;
        }
    }

    # Print out or new RewriteCond's:
    print $out $vhost_conf;

    # Print out all the rest of the config unaltered:
    print $out $last;

    # preserve the remainder of the config file and write it out:
    while(<$in>) {
        print $out $_;
    }

    return 1;
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
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