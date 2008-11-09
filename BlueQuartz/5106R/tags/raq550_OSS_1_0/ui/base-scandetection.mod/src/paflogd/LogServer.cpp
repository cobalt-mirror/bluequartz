/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/LogServer.cpp,v $
              Revision :  $Revision: 1.5 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  25-Jul-2000
    Originating Author :  Brian Adkins, Sam Napolitano

      Last Modified by :  $Author: ge $ 
    Date Last Modified :  $Date: 2001/12/04 17:28:29 $

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

# include  <sys/stat.h>           /*   stat()   */
# include  <unistd.h>             /*    ""      */

# include  <syslog.h>             /* close/openlog() */
# include  <stdarg.h>             /* varargs */

# include "LogServer.h"
# include "LogServerThread.h"
# include "LogSettings.h"
# include "ReaderThread.h"
# include "WriterThread.h"
# include "LogSocketThread.h"
# include "SignalHandler.h"

# include "Exception.h"
# include "utility.h"
# include "libphoenix.h"
# include "Profile.h"

#include <typeinfo>
#include "LogSettings.h"
#include "CommandPacket.h"
#include "DataUnit.h"
#include "ErrorPacket.h"
#include "processutil.h"

/*
 *  Globals - the horror!
 */
int    debug = LOG_BASE;       // needed by libphoenix

/* Default maximum log size before rotation */
static const unsigned long MAX_LOG_SIZE = 10485760;  /* 10MB */

/* The logfile read from the kernel */
static const char *PHOENIX_KERNEL_LOG   = "/proc/net/phoenix_log";

/* The pid file */
static const char *PAFLOGD_PID_FILE     = "/var/run/paflogd.pid";


/********************  GENERAL  FUNCTIONS  *************************/

class GeneralFunctions : public SignalHandler
{
  private:
    const char *paflogdPidFile;

  public:
    GeneralFunctions() : paflogdPidFile(PAFLOGD_PID_FILE) {}
    ~GeneralFunctions() {}

    void cleanup(void);
    int write_pid_file(int argc, char **argv);
    sigset_t mySignalSet(void);

    virtual int runHandler(int signum);
};

/*
 *  cleanup - Tidy-up before exiting
 */
void GeneralFunctions::cleanup(void)
{
    (void) closelog();          /* syslog */
    unlink (paflogdPidFile);  /* remove pid file */
}

/*
 *  write_pid_file
 */
int GeneralFunctions::write_pid_file(int argc, char **argv)
{
    return CreatePidfile(paflogdPidFile);
}

/*
 *  Returns set of signals that this class handles
 */
sigset_t GeneralFunctions::mySignalSet(void) 
{
    int signals[] = {
      SIGINT, SIGTERM, 0x0
    };

    sigset_t mask;
    sigemptyset( &mask );
    for (int i = 0; signals[i]; i++)
    {
         sigaddset( &mask, signals[i] );
    }
    return mask;
}



/*
 *  handle signals
 */
int GeneralFunctions::runHandler(int signum)
{
    switch (signum)
    {
	case SIGINT:
	{
	    LOG( LOG_BASE, "SIGINT" );
	    this->cleanup();
	    exit(0);
	    break;
	}

	case SIGTERM:
	{
	    LOG( LOG_BASE, "SIGTERM" );
	    this->cleanup();
	    exit(0);
	    break;
	}

        default:
	{
	    this->cleanup();
	    throw IllegalArgumentException( 
                  formatString("GeneralFunctions:: unexpected signal %d", signum ));
	}
    }
    return 0;
}

/********************  PARSE-ARG  FUNCTIONS  *************************/

/*
 * strtolong -  convert the characters at s to an positive long integer.
 *              If there is an error (any non-numeric characters), log
 *              with the error message in errmess at 'log_level' and
 *              return -1.  Should be moved to utility.h.
 */
