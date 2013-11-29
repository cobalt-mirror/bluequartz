#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: gen_cert.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# Use the SSL information for the vsite to generate a private key, self-signed
# certificate and a certificate signing request.

use CCE;
use SSL qw(
            ssl_set_identity ssl_get_cert_info 
            ssl_error ssl_create_directory
            ssl_check_days_valid
            );
use Base::HomeDir qw(homedir_get_group_dir);
use Sauce::Util;

my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

$DEBUG && print STDERR "$0: starting up\n";
my $cce = new CCE('Namespace' => 'SSL', 'Domain' => 'base-ssl');
$cce->connectfd();

# get ssl information and vsite information
my $ssl_info = $cce->event_object();
my ($ok, $vsite) = $cce->get($cce->event_oid());

# The certificate information for Apache will be kept
# in the sites home diretory in the sub-directory specified
# in $SSL::CERT_DIR.  There may be a need to store a modified
# pem format certificate in a common certs directory, if possible, 
# to allow ssl for email servers.
my $cert_dir = ($vsite->{basedir} ? "$vsite->{basedir}/$SSL::CERT_DIR" : homedir_get_group_dir($vsite->{name}, $vsite->{volume}) . "/$SSL::CERT_DIR");

$DEBUG && print STDERR Dumper($vsite);
$DEBUG && print STDERR "$0: cert directory is $cert_dir\n";

my $gid = getgrnam($vsite->{name});

# make sure the umask is ok
umask(022);

# create cert dir if it doesn't exist
if (! -d $cert_dir)
{
    if (!ssl_create_directory(02770, $gid, $cert_dir))
    {
        $cce->bye('FAIL', 'cantMakeDirectory', { 'dir' => $cert_dir });
        exit(1);
    }
}

# make sure we don't hit 2038 rollover
$ssl_info->{daysValid} = ssl_check_days_valid($ssl_info->{daysValid}); 

# call ssl_set_identity which generates a self-signed certificate
my $ret = ssl_set_identity(
            $ssl_info->{daysValid},
            $ssl_info->{country},
            $ssl_info->{state},
            $ssl_info->{city},
            $ssl_info->{orgName},
            $ssl_info->{orgUnit},
            substr($vsite->{fqdn}, 0, 64),
            $ssl_info->{email},
            $cert_dir
          );

$DEBUG && print STDERR "$0: ssl_set_identity returned $ret\n";

# check the return value and return an appropriate error message
# as necessary
if ($ret != 1)
{
    $cce->bye('FAIL', ssl_error($ret));
    exit(1);
}

# chown the files, so that site admins can export and import
Sauce::Util::chownfile(0, $gid, "$cert_dir/certificate");
Sauce::Util::chownfile(0, $gid, "$cert_dir/key");
Sauce::Util::chownfile(0, $gid, "$cert_dir/request");

# read the expiration date from the new certificate
my ($sub, $iss, $date) = ssl_get_cert_info($cert_dir);

# munge date because they changed the strtotime function in php
$date =~ s/(\d{1,2}:\d{2}:\d{2})(\s+)(\d{4,})/$3$2$1/;

($ok) = $cce->set($cce->event_oid(), 'SSL', { 'expires' => $date });

# failing to set expires is non-fatal
if (not $ok)
{
    $cce->warn('[[base-ssl.cantSetExpires]]');
}

if (length($vsite->{fqdn}) > 64)
{
    $cce->baddata(0, 'fqdn', 'fqdnTooLongOkay', { 'fqdn' => $vsite->{fqdn} });
}

$cce->bye('SUCCESS');
exit(0);
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
