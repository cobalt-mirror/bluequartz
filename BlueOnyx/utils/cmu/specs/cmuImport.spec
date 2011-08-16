# $Id: cmuImport.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

NAME
	import - Adds exported configuration and data to current machine

SYNOPSIS
	cmuImport 	[OPTION] 

DESCRIPTION
	cmuImport - Runs an import, the destation unit should be 
	clean


GLOBAL OPTIONS

	-c import configuration only, no vsite or user data is restored

	-d directory of that contains the exported files

	-l [file name] log file location the default is /home/cmu/cmuLog

	-n [fqdn] Fully qualified domain name of the vsite to import

	-v verbose, print all messages to stdout


Options to add
	-V run verify only

	-I	ignore any conflict elemets and objects, and try to add all objects

	-A	attempt to automagically reslove conflict, all changes are logged

	-C  just restore file data, assume a sucessful import -c has already been run

	-v  print more debugging information to the logfile

	-p  [file name] apply this policy file
