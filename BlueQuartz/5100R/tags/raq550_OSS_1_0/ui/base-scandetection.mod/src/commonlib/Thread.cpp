/*

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/Thread.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  30-Oct-2000
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

#include <stdio.h>
#include <sys/time.h>
#include <unistd.h>
#include <errno.h>
#include <cstdarg>

#include "Exception.h"
#include "Thread.h"
#include "utility.h"

void * Thread::join ()
{
    void * result;

    int rc = pthread_join (tid, &result);

    if (rc != 0)
    {
	throw ThreadException (
	    formatString ("pthread_join() returned %d", rc));
    }

    return result;
}

static void * func (void * r)
{
    Runnable * runnable = static_cast<Runnable*>(r);
    runnable->run();
}

void Thread::start ()
{
    int rc = pthread_create (&tid, 0, func, runnable);

    if (rc != 0)
    {
	char buffer[256];
	snprintf (buffer, sizeof(buffer),
		  "pthread_create() returned %d", rc);
	throw ThreadException (buffer);
    }
}

void Mutex::lock ()
{
    int rc = pthread_mutex_lock (&mutex);

    if (rc != 0)
    {
	char buffer[256];
	snprintf (buffer, sizeof(buffer),
		  "pthread_mutex_lock() returned %d", rc);
	throw ThreadException (buffer);
    }
}

void Mutex::unlock ()
{
    int rc = pthread_mutex_unlock (&mutex);

    if (rc != 0)
    {
	char buffer[256];
	snprintf (buffer, sizeof(buffer),
		  "pthread_mutex_unlock() returned %d", rc);
	throw ThreadException (buffer);
    }
}

// wait() throws ThreadTimeoutException if the time expires
// before the condition is signalled

void Condition::wait (Mutex& mutex, int milliseconds)
{
    int rc;

    if (milliseconds == 0)
    {
	rc = pthread_cond_wait (&condition, &mutex.mutex);

	if (rc != 0)
	{
	    char buffer[256];
	    snprintf (buffer, sizeof(buffer),
		      "pthread_cond_wait() returned %d", rc);
	    throw ThreadException (buffer);
	}
    }
    else
    {
	struct timeval tv;
	struct timespec ts;

	if (gettimeofday(&tv, 0) < 0)
	{
	    throw RuntimeException ("gettimeofday() failed");
	}

	ts.tv_sec = tv.tv_sec;
	ts.tv_nsec = tv.tv_usec * 1000;

	if (milliseconds >= 1000)
	{
	    ts.tv_sec += + milliseconds / 1000;
	    milliseconds = milliseconds % 1000;
	}

	ts.tv_nsec += milliseconds * 1000000;

	rc = pthread_cond_timedwait (&condition, &mutex.mutex, &ts);

	if (rc != 0)
	{
	    if (rc == ETIMEDOUT)
	    {
		throw ThreadTimeoutException ();
	    }

	    char buffer[256];
	    snprintf (buffer, sizeof(buffer),
		      "pthread_cond_timedwait() returned %d", rc);
	    throw ThreadException (buffer);
	}
    }

}

void Condition::signal ()
{
    int rc = pthread_cond_signal (&condition);

    if (rc != 0)
    {
	char buffer[256];
	snprintf (buffer, sizeof(buffer),
		  "pthread_cond_signal() returned %d", rc);
	throw ThreadException (buffer);
    }
}