static long strtolong(const char *s, const char *errmess)
{
    char *last = (char *) s;
    long int ret;
    char errmsg[1024];

    memset(errmsg, '\0', sizeof(errmsg));

    errno = 0;
    ret = strtol(s, &last, 10);

    if (errno == ERANGE)
    {
        snprintf(errmsg, sizeof(errmsg), 
		 "Numeric parameter too large; must be < %lu", ret);
    }
    else if ( ! last)
    {
        snprintf(errmsg, sizeof(errmsg), 
		 "Internal error parsing numeric parameter in '%s'", errmess);
    }
    else if (last == s)
    {
        snprintf(errmsg, sizeof(errmsg), 
		 "Missing numeric parameter in '%s'", errmess);
    }
    else if (*last != '\0')
    {
        snprintf(errmsg, sizeof(errmsg), 
		 "Invalid character in numeric parameter '%s'", errmess);
    }
    else if (ret < 0)
    {
        snprintf(errmsg, sizeof(errmsg), 
		 "Numeric parameter must be a positive integer in '%s'", 
		 errmess);
    }
    
    if (strlen(errmsg) > 0)
    {
	cerr << errmsg;
	return (-1);
    }

    return ret;
}

/*
 *  print_version
 */
static void print_version(int debuglvl)
{
    # include "version.h"

    Profile_ptr profile = getProfile ();

    LOG(debuglvl, "%s\n", profileGetCompanyName(profile));
    LOG(debuglvl, "%s Log Daemon version %s (%s)\n", 
                          profileGetProductName(profile), 
                          profileGetProductVersion(profile), 
			  version);
}

/*
 *  usage
 */
static void usage(const char *program_name)
{
    print_version(LOG_STDERR);
# if 0
   fprintf(stderr, "Usage: %s [-hnV] [-s max_size] [-l pkt_log] [-d debug]\n", 
	   program_name);
# endif
    fprintf(stderr, "Usage: %s [-hnV] [-d debug]\n", program_name );
    fprintf(stderr, "      -h           help\n");
    fprintf(stderr, "      -d           debug level\n");
    fprintf(stderr, "      -n           do not detach from terminal\n");
    fprintf(stderr, "      -V           version\n");
# if 0
    fprintf(stderr, "      -s max_size  rotate log file when max_size exceeded (default %ld bytes)\n", MAX_LOG_SIZE);
    fprintf(stderr, "      -l pkt_log   output log file (default %s)\n", PHOENIX_LOG);
# endif
    fprintf(stderr, "\n");
    fprintf(stderr, " Signal operations:\n");
    fprintf(stderr, "       SIGHUP      close and reopen packet log file\n");
    fprintf(stderr, "       SIGINT      exit\n");
    fprintf(stderr, "       SIGTERM     exit\n");
    fflush(stderr);
}


/*
 *  parse_args
 *    Future options:
 *      Option to ignore config file 
 *      Option to use different config file
 *      Option to set number of rotate files
 */
static void parse_args(int argc, char **argv, LogServerThread& ls, bool *detach)
{
    extern   char *optarg;
    extern   int   optind;
    int            opt;
    LogSettings    settings = ls.getSettings();

    char *program_name = argv[0];

# if 0
    while (( opt = getopt( argc, argv, "l:s:d:nhV" )) != EOF )
# endif
    while (( opt = getopt( argc, argv, "d:nhV" )) != EOF )
    {
        switch (opt)
        {
            /* log filename */
            case 'l':
		settings.setOutputFile(optarg);
                break;

            /* logfile size limit */
            /* Future: allow suffix 'k','m','g' to number to specify
		       KB, MB or GB */
            case 's':
	    {
	        int max_size = strtolong( optarg, "-s parameter" );
                if (max_size < 0)
                    exit (OSEXITOKAY);
		settings.setMaxLogFileSize(max_size);
                break;
	    }

            /* do not detach from terminal */
            case 'n':
                *detach = false;
                break;

            /* debug level */
            case 'd':
	        debug = strtolong( optarg, "-d debug" );
                break;

            /* version */
            case 'V':
                print_version(LOG_STDERR);
                exit (OSEXITOKAY);
                break;

            /* Unknown option or help */
            case '?':                     
            case 'h':
                usage(program_name);
                exit (OSEXITOKAY);
                break;
        }
    }
    
    /* Do not allow extra arguments on the command line just to be anal */
    if (optind != argc)
    {
        usage(program_name);
        exit (OSEXITOKAY);
    }

    ls.modifySettings(settings);
}


