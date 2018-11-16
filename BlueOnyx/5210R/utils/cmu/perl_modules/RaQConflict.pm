# $Id: RaQConflict.pm

package RaQConflict;
use strict;

use Conflict;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA    = qw(Conflict); 
@EXPORT = qw($EX $IM);
@EXPORT_OK = qw();

require Archive;

1;

sub new
{
    my $proto = shift;
    my $class = ref($proto) || $proto;
    
    my $expt = shift || die "You must provide an export tree\n";
    my $impt = shift || die "You must provide an import tree\n";
    my $glbConf = shift || die "You must provide config info\n";

    my $self = Conflict->new($expt, $impt, $glbConf);
    bless ($self, $class);
    return $self;
}

sub getVsites { 
    my $self = shift; my $scope = shift;
    if(defined $self->{$scope}->{vsite}) { return $self->{$scope}->{vsite} }
    else { return \%_ }
}
sub getVsite { return($_[0]->{$_[1]}->{vsite}->{$_[2]}) }
sub getVsiteAttr { 
    return($_[0]->{$_[1]}->{vsite}->{$_[2]}->{$_[3]}) 
}

sub getFqdns
{
    my $self = shift;
    my @fqdns;
    foreach my $site (keys %{ $self->getVsites() }) {
        push(@fqdns, $site);
    }
    return @fqdns;
}

sub getVsiteDomains
{
    my $self = shift;
    my $scope = shift;
    my $attr = shift;
    my $fqdns = {};

    foreach my $site (keys %{ $self->getVsites($scope) }) {
        my $vTree = $self->getVsite($scope, $site);
        $fqdns->{$site} = 'fqdn';
        next if(!defined($vTree->{$attr}));
        if(!ref $vTree->{$attr}) {
            if($vTree->{$attr} eq 'on') {
                # this is the vsite domain name 
                $fqdns->{ $vTree->{domain} } = $attr;
            } elsif($vTree->{$attr} eq 'off') { next; } 
            else { $fqdns->{ $vTree->{$attr}->{domain} } = $attr }
        } elsif(ref $vTree->{$attr}->{domain} eq 'ARRAY') {
            foreach my $d (@{ $vTree->{$attr}->{domain} }) { 
                $fqdns->{$d} = $attr;
            }
        } 
    }
    return $fqdns;
}

sub getWebDomains { return($_[0]->getVsiteDomains($_[1], 'webdomain')); }
sub getEmailDomains { return($_[0]->getVsiteDomains($_[1], 'emaildomain')); }

sub detectVsiteFqdn
{
    my $self = shift;
    my $site = shift || return 0;
    my $sc = shift || $EX;
    my ($ret, $val);

    my $emailDoms = $self->getEmailDomains($sc);
    my $webDoms = $self->getWebDomains($sc);
    if(defined($webDoms->{$site}) || defined($emailDoms->{$site})) {
        my $reslv = Resolve->new(%{ $self->getConflict('vsiteFqdn') });
        $reslv->text("Virtual site name $site conflicts with current state\n");
        $reslv->key($site);
        return($reslv);
    } else { return 1 }
}

sub detectIpService
{
    my $self = shift;
    my $site = shift;
    my $attr = shift || return 1;
    my $sc = shift || $EX;
    
    my (%ip, $vTree);
    
    foreach my $vsite (keys %{ $self->getVsites($sc) }) {
        $vTree = $self->getVsite($sc, $vsite);
        if($vTree->{$attr} =~ /^(t|on)$/) { 
            $ip{ $vTree->{ipaddr} }  = $vTree->{fqdn};
        }
    }
    $vTree = $self->getVsite($IM, $site);
    if($vTree->{$attr} =~ /^(t|on)$/ && 
        defined $ip{ $vTree->{ipaddr} } &&
        $ip{ $vTree->{ipaddr} } ne $site) 
        {
        
        my $reslv = Resolve->new(%{ $self->getConflict('ipService') });
        $reslv->key($site);
        $reslv->attr($attr);
        if($attr eq 'ftp') { $attr = "anonymous ftp" }
        $reslv->text("$site $attr is already active at ip address $vTree->{ipaddr}\n");
        return($reslv);
    } else { return 0 }
}

sub detectDomainAlias
{
    my $self = shift;
    my $site = shift;
    my $attr = shift; 
    my $doms = shift || return 0;

    my ($reslv, $resList);
    my $vTree = $self->getVsite($IM, $site);

    if(!ref $vTree->{$attr} && $vTree->{$attr} eq 'on') {
        if(defined $doms->{ $vTree->{domain} }) {
        $reslv = Resolve->new(%{ $self->getConflict('domainAlias') });
        $reslv->text("$attr alias $vTree->{domain} conflicts in $site\n");
        $reslv->key($site);
        $reslv->attr($attr);
        return($reslv);
        } else { return 1; }
    } elsif(ref $vTree->{$attr}) {
        foreach my $d (@{ $vTree->{$attr}->{domain} }) { 
            next unless(defined($doms->{$d}));
            $reslv = Resolve->new(%{ $self->getConflict('domainAlias') });
            $reslv->text("$attr alias $d conflicts in $site\n");
            $reslv->key($site);
            $reslv->attr($attr);
            $reslv->attrValue($d);
            push(@{ $resList }, $reslv);
        }
        return($resList) if(ref $resList eq 'ARRAY');
    }
    return 1;
}

