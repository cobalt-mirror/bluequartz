#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: log_account.pl
#
# manages the per-site log user account and ServiceQuota object
# for site-level split logs and usage stats

use CCE;
use Sauce::Config;
use Sauce::Util;
use Base::User qw(useradd userdel);

my $DEBUG = 0;
$DEBUG && warn `date`."$0\n";

# make sure umask is sane, we create and chmod dirs here
umask(002);

my $cce = new CCE;
$cce->connectfd();

my $err; # Global error message/return state

# We're triggered on Vsite create/mod/edit
my $oid = $cce->event_oid(); 
my $obj = $cce->event_object(); # Vsite
my $obj_new = $cce->event_new();
my $obj_old = $cce->event_old();

# Find matching ServiceQuota objects
my $sitegroup = $obj_old->{name};
$sitegroup ||= $obj->{name};
my @oids = $cce->find('ServiceQuota', {
    'site' => $sitegroup,
    'label' => '[[base-sitestats.statsQuota]]',
    }); 

if($cce->event_is_destroy())
{
    # destroy the associated ServiceQuota object
    $DEBUG && warn "Deleting ServiceQuota objects:\n";

    foreach my $i (@oids)
    {
        my ($ret, @info) = $cce->destroy($i);
        $err .= '[[base-sitestats.couldNotClearStatsQuotaMon]]' 
            unless ($ret);
        $DEBUG && warn "destroy $i $ret\n";
    }
    
    # Delete the site logs user
    my $user = &group_to_user($sitegroup);
    
    if(getpwnam($user))
    {
        # delete user, no need to tell userdel to remove dir
        # vsite destroy will take care of that
        userdel(0, $user);
    }
} 
elsif ($cce->event_is_create())
{
    # make sure vsite_create.pl has created the system group already
    if (!$obj->{name})
    {
        $cce->bye('DEFER');
        exit(0);
    }

    # create a ServiceQuota object
    $DEBUG && warn "Creating ServiceQuota object\n";

    $owner = &group_to_user($sitegroup);

    my($ret) = $cce->create('ServiceQuota', { 
        'label' => '[[base-sitestats.statsQuota]]',
        'site' => $obj->{name},
        'account' => $owner,
        'isgroup' => 0,
        'quota' => 20,
        'used' => 0,
        });
        
    $err .= '[[base-sitestats.couldNotSetStatsQuotaMon]]' 
        unless ($ret);
    
    ($ret) = $cce->set($oid, 'SiteStats', { 
        'owner' => $owner,
        });
        
    $err .= '[[base-sitestats.couldNotSetStatsQuotaMon]]' 
        unless ($ret);

    # Create the site logs user
    my $user = {
                    'comment' => $obj->{fqdn},
                    'homedir' => $obj->{basedir}.'/logs',
                    'group' => $obj->{name},
                    'shell' => Sauce::Config::bad_shell(),
                    'name' => $owner,
                    };

    # this also creates the logs directory
    if (!(useradd($user))[0])
    {
        $err .= '[[base-sitestats.couldNotCreateStatsUser]]';
    }
    else
    {
        # make sure the dir permissions are correct
        Sauce::Util::chmodfile(02751, $user->{homedir});
    }
}

if($err)
{
    $cce->bye('FAIL', $err);
    exit 1;
}
else
{
    $cce->bye('SUCCESS');
    exit 0;
}


sub group_to_user
{
    my $x = $_[0];
    $x =~ tr/[a-z]/[A-Z]/;
    return $x.'-logs'; 
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