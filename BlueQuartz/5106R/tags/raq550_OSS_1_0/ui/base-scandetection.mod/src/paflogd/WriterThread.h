/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/WriterThread.h,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  25-Jul-2000
    Originating Author :  Brian Adkins, Sam Napolitano

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

# ifndef _WRITER_THREAD_H
# define _WRITER_THREAD_H

# include <cstdio>
# include <string>
# include "BufferedQueue.h"
# include "LogServerThread.h"
# include "SignalHandler.h"

class WriterThread : public Runnable, public SignalHandler
{
  private:
    BufferedQueue&   bufQ;
    LogServerThread& logServer;
    SignalThread&    sigThread;         // thread who will send us signals
    bool             reopen_log;        // for sighups
    FILE            *log_fp;            // output stream
    off_t            currentFileSize;   // current size of file

  public: 
    WriterThread (BufferedQueue& bq, LogServerThread& ls, SignalThread& st) :
                  bufQ(bq), logServer(ls), sigThread(st), 
                  reopen_log(false),
                  log_fp(0x0), 
                  currentFileSize(0)
                  {}
    ~WriterThread () { close(); }

    void reopenLog () { open_packet_log(); }
    void close() 
    { 
	if (log_fp)
	{
	    fflush (log_fp);
	    fclose (log_fp);
	    log_fp = 0x0;
	}
    }

    virtual void run ();              // needed by Runnable
    virtual int  runHandler(int);     // needed by SignalHandler

  private:
    void open_packet_log();
};

# endif /* _WRITER_THREAD_H */
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
