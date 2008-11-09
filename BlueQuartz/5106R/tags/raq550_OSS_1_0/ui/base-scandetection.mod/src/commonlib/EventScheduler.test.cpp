#include <unistd.h>

#include "EventScheduler.h"
#include "Exception.h"

int debug = 0;

class PrintCommand : public Runnable
{
private:
    string str;

public:
    PrintCommand (const string& s) : str(s) {}
    void run () { cout << str << endl; }
};

int main()
{
    try
    {
	EventScheduler scheduler;
	Thread th (&scheduler);
	th.start ();
	PrintCommand cmd ("Hello, world!");
	Event evt (time(0) + 5, &cmd, 0, true, 4);
	scheduler.addEvent (evt);
	sleep (15);
	scheduler.stop ();
	th.join ();
    }
    catch (const Exception& ex)
    {
	cout << ex.message() << endl;
    }

    return 0;
}
