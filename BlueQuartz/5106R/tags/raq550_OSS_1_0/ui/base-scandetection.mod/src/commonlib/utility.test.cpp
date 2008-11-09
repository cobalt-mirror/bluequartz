#include <iostream>
#include "utility.h"

int main ()
{
    unsigned long megs;

    int rc = getAvailableDisk ("utility.cpp", &megs);
    assert (rc == 0);
    cout << "Available disk space is: " << megs << " megs.\n";

    return 0;
}
