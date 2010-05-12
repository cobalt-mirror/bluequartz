#!/usr/bin/perl
# $Id: TLAS1HEadjust.pl 956 2006-05-04 05:53:42Z shibuya $
#!/usr/bin/perl
use strict;

# If you are not toor, go away :)
die "You must run this script as root\n" if ($< != 0);

use lib "/usr/cmu/perl";
require CmuCfg;

my $cfg = CmuCfg->new(type => 'adjust');
$cfg->parseOpts();

require TreeXml;

my ($cmuXml, $outFile);
if($cfg->isSess) {
        $cmuXml = $cfg->cmuXml;
        $outFile = $cfg->sessXml;
        if(-f $outFile) { $cmuXml = $outFile; }
} else { die "ERROR: no session id given, cannot adjust files\n" }

my $tree = TreeXml::readXml($cmuXml, 0);

$tree->{adjustPlatform} = "TLAS1HE";

my $migrate = {};
TreeXml::addNode('migrate', $tree, $migrate);
TreeXml::writeXml($migrate, $outFile);
exit 0;

