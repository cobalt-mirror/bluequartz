/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/Socket.cpp,v $
              Revision :  $Revision: 1.2 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  28-Sep-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: ge $ 
    Date Last Modified :  $Date: 2001/11/20 00:26:54 $

   **********************************************************************

   Copyright (c) 2000 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   ********************************************************************** */

#include <arpa/inet.h>
#include <string.h>
#include <errno.h>
#include "utility.h"

#include "Socket.h"
#include "netdb.h"
#include "Exception.h"

Socket::Socket (string host, int port)
{
    int rc;

    socket = ::socket (AF_INET, SOCK_STREAM, 0);

    if (socket < 0) throw IOException("::socket() failed");

    struct sockaddr_in servaddr;

    bzero (&servaddr, sizeof(servaddr));
    servaddr.sin_port = htons(port);

    rc = ::inet_pton (AF_INET, host.c_str(), &servaddr.sin_addr);

    if (rc == 1)
    {
	servaddr.sin_family = AF_INET;
    }
    else
    {
	struct hostent *hp;

	if (hp = gethostbyname(host.c_str()))
	{
	    servaddr.sin_family = hp->h_addrtype;
	    bcopy ((char *)hp->h_addr_list[0], 
		   (char *)&servaddr.sin_addr.s_addr,
		   hp->h_length);
	}
	else
	{
	    throw IOException ("Invalid host");
	}
    }

    rc = ::connect (socket, reinterpret_cast<const struct sockaddr*>(&servaddr),
		    sizeof(servaddr));
    if (rc < 0)
        throw IOException (formatString("connect failed: %s", strerror(errno)));
}

Socket::Socket (const Socket& s)
{
    init (s);
}

Socket& Socket::operator=(const Socket& s)
{
    if (this != &s)
    {
	init (s);
    }

    return *this;
}

void Socket::init (const Socket& obj)
{
    socket = obj.socket;
}

void Socket::close ()
{
    if (socket > 0)
    {
	::close(socket);
	socket = -1;
    }
}

/*
 *  return the number of bytes read
 */

int Socket::read (void * buf, size_t count) const
{
    int len = ::read (socket, buf, count);

    if (len > 0)
    {
	return len;
    }
    else if (len == 0)
    {
	throw EOFException ("EOF in socket read");
    }
    else
    {
	throw IOException (formatString("error in socket read: %s",
                                        strerror(errno)));
    }
}

/*
 * return the number of bytes written
 */

int Socket::write (const void * buf, size_t count) const
{
    size_t written = 0;
    const char *ptr = static_cast<const char *>(buf);

    while(written < count){
        int len = ::write (socket, &ptr[written], count-written);
        if(len <= 0)
	   throw IOException (formatString("write error: %s",strerror(errno)));
	written += len;
    };
    return written;
}
