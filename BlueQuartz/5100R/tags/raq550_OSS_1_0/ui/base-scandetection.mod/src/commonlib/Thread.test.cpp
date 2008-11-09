#include <iostream>
#include <unistd.h>

#include "Thread.h"

Mutex mutex;

class foo : public Runnable
{
public:
    void run ()
	{
	    mutex.lock ();
	    cout << "foo entered\n";
	    mutex.unlock ();
	}
};

int main (int argc, char * argv[])
{
    foo bar;
    Thread th (&bar);

    cout << "main() entered\n";

    mutex.lock ();
    th.start ();

    for (int i = 0; i < 7; i++)
    {
	cout << "main() loop\n";
	sleep (1);
    }

    mutex.unlock ();

    void * result = th.join ();
    cout << "thread result is " << result << endl;

    return 0;
}
