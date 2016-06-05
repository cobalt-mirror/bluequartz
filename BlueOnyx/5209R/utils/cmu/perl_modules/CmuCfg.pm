# $Id: CmuCfg.pm

package CmuCfg;
use strict;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA    = qw(Exporter); 
@EXPORT = qw();
@EXPORT_OK = qw();

require TreeXml;
import TreeXml qw(&addNode &readXml &deleteNode);

use vars qw($export $import $scanin $scanout $adjust $conflict $stats $confirm);
my $export = {
    args => 'acd:Dhi:l:n:pv',
    maps => { 
        a => 'adminFiles',
        c => 'confOnly',
        d => 'destDir',
        D => 'dns',
        h => 'help',
        i => 'ipaddr',
        n => 'subsetNames',
        p => 'noPasswd'
    }
};

my $import = {
    args => 'AaCcd:Dhi:l:n:psS',
    maps => {
        A => 'skipAdjust',
        a => 'adminFiles',
        C => 'conflictOnly', # do not run scanin
        c => 'confOnly', # configuration files
        d => 'destDir',
        D => 'dns',
        h => 'help',
        i => 'ipaddr',
        n => 'subsetNames',
        p => 'noPasswd',
        s => 'superUsers',
        S => 'skipConflict'
    }
};

my $config = {
    args => 'a:c:f:hn:',
    maps => {
        a => 'action',
        c => 'xmlConfig',
        f => 'cmuConfig',
        h => 'help',
        n => 'name'
    }
};

my $scanout = {
    args => 'acd:Df:ghi:n:pwx',
    maps => {
        a => 'adminFiles',
        c => 'confOnly',
        d => 'destDir',
        D => 'dns',
        f => 'outFile',
        g => 'readConfig',
        h => 'help',
        i => 'ipaddr',
        n => 'subsetNames',
        p => 'noPasswd',
        w => 'webEnabled',
        x => 'express'
    }
};

my $scanin = {
    args => 'acd:Df:ghi:n:psw',
    maps => {
        a => 'adminFiles',
        c => 'confOnly',
        d => 'destDir',
        D => 'dns',
        f => 'inFile',
        g => 'readConfig',
        h => 'help',
        i => 'ipaddr',
        n => 'subsetNames',
        p => 'noPasswd',
        s => 'superUsers',
        w => 'webEnabled'
    }
};

my $adjust = {
    args => 'cd:ghs:',
    maps => {
        c => 'confOnly',
        d => 'destDir',
        g => 'readConfig',
        h => 'help',
        s => 'sessID'
    }
};

my $conflict = {
    args => 'cd:f:e:ghi:n:s:w',
    maps => {
        c => 'confOnly',
        d => 'destDir',
        f => 'resoXml',
        e => 'exData',
        g => 'readConfig',
        h => 'help',
        i => 'imData',
        s => 'sessID',
        w => 'webEnabled'
    }
};

my $stats = {
    args => 'f:l:h',
    maps => {
        f => 'outFile',
        l => 'logFile',
        h => 'help' 
    }
};

1;

sub new
# Creates a new config object you don't need to pass any args
{
    my $proto = shift;
    my $class = ref($proto) || $proto;
    my $self = {};
    $self->{opts} = {};
    $self->{glbConf} = {};
    $self->{appConf} = {};
    bless ($self, $class);
    if (@_) {
        my(%hash) = (@_);
        while(my($name,$val) = each %hash) { $self->{$name} = $val; }
    }
    $self->setSignals;
    #if($self->{type} !~ /^(export|import)$/) { 
    #   $self->putLockFile('/home/cmu/cmu.lock');
    #   $self->createLock; 
    #}
    return $self;
}

sub setSignals
{
    my $self = shift;
    $SIG{INT} = sub { $self->signalINT() };
    $SIG{TERM} = sub { $self->signalINT() };
    $SIG{HUP} = sub { $self->signalHUP() };
    $SIG{USR1} = sub { $self->signalUSR1() };
    
}

sub signalINT {
    my $self = shift;
    # don't dump the parents
    return if($self->{type} =~ /^(export|import)$/);
    warn "ERROR Dumping debug to ", $self->logFile;
    warn "WARN ", $self->dumpDebug;
    warn "ERROR Exiting...\n";
    exit 1;
}

sub signalHUP {
    my $self = shift;
    warn "ERROR Got HUP signal, I am not a daemon.\n";
    return;
}

sub signalUSR1 {
    my $self = shift;
    
    return if($self->{type} =~ /^(export|import)$/);
    warn "INFO Got USR1 signal, sleeping for 60 seconds\n";
    sleep(60);
    return;
}

