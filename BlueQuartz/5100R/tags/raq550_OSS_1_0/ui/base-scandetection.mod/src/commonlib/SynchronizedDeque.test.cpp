#include <iostream>
#include <string>
#include "SynchronizedDeque.h"

int main()
{
    SynchronizedDeque<string> syncDeque;

    syncDeque.push_back ("hello ");
    syncDeque.push_back ("how ");
    syncDeque.push_back ("are ");
    syncDeque.push_back ("you?");

    while (syncDeque.size() > 0)
    {
	string str = syncDeque.front();
	cout << str << endl;
	syncDeque.pop_front();
    }

    return 0;
}
