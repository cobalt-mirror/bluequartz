
# $Id: migrateLib.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

This spec contains definitions for the migrate libraries.  
It requires the use of platformLib.spec for system dependent calls.

Functions:
data structure = ScanOut(options)
	Desc: Scans out the current state of the system.
	Args: Options to control the scanout files, types for children, etc.
	Ret:  tree of data implemented as a hash of hashes.

	This function would include or require the functions below.

		data structure = ScanOutVsite(string) 
		data structure = ScanOutUser(string)
		data structure = ScanOutMailList(string)
		data structure = ScanOutGroup(string)
		data structure = ScanOutArchive(string)

return code = ScanIn(data structure)
	Desc: Add the data structure to the system. 
	Args: tree of data implemented as a hash of hashes.
	Ret:  If successful and if not error messages.


	This function would include or require the functions below.

		data structure = ScanInVsite(string) 
		data structure = ScanInUser(string)
		data structure = ScanInMailList(string)
		data structure = ScanInGroup(string)
		data structure = ScanInArchive(string)


