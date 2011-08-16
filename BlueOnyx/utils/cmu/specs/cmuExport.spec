# $Id: cmuExport.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

NAME
	export - migrate data to another unit, export is not called directly

SYNOPSIS
	cmuExport 	[OPTIONS]

DESCRIPTION
	cmuExport - Runs a box migratation, the destation unit should be 
	clean


GLOBAL OPTIONS
	-c only include configuration information, no data

	-d build directory, this is where export will place all exported files, the default is /home/cmu/FQDN

	-l [fileName] log all messages to file (default: /home/cmu/cmuLog)

	-n [fqdn] Fully qualified domain name of the vsite to export

	-v verbose, print all messages to stdout
