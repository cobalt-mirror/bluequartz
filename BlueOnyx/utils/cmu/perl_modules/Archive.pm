# $Id: Archive.pm

package Archive;
#use strict;

# Method of how to create TarBalls:
# internal: Archive::Tar
# external: tar system command
if (-e "/usr/bin/pigz") {
    $TARMETHOD = "external";
}
else {
    $TARMETHOD = "internal";
}

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA = qw(Exporter);
@EXPORT = qw();
@EXPORT_OK = qw();
    
use vars qw($VERSION $XMLPROLOG $XMLHEADER);
require TreeXml;
import TreeXml qw($VERSION $XMLPROLOG $XMLHEADER);
    
require Global;
import Global qw(&getBuild &memInfo);
    
require Archive::Tar;

require MIME::Base64;
import MIME::Base64 qw(decode_base64 encode_base64);
    
use vars qw($archConf $archBuild);
use File::stat;

use Data::Dumper;

$archConf = {
    findBin     =>  'find', 
    tarBin      =>  '/bin/tar',
    gzipBin     =>  '/bin/gzip',
    cpBin       =>  '/bin/cp',
    catBin      =>  '/bin/cat',
    chownBin    =>  '/bin/chown',
    md5Bin      =>  '/usr/bin/md5sum',
    baseName    =>  '/bin/basename',
    lnBin       =>  '/bin/ln -s',
    tmpDir      =>  '/home/tmp',
    mailSpool   =>  '/var/spool/mail',
    maxSize     =>  '1000', # never set this below 101
    dflUser     =>  'admin',
    dflGroup    =>  'users',
    debug       =>  0
};

$archBuild = Global::getBuild();

1;

sub new
{
    my $proto = shift;
    my $class = ref($proto) || $proto;
    my $self  = {};

    bless($self, $class);

    $self->{_build} = $archBuild;
    $self->{_conf} = $archConf;
    $self->{name} = undef;
    $self->{archives} = {};

    if (@_) {
        my(%hash) = (@_);
        while(my($name,$val) = each %hash) { $self->{$name} = $val }
    }
    return $self;

}

sub build { return $_[0]->{_build} }
sub setBuild { $_[0]->{_build} = $_[1] }

sub cfg { return $_[0]->{_conf}->{$_[1]} }
sub setCfg { $_[0]->{_conf}->{$_[1]} = {$_[2]} }

sub type { return $_[0]->{type} }
sub setType { $_[0]->{type} = $_[1] }

sub name { return $_[0]->{name} }
sub setName { $_[0]->{name} = $_[1]; $_[0]->clearArchive }

sub destDir { return $_[0]->{destDir} }
sub setDestDir { $_[0]->{destDir} = $_[1] }

sub set { return $_[0]->{set} }
sub setSet { $_[0]->{set} = $_[1] }

sub baseDir { return $_[0]->{baseDir} }
sub setBaseDir { $_[0]->{baseDir} = $_[1] }

sub gid { return $_[0]->{gid} }
sub setGid { $_[0]->{gid} = $_[1] }

sub ignore { return($_[0]->{ignore}) }
sub setIgnore { 
    my $self = shift;
    @{ $self->{ignore} } = @_; 
}
sub addIgnore { push(@{ $_[0]->{ignore} }, $_[1]) }
sub popIgnore { pop(@{ $_[0]->{ignore} }) }

sub sessID { return $_[0]->{sessID} }
sub setSessID { $_[0]->{sessID} = $_[1] }

sub tarName { return $_[0]->{tarName} }
sub setTarName { $_[0]->{tarName} = $_[1] }
sub buildTarName {
    my $self = shift;
    my $span = shift;
    my $tarName = $self->destDir."/";

    if($span) {
        $tarName .= $span.".".$self->type."-".$self->name."-".$self->set;
    } else { $tarName .= $self->type."-".$self->name."-".$self->set; }

    $tarName .= ".tar.gz";
    return $tarName;
}

sub xmlName { return $_[0]->{xmlName} }
sub setXmlName { $_[0]->{xmlName} = $_[1] }
sub buildXmlName {
    my $self = shift;
    my $span = shift;
    my $xmlName = $self->destDir."/";

    if($span) {
        $xmlName .= $span.".".$self->type."-".$self->name."-".$self->set;
    } else { $xmlName .= $self->type."-".$self->name."-".$self->set; }

    $xmlName .= ".xml";
    return $xmlName;
}

