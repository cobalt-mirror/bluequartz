# $Id: export.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com

This spec is a high level design of the export process.

Export consists of several steps:

1. Read command line options

2. Call to readConf() to load system variables and defaults into memory

3. Call to init() to read cmuConfig.xml and third party xml files into memory

4. Check to see that installed software matches third party xml

5. Loop through cmuConfig.xml data and execute the specified scanout scripts

6. Package the resulting config data and tar files

General Code Example:

Export {
  readOptions(); 

  configData = readConf();
  initData = init();

  if initData.ThirdParty != configData.installedSoftware
    log("warning did not find initData.ThirdParty on system. Will not export");
  
    while (initData.scanout) {
      execute scanout.script;
      next scanout.script;
    }

  pack(dataLocation);

}
  
  
