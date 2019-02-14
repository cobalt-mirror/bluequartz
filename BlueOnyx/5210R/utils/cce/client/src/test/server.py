#!/usr/bin/env python2
# $Id: server.py 259 2004-01-03 06:28:40Z shibuya $
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

from socket import *
from os import unlink

s = socket(AF_UNIX, SOCK_STREAM)


try:
	unlink('/usr/sausalito/cced.socket')
except:
	pass

response = {
'AUTH foo bar':
	'201 OK',
'AUTH bar foo':
	'401 FAIL',
'COMMIT':
	'106 INFO "You smell bad!"\n'+
	'201 OK',
# Have both variation just in case.
'CREATE class foo = bar bar = foo':
	'104 OBJECT 12\n'+
	'201 OK',
'CREATE class bar = foo foo = bar':
	'104 OBJECT 12\n'+
	'201 OK',
# FIXME: Should also test binary strins here.. 
'CREATE badclass "Quote me" = "A quoted string"':
	'301 UNKNOWN CLASS\n'+
	'401 FAIL',
'SET 12 foo = "New Foo!"':
	'201 OK',
'SET 12.namespace foo = "New Foo!"':
	'201 OK',
'SET 12 baz = bar':
	'302 UNKNOWN ATTRIBUTE baz\n'+
	'401 FAIL',
'SET 12 foo = naughty':
	'304 BAD DATA foo "The data was naughty"\n'+
	'401 FAIL',
'GET 12':
	'102 DATA foo = "New Foo!"\n' +
	'103 DATA baz = "Mein got I do exist"\n' +
	'201 OK',
'GET 12.namespace':
	'102 DATA foo = "Old Foo!"\n' +
	'103 DATA foo = "New Foo!"\n' +
	'103 DATA baz = "Mein got I do exist"\n' +
	'201 OK',
'GET 21':
	'303 UNKNOWN NAMESPACE Identity\n' +
	'102 DATA foo = "New Foo!"\n' +
	'103 DATA baz = "Mein got I do exist"\n' +
	'107 CREATED\n'+
	'201 OK',
'GET 22':
	'102 DATA foo = "New Foo!"\n' +
	'103 DATA baz = "Mein got I do exist"\n' +
	'108 DESTROYED\n'+
	'201 OK',
'NAMES 12':
	'105 DATA foo\n'+
	'105 DATA bar\n'+
	'201 OK',
'NAMES class':
# FIXME: Confirm that this is the righ tstring when I get back to the
# desktop.
	'105 DATA "Quoted!"\n'+
	'105 DATA "A long \\" quoted string \\" \\" that is stra\\"nge"\n'+
	'201 OK',
'FIND class foo = bar':
	'104 OBJECT 12\n'+
	'104 OBJECT 13\n'+
	'201 OK',
'DESTROY 400':
	'300 UNKNOWN OBJECT\n'+
	'401 FAIL',
'DESTROY 12':
	'306 WARN "domain:tag var1 = "  val  val ""\n' +
	'201 OK',
'DESTROY 22':
	'Qoo!! This line is invalid..XXX asfiasopjf a&^EAWFGDAS^ """ "" \"'
}

s.bind('/usr/sausalito/cced.socket')
print "Sock name is: ['%s']" % ( s.getsockname() )

s.listen(1)
while 1:
	conn,addr = s.accept();
	print 'Connected by %s' % addr

	conn.send( '100 CCE/0.6\n200 READY\n')

	# Basic operation is this, read a line from the client, look up the line ina
	# hash, if we find it send it's value in the hash..
	
	while 1:
		data = conn.recv(1024)
		if not data: break
		data = data[:-1]
		print data
		if response.has_key(data):
			print response[data]
			conn.send(response[data])
			conn.send('\n')
		else:
			conn.send("402 BAD COMMAND\n")
			print("402 BAD COMMAND")
	conn.close