sub archives { return $_[0]->{archives} }
sub addArchive {
    my $self = shift;

    my $set = $self->set;
    my $xmlName = $self->baseName($self->xmlName);
    push @{ $self->{archives}->{$set} }, $xmlName;
}
sub setArchive { $_[0]->{archives} = $_[1] }
sub clearArchive { $_[0]->{archives} = {} }

sub buildTar
# used for creating two different tars for a user
# Arguments: user
{
    my $self = shift;
    my ($ret, $homeDir); 

    # Sun 05 Feb 2012 04:02:51 AM CET (mstauber): Added php.ini to the ignore list. On cmuImport when the site is created,
    # CCE will create a php.ini already if suPHP is enabled. Extracting the php.ini from the site's private tarball will
    # then fail, as the CCE created php.ini is protected with chattrib anyway. Otherwise we'd run into an ugly error.
    $self->setIgnore(qw(.bash_history _vti .forward .vacation_msg php.ini));
    if($self->type eq "users") {
        $homeDir = (getpwnam($self->name))[7];
    } elsif($self->type eq "groups") {
        if($self->build =~ /^Qube/) {
            $homeDir = "/home/groups/".$self->name;
        } elsif($self->build =~ /^RaQ550$/ || $self->build =~ /^5100R$/ || $self->build =~ /^510[6-8]R$/ || $self->build =~ /^520[7-9]R$/ || $self->build =~ /^516[0-1]R$/ || $self->build =~ /^5200R$/ || $self->build =~ /^TLAS1HE$/ || $self->build =~ /^TLAS2$/) {
            $homeDir = $self->{baseDir};
        } elsif($self->build =~ /^RaQ/) {
            $homeDir = "/home/sites/".$self->name;
        } else {
            warn "ERROR Cannot find build type: ", $self->build, "\n";
            return 0;
        }
    }
    warn "homeDir is: ", $homeDir, "\n";
        
    if(! -d $homeDir) {
        warn "ERROR Home directory does not exists\n";
        return 0;
    }
        
    # get the public web stuff
    # Change the dirs depending on which Qube we are on
    if($self->build eq "Qube2") {
        $self->setBaseDir($homeDir);
        $self->addIgnore("private");
    } else { $self->setBaseDir($homeDir."/web"); }

    if(-d $self->baseDir) {
        $self->setSet("public");
        $self->buildArchive();
    }

    # private files
    if($self->build eq "Qube2") {
        $self->popIgnore();
        $self->addIgnore(".htaccess");
        $self->setBaseDir($homeDir."/private");
    } else { 
        $self->addIgnore("web");
        if(($self->build =~ /^RaQ550$/ || $self->build =~ /^5100R$/ || $self->build =~ /^510[6-8]R$/ || $self->build =~ /^520[7-9]R$/ || $self->build =~ /^516[0-1]R$/ || $self->build =~ /^5200R$/ || $self->build =~ /^TLAS1HE$/ || $self->build =~ /^TLAS2$/) && $self->type eq "groups") {
            $self->addIgnore("users");
            $self->addIgnore(".users");
        }   
        $self->setBaseDir($homeDir);
    }
        
    # Not so dirty hack to get the mail file
    # mstauber Sun May 24 18:31:25 2009
    # Whoever coded the initial version of this didn't take into account that the spools do not always reside under /var/spool/mail/<username>
    # I can't use a platform check here or Perl goes mad at me. So I instead check if we have an ~<username>/mbox file first and use that one.
    # If we don't find one there, then we use /var/spool/mail/<username> instead. This should still cover all platforms.
    if($self->type eq "users" && -f $self->baseDir."/mbox") {
        my $mailFile = $self->baseDir."/mbox";
        warn "INFO: Using ", $mailFile, " as mailspool.\n" if($self->cfg('debug'));
        if(-f $mailFile) {
            #my $cpCmd = $self->cfg('cpBin')." -p ".$mailFile." ".$self->baseDir."/cmu-mailspool";
            my $cpCmd = $self->cfg('cpBin')." ".$mailFile." ".$self->baseDir."/cmu-mailspool";
            qx/$cpCmd/;
            warn "INFO: Using command ", $cpCmd, " to create copy of mailspool.\n" if($self->cfg('debug'));
        }
    }
    elsif($self->type eq "users" && -f $self->cfg('mailSpool')."/".$self->name) {
        my $mailFile = $self->cfg('mailSpool')."/".$self->name;
        warn "INFO: Using", $mailFile, " as mailspool.\n" if($self->cfg('debug'));
        if(-f $mailFile) {
            #my $cpCmd = $self->cfg('cpBin')." -p ".$mailFile." ".$self->baseDir."/cmu-mailspool";
            my $cpCmd = $self->cfg('cpBin')." ".$mailFile." ".$self->baseDir."/cmu-mailspool";
            qx/$cpCmd/;
            warn "INFO: Using command ", $cpCmd, " to create copy of mailspool.\n" if($self->cfg('debug'));
        }
    }
    # get the private stuff
    if(-d $self->baseDir) {
        $self->setSet("private");
        $self->buildArchive();
    }
    if($self->type eq "users" && -f $self->baseDir."/cmu-mailspool") {
        unlink($self->baseDir."/cmu-mailspool");
    }
        
    return 1;
}

sub buildArchive 
{
    my $self = shift;
    my $cursive = shift || 0;
    my $fList = $self->getFileList();

    if(!defined $fList->{totalSize}) {
        warn "WARN No files to archive for ", $self->name, "\n";
        return;
    }

    my $cnt = 1;
    my $curSize = 0;
    my $fSpan = {};         
    warn "INFO: ", $self->set, " archive uncompressed size: ", $fList->{totalSize}, " MB\n";
    if($fList->{totalSize} > $self->cfg('maxSize') && !$cursive) {
        foreach my $f (@{ $fList->{file} }) {
            $curSize += $f->{size}/1048576;
            push(@{ $fSpan->{file} }, $f);
            if($curSize > ($self->cfg('maxSize') - 100)) {
                if ($TARMETHOD eq "internal") {
                    $self->createTar($fSpan, $cnt, 1);
                }
                else {
                    $self->createTarNew($fSpan, $cnt, 1);
                }
                $cnt++; $fSpan = {}; $curSize = 0;
            }
        }
        if ($TARMETHOD eq "internal") {
            $self->createTar($fSpan);
        }
        else {
            $self->createTarNew($fSpan);
        }
    }
    else {
        if ($TARMETHOD eq "internal") { 
            $self->createTar($fList); 
        }
        else {
            $self->createTarNew($fList); 
        }
    }

    return 1;
}

sub createTar
{
    my $self = shift;
    my $fHash = shift;
    my $span = shift;

    if($span) {
        $self->setTarName($self->buildTarName($span));
        $self->setXmlName($self->buildXmlName($span));
    } else {
        $self->setTarName($self->buildTarName);
        $self->setXmlName($self->buildXmlName);
    }

    my $tarName = $self->tarName;
    my $baseDir = $self->baseDir;
    chdir "$baseDir" || return "Could not chdir to $baseDir: $!\n";

    my @files;
    my $file_list;
    foreach my $f (@{ $fHash->{file} }) {
        push(@files, $f->{name});
        $file_list .= $f->{name} . "\n";
    }
       
    warn "creating tar (internal TAR): $tarName with ", scalar @files, " files\n";
    my $tar = Archive::Tar->new();
    $tar->create_archive($tarName, 2, @files);
    my $err = $tar->error();
    if($err) { 
        warn "ERROR createTar: $err\n"; 
    } else { 
        $self->writeXml($fHash);    
        $self->addArchive;
    }
}

