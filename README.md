php_srrFileManager
==================

== DESCRIPTION ==

A unique PHP script of less than 100kb which help you to list, add, edit, move, delete, rename, search, etc files and directories. It doesn't need a database, so it's fast to "install" and use :)


== INSTALLATION ==

1. Simply put the file srrFilemanager.php inside some directory that is accesible via web.

2. edit the a_users array in the $config variable for specify a username, password and "filefolder".


== USERS & FOLDERS ==

- in the array a_users you can add as users as you need
- each one has permission to access to the files/directories contained under the "filefolder" directory that you specify for them
- this "filefolder" usually take the value: ./ (for access the same directory where you put this script)
- this other value: ../  will give access to the parent directory to the container of this script
- so, briefly: the "filefolder" is a RELATIVE path !


== PROBLEMS ==

- if you have problems for move, edit, upload, etc... probably is due to a problem with permissions:

 + perhaps the owner of the script is not the necesary for read/write the files
 + or perhaps the files you need read/write has quite low permissions, so you must to increase it

== SECURITY ==

- this tool is very dangerous for be accessed by bad people, so although it use cookies for login, it's recommendable that you take one of these measures:

 + if you can is better that you access the script using SSL certificate (https://)
 + upload this script only when you need it and after delete it
 + possibly the best option is this: put the script inside a directory protected with user/password through Apache directory protection