# isFunc are used to test existance while ifFunc are used for true false vals
sub glb { return $_[0]->{glbConf}->{$_[1]} }
sub isGlb {
    my $self = shift; my $attr = shift;
    (defined $self->{glbConf}->{$attr}) ? (return 1) : (return 0);
}
sub putGlb { 
    my $self = shift; my $attr = shift; my $val = shift;
    $self->{glbConf}->{$attr} = $val
}

# accessors for the libs
sub lib { return $_[0]->{glbConf}->{libs}->{$_[1]} }
sub isLib {
    my $self = shift; my $lib = shift;
    (defined $self->{glbConf}->{libs}->{$lib}) ? (return 1) : (return 0);
}

# accessors for some frequently used params
# destDir is used a lot of places, this the source or destination
sub destDir { return $_[0]->glb('destDir') }
sub putDestDir { $_[0]->putGlb('destDir', $_[1]) }
sub isDestDir { ($_[0]->isGlb('destDir')) ? (return 1) : (return 0) }

# isSubSet used for exporting and importing a subset of vsites
sub isSubSet {
    my $self = shift;
    my $attr = shift || 0;
    if($attr) {
        (defined $self->{glbConf}->{subsetNames}->{$attr}) ? (return 1) : (return 0);
    } else { return $self->isGlb('subsetNames') }
}

# ipaddr is used to remap ip address on import and export
sub ipaddr { return $_[0]->glb('ipaddr') }
sub isIpaddr { ($_[0]->isGlb('ipaddr')) ? (return 1) : (return 0) }

# session ID is used for import and keeping the original files around.
sub sess { return $_[0]->glb('sessID') }
sub putSess { $_[0]->putGlb('sessID', $_[1]) }
sub isSess { ($_[0]->isGlb('sessID')) ? (return 1) : (return 0) }

# logFile is where all info is logged
sub logFile { return $_[0]->glb('logFile') }
sub putLogFile { $_[0]->putGlb('logFile', $_[1]) }

sub lockFile { return $_[0]->glb('lockFile') }
sub putLockFile { $_[0]->putGlb('lockFile', $_[1]) }

# this method is used to get the default cmu.xml file location
sub cmuXml {
    my $self = shift;
    return $self->destDir."/".$self->glb('cmuXml');
}
# same as above but with sessID
sub sessXml {
    my $self = shift;
    return $self->destDir."/".$self->glb('cmuXml').".".$self->sess;
}

sub cmuUser { return $_[0]->glb('cmuUser') }
sub cmuGroup { return $_[0]->glb('cmuGroup') }

sub noPasswd { return $_[0]->glb('noPasswd') }
sub superUsers { return $_[0]->glb('superUsers') }
sub confOnly { return $_[0]->glb('confOnly') }
sub adminFiles { return $_[0]->glb('adminFiles') }
sub webEnabled { return $_[0]->glb('webEnabled') }
sub skipAdjust { return $_[0]->glb('skipAdjust') }
sub skipConflict { return $_[0]->glb('skipConflict') }
sub conflictOnly { return $_[0]->glb('conflictOnly') }
sub dns { return $_[0]->glb('dns') }

sub createLock {
    my $self = shift;
    my $file = shift || $self->lockFile;
    if(-f $file) {
        warn "ERROR Cannot run more than one process at a time\n";
        warn "ERROR Lock file detected: $file\n";
        exit 1;
    } else { qx(/bin/touch $file) }
}

sub removeLock {
    my $self = shift;
    my $file = shift || $self->lockFile;
    if(-f $file) {
        unlink $file;
    } else { warn "WARN Lock does not exsit: $file\n"; }
}

sub addDebug {
    my $self = shift;
    my $data = shift || return;
    push @{ $self->{debug} }, $data;
}

sub dumpDebug {
    my $self = shift;
    my $text;

    return "No info in Debug\n"  if(defined $self->{debug} == 0);
    require Data::Dumper;
    foreach my $bug (@{ $self->{debug} }) {
        $text .= Data::Dumper::Dumper($bug);        
    }
    return $text;
}

sub opt { return $_[0]->{opts}->{$_[1]} }
sub isOpt {
    my $self = shift; my $attr = shift;
    (defined $self->{opts}->{$attr}) ? (return 1) : (return 0);
}
sub putOpt {
    my $self = shift; my $attr = shift; my $val = shift;
    $self->{opts}->{$attr} = $val
}

sub type { return $_[0]->{type} }
sub putType { $_[0]->{type} = $_[1] }