sub createTarNew
{
    my $self = shift;
    my $fHash = shift;
    my $span = shift;

    if($span) {
        $self->setTarName($self->buildTarName($span));
        $self->setXmlName($self->buildXmlName($span));
    } else {
        $self->setTarName($self->buildTarName);
        $self->setXmlName($self->buildXmlName);
    }

    my $tarName = $self->tarName;
    my $baseDir = $self->baseDir;
    chdir "$baseDir" || return "Could not chdir to $baseDir: $!\n";

    my @files;
    my $file_list;
    foreach my $f (@{ $fHash->{file} }) {
        if (-d $f->{name}) {
            my $dirtest = `ls -al $f->{name} | wc -l`;
            chomp($dirtest);
            if ($dirtest eq '3') {
                # Empty directory. Not skipping.
                #warn "Not skipping dir $f->{name}";
            }
            else {
                # Directory not empty. So we have it's files in the file-list. Skipping directory name.
                #warn "Skipping dir $f->{name}";
                next;
            }
        }
        push(@files, $f->{name});
        $file_list .= $f->{name} . "\n";
    }

    # Write tempfile with $file_list:
    $fileListTmp = "/tmp/cmu-files.dat";
    warn "Creating $fileListTmp\n";
    open(my $fh, '>', $fileListTmp);
    print $fh $file_list;
    close $fh;

    if (-e "/usr/bin/pigz") {
        warn "creating tar (external TAR with PigZ): $tarName with ", scalar @files, " files\n";
        warn "running: tar cf - --files-from=$fileListTmp | pigz > $tarName";
        system("tar cf - --files-from=$fileListTmp | pigz > $tarName");
    }
    else {
        warn "creating tar (external TAR with GZip): $tarName with ", scalar @files, " files\n";
        system("tar cf - --files-from=$fileListTmp | gzip > $tarName");
    }
    if ($? == -1) {
        warn "ERROR createTarNew: $err\n";
    }
    else {
        $self->writeXml($fHash);    
        $self->addArchive;        
    }
    system("rm -f $fileListTmp");
}

sub getFileList
# Arguments: Dir name, ignore these
# Returns: A hash containing two arrays with the name good, bad
# This is use to build the file list
{
    my $self = shift;
    my ($uid, $gid, $size, $name);
    
    my $dir = $self->baseDir;
    warn "getFileList dir is: $dir\n" if($self->cfg('debug'));
    chdir "$dir" || return "Could not chdir to $dir: $!\n";

    my $cmd = $self->cfg("findBin");
    my @curFiles = qx/$cmd/;

    my $xmlData = {base => $dir};
    my $uidHash = {0 => 'root'};
    my $gidHash = {0 => 'root'};
    my $total = 0;
    my $build = $self->build;
    while (my $f = pop(@curFiles)) {
        chomp($f);
        next if($f eq ".");
        next if($f eq "..");
        $f =~ s/^\.\///o;       
        unless(grep {$f =~ /^$_/} @{ $self->{ignore} }) {
            # The function below does no longer work under Perl-5.8.X for whatever weird reason.
            # Part of the problem is that it barfs on symlinks. Using lstat instead gets around that,
            # but there we have to make sure to not run it on sockets, special devices and what not.
            #($uid,$gid,$size) = (stat($dir."/".$f))[4,5,7]; 

            # Run lstat() on files, directories and symlinks only:
                        if ((-f $dir."/".$f) || (-d $dir."/".$f) || (-l $dir."/".$f)) {
                                my $sb = lstat($dir."/".$f);
                                my $uid = $sb->uid;
                                my $gid = $sb->gid;
                                my $size = $sb->size;
                # Uncomment the next line to log debug output to cmu.log:
                #warn "IN-IF File: $dir/$f | UID: $uid | GID: $gid | Size: $size \n";

                my $fHash;
                $fHash->{name} = $f;
                $fHash->{size} = $size;
                if(defined($uidHash->{$uid})) {
                    $fHash->{uid} =  $uidHash->{$uid};
                } else {
                    $name = (getpwuid($uid))[0];
                    if($name) { $fHash->{uid}= $uidHash->{$uid} = $name; } 
                    else { $fHash->{uid} = $self->cfg('dflUser') }
                }
                if($build =~ /^Qube/) {
                    if(defined $gidHash->{$gid}) { 
                        $fHash->{gid} = $gidHash->{$gid}; 
                    } else {
                        $name = (getgrgid($gid))[0];
                        if($name) { $fHash->{gid} = $gidHash->{$gid} = $name; } 
                        else { $fHash->{gid} = $self->cfg('dflGroup') }
                    }
                }
                push(@{ $xmlData->{file} }, $fHash);
                $total += $size;                
                        }
            # On anything else we assume safe defaults:
                        else {
                                my $uid = "nobody";
                                my $gid = "users";
                                my $size = "0";
                # Uncomment the next line to log debug output to cmu.log:
                #warn "IN-ELSE File: $dir/$f | UID: $uid | GID: $gid | Size: $size \n";

                my $fHash;
                $fHash->{name} = $f;
                $fHash->{size} = $size;
                if(defined($uidHash->{$uid})) {
                    $fHash->{uid} =  $uidHash->{$uid};
                } else {
                    $name = (getpwuid($uid))[0];
                    if($name) { $fHash->{uid}= $uidHash->{$uid} = $name; } 
                    else { $fHash->{uid} = $self->cfg('dflUser') }
                }
                if($build =~ /^Qube/) {
                    if(defined $gidHash->{$gid}) { 
                        $fHash->{gid} = $gidHash->{$gid}; 
                    } else {
                        $name = (getgrgid($gid))[0];
                        if($name) { $fHash->{gid} = $gidHash->{$gid} = $name; } 
                        else { $fHash->{gid} = $self->cfg('dflGroup') }
                    }
                }
                push(@{ $xmlData->{file} }, $fHash);
                $total += $size;
                        }
        }

    }
    return if(!defined $xmlData->{file});
    $xmlData->{totalSize} = int($total/1048576);
    return $xmlData;
}