/*********************  MAIN  *************************/

typedef Externalizable * (*PF)();
map<string, PF> io_map;

static void init_io_map ()
{
    // LogSettings
    io_map[typeid(LogSettings).name()] = 
	&LogSettings::createExternalizable;

    // CommandPacket
    io_map[typeid(CommandPacket).name()] = 
	&CommandPacket::createExternalizable;

    // DataUnit
    io_map[typeid(DataUnit).name()] = 
	&DataUnit::createExternalizable;

    // ErrorPacket
    io_map[typeid(ErrorPacket).name()] = 
	&ErrorPacket::createExternalizable;
}

int main (int argc, char **argv)
{
    try {
	bool  detach = true;            /* default: detach from terminal */

	SignalThread     sigT;
	EventScheduler   evtS;
	LogServerThread  logST (evtS);
	BufferedQueue    bufQ;
	WriterThread     writeT (bufQ, logST, sigT);
	LogReaderThread  readT (bufQ, PHOENIX_KERNEL_LOG);
	GeneralFunctions genF;
	//int fd;

	init_io_map ();
	sigT.addSignalHandler(&genF, genF.mySignalSet());

	/*
	 *  Block signals - all threads will inherit this mask.  Each
	 *  thread can decide whether to register or not with SignalThread
	 *  to have ST deliver the specified signal to the thread.  Note
	 *  that in order for a signal to be delivered to a thread it MUST
	 *  be blocked here, otherwise the kernel won't even deliver it to ST.
	 */
	int signals[] = {
	  SIGHUP, SIGINT, SIGQUIT, SIGTERM, 0x0
	};
	sigset_t mask;
	sigemptyset( &mask );
	for (int i = 0; signals[i]; i++)
	     sigaddset( &mask, signals[i] );
	sigprocmask( SIG_BLOCK, &mask, (sigset_t *)0 );  // block'em all

	/* 
	 *  Initialize arguments
	 */
	LogSettings settings = logST.getSettings();
	settings.setMaxLogFileSize(MAX_LOG_SIZE);
	settings.setOutputFile(PHOENIX_LOG);
    # ifdef DEBUG
	settings.printSettings();
    # endif /* DEBUG */
	logST.modifySettings(settings);

	/*
	 *  Parse command line arguments
	 */
	parse_args(argc, argv, logST, &detach);

	/*
	 *  Become a daemon
	 */

	int pidfd = genF.write_pid_file(argc, argv);
	if (detach)
	{
	    Daemonize(pidfd);
	}

	/*
	 *  Now that we are a daemon, we should start logging to syslog.
	 */
	openlog( "paflogd", LOG_PID, LOG_LOCAL7 );    

	/*
	 *  Log the version
	 */
	print_version(LOG_BASE);

	/*
	 *  Start threads
	 */
	Thread signalThread(&sigT);
	Thread readerThread(&readT);
	Thread writerThread(&writeT);
    # if 0
	Thread eventThread(&evtS);

	ServerSocket serverSocket (LogServerThread::SERVER_PORT);
	LogSocketThread socketThread (logST, serverSocket);
	Thread osThread (&socketThread);
    # endif
	signalThread.start();
	readerThread.start();
	writerThread.start();
    # if 0
	osThread.start();
	eventThread.start();

	eventThread.join();
	osThread.join();
    # endif
	writerThread.join();
	readerThread.join();
	signalThread.join();
    }
    catch (Exception &e)
    {
        LOG(LOG_BASE, e.message().c_str());
        exit(1);
    }
    catch (...)
    {
        LOG(LOG_BASE, "Unknown exception");
	assert (0);
    }

    exit( 0 );
}
