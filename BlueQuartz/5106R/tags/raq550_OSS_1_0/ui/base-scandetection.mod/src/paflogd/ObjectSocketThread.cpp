/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/ObjectSocketThread.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  16-Nov-2000
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

#include "ObjectSocketThread.h"
#include "ObjectSocket.h"
#include "Exception.h"

void ObjectSocketThread::run ()
{
    try
    {
	while (true)
	{
	    Socket * socket = serverSocket.accept ();
	    ObjectSocket objectSocket (socket);

	    while (true)
	    {
		try
		{
		    Externalizable * obj = objectSocket.readObject ();
		    dispatchExternalizable (objectSocket, obj);
		}
		catch (EOFException& ex)
		{
                    delete socket;
                    break;
		}
	    }
	}
    }
    catch (Exception& ex)
    {
	assert (0);
    }
    catch (...)
    {
	assert (0);
    }
}