sub extractTar
# used for upacking files.
# Arguments: User name
{
    my $self = shift;
    my ($ret, $homeDir, $fData);    

    if($self->type eq "users") {
        $homeDir = (getpwnam($self->name))[7];
    } elsif($self->type eq "groups") {
        if($self->build =~ /^Qube/) {
            $homeDir = "/home/groups/".$self->name;
        } elsif($self->build =~ /^RaQ550$/ || $self->build =~ /^5100R$/ || $self->build =~ /^510[6-8]R$/ || $self->build =~ /^520[7-9]R$/ || $self->build =~ /^516[0-1]R$/ || $self->build =~ /^5200R$/ || $self->build =~ /^TLAS1HE$/ || $self->build =~ /^TLAS2$/) {
            $homeDir = "/home/sites/".$self->name;
        } else {
            warn "ERROR extractTar: Cannot find build type: ", $self->build, "\n";
            return 0;
        }
    } else {
        warn "ERROR extractTar: Unknown type: ", $self->type, "\n";
        return 0;
    }
    if(! -d $homeDir) {
        warn "ERROR extractTar: Home directory $homeDir does not exists\n";
        return 0;
    }

    $self->setSet("public");
    $self->setBaseDir("$homeDir/web");
    foreach my $xml (@{ $self->{archives}->{public} }) {
        if(-f $self->destDir."/".$xml.".".$self->sessID) {
            $self->setXmlName($self->destDir."/".$xml.".".$self->sessID);
        } else { $self->setXmlName($self->destDir."/".$xml) }

        if(! -f $self->xmlName) { 
            warn "ERROR extractTar: cannot find xml file ", $self->xmlName, "\n";
            next;   
        }
        $fData = $self->readXml;
        $self->setTarName($self->destDir."/".$fData->{tarFile});
        if(! -f $self->tarName) {
            warn "ERROR extractTar: cannot find tar file, ", $self->tarName,
                " listed in ", $self->xmlName, "\n";
            next;
        }
        if($fData->{md5sum} ne $self->getTarMd5) {
            warn "ERROR extractTar: Md5sums do not match for tar archive,",
                " data could be lost or corrupted\n";
            warn "ERROR skipping tarfile: ", $self->tarName, "\n";  
            next;
        }
        
        warn "xmlFile: ", $self->xmlName, " tarFile ", $self->tarName, "\n";
        if ($TARMETHOD eq "internal") {
            $ret = $self->undoTar;
        }
        else {
            $ret = $self->undoTarNew;
        }
        $self->setAttr($fData) if($ret eq 1);
    }

    $self->setSet("private");
    $self->setBaseDir($homeDir);
    foreach my $xml (@{ $self->{archives}->{private} }) {

        if(-f $self->destDir."/".$xml.".".$self->sessID) {
            $self->setXmlName($self->destDir."/".$xml.".".$self->sessID);
        } else { $self->setXmlName($self->destDir."/".$xml) }

        if(! -f $self->xmlName) { 
            warn "ERROR extractTar: cannot find xml file ", $self->xmlName, "\n";

            next;   
        }
        $fData = $self->readXml;
        $self->setTarName($self->destDir."/".$fData->{tarFile});
        if(! -f $self->tarName) {
            warn "ERROR extractTar: cannot find tar file, ", $self->tarName,
                " listed in ", $self->xmlName, "\n";
            next;
        }
        if($fData->{md5sum} ne $self->getTarMd5) {
            warn "ERROR extractTar: Md5sums do not match for tar archive,",
                " data could be lost or corrupted";
            warn "ERROR skipping tarfile: ", $self->tarName, "\n";  
            next;
        }
        
        warn "xmlFile: ", $self->xmlName, " tarFile ", $self->tarName, "\n";
        if ($TARMETHOD eq "internal") {
            $ret = $self->undoTar;
        }
        else {
            $ret = $self->undoTarNew;
        }
        $self->setAttr($fData) if($ret eq 1);
    
        my $mailFile = $homeDir."/cmu-mailspool";
        my $mailBox = $homeDir."/mbox";
        if($self->type eq "users" && -f $mailFile) {
            if($self->build =~ /^Qube/) {
                my $mailDest = $self->cfg('mailSpool')."/".$self->name;
                #my $cpCmd = $self->cfg('cpBin')." -p ".$mailFile." ".$mailDest;
                my $cpCmd = $self->cfg('cpBin')." ".$mailFile." ".$mailDest;
                qx/$cpCmd/;
                qx(/bin/chgrp mail $mailDest);
                chmod 0660, $mailDest;
                
            } elsif($self->build =~ /^RaQ550$/ || $self->build =~ /^5100R$/ || $self->build =~ /^510[6-8]R$/ || $self->build =~ /^520[7-9]R$/ || $self->build =~ /^516[0-1]R$/ || $self->build =~ /^5200R$/ || $self->build =~ /^TLAS1HE$/ || $self->build =~ /^TLAS2$/) {
                if(-l $homeDir."/mbox") {
                    warn "WARN $homeDir/mbox is a symlink - removing it.\n";
                    unlink($homeDir.'/mbox');
                }
                # mstauber Sun May 24 17:20:37 2009
                # Check the size of the file 'cmu-mailspool' that's included in the user's 'private' tarball.
                # It contains a copy of his 'mbox' file. Store the size in $cmu_mailspool_size
                my $cmu_mailspool_size = "";
                if (-f $mailFile) {
                    my $cmu_mailspool_size = stat("$mailFile")->size;
                }
                else {
                    my $cmu_mailspool_size = "0";
                }
                warn "INFO: Spool ", $mailFile, " is ", $cmu_mailspool_size, " bytes large.\n" if($self->cfg('debug'));

                # We only copy 'cmu-mailspool' over 'mbox' if 'cmu-mailspool' is NOT 0 bytes.
                # We especially don't copy over if 'mbox' exists, because that would be plain stupid:
                unless ((-f $homeDir."/mbox") && ($cmu_mailspool_size == "0")) {
                    #my $cpCmd = $self->cfg('cpBin')." -p ".$mailFile." ".$homeDir."/mbox";
                    my $cpCmd = $self->cfg('cpBin')." ".$mailFile." ".$homeDir."/mbox";
                    qx/$cpCmd/;
                    warn "INFO: Using command ", $cpCmd, " to restore mailspool.\n" if($self->cfg('debug'));
                    chmod 0600, 'mbox';
                }
            } else {
                #my $catCmd = $self->cfg('cpBin')." -p ".$mailFile." ".$self->cfg('mailSpool')."/".$self->name;
                my $catCmd = $self->cfg('cpBin')." ".$mailFile." ".$self->cfg('mailSpool')."/".$self->name;
                qx/$catCmd/;
            }
            unlink($mailFile);
        } elsif ($self->type eq "users" && -f $mailBox) {
          if ($self->build =~ /^TLAS1HE/ || $self->build =~ /^TLAS2/) {
            my $mailDest = $self->cfg('mailSpool')."/".$self->name;
            my $cpCmd = sprintf("%s -p %s %s",
             $self->cfg('cpBin'),
             $mailBox,
             $mailDest );
            qx/$cpCmd/;
            chmod 0600, 'mbox';
          }
        } elsif($self->type eq "users") {
            warn "INFO: User ", $self->name, " has no mail spool\n";
        }
    }
    return 1;
}

