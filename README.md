php_srrFileManager
==================

== DESCRIPTION ==

A simple PHP script of less than 100kb which help you to list, add, edit, move, delete, rename, search, etc files and directories. It doesn't need a database, so it's fast to "install" and use :)

It contain several languages interface: english, catalan, castillian (spanish), germany.
It contain two skins for style displaying: mint, night.


== INSTALLATION ==

1. Simply create a folder within the directory that you wants to "explore" and put inside these files

2. edit the a_users array in the $config variable for specify a username, password and "filefolder" (read below before use it)


== USERS & FOLDERS ==

- in the array a_users you can add as users as you need
- each one has permission to access to the files/directories contained under the "filefolder" directory that you specify for them
- this "filefolder" usually take the value: ../ (will give access to the parent directory to the container of this script)
- so, briefly: the "filefolder" is a RELATIVE path !

- Note: in the downloaded package you will find the directory "tmp" which is only for development purpose, so it is not necesary for the script, and you can delete it.


== PROBLEMS ==

- if you have problems for move, edit, upload, etc... probably is due to a problem with permissions:

 + perhaps the owner of the script is not the necesary for read/write the files
 + or perhaps the files you need read/write has quite low permissions, so you must to increase it


== SECURITY ==

- this tool is very dangerous for be accessed by bad people, so although it use cookies for login, it's recommendable that you take one of these measures:

 + if you can is better that you access the script using SSL certificate (https://)
 + upload this script only when you need it and after delete it
 + possibly the best option is this: put the script inside a directory protected with user/password through Apache directory protection


== LICENSE ==

LICENSE: GPL v2

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.


== TEAM ==

- As developers & translators: 
 + Sergi Rodrigues (from 2009)
 + Daniel Schlichtholz (from Aug 2012)


== VERSIONS LOG ==

 + see more details at: https://github.com/caos30/php_srrFileManager

== 1.0 [2012-08-14]

 + Initial version

== 1.1 [2012-09-01]

 + Fixed login issues
 + Solved PHP notices and warnings
 + Added language: germany
 + Frontend: sort language list by name
 + Removed some unused variables

== 1.2 [2012-09-04]

 + extracted the language translations from the main PHP file to the folder "languages" for be compatible with oTranCe 
 + the translations are now defined within an array instead to be defined as PHP constants
 + extracted the css styles from the main PHP file to the folder "skins"
 + renamed the main PHP script ("srrFileManager.php") to "index.php"
