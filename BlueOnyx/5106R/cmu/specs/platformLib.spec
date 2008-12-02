# $Id: platformLib.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

This spec contains definitions for the platform dependant libraries.

Base functions:
Each data structure listed in the datadef directory requires 
at least three type of calls enable to operate on it.
	
data structure = get(string) 
	Desc: Given the name get populates the data members 
	of the structure type.
	Args: The unique identifier
	Ret:  tree of data implemented as a hash of hashes.
	
return code = add(struct) 
	Desc: Adds the structure to the system.
	Args: tree of data implemented as a hash of hashes.
	Ret:  success or failure
	
array = getChildren(string) 
	Desc: This gets the children for a given data type
	are present.
	Args: The unique identifier of the parent
	Ret:  Returns an array of the children for the data structure.  
	This returns empty if structure has no children or if no children
    are present.

 
Additional functions:
These calls will be used for redundant or complex calls that need
to interface directly to the system. 

vsite.def: 
	string = getGroup(string) 
		Desc: converts the fqdn into the local group name
		Args: Fqdn name
		Ret:  Group name

user.def:

group.def:

mailList.def:


