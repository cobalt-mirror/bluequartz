/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/SmtpNotifier.cpp,v $
              Revision :  $Revision: 1.3 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  22-Sep-2000
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

#include <stdio.h>
#include <unistd.h>
#include "SmtpNotifier.h"
#include "utility.h"
#include "SmtpClient.h"
#include "libphoenix.h"
#include "CceClient.h"

SmtpNotifier::SmtpNotifier (const SmtpNotifier& obj)
{
    init (obj);
}

SmtpNotifier& SmtpNotifier::operator=(const SmtpNotifier& obj)
{
    if (this != &obj)
    {
	init (obj);
    }

    return *this;
}

void SmtpNotifier::init (const SmtpNotifier& obj)
{
    emailAddress = obj.emailAddress;
    smtpServer = obj.smtpServer;
}

#if 0
int SmtpNotifier::notify (const Alert& alert)
{
    SmtpClient client (smtpServer);
    string subject = formatString ("Alert %d", alert.getType());
    EmailMessage message(emailAddress, fromAddress, "Alert", alert.getMessage());
    client.sendMessage (message);
    return 0;
}
#endif

int SmtpNotifier::notify (const Alert& alert)
{
#if 0
    // This isn't great, but it will have to do for now.
    // Poll CCE for the admin mailing list no more than once a minute

    static time_t old_time;
    time_t new_time = alert.getTimestamp();
    if ( (new_time - old_time) > 60 )
    {
        char buf[1024];
	if (get_admin_email(buf, 1024)) {
	    emailAddress = buf;
	}
	old_time = new_time;
    }
#endif
    string subject;
    char emailbuffer[1024];

    switch (alert.getType()) 
      {
      case Alert::SCAN_ATTACK_ALERT:
	subject = "portscanSubject";
	break;
      case Alert::RESTRICTED_SITE_ALERT:
	subject = "restrictedSiteSubject";
	break;
      default:
	subject = "errorUnknownAlertSubject";
	break;
      }
    
    if(get_admin_email(emailbuffer, sizeof(emailbuffer))) {
	emailAddress = emailbuffer; 
	}

    if(send_alert(
	       alert.getTimestamp(),
	       emailAddress.c_str(), 
	       subject.c_str(),
	       alert.getMessage().c_str()
	       ) < 0){
	LOG(LOG_ERROR, "Alert notification failed");
    }
    return 0;
}
