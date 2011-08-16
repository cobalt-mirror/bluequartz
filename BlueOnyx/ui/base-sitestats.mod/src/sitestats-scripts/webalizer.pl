#!/usr/bin/perl
#
# This script runs webalizer for every virtual site on a Cobalt RaQ3
# machine.  This depends on the configuration file /etc/webalizer.conf
# and several command-line switches including those for the LogFile and
# Hostname.
#
# PLEASE NOTE: The /stats directory created will be password protected
# allowing only site admins in for that site.
#
use Time::localtime;
# Where sites on a RaQ3/4 live
$prefix = "/home/sites";

# Status messages
my $messages;

chdir "$prefix" or die "Can't cd to $prefix??: $!\n";
opendir THEROOT, $prefix or die "Couldn't open $prefix?: $!\n";
@allsites = grep !/^\.\.?$/,  readdir THEROOT;

foreach $asite (@allsites)
{  
  if (-l "$asite")
  {
    $webpath = "$prefix/$asite/web";
    $thepath = "$prefix/$asite/webalizer";

    # Get the group id of the directory
    my $gid = (stat $webpath)[5];
    my $name = (getgrgid $gid)[0];

    # Create a directory /web/stats if it isn't there yet
    if (!-d $thepath)
    {
       mkdir $thepath, 775; 
       chown 0, $gid, $thepath;
       chmod 0755, $thepath;
    }
    if ( -e $prefix."/".$asite."/logs/web.log"){
    # Now just run webalizer
    $messages .= `/usr/bin/webalizer -p -n $asite -s $asite -r $asite  -T -o $thepath $prefix/$asite/logs/web.log`;

    # Now change ownership of files for frontpage and non-frontpage enabled sites 

    if (!-d $webpath . "/_vti_bin") {
        $messages .= `echo "Frontpage NOT ENABLED on $webpath"`;
        $messages .= `chown -R apache:$name $thepath`;
    } else {
        $messages .= `echo "Frontpage ENABLED on $webpath"`;
        $messages .= `chown -R nobody:$name $thepath`;
    }
   }else{

        $message .= "No log file for: " . $asite ."\n";

   }
  }
}
open(BOOK, ">>/home/webalizer.log");
print BOOK $messages;
close(BOOK);
exit 0;