sub undoTar
# Arguments: base dir, tar file name, orig file list
# Returns: sucess of failure
# Side Effects: Pulls file out of the tar ball  
{
    my $self = shift;

    my $baseDir = $self->baseDir;
    chdir $baseDir || return "ERROR: undoTar: Could not chdir to $baseDir: $!\n";

    my $tar = Archive::Tar->new();
    $tar->extract_archive($self->tarName);
    my $err = $tar->error();
    if($err) { warn "ERROR undoTar: $err\n"; return 0}
    return 1;
}

sub undoTarNew
# Arguments: base dir, tar file name, orig file list
# Returns: sucess of failure
# Side Effects: Pulls file out of the tar ball  
{
    my $self = shift;

    my $baseDir = $self->baseDir;
    chdir $baseDir || return "ERROR: undoTarNew: Could not chdir to $baseDir: $!\n";

    $tBname = $self->tarName;

    if (-e "/usr/bin/pigz") {
        warn "unpacking tar (external TAR with PigZ): with command: tar xf $tBname --use-compress-program=pigz \n";
        system("tar xf $tBname --use-compress-program=pigz");
    }
    else {
        warn "unpacking tar (external TAR with Gzip): with command: tar zxf $tBname\n";
        system("tar zxf $tBname");
    }
    if ($? == -1) {
        warn "ERROR undoTarNew: $err\n";
        return 0;
    }
    else {
        return 1;
    }
}

