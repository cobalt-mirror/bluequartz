#!/usr/bin/perl -w -I/usr/sausalito/perl
#  ___________________________________________________________
# /\                                                          \
# \_| SSL.pm: Functions for manging SSL certificates.         |
#   |                                                         |
#   | $Id: SSL.pm                                             |
#   |                                                         |
#   | Originally written by                                   |
#   |   Harris Vaegan-Lloyd <harris@cobaltnet.com>            |
#   |                                                         |
#   | Copyright 1999, 2000, 2001 Sun Microsystems, Inc.       |
#   | All rights reserved.                                    |
#   |   ______________________________________________________|__
#    \_/________________________________________________________/


package SSL;
use vars qw(@ISA @EXPORT_OK);
require Exporter;
@ISA    = qw(Exporter);
@EXPORT_OK = qw( 
            ssl_set_cert
            ssl_get_cert
            ssl_set_identity
            ssl_get_cert_info
            ssl_gen_csr
            ssl_cert_to_req
            ssl_cert_check
            ssl_check_valid_cert
            ssl_clear
            ssl_error
            ssl_create_directory
            ssl_add_ca_cert
            ssl_rem_ca_cert
            ssl_check_days_valid
            );

use File::Copy;
use Sauce::Util;
use IPC::Open2;

use vars qw($OPENSSL $DEBUG);
$DEBUG = 0;
$OPENSSL = '/usr/bin/openssl';

# variables used by outside sources
$SSL::CERT_DIR = 'certs'; # sub-directory where certs should be kept

sub ssl_set_cert
#
# Arguments: A string containing the new certificate.
#            A certificate directory root.
{
    my $cert = shift;
    my $cert_root = shift;

    $cert =~ s/\r//g;

    $cert =~ m#(-----BEGIN RSA PRIVATE KEY-----.*-----END RSA PRIVATE KEY-----)#s;
    my $key = $1;
    $cert =~ m#(-----BEGIN CERTIFICATE-----.*-----END CERTIFICATE-----)#s;
    $cert = $1;

    print STDERR "Cert: $cert\n Key: $key\n" if $DEBUG;

    # First off we must check if this is a valid certificate. The cgi may
    # have passed up just anything.
    if (not ssl_check_valid_cert($cert)) 
    {
        return 0;
    }

    my $file_edit_sub = sub { 
                my ($in, $out, $data) = @_; 
                print $out $data;
                return 1;
            };

    if ($key) 
    {
        if (not Sauce::Util::editfile("$cert_root/key", $file_edit_sub, $key))
        {
            return 0;
        }
        Sauce::Util::chmodfile(0640, "$cert_root/key");
    }
        
    # need to verify that the cert it matches the private
    # key, or apache dies horribly
    if (not ssl_cert_matches_key($cert_root, $cert))
    {
        return -1;
    }

    if (not Sauce::Util::editfile("$cert_root/certificate", $file_edit_sub, $cert))
    {
        return 0;
    }
    Sauce::Util::chmodfile(0640, "$cert_root/certificate");

    return 1;
}

# verifies that the input cert matches the current private key
# args: directory containing private key, cert in string form
# returns true on match, false on mismatch
sub ssl_cert_matches_key
{
    my ($cert_root, $cert) = @_;

    $DEBUG && print STDERR "$cert_root\n$cert\n";

    # if no key, then definite mismatch
    if (! -f "$cert_root/key")
    {
        return 0;
    }

    # get the modulus of the key
    my $pkey_mod = `$OPENSSL rsa -modulus -in $cert_root/key -noout 2>/dev/null`;
    if ($?)
    {
        return 0;
    }
    
    chomp($pkey_mod);

    # get the cert modulus
    local(*RDRFH, *WRTRFH);
    if (!open2(\*RDRFH, \*WRTRFH, "$OPENSSL x509 -modulus -noout 2>/dev/null"))
    {
        return 0;
    }

    print WRTRFH $cert, "\n";
    my $cert_mod = <RDRFH>;
    chomp($cert_mod);
    close(RDRFH);
    close(WRTRFH);
    
    $DEBUG && print STDERR "$pkey_mod\n$cert_mod\n";
    if ($pkey_mod eq $cert_mod)
    {
        return 1;
    }

    return 0;
}