sub readConf 
# Pulls the core cpr xml and gets all the nodes
# Arguments: file name
# Side Effects: reads all other files listed in cmu config file and
# places the app data in $self->{apps} and the glbConf stuff in 
# $self->{glbConf}
{
    my $self = shift;
    my $configFile = shift || '/etc/cmu/cmuConfig.xml'; 
    
    warn "Reading config file: $configFile\n";
    my $appList = TreeXml::readXml($configFile, 0);
    
    my @apps = keys %{ $appList };
    foreach my $app (@apps) {
        my $appData = TreeXml::readXml($appList->{$app}->{configFile}, 0);
        TreeXml::addNode('configData', $appData, $appList->{$app});
    }
    $self->{glbConf} = $appList->{cobaltBase}->{configData};
    delete $appList->{cobaltBase};
    $self->{appConf} = $appList;
}

sub readGlbConf
{
    my $self = shift;
    my $cobaltBase = shift || '/etc/cmu/cobaltBase.xml';

    $self->{glbConf} = TreeXml::readXml($cobaltBase, 0);
}

sub getMap
{
    my $self = shift;
    my $attr = shift;
    
    if($self->type() eq 'import') { return $import->{$attr} }
    elsif($self->type() eq 'export') { return $export->{$attr} }        
    elsif($self->type() eq 'scanout') { return $scanout->{$attr} }      
    elsif($self->type() eq 'scanin') { return $scanin->{$attr} } 
    elsif($self->type() eq 'adjust') { return $adjust->{$attr} }
    elsif($self->type() eq 'config') { return $config->{$attr} }
    elsif($self->type() eq 'stats') { return $stats->{$attr} }
    elsif($self->type() eq 'conflict') { return $conflict->{$attr} }
    else { die "CmuCfg->getMap Cannot map ", $self->type(), "\n" }
}   

sub parseOpts 
{
    my $self = shift;

    require Getopt::Std; 
    Getopt::Std::getopts($self->getMap('args'), $self->{opts});

    if($self->isOpt('h')) { 
        my $func = $self->type()."Help";
        $self->$func();
    } 
    if($self->type ne 'import' && $self->type ne 'export' && $self->type ne 'config') {
        if($self->isOpt('g')) { $self->readGlbConf(); }
        else {
            my $confXml;
            while (my $line = <STDIN>) { $confXml .= $line; }
            $self->{glbConf} = TreeXml::readXmlStream($confXml, 0);
        }
    }
    $self->mapOpts();
    if($self->isGlb('subsetNames')) { $self->parseNames('subsetNames') }
}   


sub mapOpts
{
    my $self = shift;
    my $oHash = $self->{opts};

    my $mapping = $self->getMap('maps');

    foreach my $o (keys %{ $oHash }) {
        unless($mapping->{$o}) {
            warn "Invalid option: -$o ", $mapping->{$o}, "\n";
            next;
        }
        # change 1 to true
        $oHash->{$o} = 't' if($oHash->{$o} eq '1');
        $self->putGlb($mapping->{$o}, $oHash->{$o});
    }
}

sub parseNames
# used to convert a space or comma deliminated option into a hash
# arguments: global config hash, attribute name
# returns: hash of the opts
{
    my $self = shift;
    my $attr = shift;
    
    return if(ref($self->{glbConf}->{$attr}) eq 'HASH');
    my $subset = $self->{glbConf}->{$attr};
    delete $self->{glbConf}->{$attr};
    if($subset =~ /[\s,&]/) {
        foreach my $site (split(/[\s,&]/, $subset)) {
            next unless($site);
            $self->{glbConf}->{$attr}->{$site} = 1; 
        }
    } else { $self->{glbConf}->{$attr}->{$subset} = 1 }
}

sub checkVersion
# arguments: data tree, current version
# returns 1 for good, 0 for bad
{
    my $self  = shift;
    my $importVer = shift || return 0;
    my $reqVer = $self->glb('versionRequired');

    $importVer =~ /^(\d+)\.(\d+)$/;
    my $imptMajor = $1;
    my $imptMinor = $2;
    
    $reqVer =~ /^(\d+)\.(\d+)$/;
    my $reqMajor = $1;
    my $reqMinor = $2;
    
    if($reqMajor > $imptMajor || $reqMinor > $imptMinor) {
    die "Export was created with version ", $importVer, 
        " you must re-export with version ", $reqVer, " or later\n";
    }
    return 1;
}