sub setAttr
# Returns: error code
# Side Effects: sets ownership on all of the files
{
    my $self = shift;
    my $fData = shift || return;
    my $dir = $self->baseDir;
    my $uidHash = {root => '0'};

    my ($chownCmd, $ret);

    chdir "$dir" || return "ERROR: Could not chdir to $dir: $!\n";
    foreach my $file (@{ $fData->{file} }) {
        if(!defined $file->{uid}) {
            next;
        } 
        elsif(!defined $uidHash->{ $file->{uid} }) {
            my $uid = getpwnam($file->{uid});
            if($uid) {
                $uidHash->{ $file->{uid} } = $uid;
            } else {
                $file->{uid} = 'admin';
            }
        }
    
        if(! -f $file->{name} && ! -d $file->{name}) {
            warn "ERROR Could not find file: $dir/", $file->{name}, "\n";
            next;
        }

                my $gid; 
                if (defined $self->{gid}) { 
                        $gid = $self->{gid}; 
                } else { 
                        $gid = 'users'; 
                } 

        # escape problem chars
        if($file->{name} =~ /\$/) { $file->{name} =~ s/\$/\\\$/g; }
#       if($file->{name} =~ /\%/) { $file->{name} =~ s/\%/\\\%/g; }
        if($file->{name} =~ /\`/) { $file->{name} =~ s/\`/\\\`/g; } #`

        if($self->build =~ /^RaQ550$/ || $self->build =~ /^5100R$/ || $self->build =~ /^510[6-8]R$/ || $self->build =~ /^520[7-9]R$/ || $self->build =~ /^516[0-1]R$/ || $self->build =~ /^5200R$/ || $self->build =~ /^TLAS1HE$/ || $self->build =~ /^TLAS2$/) {
            $ret = chown((getpwnam($file->{uid}))[2],
             (getgrnam($self->{gid}))[2], $file->{name} );
            $ret = ($ret > 0)? 0: 1;
        } elsif($self->build =~ /^Qube/) { 
            $ret = chown((getpwnam($file->{uid}))[2],
             (getgrnam($file->{gid}))[2], $file->{name} );
            $ret = ($ret > 0)? 0: 1;
        }
        if($ret != 0) {
            # During the first try we couldn't set the UID and GID of this file. So we try it again using a system call before we really fail:
            my $ret_second_attempt = system("chown $file->{uid}:$self->{gid} \"$dir/$file->{name}\"");

            if ($ret_second_attempt != 0) {
                warn "ERROR Cannot set ownership for file: $dir/", $file->{name}, " - Tried to chown with: UID: $file->{uid} - GID: $self->{gid} and failed.\n";
            }
        }
    
    }
    return 1;
}

sub encodeFileNames
# argument: file data structure # returns: file data structure
# side effect: encode_base64 each of the file names
{
    my $self = shift;
    my $hash = shift;
    
    if(defined $hash->{file}) {
        foreach my $f (@{ $hash->{file} }) {
            next if(!defined $f->{name});
            $f->{name} = encode_base64($f->{name}, '');
            delete $f->{size};
        }
    }
    return $hash; 
}

sub decodeFileNames 
# argument: file data structure
# returns: file data structure
# side effect: decode_base64 each of the file names
{
    my $self = shift;
    my $hash = shift;

    if(defined $hash->{file}) {
        foreach my $f (@{ $hash->{file} }) {
            next if(!defined $f->{name});
            $f->{name} = decode_base64($f->{name});
        }
    }
    return $hash;
}

sub writeXml 
{
    my $self = shift;
    my $fHash = shift || return;
    my ($tarName, $md5);

    my $xmlName = $self->xmlName;
    my $build = $self->build;
    
    if(defined $fHash->{tarFile}) { $tarName = $fHash->{tarFile} }
    else { $tarName = $self->baseName($self->tarName) }

    if(defined $fHash->{md5sum}) { $md5 = $fHash->{md5sum} }
    else { $md5 = $self->getTarMd5 }

    $self->encodeFileNames($fHash);
    my $xml = $XMLPROLOG."\n". $XMLHEADER."\n";
    $xml .= qq(<archive>\n  <cmuVersion value = "$VERSION"/>\n);
    $xml .= qq(  <md5sum value = "$md5"/>\n);
    $xml .= qq(  <tarFile value = "$tarName"/>\n);
    foreach my $f (@{ $fHash->{file} }) {
        if($build =~ /^Qube/) {
            $xml .= qq(  <file name = "$f->{name}" uid = "$f->{uid}" gid = "$f->{gid}"/>\n);
        } else { $xml .= qq(  <file name = "$f->{name}" uid = "$f->{uid}"/>\n); }
    }
    $xml .= qq(</archive>\n);

    open(FH, ">$xmlName") || return "ERROR: Cannot open $xmlName: $!\n";
    print FH $xml;
    close(FH);
    return 1;
}

sub readXml
{
    my $self = shift;
    my $file = shift || $self->xmlName;
    
    # this is a hack, but time is pretty important
    my $fHash = TreeXml::readXml($file, 0);
    if(ref $fHash->{file} eq "HASH") {
        my $tmp = $fHash->{file};
        delete $fHash->{file};
        push @{ $fHash->{file} }, $tmp; 
    }
    $self->decodeFileNames($fHash);
    return $fHash;  
}

sub xmlAttrConvert
# this used to be Remap::xmlFile, but since I don't symlink tar files
# anymore it makes sense to put this here
{
    my $self = shift;
    my $old = shift;    
    my $new = shift;
    my $param = shift;
    my ($cpCmd, $fData);

    my $archives = $self->archives;
    foreach my $xml (@{ $archives->{public} }, @{ $archives->{private} }) { 
        if(-f $self->destDir."/".$xml.".".$self->sessID) {
            $self->setXmlName($self->destDir."/".$xml.".".$self->sessID);
        } elsif(-f $self->destDir."/".$xml) { 
            $cpCmd = $self->cfg('cpBin')." ".$xml." ".$xml.".".$self->sessID;
            qx/$cpCmd/;
            $self->setXmlName($self->destDir."/".$xml.".".$self->sessID);
        } else { 
            warn "ERROR xmlAttrConvert: cannot find XML file: $xml\n";
            next;
        }
        $fData = $self->readXml;

        foreach my $f (@{ $fData->{file} }) {
            #warn $f->{name}, " param: $param value: ", $f->{$param}, "\n";
            if($old eq "") {
                $f->{$param} = $new;
            } elsif($f->{$param} eq $old) {
                $f->{$param} = $new;
            }
        }
        $self->writeXml($fData);
    }
    return 1;
}

sub baseName 
# use the get the basename of a file
{
    my $self = shift;
    my $file = shift || return;
    my $cmd = $self->cfg("baseName")." ".$file;
    my $base = qx/$cmd/;
    chomp($base);
    return $base;
}

sub getTarMd5
# used for getting the md5sum of the file
{
    my $self = shift;
    my $file = shift || $self->tarName;

    my ($md5, $sum);
    my $cmd = $self->cfg("md5Bin")." ".$file;

    if(-f $file) {
        $md5 = qx/$cmd/;
        chomp($md5);
        $sum = (split(" ", $md5))[0];
        return($sum);
    } else {
        warn "ERROR getMd5sum file does not exist: $file\n";
        return;
    }
}

1;

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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