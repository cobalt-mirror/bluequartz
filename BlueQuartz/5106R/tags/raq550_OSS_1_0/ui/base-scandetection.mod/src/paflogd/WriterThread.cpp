/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/WriterThread.cpp,v $
              Revision :  $Revision: 1.2.2.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  25-Jul-2000
    Originating Author :  Brian Adkins, Sam Napolitano

      Last Modified by :  $Author: ge $ 
    Date Last Modified :  $Date: 2002/01/03 20:02:05 $

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

# include  <sys/types.h>          /* stat() */
# include  <sys/stat.h>           /*   ""   */

# include  <sys/types.h>          /* chown    */
# include  <unistd.h>             /*   ""     */
# include  <grp.h>                /* getgrnam */  

# include  "Exception.h"
# include  "utility.h"
# include  "libphoenix.h"
# include  "Thread.h"
# include  "WriterThread.h"
# include  "BufferedQueue.h"
# include  "LogSettings.h"
# include  "LogServerThread.h"


#if 1
/* The name of the group that the log file belongs to */
static const char *PHOENIX_LOG_GROUP  = "wheel";
#endif

static FILE *open_log(FILE *fp, const char *file, off_t* file_size);


/*
 *  open_packet_log
 */
void WriterThread::open_packet_log()
{
    LogSettings settings = logServer.getSettings();
    const char *outf = settings.getOutputFile().c_str();

    if ((log_fp = open_log(log_fp, outf, &currentFileSize)) == NULL) 
    {
	throw RuntimeException( formatString("unable to open log file '%s'", 
					      outf) );
    }
}


/*
 *  Read packets from the buffered queue and write them to the log file
 */
void WriterThread::run()
{
    LOG(LOG_PROCTOL, "WriterThread:: entered run");

    try 
    {
      // register SIGHUP as signal to deliver to this thread
      sigset_t mask;
      sigemptyset( &mask );
      sigaddset( &mask, SIGHUP );
      sigThread.addSignalHandler( this, mask );

      open_packet_log();

      while (true)
      {
	  const QueueBlock *qb = bufQ.getHeadBlock(0);  // block indefinitely

	  if (qb == 0x0)                                // no block to write
	      continue;

	  unsigned int len = qb -> getLength();

#if 0
	  unsigned int maxLogFileSize = 
	                      logServer.getSettings().getMaxLogFileSize();

	  // Is logfile big enough to rotate?
	  if ( maxLogFileSize > 0  &&
	       (currentFileSize + len > maxLogFileSize) )
	  {
	      RotateLogs rlogs( logServer );
	      Thread threadRotate( &rlogs );
	      threadRotate.start();
	      threadRotate.join();
	      this->reopenLog();
	  }
#endif

	  // SIGHUP arrived
	  if (reopen_log) 
	  {
	      reopen_log = false;
	      this->reopenLog();
	  }

	  // Block SIGHUPs
	  sigset_t hup;
	  sigemptyset( &hup );
	  sigaddset( &hup, SIGHUP );
	  pthread_sigmask( SIG_BLOCK, &hup, (sigset_t *)0 );

	  unsigned int bytes_out = 
	      fwrite( qb -> getBuffer(), sizeof(char), len, log_fp );

	  // Unblock SIGHUPs
	  pthread_sigmask( SIG_UNBLOCK, &hup, (sigset_t *)0 );

	  if (bytes_out != len)
	  {
	      LOG(LOG_BASE, "fwrite: %d of %d written: %s\n",
		  bytes_out, len, strerror(errno));
	  }

	  fflush (log_fp);
	  bufQ.freeHeadBlock();
	  currentFileSize += bytes_out;    /* update file size counter */
      } // while
    } // try
    catch (Exception& ex)
    {
        LOG (LOG_ERROR, ex.message().c_str());
        LOG (LOG_ERROR, "WriterThread::run(): exception caught");
    }
    catch (...)
    {
        LOG (LOG_ERROR, "WriterThread::run(): unexpected exception");
        LOG (LOG_ERROR, "WriterThread::run(): thread ending");
    }
}


/*
 *  Handle signals
 */
int WriterThread::runHandler(int signum)
{
    switch (signum)
    {
	case SIGHUP:
	{
	    LOG( LOG_BASE, "WriterThread: SIGHUP" );
	    reopen_log = true;
	    break;
	}

        default:
	{
	    LOG( LOG_BASE, "WriterThread:: unexpected signal %d", signum);
	    break;
	}
    }
    return 0;
}

/*********************************************************************/

/*
 *    open_log - Open the packet log file on disk and append to it
 *               Returns NULL on error and does not modify file_size.
 */
static FILE *open_log(FILE *fp, const char *file, off_t* file_size)
{
    if (file)
        if (strcmp(file, "-") == 0)
            return (stderr);
        else
	{
            struct stat statbuf;

            if (fp != NULL)
                fclose(fp);

	    /* Opening file for the first time? */
            if (stat(file, &statbuf) < 0) 
	    {
                if ((fp = fopen(file, "a+")) == NULL)
		{
                    LOG( LOG_BASE, "cannot open '%s': %s", 
			 file, strerror(errno));
		    return (NULL);
		}
                else
                {
#if 1
                    struct group *g;

                    /* Get phoenix gid and set group perms to phoenix */
                    if ((g = getgrnam(PHOENIX_LOG_GROUP)) == NULL)
                    {
                      LOG( LOG_BASE, "No such group %s found in group file." \
                                     "  Unable to setgid on log file.",
                                     PHOENIX_LOG_GROUP );
                    }
                    else
                        if ( chown(file, (uid_t) -1, g -> gr_gid) < 0 )
                        {
                            LOG( LOG_BASE, "chown(%s, %d, %d) failed: %s",
                                 file, -1, g -> gr_gid, strerror(errno) );
                        }

                    chmod(file, 0644);
#else
                    chmod(file, 0600);
#endif
                }

                *file_size = 0;
	    }
            else
            {
                if ((fp = fopen(file, "a+")) == NULL)
		{
                    LOG( LOG_BASE, "cannot open '%s': %s", 
			 file, strerror(errno));
		    return (NULL);
		}
                
                *file_size = statbuf.st_size;
            }

            return (fp);
	}
    else
        return (NULL);
}
