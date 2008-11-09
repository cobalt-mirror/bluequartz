/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/SignalHandler.cpp,v $
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

# include "SignalHandler.h"
# include "libphoenix.h"
# include "Exception.h"
# include "utility.h"

int SignalThread::findHandler( SignalHandler* handler ) const
{
    for (unsigned int i = 0; i < elements.size(); i++)
    {
      if (elements[i].handler == handler)
	  return i;
    }
    LOG(LOG_BASE, "SignalThread::findHandler: handler not found");
    throw RuntimeException(formatString("SignalThread::findHandler: 
                                         handler not found"));
}


void SignalThread::addSignalHandler( SignalHandler* handler, sigset_t mask,
				     bool asynchronous )
{
    // Does handler already exist?
    for (unsigned int i = 0; i < elements.size(); i++)
    {
        if (elements[i].handler == handler)
	{
	    elements[i].sigmask = mask;
	    elements[i].asynchronous = asynchronous;
	    return;
	}
    }

    sigdata newh = { handler, mask, asynchronous };
    elements.push_back( newh );
}


void SignalThread::deleteSignalHandler( SignalHandler* handler )
{
    vector<sigdata>::iterator iv = elements.begin() + findHandler(handler);  
    elements.erase( iv );
}


void SignalThread::resetSignalSet( SignalHandler* handler, sigset_t newmask )
{
    elements[findHandler(handler)].sigmask = newmask;
}


sigset_t SignalThread::getSignalMask( SignalHandler* handler )
{ 
    return ( elements[findHandler(handler)].sigmask ); 
}


void SignalThread::run()
{
    LOG(LOG_PROCTOL, "SignalThread:: entered run");

    sigset_t mask;
    pthread_sigmask(SIG_BLOCK, (sigset_t *) 0, &mask);

# ifdef DEBUG
    // See what signals are set
    char buf[40] = { 'X' };
    for (int i = 1; i < 32; i++)
      sprintf(buf+i, "%c", sigismember(&mask, i) ? '1' : '0');
    LOG(LOG_BASE, "%s", buf);
# endif

    // Block in sigwait and when signal arrives, deliver it only to those
    // registered callbacks who are expecting that signal.
    while (true)
    {
        int signo;
        sigwait( &mask, &signo );

	LOG(LOG_PROCTOL, "SignalThread:: received signal %d", signo);
	for (int i = 0; i < elements.size(); i++)
	{
	    if (sigismember( &elements[i].sigmask, signo ))
	    {
	        int rc = elements[i].handler -> runHandler(signo);
		if (rc != 0)
		{
		    LOG(LOG_BASE, "SignalThread:: signal(%d) handler 
                                   returned %d", signo, rc);
		}
	    }
	}
    }
}
