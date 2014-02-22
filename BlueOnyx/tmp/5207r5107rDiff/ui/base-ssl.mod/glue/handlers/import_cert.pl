#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: import_cert.pl 259 2004-01-03 06:28:40Z shibuya $
# Copyright Sun Microsystems, Inc.  All rights reserved.
#
# suck in imported certificates and keys

use CCE;
use SSL qw(ssl_set_cert ssl_add_ca_cert ssl_create_directory);
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
    $cce->bye('FAIL', 17);
    exit(1);
}

my ($cert_dir);
if (exists($site->{basedir}))
{
    # it's a vsite
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
        $cce->bye('FAIL', 16);
        exit(1);
    }
}
else # must be System
{
    $cert_dir = '/etc/admserv/certs';

    # make sure cert dir exists
    if (!ssl_create_directory(0700, 0, $cert_dir))
    {
        $cce->bye('FAIL', 16);
        exit(1);
    }
}

my $type = '';
if (-f "$cert_dir/.import_cert")
{
    $type = 'server';
    $cert = &read_file("$cert_dir/.import_cert");
    unlink("$cert_dir/.import_cert");
}
elsif (-f "$cert_dir/.import_ca_cert")
{
    $type = 'ca';
    $cert = &read_file("$cert_dir/.import_ca_cert");
    unlink("$cert_dir/.import_ca_cert");
}
else
{
    $cce->bye('FAIL', 14);
    exit(1);
}

if ($type eq 'server')
{
    $ret = ssl_set_cert($cert, $cert_dir);
    if (not $ret)
    {
        $DEBUG && print STDERR "Couldn't set certificate!\nCert Dir is $cert_dir\n",
                        "Certificate is:\n$cert\n";
        $cce->bye('FAIL', 5);
        exit(1);
    }
    elsif ($ret == -1)
    {
        $DEBUG && print STDERR "private key does not match cerificate\n";
        $cce->bye('FAIL', 8);
        exit(1);
    }
}
elsif ($type eq 'ca')
{
    $ret = ssl_add_ca_cert(\$cert, $cert_dir);
    if (!$ret)
    {
        $DEBUG && print STDERR "ssl_add_ca_cert failed: $ret\n";
        $cce->bye('FAIL', 9);
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

sub read_file
{
    my $filename = shift;

    if (!open(FILE, $filename))
    {
        return '';
    }

    my $ret = '';
    while (<FILE>)
    {
        $ret .= $_;
    }
    close(FILE);

    return $ret;
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
