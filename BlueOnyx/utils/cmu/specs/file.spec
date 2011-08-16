#  $Id: file.spec 922 2003-07-17 15:22:40Z will $
# 
# Cobalt Networks, Inc http://www.cobalt.com/


Objective:
    - an abstracted representation of a file object
    - expressed in the general object XML format

Types of file collections:
	home - Files that are not publically accessible.
	web - Files that are publically accessible.
	mail - Contains mailspool information

File data types:
    name       value=string
    mode       value=string
    type       value=string
    uid        value=number
    gid        value=number
    size       value=number
    mtime      value=string

