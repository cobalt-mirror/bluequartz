/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/SignalHandler.h,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  15-Feb-2001
    Originating Author :  Sam Napolitano

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

#ifndef _SIGNAL_HANDLER_H_
#define _SIGNAL_HANDLER_H_

# include <sys/types.h>
# include <signal.h>
# include <vector>
# include "Thread.h"

using namespace std;

// Callback function used when signal arrives
// Extend any class w/SignalHandler to have it get a signal
class SignalHandler
{
  public:
    virtual int runHandler(int) = 0;
    virtual ~SignalHandler() {}
};


//
//  Main thread that sigwait's for all signals
//
class SignalThread : public Thread
{
  private:
    struct sigdata {
      SignalHandler*  handler;
      sigset_t        sigmask;
      bool            asynchronous;    // not implemented
    };
    vector<sigdata> elements;

  private:
    int findHandler( SignalHandler* handler ) const;

  public:
    // Canonical methods
    SignalThread() : elements() {}
    ~SignalThread() {} 

    // Public interface
    void run();                // needed for Thread

    // Note that I purposely didn't provide functions to manipulate
    // the signal mask, as POSIX already provides them.  SIGSETOPS(3):
    // sigemptyset, sigfillset, sigaddset, sigdelset, sigismember
    // - POSIX signal set operations.

    void addSignalHandler( SignalHandler* handler, sigset_t mask, 
			   bool asynchronous = false);
    void deleteSignalHandler( SignalHandler* handler );
    void resetSignalSet( SignalHandler* handler, sigset_t newmask );
    sigset_t getSignalMask( SignalHandler* handler );
};

#endif  /* SIGNAL_HANDLER_H */
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */
