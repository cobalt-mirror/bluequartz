/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/Event.cpp,v $
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

#include "Event.h"
#include "Exception.h"
#include "utility.h"

Event::Event (time_t et, Runnable * r, int p = 0, bool a = false, int per = 0)
    : asynchronous(a), eventTime(et), period(per), priority(p), runnable(r) 
{
    if (eventTime <= 0)
    {
	string str = formatString (
	    "Event::Event(): invalid event time: %d", eventTime);
	throw IllegalArgumentException (str);
    }
    
    nextTimeFunc = 0;
}

Event::Event (const Event& obj)
{
    init (obj);
}

Event& Event::operator=(const Event& obj)
{
    if (this != &obj)
    {
	init (obj);
    }

    return *this;
}

void Event::init (const Event& obj)
{
    asynchronous = obj.asynchronous;
    eventTime    = obj.eventTime;
    period       = obj.period;
    priority     = obj.priority;
    runnable     = obj.runnable;
    nextTimeFunc = obj.nextTimeFunc;
}

bool Event::operator<(const Event& evt) const
{
    if (eventTime < evt.eventTime)
    {
        return true;
    }

    if ((eventTime == evt.eventTime) && (priority < evt.priority))
    {
        return true;
    }

    return false;
}

void Event::nextTime ()
{ 
    if (nextTimeFunc != 0)
    {
	eventTime = nextTimeFunc (*this);
    }
    else if (period > 0)
    {
	eventTime += period; 
    }
}