sub removeNamesVsite
# used to remove names of the attribute out of the import config data
# arguments: $tree, global config hash of attribute name
# returns the tree
{
    my $self = shift;
    my $tree = shift;   
    my $attr = shift || 'subsetNames';

    if(!defined $tree->{vsite}) {
        die "No virtual sites in import file, cannot continue (-n option)\n";
    }
    
    my @names;
    my @keys = keys %{ $tree->{vsite} };
    foreach my $fqdn (@keys) {
        if(defined $self->{glbConf}->{$attr}->{$fqdn}) {
            $self->{glbConf}->{$attr}->{$fqdn} = 'found';
            next;
        }
        TreeXml::deleteNode($fqdn, $tree->{vsite});
        if(defined $tree->{user}) {
            @names = keys %{ $tree->{user} };
            foreach my $user (@names) {
                TreeXml::deleteNode($user, $tree->{user}) 
                    if($tree->{user}->{$user}->{fqdn} eq $fqdn);
            }
        }
        if(defined $tree->{list}) {
            @names = keys %{ $tree->{list} };
            foreach my $list (@names) {
                TreeXml::deleteNode($list, $tree->{list}) 
                    if($tree->{list}->{$list}->{fqdn} eq $fqdn);
            }
        }   
    }
    @keys = keys %{ $self->{glbConf}->{$attr} };
    foreach my $fqdn (@keys) {
        warn "ERROR: Could not find virtual site name $fqdn in cmu.xml file\n" 
            if($self->{glbConf}->{$attr}->{$fqdn} eq 1);
    }
    @keys = keys %{ $tree };
    foreach my $key (@keys) {
        next unless(ref $tree->{$key} eq 'HASH');
        unless(keys %{ $tree->{$key} }) {
            delete $tree->{$key};
        }
    }
    return $tree;
}

sub convertIpaddr 
{
    my $self = shift;
    my $tree = shift;

    my @keys = keys %{ $tree->{vsite} };
    foreach my $fqdn (@keys) {
        $tree->{vsite}->{$fqdn}->{ipaddr} = $self->ipaddr;
    }
    return $tree;
}

# Here we have the help files fer all the script types
# this is to save placing each one of these in 8 different files

sub exportHelp
{
    print <<EOF;
usage:   cmuExport [OPTIONS] 
         -a export admin's files
         -c export configuration only
         -d build directory, this is where export will place all exported files, the default is /home/cmu/FQDN
         -i export all virtual sites with this IP address
         -n export these virtual sites, ie "www.foo.com,www.bar.com"
         -p do not export user passwords
         -v verbose, print all messages to stdout
         -h help, this help text
EOF

    exit 1;
}

sub importHelp
{
    print <<EOF;
usage:   cmuImport [OPTION]
         -a import admin's files
         -c import configuration only
         -d directory of that contains the exported files
         -p do not import user passwords, userPasswd in /etc/cmu/cobaltBase.xml will be used instead
         -s use session id #####
         -i import all virtual sites with this IP address
         -n import only these sites ie -n "ftp.foo.com,www.bar.com"
         -A skip adjust script, this is dangerous
         -S skip conflict resolution
         -C skip scanin script, this is useful for see what conflict exsist
         -h help, this help text
EOF
    exit 1;
}

sub scanoutHelp
{
    print <<EOF;
usage:   $0 [OPTIONS] 
         -a export the user admin files
         -c export configuration only
         -d build directory, this is where export will place all exported files, the default is /home/cmu/FQDN 
         -f the file where the output xml is placed
         -g read the config info from /etc/cmu/, you must use this option if calling this script directly
         -i export all virtual sites with this IP address 
         -n export these virtual sites, ie "www.foo.com,www.bar.com"
         -p do not export user passwords
         -h help, this help text
EOF

    exit 1;
}

sub scaninHelp
{
    print <<EOF;
usage:   $0 [OPTIONS] 
         -a import the user admin files
         -c import configuration only
         -d build directory, this is where the import files are located
         -f the file where the in xml is located
         -g read the config info from /etc/cmu/, you must use this option if calling this script directly
         -i import all virtual sites with this IP address
         -n import only these sites ie -n "ftp.foo.com,www.bar.com"
         -p do not import user passwords
         -h help, this help text
EOF

    exit 1;
}

sub adjustHelp
{
    print <<EOF;
usage:   $0 [OPTIONS] 
         -g read the config info from /etc/cmu/, you must use this option if calling this script directly
         -s session id
         -h help, this help text
EOF

    exit 1;
}

sub conflictHelp
{
    print <<EOF;
usage:   $0 [OPTIONS] 
         -f file location of the resloved xml data
         -e file location of the export xml data
         -i file location of the import xml data
         -g read the config info from /etc/cmu/, you must use this option if calling this script directly
         -s session id
         -h help, this help text

EOF

    exit 1;
}

sub configHelp
{
    print <<EOF;
usage:   $0 [OPTIONS] 
         -a action (add|del)
         -f config file name (default /etc/cmu/cmuConfig.xml)
         -n name of the third party application (must be unique)
         -c locatation of the config xml script 
         -h help, this help text
EOF

    exit 1;
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