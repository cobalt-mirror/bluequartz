# include <stdio.h>
# include <stdlib.h>
# include <time.h>
# include <string>

# include "TimeTable.h"

// g++ -g -o TimeTable TimeTable.test.cpp TimeTable.cpp

time_t now = time(0);
unsigned int H = 11;
unsigned int M = 30;


void printstruct (time_t now, struct tm *t = 0)
{
  if (t == 0)
      t = localtime(&now);

  printf ("tm_sec=%d tm_min=%d tm_hour=%d " \
	  "tm_mday=%d tm_mon=%d tm_year=%d " \
	  "tm_wday=%d tm_yday=%d tm_isdst=%d \n",
	  t->tm_sec ,
	  t->tm_min ,
	  t->tm_hour,
	  t->tm_mday,
	  t->tm_mon ,
	  t->tm_year,
	  t->tm_wday,
	  t->tm_yday,
	  t->tm_isdst);
}


void daily ()
{
  DailyTimeTable dt (H, M);
  time_t stime = dt.nextTime(now);

  printf ("\n");
  printf ("now = %ld next time= %ld\n", now, stime);
  printf ("diff = %d hours from now = %02d:%02d\n", stime - now, 
	  (stime - now) / 3600, 
	  ((stime - now) % 3600) / 60);
  string str = dt.timeDescription();
  printf("String: %s Len: %d \n", str.c_str(), str.length());
}

void weekly ()
{
 // unsigned char ww = WeeklyTimeTable::TUESDAY | WeeklyTimeTable::THURSDAY;
  //unsigned char ww = WeeklyTimeTable::SUNDAY;
  unsigned char ww = WeeklyTimeTable::SUNDAY | WeeklyTimeTable::MONDAY;

  WeeklyTimeTable wt (H, M, ww);
  time_t stime = wt.nextTime(now);

  printf ("\n");
  printf ("now = %ld next time= %ld\n", now, stime);
  printf ("diff = %d hours from now = %02d:%02d\n", stime - now, 
	  (stime - now) / 3600, 
	  ((stime - now) % 3600) / 60);
  string str = wt.timeDescription();
  printf("String: %s Len: %d \n", str.c_str(), str.length());

}

void monthly ()
{
  int day = 2;

  MonthlyTimeTable wt (H, M, day);
  time_t stime = wt.nextTime(now);

  printf ("\n");
  printf ("now = %ld next time= %ld\n", now, stime);
  printf ("diff = %d hours from now = %02d:%02d\n", stime - now, 
	  (stime - now) / 3600, 
	  ((stime - now) % 3600) / 60);
  string str = wt.timeDescription();
  printf("String: %s Len: %d \n", str.c_str(), str.length());
}

void monthly2 ()
{
  int day = 2;

  MonthlyTimeTable wt (H, M, day);
  time_t stime = wt.nextTime(now+2419200);

  printf ("\n");
  printf ("now = %ld next time= %ld\n", now, stime);
  printf ("diff = %d hours from now = %02d:%02d\n", stime - now, 
	  (stime - now) / 3600, 
	  ((stime - now) % 3600) / 60);
  string str = wt.timeDescription();
  printf("String: %s Len: %d \n", str.c_str(), str.length());

  time_t stime2 = wt.nextTime(stime+1);
  printf ("now = %ld next time= %ld delta=%ld\n", stime, stime2, stime2-stime);
}

# define HOURS 10
# define PAST 30

void custom()
{
  time_t settime = now - 3600 * PAST;
  time_t calltime = now;

  CustomTimeTable ct (settime, HOURS);
  time_t stime = ct.nextTime(calltime);

  printf ("\n");
  printf ("Custom Test:\n");
  printf ("settime=%ld calltime=%ld scheduletime=%ld\n", settime, calltime, stime);

  printf ("diff set/call=%d diff call/sched=%d diff set/sched=%d hours from now=%d\n",
	  calltime - settime,
	  stime - calltime,
	  stime - settime,
	  (stime - settime) / 3600);
  string str = ct.timeDescription();
  printf("String: %s Len: %d \n", str.c_str(), str.length());
}

main()
{
  printf ("Time now: %ld\n", now);
  printstruct(now);

  daily();
  weekly();
  monthly();
  custom();
  monthly2();
}
