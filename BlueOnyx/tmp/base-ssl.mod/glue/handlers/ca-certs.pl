#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: ca-certs.pl 463 2005-04-01 16:10:45Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
# update vsite or system ssl configuration to include exclude
# an ssl ca cert file as necessary

use CCE;
use SSL qw(ssl_rem_ca_cert);
use Base::HomeDir qw(homedir_get_group_dir);
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE;
$cce->connectfd();

my $site = $cce->event_object();
my ($ok, $ssl, $ssl_old) = $cce->get($cce->event_oid(), 'SSL');
if (not $ok)
{
    $cce->bye('FAIL', '[[base-ssl.cantReadSSLNS]]');
    exit(1);
}

# figure out whether cas are being added or removed
my @cas = $cce->scalar_to_array($ssl->{caCerts});
my @old_cas = $cce->scalar_to_array($ssl_old->{caCerts});

my @remove = ();
for my $old_ca (@old_cas)
{
    if (!grep(/^$old_ca$/, @cas))
    {
        push @remove, $old_ca;
    }
}

# FIXME: this is totally wrong for this to have to know about
# the fact that apache is the underlying server, but this isn't easy
# to fix and for the forseeable future it isn't going to change.

my $ssl_conf = '/etc/admserv/conf.d/ssl.conf';
if ($site->{CLASS} eq 'System')
{
    my @ca_certs = $cce->scalar_to_array($ssl->{caCerts});
    if (!Sauce::Util::editfile($ssl_conf, *edit_ssl_conf, scalar(@ca_certs)))
    {
        $cce->bye('FAIL', '[[base-ssl.cantUpdateSSLConf]]');
        exit(1);
    }

    # remove cas if necessary
    for my $remove (@remove)
    {
        if ($remove && !ssl_rem_ca_cert('/etc/admserv/certs', $remove))
        {
            $cce->bye('FAIL', '[[base-ssl.cantRemoveCAs]]');
            exit(1);
        }
    }
}
else
{
    my $cert_dir;
    if ($site->{basedir})
    {
        $cert_dir = "$site->{basedir}/$SSL::CERT_DIR";
    }
    else
    {
        $cert_dir = homedir_get_group_dir($site->{name}, $site->{volume});
    }
    
    # take care of removals
    for my $remove (@remove)
    {
        if ($remove && !ssl_rem_ca_cert($cert_dir, $remove))
        {
            $cce->bye('FAIL', '[[base-ssl.cantRemoveCAs]]');
            exit(1);
        }
    }

    # check if the ca-certs file should be removed
    if ((stat("$cert_dir/ca-certs"))[7] == 0)
    {
        print STDERR "removing $cert_dir/ca-certs\n";
        # no need to worry about rollback here, since editfile was called above
	unlink("$cert_dir/ca-certs");
	$site_conf = httpd_get_vhost_conf_file($site->{name});
	Sauce::Util::editfile($site_conf, *remove_site_conf);
	service_run_init('httpd', 'restart');
    }
}

# the server gets restarted by the ui script after this succeeds
$cce->bye('SUCCESS');
exit(0);

sub edit_ssl_conf
{
    my ($in, $out, $add) = @_;
    
    my $found = 0;
    
    while (<$in>)
    {
        if (/^SSLCACertificateFile/)
        {
            $found = 1;
            if (!$add) { next; }
        }

        print $out $_;
    }

    if ($add && !$found)
    {
        print $out "SSLCACertificateFile /etc/admserv/certs/ca-certs\n";
    }

    return 1;
}

sub remove_site_conf
{
    my ($in, $out) = @_;

    while (<$in>)
    {
        if (/^SSLCACertificateFile/)
        {
            next;
        }
                                                                                
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
