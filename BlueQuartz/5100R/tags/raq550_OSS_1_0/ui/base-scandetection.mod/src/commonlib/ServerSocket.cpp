/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/ServerSocket.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  28-Sep-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

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

#include <string.h>

#include "Socket.h"
#include "ServerSocket.h"
#include "Exception.h"

ServerSocket::ServerSocket(int port, int backlog)
{
    socket = ::socket (AF_INET, SOCK_STREAM, 0);
    if (socket < 0) throw IOException();

    int val = 1;
    int rc = setsockopt (socket, SOL_SOCKET, SO_REUSEADDR, &val, sizeof(val));
    if (rc != 0) throw IOException ();

    struct sockaddr_in myaddr;

    bzero (&myaddr, sizeof(myaddr));
    myaddr.sin_family = AF_INET;
    myaddr.sin_addr.s_addr = htonl (INADDR_ANY);
    myaddr.sin_port = htons(port);
    
    rc = ::bind (socket, reinterpret_cast<const struct sockaddr*>(&myaddr), 
		     sizeof(myaddr));
    if (rc != 0) throw IOException();

    rc = ::listen (socket, backlog);
    if (rc != 0) throw IOException();
}

ServerSocket::ServerSocket (const ServerSocket& s)
{
    init (s);
}

ServerSocket& ServerSocket::operator=(const ServerSocket& s)
{
    if (this != &s)
    {
	init (s);
    }

    return *this;
}

void ServerSocket::init (const ServerSocket& obj)
{
    socket = obj.socket;
}

Socket * ServerSocket::accept ()
{
    struct sockaddr cliaddr;
    socklen_t addrlen;

    int sd = ::accept (socket, &cliaddr, &addrlen);

    if (sd == -1) throw IOException();

    Socket * s = new Socket(sd);
    return s;
}

void ServerSocket::close ()
{
    if (socket > 0)
    {
	::close (socket); 
	socket = -1;
    }
}


