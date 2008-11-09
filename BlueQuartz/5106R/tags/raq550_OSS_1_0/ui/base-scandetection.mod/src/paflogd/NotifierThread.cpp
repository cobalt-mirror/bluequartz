/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/NotifierThread.cpp,v $
              Revision :  $Revision: 1.2 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  14-Nov-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: ge $ 
    Date Last Modified :  $Date: 2001/12/18 16:26:26 $

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

#include "Alert.h"
#include "AlertServer.h"
#include "BufferedQueue.h"
#include "libphoenix.h"
#include "NotifierThread.h"
#include "SmtpNotifier.h"
#include "SynchronizedDeque.h"
#include "utility.h"

static Alert::AlertType alertType (const char * buffer, int beg, 
				   int end);


NotifierThread::NotifierThread (AlertServer& as, BufferedQueue& bq,
				SynchronizedDeque<Alert>& aq) : 
    alertServer(as), bufferedQueue (bq), alertQueue(aq)
{
    settings = alertServer.getSettings ();
    char dn[4096];

    if (gethostname(dn, sizeof(dn)) < 0)
    {
	throw RuntimeException (
	    "NotifierThread::NotifierThread(): gethostname() failed.");
    }

    dn[sizeof(dn)-1] = '\0';
    string str = string("Alert Daemon (") + string(dn) + string(")");

    notifier = new SmtpNotifier (settings.getSmtpServer(),   // server
				 settings.getEmailAddress(), // to
				 str);                       // from
}

void NotifierThread::run ()
{
    LOG (LOG_PROCTOL, "NotifierThread::run(): entered");

    try
    {
	while (true)
	{
	    const QueueBlock * block = bufferedQueue.getHeadBlock (
		BUFFERED_QUEUE_WAIT);

	    settings = alertServer.getSettings ();
	    
	    if (block != 0)
	    {
		handleBlock (block);
		bufferedQueue.freeHeadBlock();
	    }
	    else
	    {
		// If we've timed out waiting for a block, do some idle 
		// processing.
		
		idleFunction ();
	    }
	}
    }
    catch (Exception& ex)
    {
	LOG (LOG_ERROR, ex.message().c_str());
	LOG (LOG_ERROR, "NotifierThread::run(): exception caught");
    }
    catch (...)
    {
	LOG (LOG_ERROR, "NotifierThread::run(): unexpected exception");
	LOG (LOG_ERROR, "NotifierThread::run(): thread ending");
    }
}

void NotifierThread::handleBlock (const QueueBlock * block)
{
    // If neither alerts coming from the proc file are set, then return
    if (!settings.getScanAttack() && !settings.getRestrictedSiteAccess())
    {
	return;
    }

    const char * buffer = block->getBuffer();
    int length = block->getLength();
    int begin = 0;
    int end;

    while (begin < length)
    {
	// Find the end of the record
	for (end = begin; end < length && buffer[end] != '\n'; end++);
#if 0	
	if (end >= length)
	{
	    assert (0);
	}

	handleRecord (buffer, begin, end);
#endif
	if (end < length)
	{
	    handleRecord (buffer, begin, end);
	}
	begin = end + 1;
    }
}

static Alert::AlertType alertType (const char * buffer, int begin, 
				   int end)
{
    Alert::AlertType type = Alert::NONE;

    while (begin <= end)
	if (buffer[begin++] == ':')
	    break;

    if ((end - begin + 1) >= 10)
    {
	if (memcmp(buffer+begin, "portscan:", 9) == 0)
	    type = Alert::SCAN_ATTACK_ALERT;
	else if (memcmp(buffer+begin, "restrict:", 9) == 0)
	    type = Alert::RESTRICTED_SITE_ALERT;
#if 0
	// Unknown type; ignore
	else 
	{
	    assert (0);
	}
#endif
    }
#if 0
    // Not enough characters to comparse; ignore
    else
    {
	assert (0);
    }
#endif

    return type;
}

/* Number of seconds that must pass before another alert email will be sent */

static const int ALERT_THRESHOLD = 60;

void NotifierThread::handleRecord (
    const char * buffer, int begin, int end)
{
    Alert::AlertType recordType = alertType (buffer, begin, end);
    time_t now = time(0);

    switch (recordType)
    {
    case Alert::SCAN_ATTACK_ALERT:
	if (settings.getScanAttack())
	{
	    static time_t last_scan_time = 0;
	    if ( (now - last_scan_time) > ALERT_THRESHOLD )
	    {
	        last_scan_time = now;
		string msg(reinterpret_cast<const char*>(buffer+begin), 
			   end-begin+1);
		Alert alert(Alert::SCAN_ATTACK_ALERT, now, msg);
		notifier->notify (alert);
	    }
	}
	break;
    case Alert::RESTRICTED_SITE_ALERT:
	if (settings.getRestrictedSiteAccess())
	{
	    static time_t last_restrict_time;
	    if ( (now - last_restrict_time) > ALERT_THRESHOLD )
	    {
	        last_restrict_time = now;
		string msg(reinterpret_cast<const char*>(buffer+begin), 
			   end-begin+1);
		Alert alert(Alert::RESTRICTED_SITE_ALERT, now, msg);
		notifier->notify (alert);
	    }
	}
	break;
    default:
#if 0
	assert (0);
#endif
	break;
    }
}

void NotifierThread::idleFunction ()
{
    while (alertQueue.size() > 0)
    {
	Alert alert = alertQueue.front ();
	alertQueue.pop_front ();
	notifier->notify (alert);
    }
}


