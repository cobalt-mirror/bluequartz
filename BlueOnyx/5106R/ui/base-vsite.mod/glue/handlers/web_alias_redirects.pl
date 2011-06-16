#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: web_alias_redirects.pl, v1.0.0.0 Thu 16 Jun 2011 01:15:38 AM CEST mstauber Exp $
# Copyright 2006-2011 Team BlueOnyx. All rights reserved.

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
           $aliasRewrite .= "RewriteCond %{HTTP_HOST}                !^$alias(:80)?\$ [NC]\n";
        }
    }

    # Build our output string:
    my $vhost_conf =<<END;
RewriteCond %{HTTP_HOST}                !^$vsite->{ipaddr}(:80)?\$
RewriteCond %{HTTP_HOST}                !^$vsite->{fqdn}(:80)?\$ [NC]
$aliasRewrite
END

    # Do the actual editing:
    my $last;
    while(<$in>) {
        if (/^RewriteRule/i) { 
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
