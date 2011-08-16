# $Id: import.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com

This spec is a high level design of the import process.

Import consists of several steps:

1. Read command line options

2. Call to readConf() to load system variables and defaults into memory

3. Call to init() to read cmuConfig.xml and third partyxml files into memory

4. Check to see that installed softwarematches third party xml

5. Unpack the transferred configuration data

6. Loop through cmuConfig.xml data and execute the specified adjust scripts

7. Loop throughcmuConfig.xml data and execute the specified conflict scripts

8. Loop throughcmuConfig.xml data and execute the specified scanin scripts

General Code Example:

Import {
  readOptions();

  unpack(dataLocation);

  configData = readConf();
  initData = init();

  if initData.ThirdParty != configData.installedSoftware
    log("warning did not find initData.ThirdParty on system. Will not import")

  while (initData.adjust) {
      execute adjust;
      next adjust;
    }
  
   while (initData.conflict) {
     execute conflict;
     next conflict;
   }

   while (initData.scanin) {
     execute scanin;
     next scanin;
   }

}   




