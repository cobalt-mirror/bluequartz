#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/email
# $Id: validate_alias.pl
#
# check for alias collision with other aliases and with system accounts
# this doesn't resolve conflicts with aliases in /etc/mail/aliases, because
# it assumes that if the local_alias property is set that whoever is doing
# it knows what they are doing
#

use CCE;
use POSIX qw(isalpha);
use Email;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

# reserved system accounts to check for
%reserved = map { $_ => 1 } qw(
mailer-daemon
bin
daemon
ingres
system
toor
uucp
dumper
decode
nobody
root
);

# Removed the following:
#abuse
#postmaster
#operator
#manager

$cce = new CCE('Domain' => 'base-email');
$cce->connectfd();

$obj = $cce->event_object();
$new = $cce->event_new();

$lcname = $obj->{alias}; 
$lcname =~ tr/A-Z/a-z/;

if (exists($new->{alias}) || exists($new->{fqdn})) {
    #verify uniquness

    &debug_msg("Start of Email Alias validation.\n");

    $fail = 0;
    $find_criteria = { 
                'alias' => &build_regi($obj->{alias}),
                'fqdn' => &build_regi($obj->{fqdn})
                };

    @oids = $cce->findx('EmailAlias', {}, $find_criteria);
    @poids = $cce->findx('ProtectedEmailAlias', {}, $find_criteria);

    #
    # find returns the object being modified, so if the sum
    # is more than one there's a problem
    #
    if ((scalar(@oids) + scalar(@poids)) > 1) { 
        &debug_msg("oids: @oids\n");
        &debug_msg("poids: @poids\n");
        $fail = 1; 
        &debug_msg("fail: $fail\n");
    }
  
    # ignore the reserved names if this is a ProtectedEmailAlias
    if ($obj->{CLASS} eq 'EmailAlias' && $reserved{$lcname}) {
        $fail = 2; 
        &debug_msg("fail: $fail\n");
    }
    if ($fail) {
        &fail($cce, $obj->{alias}, $fail);
    }
}

#
# get the system's fqdn, since there can be a vsite with the same fqdn
# as the system
#
($soid) = $cce->find('System');
($ok, $sys) = $cce->get($soid);
if (!$ok) {
    $cce->bye('FAIL', '[[base-email.cantReadSystem]]');
    exit(1);
}

$sys_fqdn = $sys->{hostname} . '.' . $sys->{domainname};

# make sure there aren't any conflicts involving a vsite with the same
# fqdn as the system if the alias and action are not the same
@conflicts = ();
if ($obj->{alias} ne $obj->{action}) {
    if (!$obj->{fqdn}) {
        $find_criteria = {
                    'alias' => &build_regi($obj->{alias}),
                    'fqdn' => &build_regi($sys_fqdn)
                    };
                            
        @conflicts = $cce->findx('EmailAlias', {}, $find_criteria);
        push @conflicts, $cce->findx('ProtectedEmailAlias', {},
                         $find_criteria);
    
        if (scalar(@conflicts)) {
            &fail($cce, $obj->{alias}, 2);
        }
    }
    elsif ($obj->{fqdn} eq $sys_fqdn) {
        $find_criteria = {
                    'alias' => &build_regi($obj->{alias}),
                    };
    
        @conflicts = $cce->findx('EmailAlias', { 'fqdn' => '' }, $find_criteria);
        push @conflicts, $cce->findx('ProtectedEmailAlias', { 'fqdn' => '' }, $find_criteria);
    
        if (scalar(@conflicts)) {
            &fail($cce, $obj->{alias}, 2);
        }
    }
}

# if the alias and action are not the same, make sure there is
# not another user in CCE whose name is equal to the alias
if ($obj->{alias} ne $obj->{action}) {
    $regex_criteria = { 
                'name' => &build_regi($obj->{alias})
                 };
    
    # if this isn't a local alias, restrict the search to the same site.
    $exact_criteria = {};
    if (!$obj->{local_alias}) {
        $exact_criteria->{'site'} = $obj->{site};
    }

    @conflicts = $cce->findx('User', $exact_criteria, $regex_criteria);

    # if the fqdn of the alias is equal to the system fqdn, check for
    # system users
    if ($obj->{fqdn} eq $sys_fqdn) {
        $exact_criteria->{'site'} = '';
        push @conflicts, $cce->findx('User', $exact_criteria, $regex_criteria);
    }

    if (scalar(@conflicts)) {
        &fail($cce, $obj->{alias}, 2);
    }
}

&debug_msg("Final fail status before exit: $fail\n");

$cce->bye('SUCCESS');
exit(0);

sub fail
{
    ($cce, $alias, $code) = @_;
    $cce->warn('aliasInUse', { 'name' => $alias, 'code' => $code });
    $cce->bye('FAIL');
    exit(1);
}

#
# build a case insensitive posix regex to pass to findx
# this is stuff that must pass through smtp, so we assume ascii since
# that is all that is generally accepted for smtp still (see RFC 2821)
#
sub build_regi
{
    $string = shift;
    
    $regex = '';
    for ($i = 0; $i < length($string); $i++) {
        $char = substr($string, $i, 1);
        
        if (!isalpha($char)) {
            # not an alphbetic char, see if it should be escaped
            $char =~ s/([\^\\\+\-\.\?\{\}\(\)\$])/\\$1/;
            $regex .= $char;
        }
        else {
            # alphabetical, add lower and upper case
            $regex .= '[' . lc($char) . uc($char) . ']';
        }
    }

    # always want exact matching here
    return "^$regex\$";
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
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 