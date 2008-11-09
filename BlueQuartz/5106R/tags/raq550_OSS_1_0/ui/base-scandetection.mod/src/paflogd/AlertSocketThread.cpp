/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/AlertSocketThread.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  15-Nov-2000
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

#include "AlertSocketThread.h"
#include "CommandPacket.h"
#include "AlertServer.h"
#include "AlertSettings.h"
#include "AlertServer.h"
#include "Exception.h"
#include "libphoenix.h"

AlertSocketThread::AlertSocketThread (AlertServer& server, ServerSocket& ss, 
				      SynchronizedDeque<Alert>& aq) : 
    ObjectSocketThread (ss), alertServer(server), alertQueue(aq)
{
}

void AlertSocketThread::dispatchExternalizable (ObjectSocket& objSocket, 
						Externalizable * ext)
{
    Alert * alert;
    CommandPacket * command;

    try
    {
	if ((alert = dynamic_cast<Alert*>(ext)) != 0)
	{
	    // Handle the alert
	    alertQueue.push_back(*alert);
	    delete alert;
	}
	else if ((command = dynamic_cast<CommandPacket*>(ext)) != 0)
	{
	    // Handle command
	    if (command->data() == AlertServer::SET_ALERT)
	    {
		AlertSettings * as = dynamic_cast<AlertSettings*>(
		    command->getAttachment());
		
		if (as != 0)
		{
		    alertServer.modifySettings(*as);
		}
		else
		{
		    assert (0);
		}
		
		CommandPacket pkt (CommandPacket::SUCCESS);
		objSocket.writeObject (pkt);
	    }
	    else if (command->data() == AlertServer::GET_ALERT)
	    {
		AlertSettings settings = alertServer.getSettings ();
		CommandPacket pkt (AlertServer::GET_ALERT, &settings);
		objSocket.writeObject (pkt);
	    }
	    else
	    {
	        assert (0);
	    }
	    delete command;
	}
	else
	{
	    assert (0);
	}
    }
    catch (Exception& ex)
    {
	LOG (LOG_ERROR, "AlertSocketThread::dispatchExternalizable(): exception caught");
	throw;
    }
    catch (...)
    {
	assert (0);
    }
}
