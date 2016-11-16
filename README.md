# filecount
Recusive File cont browser from web or cron/CLI email notifier

in CLI following arguments are accepted:

-mmaxcount  maximum number of files allowed, for example 100000, only mandatory argument

-ppath      path from where start checking, decault to local directory

-eemail     e-mail address to send notification

-ttreshold  percentage (as number)  of maxcount, when warning email is sent

-ddepth     number of directory levels to print

-v          verbose output

-n          no output at all (helpful if running from cron)


ps. no space is allowed between option and value.
