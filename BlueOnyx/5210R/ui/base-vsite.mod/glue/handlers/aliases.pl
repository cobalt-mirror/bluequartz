#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: aliases.pl
#
# handle any configuration for the mailAliases and webAliases fields
# like adding mail alias routes to the virtusertable and mail aliases 
# to sendmail's localhost file

use CCE;
use Sauce::Util;
use Sauce::Config;
#use Base::Httpd qw(httpd_set_server_aliases);
#use Sauce::Service qw(service_run_init);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my $vsite = $cce->event_object();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

if ($cce->event_is_create() && !$vsite->{name})
{
    $cce->bye('DEFER');
    exit(0);
}

if ($cce->event_is_destroy())
{
    # delete all aliases for this site
    my @aliases = $cce->find('ProtectedEmailAlias', 
                    { 'site' => $vsite_old->{name} });
    push @aliases, 
        $cce->find('EmailAlias', { 'site' => $vsite_old->{name} });

    for my $alias (@aliases) {
        my ($ok) = $cce->destroy($alias);
        if (!$ok) {
            $cce->bye('FAIL');
            exit(1);
        }
    }
} else {
    my %new_aliases;
    map { $new_aliases{$_} = '%1@' . $vsite->{fqdn} } 
            $cce->scalar_to_array($vsite->{mailAliases});

    # add catchall email alias
    if ($vsite->{mailCatchAll})
    {
        $new_aliases{$vsite->{fqdn}} = $vsite->{mailCatchAll};
    }
    else
    {
        $new_aliases{$vsite->{fqdn}} = 'error:nouser No such user here';
    }

    # go through new aliases and create and destroy as necessary
    for my $alias (keys(%new_aliases))
    {
        my ($oid) = $cce->find('ProtectedEmailAlias', 
                        { 
                            'site' => $vsite->{name},
                            'fqdn' => $alias,
                            'alias' => ''
                        });

        if (!$oid)
        {
            # need to create
            my ($ok) = $cce->create('ProtectedEmailAlias',
                            {
                                'site' => $vsite->{name},
                                'fqdn' => $alias,
                                'action' => $new_aliases{$alias},
                                'build_maps' => 0
                            });
            if (!$ok)
            {
                $cce->bye('FAIL', 'cantCreateMailAlias',
                            { 'alias' => $alias });
                exit(1);
            }
        }
        else
        {
            # make sure the alias is up to date
            my ($ok) = $cce->set($oid, '',
                            {
                                'site' => $vsite->{name},
                                'fqdn' => $alias,
                                'action' => $new_aliases{$alias}
                            });
            if (!$ok)
            {
                $cce->bye('FAIL', 'cantUpdateMailAlias', { 'alias' => $alias });
                exit(1);
            }
        }
    }
   
    # delete old aliases that are no longer needed
    if (exists($vsite_old->{mailAliases}))
    {
        my @old_aliases = $cce->scalar_to_array($vsite_old->{mailAliases});

        # delete old catch all if fqdn changed
        if ($vsite_old->{fqdn})
        {
            push @old_aliases, $vsite_old->{fqdn};
        }

        for my $alias (@old_aliases)
        {
            if (!exists($new_aliases{$alias}))
            {
                my ($destroy_oid) = $cce->find('ProtectedEmailAlias',
                                {
                                    'site' => $vsite->{name},
                                    'fqdn' => $alias,
                                    'alias' => ''
                                });
                my ($ok) = $cce->destroy($destroy_oid);
                if (!$ok) {
                    $cce->bye('FAIL');
                    exit(1);
                }
            }
        }
    }

    # update all user aliases associated with this site if the fqdn changed
    if (!$cce->event_is_create() && $vsite_new->{fqdn})
    {
        my @aliases = $cce->find('ProtectedEmailAlias',
                            {
                                'site' => $vsite->{name},
                                'fqdn' => $vsite_old->{fqdn}
                            });

        push @aliases, 
            $cce->find('EmailAlias',
                {
                    'site' => $vsite->{name},
                    'fqdn' => $vsite_old->{fqdn}
                });
    
        &debug_msg("oids: " . join(', ', @aliases) . "\n");
        for my $alias (@aliases)
        {
            my ($ok, $badkeys, @info) = $cce->set($alias, '', { 'fqdn' => $vsite->{fqdn} });
            &debug_msg("set $alias, ok = $ok \n");
            if (!$ok)
            {
                &debug_msg("[[base-vsite.cantUpdateUserMailAliases]]\n");
                $cce->bye('FAIL', '[[base-vsite.cantUpdateUserMailAliases]]');
                exit(1);
            }
        }
    }
}

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

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2017 Team BlueOnyx, BLUEONYX.IT
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