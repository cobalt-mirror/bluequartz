/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/LogSocketThread.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  15-Nov-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:38 $

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

#include "LogSocketThread.h"
#include "CommandPacket.h"
#include "LogSettings.h"
#include "LogServerThread.h"
#include "Exception.h"
#include "libphoenix.h"


void LogSocketThread::dispatchExternalizable (ObjectSocket& objSocket, 
						Externalizable * ext)
{
    CommandPacket * command;

    try
    {
	if ((command = dynamic_cast<CommandPacket*>(ext)) != 0)
	{
	    // Handle command
	    if (command->data() == LogServerThread::SET_SETTINGS)
	    {
		LogSettings * as = dynamic_cast<LogSettings*>
		                     (command->getAttachment());
		if (as != 0)
		{
		    logServer.modifySettings(*as);
		}
		else
		{
		    LOG(LOG_ERROR, "LogSocketThread::dispatchExternalizable:
			            empty attachment");
		    assert (0);
		}
	    }
	    else if (command->data() == LogServerThread::GET_SETTINGS)
	    {
		LogSettings settings = logServer.getSettings ();
		CommandPacket pkt (LogServerThread::GET_SETTINGS, &settings);
		objSocket.writeObject (pkt);
		objSocket.flush ();
	    }
	    else
	    {
	        LOG(LOG_ERROR, "LogSocketThread::dispatchExternalizable: 
		                unknown command %s", command->data().c_str());
	        assert (0);
	    }
	    delete command;
	}
	else
	{
	    LOG(LOG_ERROR, "LogSocketThread::dispatchExternalizable: 
                            null CommandPacket");
	    assert (0);
	}
    }
    catch (Exception& ex)
    {
	LOG (LOG_ERROR, "LogSocketThread::dispatchExternalizable(): %s",
	                 ex.message().c_str());
	throw;
    }
    catch (...)
    {
	assert (0);
    }
}
