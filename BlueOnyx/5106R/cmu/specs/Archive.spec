# $Id: Archive.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com

This spec contains definitions for the combined tree and xml libraries.

TODO: This will abstract a file and all of its attributes.

Spec Definitions:
name - user or group the file belongs to

Functions:

String tarUser(String user, String dest) 
  Desc: creates the xml and tar archive for the user
  Args: user - the user name to be archived
		dest - directory to write the file archive
  Ret:  Error Code, location of tar/xml file

Array getList(String user, String directory, Hash ignore)         
  Desc: this returns a list of files to be archived
  Args: user/group - The user name or group
        directory - The base dir to start at
		ignore - what files/directories to ignore
  Ret:  Array of file names 

Array getAttr(String dir, Array list)
  Desc: Get the invidual file attributtes for the xml file
  Args: dir - base dir, used for chrooting files
		list - list of the files to get info about
  Ret:  returns an array of all the file info

Notes:
Haven't decided if this lives in the base cmu.xml file or in a seperate xml
file.









