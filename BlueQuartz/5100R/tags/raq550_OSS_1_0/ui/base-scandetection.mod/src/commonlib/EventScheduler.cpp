/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/EventScheduler.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  24-Jul-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:36 $

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

#include "EventScheduler.h"
#include "Exception.h"
#include "libphoenix.h"

bool EventScheduler::addEvent (Event event)
{
    MutexLock lock(mutex);
    events.insert(event);
    condition.signal ();
    return true;
}

void EventScheduler::clear ()
{
    MutexLock lock(mutex);
    events.clear();
    condition.signal ();
}

void executeEvent (const Event& evt)
{
    Runnable * cmd = evt.getRunnable();

    if (evt.getAsynchronous())
    {
	Thread th (cmd);
	th.start ();
    }
    else
    {
	cmd->run ();
    }
}

void EventScheduler::stop ()
{
    MutexLock lock(mutex);
    _active = false;
    condition.signal ();
}

void EventScheduler::run ()
{
    LOG(LOG_PROCTOL, "EventScheduler:: entered run");

    try
    {
	while (active())
	{
	    MutexLock lock(mutex);
	    
	    // If the queue is empty, wait for a condition
	    while (events.size() < 1)
	    {
		condition.wait (mutex);
		
		if (!active())
		    return;
	    }
	    
	    // Obtain the time of the most recent event
	    const Event& evt = *(events.begin());
	    
	    time_t evt_time = evt.getEventTime ();
	    time_t currentTime = time (0);
	    
	    if (evt_time <= currentTime)
	    {
		events.erase(events.begin());
		executeEvent (evt);
		
		if (evt.isRepeating())
		{
		    Event periodic (evt);
		    periodic.nextTime ();
		    events.insert(periodic);
		}
	    }
	    else
	    {
		try
		{
		    condition.wait (mutex, (evt_time - currentTime) * 1000);
		}
		catch (const ThreadTimeoutException&)
		{
		}
	    }
	}
    }
    catch (Exception& ex)
    {
	LOG (LOG_ERROR, ex.message().c_str());
	LOG (LOG_ERROR, "EventScheduler::run(): exception caught");
    }
    catch (...)
    {
	LOG (LOG_ERROR, "EventScheduler::run(): unexpected exception");
	LOG (LOG_ERROR, "EventScheduler::run(): thread ending");
    }
}
