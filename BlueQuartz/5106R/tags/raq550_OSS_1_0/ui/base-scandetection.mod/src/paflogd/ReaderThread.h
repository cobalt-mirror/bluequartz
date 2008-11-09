/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/ReaderThread.h,v $
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

# ifndef _READER_THREAD_H
# define _READER_THREAD_H

# include <string>
# include "Thread.h"
# include "BufferedQueue.h"
# include "SignalHandler.h"

class ReaderThread : public Runnable
{
  private:
    string   inputFile;       // file to open
    int      readSize;        // size for read(2)
    char     recordDef;       // what separates a record
    int      fw_fd;           // file descriptor

  protected:
    virtual void flushOutput () = 0;
    virtual void processRecord (char *, int) = 0;

  public: 
    ~ReaderThread () { close(); }

    ReaderThread( const string& inputFile,
		  int readSize = 1024, 
		  char recordDef = '\n' ) : 
                  inputFile(inputFile), 
                  readSize(readSize),
                  recordDef(recordDef), 
                  fw_fd(-1)
                  {}

    void close() { if (fw_fd >= 0) ::close (fw_fd); }

    virtual void run ();              // needed by Runnable

  private:
    void open_kernel_log();
};


class LogReaderThread : public ReaderThread
{
  private:
    BufferedQueue&   bufferedQueue;

    int timestamp(bool print_year, char *buf, unsigned buf_size);

  protected:
    virtual void flushOutput ();
    virtual void processRecord (char *, int);

  public:
    LogReaderThread(BufferedQueue& bq, const string& inputFile,
		    int readSize = 1024, char recordDef = '\n') : 
      ReaderThread(inputFile, readSize, recordDef), 
      bufferedQueue(bq) {}

};


class AlertReaderThread : public ReaderThread
{
  private:
    BufferedQueue& bufferedQueue;

  protected:
    virtual void flushOutput ();
    virtual void processRecord (char *, int);

  public:
    AlertReaderThread(BufferedQueue& bq, const string& inputFile,
		      int readSize = 1024, char recordDef = '\n') : 
      ReaderThread(inputFile, readSize, recordDef), 
      bufferedQueue(bq) {}
}; 

# endif /* _READER_THREAD_H */
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
