#include "ObjectFile.h"
#include "DataUnit.h"
#include "Exception.h"

static void out ()
{
    ObjectFile of("bja.test");
    DataUnit unit ("hello");
    of.writeObject(unit);
}

static void in ()
{
    ObjectFile of("bja.test");
    DataUnit * unit = dynamic_cast<DataUnit*>(of.readObject());
    cout << unit->data() << endl;
    return;
}

int main ()
{
    try
    {
	out();
	in();
    }
    catch (Exception& ex)
    {
	cout << ex.message() << endl;
    }

    return 0;
}
