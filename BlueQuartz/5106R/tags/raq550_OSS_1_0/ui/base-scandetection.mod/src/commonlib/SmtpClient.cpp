/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/SmtpClient.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  24-Jul-2000
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

#include <cstdio>
#include <string.h>            // bzero

#include "Exception.h"
#include "libphoenix.h"
#include "SmtpClient.h"
#include "Socket.h"


/*
 *  Canonical methods
 */

SmtpClient::SmtpClient (const SmtpClient& obj)
{
    init (obj);
}

SmtpClient& SmtpClient::operator= (const SmtpClient& obj)
{
    if (this != &obj)
    {
	init (obj);
    }
    
    return *this;
}

void SmtpClient::init (const SmtpClient& obj)
{
    smtpServer = obj.smtpServer;
}

/*
 *  Other ctors
 */

SmtpClient::SmtpClient (const string& smtpServerAddress)
{
    smtpServer = smtpServerAddress;
}

/*
 *  Public interface
 */

void SmtpClient::sendMessage (const EmailMessage& email)
{
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): entered");

    //------------------------------------------------------------
    //  Verify arguments
    //------------------------------------------------------------

    if (email.getTo().size() < 1)
    {
	throw IllegalArgumentException ("to: field required");
    }

    if (email.getFrom().size() < 1)
    {
	throw IllegalArgumentException ("from: field required");
    }

    if (email.getSubject().size() < 1 &&
	email.getMessage().size() < 1)
    {
	throw IllegalArgumentException ("subject: or message: field required");
    }

    //------------------------------------------------------------
    //  Open a socket connection
    //------------------------------------------------------------

    Socket socket (smtpServer, SMTP_PORT);

    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): socket opened");

    char buffer[256];
    char buffer2[256];

    //------------------------------------------------------------
    //  Get initial greeting
    //------------------------------------------------------------

    bzero(buffer, sizeof (buffer));
    socket.read (buffer, sizeof(buffer));

    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): greeting read");

    //------------------------------------------------------------
    //  Send HELO
    //------------------------------------------------------------

    if (gethostname(buffer2, sizeof(buffer2)) < 0)
	throw IOException ("gethostname(): unable to get hostname");

    snprintf (buffer, sizeof(buffer), "HELO %s\n", buffer2);
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    bzero(buffer, sizeof (buffer));
    socket.read (buffer, sizeof(buffer));

    if (strncmp("250", buffer, 3) != 0)
    {
	snprintf(buffer2, sizeof(buffer2), 
		 "Unexpected SMTP response to HELO: %s", buffer);
	throw IOException (buffer2);
    }

    //------------------------------------------------------------
    //  Send MAIL FROM:
    //------------------------------------------------------------

    snprintf (buffer, sizeof(buffer), "MAIL FROM:%s\n", 
	      email.getFrom().c_str());
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    bzero(buffer, sizeof (buffer));
    socket.read (buffer, sizeof(buffer));
    
    if (strncmp("250", buffer, 3) != 0)
    {
	snprintf(buffer2, sizeof(buffer2), 
		 "Unexpected SMTP response to MAIL: %s", buffer);
	throw IOException (buffer2);
    }

    //------------------------------------------------------------
    //  Send RCPT TO:
    //------------------------------------------------------------

    snprintf (buffer, sizeof(buffer), "RCPT TO:%s\n", email.getTo().c_str());
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    bzero(buffer, sizeof (buffer));
    socket.read (buffer, sizeof(buffer));
    
    if ((strncmp("250", buffer, 3) != 0) && (strncmp("251", buffer, 3) != 0))
    {
	snprintf(buffer2, sizeof(buffer2), 
		 "Unexpected SMTP response to RCPT: %s", buffer);
	throw IOException (buffer2);
    }

    //------------------------------------------------------------
    //  Send DATA
    //------------------------------------------------------------

    snprintf (buffer, sizeof(buffer), "DATA\n");
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    bzero(buffer, sizeof (buffer));
    socket.read (buffer, sizeof(buffer));

    if (strncmp("354", buffer, 3) != 0)
    {
	snprintf(buffer2, sizeof(buffer2), 
		 "Unexpected SMTP response to DATA: %s", buffer);
	throw IOException (buffer2);
    }

    //------------------------------------------------------------
    //  Send message headers
    //------------------------------------------------------------

    snprintf (buffer, sizeof(buffer), "To: %s\n", email.getTo().c_str());
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    snprintf(buffer, sizeof(buffer), "From: %s\n", email.getFrom().c_str());
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    snprintf (buffer, sizeof(buffer), "Subject: %s\n", 
	      email.getSubject().c_str());
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    // ...followed by a newline
    snprintf (buffer, sizeof(buffer), "\n");
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));
    

    //------------------------------------------------------------
    //  Send message text
    //------------------------------------------------------------

    socket.write (email.getMessage().c_str(), email.getMessage().size());

    //------------------------------------------------------------
    //  Send \n.\n
    //------------------------------------------------------------

    snprintf (buffer, sizeof(buffer), "\n.\n");
    socket.write (buffer, strlen(buffer));

    bzero(buffer, sizeof (buffer));
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.read (buffer, sizeof(buffer));

    if (strncmp("250", buffer, 3) != 0)
    {
	snprintf(buffer2, sizeof(buffer2), 
		 "Unexpected SMTP response to end of data: %s", buffer);
	throw IOException (buffer2);
    }

    //------------------------------------------------------------
    //  Send QUIT
    //------------------------------------------------------------

    snprintf (buffer, sizeof(buffer), "QUIT\n");
    LOG (LOG_PROCTOL, "SmtpClient::sendMessage(): %s", buffer);
    socket.write (buffer, strlen(buffer));

    bzero(buffer, sizeof (buffer));
    socket.read (buffer, sizeof(buffer));

    if (strncmp("221", buffer, 3) != 0)
    {
	snprintf(buffer2, sizeof(buffer2), 
		 "Unexpected SMTP response to QUIT: %s", buffer);
	throw IOException (buffer2);
    }
}
