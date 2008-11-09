#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: gen_csr.pl 201 2003-07-18 19:11:07Z will $
# Copyright Sun Microsystems, Inc.  All rights reserved.
#
# generate a certificate signing request on demand from the current info
# in the SSL namespace

use CCE;
use SSL qw(ssl_gen_csr ssl_create_directory ssl_check_days_valid);
use Base::HomeDir qw(homedir_get_group_dir);

my $DEBUG = 0;
if ($DEBUG) { use Data::Dumper; }

my $cce = new CCE('Domain' => 'base-ssl');
$cce->connectfd();

# set a sane umask
umask(022);

my $site = $cce->event_object();
my ($ok, $ssl) = $cce->get($cce->event_oid(), 'SSL');
if (not $ok)
{
    $cce->bye('FAIL', '[[base-ssl.cantReadSSLNS]]');
    exit(1);
}

my ($cert_dir, $fqdn);
if (exists($site->{basedir}))
{
    # it's a vsite
    $fqdn = $site->{fqdn};
    if ($site->{basedir})
    {
        $cert_dir = "$site->{basedir}/$SSL::CERT_DIR";
    }
    else
    {
        $cert_dir = homedir_get_group_dir($site->{name}, $site->{volume}) . '/' 
                        . $SSL::CERT_DIR;
    }

    # make sure the directory exists
    if (!ssl_create_directory(02770, scalar(getgrnam($site->{name})), $cert_dir))
    {
        $cce->bye('FAIL', 'cantMakeDirectory', { 'dir' => $cert_dir });
        exit(1);
    }
}
else # must be System
{
    $fqdn = $site->{hostname} . '.' . $site->{domainname};
    $cert_dir = '/etc/admserv/certs';

    # make sure cert dir exists
    if (!ssl_create_directory(0700, 0, $cert_dir))
    {
        $cce->bye('FAIL', 'cantMakeDirectory', { 'dir' => $cert_dir });
        exit(1);
    }
}

# for a csr if fqdn is not 64 or less fail, since the csr is pointless
# because no CA will sign it
if (length($fqdn) > 64)
{
    $cce->bye('FAIL', 'fqdnTooLongForCsr', { 'fqdn' => $fqdn });
    exit(1);
}

# need to generate a signing request
my $subject = {
                'C' => $ssl->{country},
                'ST' => $ssl->{state},
                'L' => $ssl->{city},
                'O' => $ssl->{orgName},
                'OU' => $ssl->{orgUnit},
                'CN' => $fqdn,
                'Email' => $ssl->{email}
                };

$DEBUG && print STDERR Dumper($ssl, $subject);

# check for 2038 rollover
if (!ssl_check_days_valid($ssl->{daysValid}))
{
    $cce->bye('FAIL', '[[base-ssl.2038bug]]');
    exit(1);
}

if (!ssl_gen_csr($cert_dir, $ssl->{daysValid}, $subject))
{
    $cce->bye('FAIL', '[[base-ssl.cantGenerateCsr]]');
    exit(1);
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