sub ssl_get_cert
#  ________________________________________________________________
# /\                                                               \
# \_| Arguments: A optional root directory from which to fetch the |
#   |                        certificate, req and key.             |
#   | Returns: $hash->{certificate}                                |
#   |              |->{request}                                    |
#   |              |->{key}                                        |
#   |           Null strings for non-present files.                |
#   |   ___________________________________________________________|__
#    \_/_____________________________________________________________/
{
    my $cert_root = shift;
    $cert_root ||= $CERT_DIR;

    my $certificate = {};

    if ( open(CERTIFICATE, "$cert_root/certificate") ) {
        # Simply return a blank string if no request has yet been
        # generated;
        $certificate->{certificate} = join('',<CERTIFICATE>);
        close CERTIFICATE;
    }
    if ( open(REQUEST, "$cert_root/request") ) {
        # Simply return a blank string if no request has yet been
        # generated;
        $certificate->{request} = join('',<REQUEST>);
        close REQUEST;
    }
    if ( open(KEY, "$cert_root/key") ) {
        # Simply return a blank string if no request has yet been
        # generated;
        $certificate->{key} = join('',<KEY>);
        close KEY;
    }
    return $certificate;
}

sub ssl_set_identity
#  _____________________________________________________________
# /\                                                            \
# \_| Arguments: Number of days the certificate is valid for.   |
#   |            A country.                                     |
#   |            State or province name.                        |
#   |            Locality Name (City.)                          |
#   |            Organisation Name.                             |
#   |            Organisation Unit.                             |
#   |            Fully Qualified Domain Name.                   |
#   |            Email address of maintainer.                   |
#   |            A root certificate dir.                        |
# Returns:  1 for success
#           0 for non-existent certificate dir              
#           -1 on failure to generate a private key
#           -2 on failure to generate a cert signing request
#           -3 on failure to generate a self-signed cert
#           -4 if the generated cert fails ssl_cert_check
#
{
    my %subject;

    my $days = shift;
    $subject{'C'} = shift;
    $subject{'ST'} = shift;
    $subject{'L'} = shift;
    $subject{'O'} = shift;
    $subject{'OU'} = shift;
    $subject{'CN'} = shift;
    $subject{'Email'} = substr(shift, 0, 39);
    my $cert_dir = shift;

    # fail if the cert_dir passed in doesn't exist
    if (! -d $cert_dir) 
    {
        return 0;
    }

    if ((! -f "$cert_dir/key") && !_gen_private_key($cert_dir))
    {
        return -1;
    }

    if (!ssl_gen_csr($cert_dir, $days, \%subject))
    {
        return -2;
    }

    Sauce::Util::modifyfile("$cert_dir/certificate");
    if(system($OPENSSL, 'x509', '-days', $days, '-req', '-signkey',
            "$cert_dir/key", '-in', "$cert_dir/request", 
            '-out', "$cert_dir/certificate")) 
    {
        return -3;
    }

    Sauce::Util::chmodfile(0640, "$cert_dir/certificate");

    unless(ssl_cert_check($cert_dir)) 
    {
        return -4;
    }

    return 1;
}

# generate a signing request
sub ssl_gen_csr
{
	my $cert_dir = shift;
	my $days = shift;
	my $subject = shift;

	$DEBUG && print STDERR "$cert_dir $days $subject\n";

	if ((! -f "$cert_dir/key") && !_gen_private_key($cert_dir)) {
		return 0;
	}

	for my $key (keys(%{$subject})) {
		#
		# replace blank subject entries with '.' since that is what 
		# openssl expects
		#
		if ($subject->{$key} eq '') {
			$subject->{$key} = '.';
		}
	}

	Sauce::Util::modifyfile("$cert_dir/request");
	if (not open (REQ, "|$OPENSSL req -new"
			. " -key $cert_dir/key -days $days -out $cert_dir/request 2>/dev/null"))
	{
		return 0;
	}

	# the two extra newlines are necessary
	print REQ <<USER_INFO;
$subject->{'C'}
$subject->{'ST'}
$subject->{'L'}
$subject->{'O'}
$subject->{'OU'}
$subject->{'CN'}
$subject->{'Email'}


USER_INFO
	;
	my $ret = close REQ;	

	$DEBUG && print STDERR "openssl returned $ret and $?\n";
	Sauce::Util::chmodfile(0640, "$cert_dir/request");
	return 1;
}

# used for cert import to create a signing request from an imported
# key and certificate
sub ssl_cert_to_req
{
    my $cert_dir = shift;

    my @request = ();
    
    if (!open(CERT, "$OPENSSL x509 -x509toreq -signkey $cert_dir/key -in $cert_dir/certificate |"))
    {
        return 0;
    }

    my $line;
    while($line = <CERT>)
    {
        if ($line =~ /^-----BEGIN CERTIFICATE REQUEST-----$/)
        {
            push @request, $line;
            last;
        }
    }
   
    while($line = <CERT>)
    {
        push @request, $line;
        if ($line =~ /^-----END CERTIFICATE REQUEST-----$/)
        {
            last;
        }
    }

    close(CERT);

    Sauce::Util::modifyfile("$cert_dir/request");
    if (!open(REQ, ">$cert_dir/request"))
    {
        return 0;
    }

    print REQ @request;
    close(REQ);

    return 1;
}

sub ssl_get_cert_info
#  ___________________________________________________________
# /\                                                          \
# \_| Arguments: An optional certificate root directory       |
#   | Returns: Refrence to hash of subject information.       |
#   |          Refrence to hash of issuer information.        |
#   |          date string in GMT. |
#   | e.g. ( $issuer, $subject $date) = ssl_get_cert_info   |
#   |   ______________________________________________________|_
#    \_/________________________________________________________/
{
    my $cert_root = shift;
    my %subject;
    my %issuer;
    my $date;

    # Ugh, backticks.. No other options unfortunately..
    if (!open(SSL_CMD, "$OPENSSL x509 -text -noout  < $cert_root/certificate 2>&1 |"))
    {
        return (undef, undef, undef);
    }

    while (my $line = <SSL_CMD>) 
    {
        # always remove trailing new lines
        chomp($line);
        if ($line =~ /^\s+Subject:/o) 
        {
            # Strip off the subject;
            # Do a complicated process of getting information.  
            # It would be easier, but must account for string having
            # commas as part of the string in the string separated list
            my @tmparray = split ((/(\w+)=/), $line);
            my $c;
            for ($i = 1; $i <= $#tmparray; $i += 2) 
            {
                if ($i != ($#tmparray - 1)) 
                {
                    $c = chop $tmparray[$i+1];
                    while (!($c eq ',') && !($c eq '/')) 
                    {
                        $c = chop $tmparray[$i + 1];
                    }
                }
                $subject{$tmparray[$i]} = $tmparray[$i + 1];
            }
        } 
        elsif ($line =~ /^\s+Issuer:/o) 
        {
            $line =~ s/^\s+Issuer://;
            # Do a complicate process of getting information.  
            # It would be easier, but must account for string having
            # commas as part of the string in the string separated list
            my @tmparray = split ((/(\w+)=/), $line);
            my $c;
            for ($i = 1; $i <= $#tmparray; $i += 2) 
            {
                if ($i != ($#tmparray -1 )) 
                {
                    $c = chop $tmparray[$i + 1];
                    while (!($c eq ',') && !($c eq '/')) 
                    {
                        $c = chop $tmparray[$i + 1];
                    }
                }
                $issuer{$tmparray[$i]} = $tmparray[$i + 1];
            }
        } 
        elsif ($line =~ /Not After : (.*)$/) 
        {
            $date = $1; # the date is output from strftime in the gmt time zone
        }
    }
    close SSL_CMD;

    return \%subject, \%issuer, $date;
}

sub ssl_check_valid_cert
# Arguemnts: A certificate in PEM form.
# Returns: True for a valid certificate. False otherwise.
{
    my $cert = shift;
    # We're not intrested in output here, just the return value.
    if (not open(X509,"|$OPENSSL x509 2> /dev/null > /dev/null"))
    {
        return -1;
    }

    print X509 $cert;
    if (close(X509)) 
    {
        # Closed without errors, that's a valid certificate allright.
        return 1;
    } 
    else 
    {
        return 0;
    }
}

sub ssl_cert_check
# Arguments: A certificate root.
# Returns: True for a valid certificate/key pair. False otherwise.
# Encrypted keys are considered invalid.
{

    my $cert_dir = shift;
    # First check that all the necessary files exist.
    if (! -f "$cert_dir/certificate" || ! -f "$cert_dir/key" ) 
    {
        return 0;
    }

    # Next check if the key is encrypted.
    if (not open(KEY,"$cert_dir/key"))
    {
        return -1;
    }
    
    while (<KEY>)
    {
        if (/ENCRYPTED/o) 
        {
            close KEY;
            return 0;
        }
    }
    close KEY;
   
    print STDERR "SSL::ssl_set_identity returning success\n";
    return 1;
}

sub ssl_clear
# Arguments: A certificate root directory.
# Returns: The usual mess.
{
    my $cert_dir = shift;

    foreach my $file (qw(certificate key request))
    {
        $file = "$cert_dir/$file";
        if ( -f $file ) 
        {
            Sauce::Util::unlinkfile($file);
        }
    }
    return 1;
}

# convert an error returned by one of the subroutines in this package
# to an i18n tag of the form '[[base-ssl.errorMessage]]'
sub ssl_error
{
    my $errnum = shift;
    my $vars = shift;

    # errors about missing files or directories
    ($ret == 0) && (return "[[base-ssl.nonExistentCertDir,dir=\"$vars->{cert_dir}\"]]");
    
    # errors generated when running openssl
    ($ret == -1) && (return '[[base-ssl.cantGenerateKey]]');
    ($ret == -2) && (return '[[base-ssl.cantGenerateSigningRequest]]');
    ($ret == -3) && (return '[[base-ssl.cantGenerateCert]]');
    ($ret == -4) && (return '[[base-ssl.generatedCertInvalid]]');
}

# make sure the passed in dir exists and has the correct
# permissions
sub ssl_create_directory
{
    my ($perms, $gid, $dir) = @_;

    if (!defined($perms) || !defined($gid) || !defined($dir) || !$dir)
    {
        return 0;
    }

    if (! -d $dir)
    {
        # create the whole path if necessary and at least the last element
        $copy = $dir;
        $path = '';
        while($copy =~ s/^(\/[^\/]*)//)
        {
            $path .= $1;
            if (! -d $path)
            {
                Sauce::Util::makedirectory($path, 0755);
            }
        }
    }

    # always set permissions and chown
    Sauce::Util::chmodfile($perms, $dir);
    Sauce::Util::chownfile(0, $gid, $dir);

    return 1;
}

sub ssl_add_ca_cert
{
    my ($cert_ref, $cert_dir) = @_;

    if (!defined($cert_ref) || !$$cert_ref || !$cert_dir)
    {
        return 0;
    }

    if (!Sauce::Util::editfile("$cert_dir/ca-certs", *_edit_ca_certs,
            1,  $cert_ref))
    {
        return 0;
    }

    Sauce::Util::chmodfile(0640, "$cert_dir/ca-certs");

    return 1;
}

sub ssl_rem_ca_cert
{
    my ($cert_dir, $ca_ident) = @_;

    if (!$ca_ident)
    {
        return 0;
    }

    if (!Sauce::Util::editfile("$cert_dir/ca-certs", *_edit_ca_certs,
            0, undef, $ca_ident))
    {
        return 0;
    }

    return 1;
}

# check for 2038 rollover
sub ssl_check_days_valid
{
    my $days = shift;

    # actually check for the cutoff minus a day to be safe
    my $time_diff = (2 ** 31 - 100) - time();
    my $days_valid = int($time_diff / 86400)- 20;
    if ($DEBUG)
    {
        my @time = gmtime(time());
        print STDERR "Current GMT time is ";
        print STDERR "$time[2]:$time[1]:";
        print STDERR length($time[0]) > 1 ? $time[0] : '0' . $time[0];
        print STDERR ' ' . ($time[4] + 1);
        print STDERR "/$time[3]/" . ($time[5] + 1900) . "\n";
        @time = gmtime(2 ** 31 - 86401);
        print STDERR "GMT rollover (minus one day) is ";
        print STDERR "$time[2]:$time[1]:";
        print STDERR length($time[0]) > 1 ? $time[0] : '0' . $time[0];
        print STDERR ' ' . ($time[4] + 1);
        print STDERR "/$time[3]/" . ($time[5] + 1900) . "\n";
        print STDERR "$days days are ", ($days * 86400), " seconds\n";
        print STDERR "seconds until rollover is (minus one day) $time_diff\n";
    }

    if ($days > $days_valid) 
    { 
	return $days_valid; 
    } 
    return $days; 
}

# private functions below
sub _edit_ca_certs
{
    my ($in, $out, $add, $cert_ref, $ident) = @_;

    my $begin_section = "# $ident BEGIN";
    my $end_section = "# $ident END";

    while(<$in>)
    {
        # skip whitespace lines
        if (/^\s*$/) { next; }

        if (/^$begin_section$/)
        {
            while(<$in>)
            {
                if (/^$end_section$/) { last; }
            }
        }
        else
        {
            print $out $_;
        }
    }

    if ($add)
    {
        chomp($$cert_ref);
        print $out $$cert_ref, "\n";
    }

    return 1;
}

sub _gen_private_key
{
    my $cert_dir = shift;

    Sauce::Util::modifyfile("$cert_dir/key");
    system($OPENSSL, 'genrsa', '-out', "$cert_dir/key", '2048');
    Sauce::Util::chmodfile(0640, "$cert_dir/key");
    
    return ($? ? 0 : 1);
}

1;
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
