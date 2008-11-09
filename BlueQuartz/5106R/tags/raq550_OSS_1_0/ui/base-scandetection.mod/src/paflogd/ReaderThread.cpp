/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/ReaderThread.cpp,v $
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

# include  <stdio.h>              /* std includes */
# include  <stdlib.h>
# include  <signal.h>
# include  <errno.h>
# include  <string.h>             /* strerror() */

# include  <fcntl.h>              /* fread/fwrite */
# include  <sys/types.h>          /* stat() */
# include  <sys/stat.h>           /*   ""   */
# include  <sys/time.h>           /* select() */
# include  <sys/types.h>          /*   ""     */
# include  <unistd.h>             /*   ""     */
# include  <time.h>               /* localtime() */

# include  "ReaderThread.h"
# include  "Exception.h"
# include  "utility.h"
# include  "libphoenix.h"


/* 
 *  Open the character device.
 *  It will not be available if it is already opened (eg. root 
 *  running 'cat /proc/net/phoenix_log' or the phoenix module isn't loaded.)
 */
void ReaderThread::open_kernel_log()
{
    close();
    if ((fw_fd = open( inputFile.c_str(), O_RDONLY )) < 0)
    {
        throw IOException( formatString("unable to open device '%s': %s",
					     inputFile.c_str(),
					     strerror(errno)) );
    }
}

void ReaderThread::run()
{
    LOG(LOG_PROCTOL, "ReaderThread:: entered run");

    int            bytes_in;
    fd_set         read_bits;
    struct timeval select_tv;
    char           read_buffer[readSize];
    
    try
    {
        open_kernel_log();
	time_t ctime = TIME;

	while (true)
	{
	    FD_ZERO (&read_bits);
	    FD_SET (fw_fd, &read_bits);
	    
	    // Now that we are using threads, we *might* be able to block in
	    // read(2).  But we'd have to make sure there was no pending data
	    // in a QueueBlock that hadn't been added to the Queue, thus it
	    // wouldn't show up in the log until the next read(2) - which
	    // would presumably flush it out to the Queue.  IOW, the 
	    // flush algorithm would have to flushed out. :-)

	    select_tv.tv_sec = 1;
	    select_tv.tv_usec = 0;
	    
	    if (select(16, &read_bits, (fd_set *)0, (fd_set *)0, &select_tv) < 0)
	    {
		if (errno == EINTR || errno == EAGAIN)
		    continue;
		else 
		    throw IOException( formatString("select error: %s", 
						    strerror(errno)) );
	    }
	    
	    if (FD_ISSET(fw_fd, &read_bits))
	    {
		bytes_in = read( fw_fd, read_buffer, sizeof(read_buffer) );
		
		if (bytes_in < 0)
		{
		    LOG( LOG_BASE, "read error: %s", strerror(errno));
		    continue;
		}
		
		processRecord (read_buffer, bytes_in);
	    }
	    
	    
	    /*
	      Handles case when 1 byte is read every 1/2 second.  In
	      this case timeout would be false and QueueBlock in
	      BufferedQueue would take too long to fill up.  This will
	      also handle the case when timeout is true (1 second should
	      have elapsed.)
	      
	      This can be made more efficient; but store when last flushed.
	      if delta > 1
	        flush
	      if delta > 1 and you haven't written since last flush
	        don't flush
	    */
	    if (TIME - ctime >= 1)
	    {
		flushOutput();
		ctime = TIME;
	    }
	    
	} // while
    } // try
    catch (Exception& ex)
    {
	LOG (LOG_ERROR, ex.message().c_str());
	LOG (LOG_ERROR, "ReaderThread::run(): exception caught");
    }
    catch (...)
    {
	LOG (LOG_ERROR, "ReaderThread::run(): unexpected exception");
	LOG (LOG_ERROR, "ReaderThread::run(): thread ending");
    }
}


/*
 *  LogReaderThread
 */

int LogReaderThread::timestamp (bool print_year, char *buf, unsigned buf_size)
{
    time_t t = TIME;
    struct tm *tp = localtime( (const time_t *) &t );

    if (print_year)
    {
      return snprintf(buf, buf_size, "%02d/%02d/%02d-%02d:%02d:%02d ", 
			tp -> tm_mon + 1, tp -> tm_mday, tp -> tm_year % 100, 
			tp -> tm_hour, tp -> tm_min, tp -> tm_sec); 
    }
    else 
    {
      return snprintf(buf, buf_size, "%02d/%02d-%02d:%02d:%02d ", 
			tp -> tm_mon + 1, tp -> tm_mday, tp -> tm_hour, 
			tp -> tm_min, tp -> tm_sec);
    }
}

void LogReaderThread::processRecord (char * buffer, int numBytes)
{
    int time_size;

# define TMPSIZE 20
    char time_buf[TMPSIZE];
    time_size = timestamp( true, time_buf, TMPSIZE );
# undef TMPSIZE

    // There's a bug with this.  I've seen the timestamp written out at
    // the end of one block and the packet at the beginning of the next
    // block.  We really shouldn't split a record like that.  It occurs
    // as: append time buf, read head block, append packet resulting in
    // a buffer split between two blocks.  
    //
    // One easy fix is to concat. the two buffers before appending to
    // block but that will cost us time.  Or make bufferedQueue more
    // complex by adding mutexes and such to treat multiple append
    // calls as autonomous.

    bufferedQueue.append( time_buf, time_size );
    bufferedQueue.append( buffer, numBytes );
}

void LogReaderThread::flushOutput ()
{
    bufferedQueue.flush ();
}

/*
 *  AlertReaderThread
 */

void AlertReaderThread::processRecord (char * buffer, int numBytes)
{
    bufferedQueue.append (buffer, numBytes);
}

void AlertReaderThread::flushOutput ()
{
    bufferedQueue.flush ();
}
