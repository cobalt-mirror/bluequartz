# $Id: TreeXmlLib.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com

This spec contains definitions for the combined tree and xml libraries.

TODO: Can we abstract addNode with addMember by using a common 
      scheme to name child hashes

Spec Definitions:
Node - a hash which is a child of another hash
Member - a key/value pair such as usrQuota=50

Functions:

Hash readXml(File xmlData)
  Desc: reads and parses xmlData
  Args: xmlData - xml formatted text
  Ret:  tree of data implemented as a hash of hashes

bool writeXml(Hash data, File outFile)         
  Desc: writes an xml stream to a file if specified otherwise to STDOUT
  Args: data - a hash of data to be output as xml 
        outFile - optional file to write xml to
  Ret:  success or failure

String Find(Hash root, String key)
  Desc: retrieves a string value from the specified root hash
  Args: root - hash to find the specified key in
        key - key to retrieve value of
  Ret:  string value corresponding to given key

bool addNode(String key, Hash newNode, Hash parentNode)
  Desc: adds a new node(vsite, group, user, or mailing list) to a specified
        position in the migrate hash
  Args: newNode - the new vsite, group, user, or mailing list hash
	parentNode - the parent hash i.e. a particular vsite for a user
	key - the key to insert in the parentNode
  Ret:  success or failure

bool addMember(String key, String value, Hash parentNode)
  Desc: adds a new key/value member(grpQuota=20, usrName='Joe', etc.) to a 
        specified parentNode
  Args: key - the key to insert in parentNode
        value - corresponding value to the key
	parentNode - hash of the parent 
  Ret:  success or failure

bool delKey(String key, Hash root)
  Desc: deletes a key (node or member) or keys existing in the root hash 
  Args: key - the key to delete
        root - the hash to remove the key from
  Ret:  success or failure

Notes:
The root migrate hash will need to be build with accelerators. This could take
the form of a child list. Thus every node would have a child list so that find
or other functions could retrieve the names of the keys for the children nodes.









