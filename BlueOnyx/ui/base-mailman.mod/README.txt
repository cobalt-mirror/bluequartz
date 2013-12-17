To-do list:

- Add Active Monitor component to monitor MailMan status
- Add GUI page to enable/disable MailMan
- Extend "Advanced" options with additional sensible switches
- Add GUI link to /mailman/admin/<listname>/
- Add GUI link to /pipermail/<listname>/
- Test how CMU deals with all of this. But as we renamed all keys it'll probably 
  go bust. May have to revert those back or have to mess with CMU internals to 
  'bend' the majordomo related keys to mailman keys. Fun, fun, fun.
