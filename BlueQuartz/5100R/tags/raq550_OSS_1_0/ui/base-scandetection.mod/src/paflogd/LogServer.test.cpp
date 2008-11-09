/*
**  To compile: make LogServer.test DEBUG=-DDEBUG
*/

#include <cstdio>
#include <stdlib.h>
#include <unistd.h>
#include <errno.h>

#include "Socket.h"
#include "CommandPacket.h"
#include "Exception.h"
#include "LogServerThread.h"
#include "LogSettings.h"
#include "ObjectSocket.h"
#include "utility.h"

using namespace std;

int    debug = 1;
int    argc;
char **argv;

bool          compressArchiveFiles;   // Compress log files?
time_t        rotationTime;           // Time field
unsigned char dayOfMonth;             // dailyTimeTable
bool          daysOfWeek[7];          // weeklyTimeTable 0=Sun, 1=Mon
short         customHours;            // customTimeTable (hrs. nxt rotate)
unsigned char rotationFrequency;      // none, daily, weekly, etc.

unsigned int  maxLogFileSize;         // In megabytes (k is better)
int           maxNumberLogFiles;
unsigned int  minFreeLogPartition;    // In megabytes (k is better)
string        outputFile;             // name of log file on disk

LogSettings   initial;
LogSettings   newsets;

void readSettings()
{
    try
    {
	Socket socket ("127.0.0.1", LogServerThread::SERVER_PORT);
	ObjectSocket os (&socket);

	CommandPacket cmd (LogServerThread::GET_SETTINGS);
	os.writeObject (cmd);
	os.flush ();

	CommandPacket* response = dynamic_cast<CommandPacket*>(os.readObject());

	if (response == 0)
	{
	    throw RuntimeException ("GET_SETTINGS: invalid response");
	}

	LogSettings* settings = dynamic_cast<LogSettings*>(response->getAttachment());

	if (settings == 0)
	{
	    throw RuntimeException (
		"pafdaemon.c:getalertsettings(): invalid attachment");
	}

	compressArchiveFiles = settings->getCompressArchiveFiles();
	rotationTime = settings->getRotateTime();
	dayOfMonth = settings->getDayOfMonth();
	for (int i=0; i < 7; i++) 
	  daysOfWeek[i] = settings->isWeekdaySet(i);
	customHours = settings->getCustomHours();
	rotationFrequency = settings->getRotationFrequency();
	maxLogFileSize = settings->getMaxLogFileSize();
	maxNumberLogFiles = settings->getMaxNumberLogFiles();
	minFreeLogPartition = settings->getMinFreeLogPartition();
	outputFile = settings->getOutputFile();

	initial = *settings;
	initial.printSettings(0);
    }
    catch (Exception& ex)
    {
        cout << ex.message() << endl;
    }    
    catch (...)
    {
	cout << "unexpected exception caught" << endl;
    }
}

void writeSettings()
{
    try
    {
	Socket socket ("127.0.0.1", LogServerThread::SERVER_PORT);
	ObjectSocket os (&socket);

	LogSettings * settings = new LogSettings ();

	settings->setMaxLogFileSize(maxLogFileSize);
	settings->setMaxNumberLogFiles(maxNumberLogFiles);
	settings->setMinFreeLogPartition(minFreeLogPartition);
	settings->setOutputFile(outputFile);
	settings->setCompressArchiveFiles(compressArchiveFiles);
	settings->setRotationTime(rotationTime);
	settings->setDayOfMonth(dayOfMonth);
	settings->setDaysOfWeek(daysOfWeek);
	settings->setCustomHours(customHours);
	settings->setRotationFrequency(rotationFrequency);

	CommandPacket setSettings (LogServerThread::SET_SETTINGS, settings);
	os.writeObject (setSettings);
	os.flush ();

	delete settings;
    }
    catch (Exception& ex)
    {
        cout << ex.message() << endl;
    }    
    catch (...)
    {
	cout << "unexpected exception caught" << endl;
    }
}

/*
 * strtolong -  convert the characters at s to an positive long integer.
 *              If there is an error (any non-numeric characters), log
 *              with the error message in errmess at 'log_level' and
 *              return -1.  
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


void usage()
{
  printf("Usage: %s [options]", argv[0]);
  cout << "
	  -s maxLogFileSize (int)
	  -n maxNumberLogFiles (int)
	  -f minFreeLogPartition (int)
	  -o outputFile (string)
	  -c compressArchiveFiles (boolean)
	  -t rotationTime (use date +\%s)
	  -m dayOfMonth (1-31)
	  -w daysOfWeek (can use multiple times; 0=Sun, 1=Mon...)
	  -h customHours (int)
	  -r rotationFrequency (int)
               ROTATE_CUSTOM   = 1
               ROTATE_DAILY    = 2
               ROTATE_MONTHLY  = 3
               ROTATE_NONE     = 4
               ROTATE_WEEKLY   = 5
          " << endl;
}

void parse_args()
{
    extern   char *optarg;
    //    extern   int   optind;
    int            opt;

    while (( opt = getopt( argc, argv, "s:n:f:o:c:t:m:w:h:r:" )) != EOF )
    {
        switch (opt)
        {
	case 's':
	  maxLogFileSize = strtolong(optarg, "-s");	      break;
	case 'n':
	  maxNumberLogFiles = strtolong(optarg, "-n");      break;
	case 'f':
	  minFreeLogPartition = strtolong(optarg, "-f");    break;
	case 'o':
	  outputFile = optarg;	                      break;
	case 'c':
	  compressArchiveFiles = strtolong(optarg, "-c");     break;
	case 't':
	  rotationTime = strtolong(optarg, "-t");	      break;
	case 'm':
	  dayOfMonth = strtolong(optarg, "-m");	      break;
	case 'w':
	  {
	    int i = strtolong(optarg, "-w");
	    if (i >= 7) {
	      cerr << "Weekday must be 0-6" << endl;
	      exit (1);
	    }
	    daysOfWeek[i] = true;
	    break;
	  }
	case 'h':
	  customHours = strtolong(optarg, "-h");	      break;
	case 'r':
	  rotationFrequency = strtolong(optarg, "-r");      break;

	/* Unknown option or help */
	case '?':                     
	  usage();
	  exit (0);
	  break;
        }
    }
}

int main(int argc2, char **argv2)
{
    argc = argc2;
    argv = argv2;

    readSettings();
    parse_args();

    writeSettings();
    readSettings();
    return 0;
}