sub detectEmailDomains { 
    my $self = shift;
    my $site = shift;
    my $sc = shift || $EX;
    my $doms = $self->getEmailDomains($sc);
    return($self->detectDomainAlias($site, 'emaildomain', $doms)) 
}

sub detectWebDomains { 
    my $self = shift;
    my $site = shift;
    my $sc = shift || $EX;
    my $doms = $self->getEmailDomains($sc);
    return($self->detectDomainAlias($site, 'webdomain', $doms)) 
}


sub detectUserQuota
{
    my $self = shift;
    my $user = shift || return 0;

    my $fqdn = $self->getUserAttr($IM, $user, 'fqdn');  
    my $vsiteQuota = $self->getVsiteAttr($IM, $fqdn, 'quota');
    my $userQuota = $self->getUserAttr($IM, $user, 'quota');

    if($userQuota > $vsiteQuota) {
        my $reslv = Resolve->new(%{ $self->getConflict('userQuota') });
        $reslv->text("User ".$user."'s quota is larger than site quota\n");
        $reslv->validate('isInt');
        $reslv->attr('quota');
        $reslv->key($user);
        return($reslv);
    } else { return 1 }
}

sub removeVsite
{
    my $self = shift;
    my $site = shift || return 0;
    
    my @keys = keys %{ $self->getUsers($IM) };
    foreach my $user (@keys) {
        if($self->getUserAttr($IM, $user, 'fqdn') eq $site) {
            TreeXml::deleteNode($user, $self->{$IM}->{user}); 
        }
    }
    
    @keys = keys %{ $self->getMailLists($IM) };
    foreach my $id (@keys) {
        if($self->{$IM}->{list}->{$id}->{fqdn} eq $site) {
            TreeXml::deleteNode($id, $self->{$IM}->{list});
        }
    }
    return 1;
}

sub removeUser
{
    my $self = shift;
    my $user = shift || return 0;

    # remap user owned files in site web to admin
    my $fqdn = $self->getUserAttr($IM, $user, 'fqdn');
    # only remap archive is user is a siteadmin
    if($self->getUserAttr($IM, $user, 'admin') =~ /on|t/ && 
        $self->getConfig('confOnly') eq 'f') {
        my $arch = Archive->new(type => 'groups',
            destDir => $self->getConfig('destDir'),
            sessID => $self->{sessID},
            archives => $self->getVsiteAttr($IM, $fqdn, 'archives')
        );  
        $arch->xmlAttrConvert($user, 'admin', 'uid');
    }
    $self->removeListMember($fqdn, $user);

    return 1;
}


sub remapVsite
{
    my $self = shift;
    my $oldFqdn = shift;
    my $newFqdn = shift;

    warn "starting renaming of vsite: $oldFqdn to $newFqdn\n";
    if($newFqdn =~ /([0-9a-zA-Z\-]+)\.(.*)/) {
        $self->{$IM}->{vsite}->{$oldFqdn}->{hostname} = $1;
        $self->{$IM}->{vsite}->{$oldFqdn}->{domain} = $2;
    } else { return 0 }

    my $dTree;
    my @keys = keys %{ $self->getUsers($IM) };
    foreach my $user (@keys) {
        $dTree = $self->getUser($IM, $user);
        next if($dTree->{fqdn} ne $oldFqdn);
        $dTree->{fqdn} = $newFqdn;
    }
    
    @keys = keys %{ $self->getMailLists($IM) };
    foreach my $id (@keys) {
        if($self->{$IM}->{list}->{$id}->{fqdn} eq $oldFqdn) {
            # use remap mailList?
            $dTree = $self->getMailList($IM, $id);
            $dTree->{fqdn} = $newFqdn;
            delete $dTree->{group} if(exists $dTree->{group});
            TreeXml::renameNode(
                $oldFqdn."-".$dTree->{name}, 
                $newFqdn."-".$dTree->{name}, 
                $self->{$IM}->{list}
            );
        }
    }
            
    $self->{$IM}->{vsite}->{$oldFqdn}->{fqdn} = $newFqdn;
    TreeXml::renameNode($oldFqdn, $newFqdn, $self->{$IM}->{vsite});
    return 1;   
}


sub remapUser
{
    my $self = shift;
    my $old = shift;
    my $new = shift || return 0; 
    my (@keys, $arch);

    my $uTree = $self->getUser($IM, $old);
    my $fqdn = $uTree->{fqdn};

    if($self->getConfig('confOnly') eq 'f') {
        $arch = Archive->new(type => 'users',
            destDir => $self->getConfig('destDir'),
            sessID => $self->{sessID},
            archives => $self->getUserAttr($IM, $old, 'archives')
        );  
        $arch->xmlAttrConvert($old, $new, 'uid');
        
        if($self->getUserAttr($IM, $old, 'admin') =~ /on|t/) {
            $arch = Archive->new(type => 'groups',
                destDir => $self->getConfig('destDir'),
                sessID => $self->{sessID},
                archives => $self->getVsiteAttr($IM, $fqdn, 'archives')
            );  
            $arch->xmlAttrConvert($old, $new, 'uid');
        }

    }

    $self->remapListMember($fqdn, $old, $new);

    if(exists $self->{$IM}->{user}->{$old}) {
        $self->{$IM}->{user}->{$old}->{name} = $new;
    }
    TreeXml::renameNode($old, $new, $self->{$IM}->{user});
    return 1;
}

1;
# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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