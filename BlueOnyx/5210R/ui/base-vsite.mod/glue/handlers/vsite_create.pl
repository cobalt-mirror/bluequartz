#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: vsite_create.pl
#
# largely based on siteMod.pm and siteAdd.pm in turbo_ui
# do the initial setup for a vsite like creating the system group
# and creating the home directory for the site

# this is for profiling purposes

use CCE;
use I18n;
use Vsite;
use File::Path;
use Sauce::Util;
use Sauce::Config;
use Base::HomeDir qw(homedir_get_group_dir homedir_create_group_link);
use Base::Group qw(groupadd group_add_members);
# use Base::User;

# debugging flag, set to 1 to turn on logging to STDERR
my $DEBUG = 1;
if ($DEBUG) 
{ 
    use Data::Dumper; 
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

# set umask, otherwise directories get created with the wrong permissions
umask(002);

my $cce = new CCE('Domain' => 'base-vsite');
$cce->connectfd();

my ($ok, $vsite);
my ($sysoid) = $cce->find('System');

$vsite = $cce->event_object();

# first create the system group for this site
my $group_name = &create_system_group($vsite);

if (not $group_name) 
{
    &debug_msg("Cannot add System Group for $group_name \n");
    $cce->bye('FAIL', '[[base-vsite.cantAddSystemGroup]]');
    exit(1);
}

my @admins = ('admin');

# Add created admin user to group 
push @admins, $vsite->{createdUser}; 

group_add_members($group_name, @admins);

# create link from /home/sites/fqdn to /home/sites/site
# this is just a nice thing for sys admins, it serves no functional purpose
my $site_dir = homedir_get_group_dir($group_name, $vsite->{volume});
&debug_msg("home $site_dir\n");
my ($site_link, $link_target) = homedir_create_group_link($group_name, $vsite->{fqdn}, $vsite->{volume});
&debug_msg("site link $site_link\n");
# make sure the sites directory exists
if (! -d "$vsite->{volume}/sites")
{
    Sauce::Util::makedirectory("$vsite->{volume}/sites", 0755);
    &debug_msg("Created $vsite->{volume}/sites as it didn't exist yet.\n");
}
Sauce::Util::linkfile($link_target, $site_link);

# group has been added to the system
# Define name and basedir to Vsite object
($ok) = $cce->set($cce->event_oid(), '', { 'name' => $group_name, 'basedir' => $site_dir });
if (not $ok) 
{
    $DEBUG && print STDERR "ok was $ok\n";
    &debug_msg("Cannot set site group for $group_name ($site_dir) in event_oid.\n");
    $cce->bye('FAIL', '[[base-vsite.cantSetSiteGroup]]');
    exit(1);
}

# make sure there is a network interface for this ip. We skip this on AWS as anything
# there runs on a single IP anyway:
if (!-f "/etc/is_aws") {
    vsite_add_network_interface($cce, $vsite->{ipaddr});
}

my $locale = I18n::i18n_getSystemLocale($cce);
# make the locale sane

# now copy in the default index.html
# need to do this yet, but should think about how this is going to work some
# should this do it the same as monterey? 
# copy over the index.html template
my $site_web = $site_dir . '/' . Sauce::Config::webdir();
my $webindex = "$site_web/index.html";
my $skel_web = &find_skeleton($locale);

$DEBUG && print STDERR "Setting up site web.\n";
Sauce::Util::modifytree($site_web);
system("/bin/cp -r $skel_web/* \"$site_web/.\"");
Sauce::Util::chmodfile(02775, "$site_web/error");
system("/bin/chmod 0664 $site_web/error/*"); 
system("/bin/chown -R nobody.$group_name \"$site_web/error\"");

# hack to make sure index.html is at least there
if (! -f $webindex)
{
    Sauce::Util::modifyfile($webindex);
    open HACK, ">$webindex" or die;
    print HACK "<HTML><TITLE>New BlueOnyx Vsite</TITLE></HTML><BODY>THIS PAGE IS JUST A PLACEHOLDER UNTIL THE HTML TEMPLATE FOR NEW VSITES GETS COPIED OVER.</BODY></HTML>";
    close HACK;
}
else
{
    Sauce::Util::editfile($webindex, *edit_webindex, $vsite);
            
    # restore index permissions
    Sauce::Util::chownfile((getpwnam('nobody'))[2], (getgrnam($group_name))[2], $webindex);
    Sauce::Util::chmodfile(0664,$webindex);
}

# add the default aliases to the virtuser file
for my $alias (keys %DefaultAliases)
{
    my ($ok) = $cce->create('ProtectedEmailAlias',
                        {
                            'site' => $group_name,
                            'fqdn' => $vsite->{fqdn},
                            'alias' => $alias,
                            'action' => $alias,
                            'build_maps' => 0
                        });

    # check if the create succeeded, if not send a warning and
    # fail, because the user has no way to create these other than
    # through a vsite create
    if (!$ok)
    {
        # check to see if the alias already exists and is used
        # by some user
        my ($oid) = $cce->find('EmailAlias',
                        {
                            'alias' => $alias,
                            'fqdn' => $vsite->{fqdn}
                        });
        if ($oid)
        {
            ($ok, my $alias_obj) = $cce->get($oid);
            my $other_site = {};
            if ($alias_obj->{site})
            {
                my ($void) = $cce->find('Vsite', 
                                { 'name' => $alias_obj->{site} });
                ($ok, $other_site) = $cce->get($void);
            }

            if ($other_site->{fqdn})
            {
                $cce->warn('vsiteUserOwnsAlias',
                    { 
                        'user' => $alias_obj->{action}, 
                        'site' => $other_site->{fqdn},
                        'alias' => "$alias\@$vsite->{fqdn}"
                    });
            }
            else
            {
                $cce->warn('userOwnsAlias', 
                    { 
                        'user' => $alias_obj->{action},
                        'alias' => "$alias\@$vsite->{fqdn}"
                    });
            }
        }
        else
        {
            # no idea why it can't be added
            $cce->warn('cantAddSystemAlias', { 'alias' => "$alias\@$vsite->{fqdn}" });
        }

        $cce->bye('FAIL');
        exit(1);
    }
}

# create a VirtualHost entry
($ok) = $cce->create('VirtualHost', 
            { 
                'ipaddr' => $vsite->{ipaddr}, 
                'fqdn' => $vsite->{fqdn}, 
                'documentRoot' => "$site_dir/web",
                'name' => $group_name 
            });

if (not $ok)
{
    $cce->bye('FAIL', '[[base-vsite.cantAddVirtualHost]]');
    exit(1);
}

# setup ftp host if necessary
# This is now taken care of in base-ftp
my (@site_ftp) = $cce->find('FtpSite', { 'ipaddr' => $vsite->{ipaddr} });
if (!$site_ftp[0])
{
     ($ok) = $cce->create('FtpSite', { 'ipaddr' => $vsite->{ipaddr}, 'enabled' => 1 });

    if (not $ok)
    {
        $cce->warn('[[base-vsite.cantAddFtpVhost]]');
    }
} 
else
{
    # inform FTP that the site state has changed
    ($ok) = $cce->set($site_ftp[0], '', { 'commit' => time() });
}

$cce->bye('SUCCESS');
exit(0);

sub create_system_group
{
    my $vsite = shift;

    my ($name);

    # don't waste any group names
    # go through the Vsites and find the first untaken site\d+ name
    # assume 16 bit gid fields which means there could be a whole lot of groups
    for (my $i = 1; $i <= 2 ** 16; $i++)
    {
        # use getgrnam to check for available groups, 
        # but this creates a possible
        # race condition.  would CCE take care of the race condition?
        if (not getgrnam("site$i")) {
            # found an availble name
            $name = "site$i";

            # add the group, use groupadd to make things standard
            my @ret = groupadd({ 'name' => $name });
            
            if (!$ret[0])
            {
                return '';
            }

            last;
        }
    }

    # create the home directory and sub directories for this site
    my $base = homedir_get_group_dir($name, $vsite->{volume});

    $DEBUG && print STDERR "base is $base\n";
    $DEBUG && print STDERR Dumper($vsite);

    # create dirs with looser permissions first
    if (scalar(mkpath([ "$base/users", ("$base/" . Sauce::Config::webdir()) ], 0, 02775)) == 0) 
    {
        &debug_msg("$base failed to create users and web");
        if ($base =~ /^\/.+/)
        {
            rmtree($base);
        }
        return '';
    }
    
    # be safe and make sure $base is not /
    if ($base =~ /^\/.+/)
    {
        Sauce::Util::addrollbackcommand("/bin/rm -rf $base");
    }

    # chmod all the directories
    Sauce::Util::chmodfile(02775, "$base/users");
    Sauce::Util::chmodfile(02775, "$base/" . Sauce::Config::webdir());
    Sauce::Util::chmodfile(02775, $base);

    # chown all the directories just made
    my $gid = getgrnam($name);
    # this chown doesn't need to be rolled back because if the 
    # Vsite create fails the entire $base dir will just get blown away
    system('chown', '-R', "nobody.$name", $base);

    return $name;
}

sub debug_msg {
    if ($DEBUG) {
    my $msg = shift;
    $DEBUG && print STDERR "$ARGV[0]: ", $msg, "\n";

    $user = $ENV{'USER'};
    setlogsock('unix');
    openlog($0,'','user');
    syslog('info', "$ARGV[0]: $msg");
    closelog;
    }
}

sub edit_webindex {
    my ($in, $out, $vsite) = @_;
    while (<$in>) {
        s/\[DOMAIN\]/$vsite->{fqdn}/g;
        print $out $_;
    }
    return 1;
}

sub find_skeleton
{
    my $locale = shift;

    my $skel_dir = '/etc/skel/vsite';

    # this may need to be more robust, but for now just make it simple
    if (-d "$skel_dir/$locale")
    {
        return "$skel_dir/$locale/" . Sauce::Config::webdir();
    }

    $locale =~ s/^([^_]+).*$/$1/;
    if (-d "$skel_dir/$locale")
    {
        return "$skel_dir/$locale/" . Sauce::Config::webdir();
    }

    # otherwise fall back to a default
    return "$skel_dir/en/" .Sauce::Config::webdir();
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