Migration from Xoops (CBB) to FloxBB.
=====================================

#==========================================================================
#
#  Base on xoops2punbb
#  By Guillaume Kulakowski (LLaumgui)
#   (c) 2006.
#
#  Web : 	http://www.llaumgui.com
#  eMail : guillaume AT llaumgui DOT com
#
#==========================================================================

#==========================================================================
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, 
# - write to the Free Software
#   Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
# - See http://www.gnu.org/licenses/gpl.html
# 
#==========================================================================

Requirements:
--------------

	* php4 or plus.
	* php-cli to run the script using command line.
	The script work using a browser but output is better on a shell.
	* Little knowlage of php.


Licence :
---------

Gnu/GPL version 3.

Concretely this script converted:
---------------------------------

	* Groups of members: The permissions will be the same for all groups.
		They will therefore be to change thereafter.
	* Members:
		- Xoops allowing multiple groups to one member, that punBB do not, the members are all tarred with the same group members (id = 4).
		- Avatars must all put in the correct folders (img / avatars).
	* The categories.
	* Forums.
	* The topics
	* Positions: It is the big piece, there is a batch processing for large databases.

Other info:
-----------

More info about the conversion of Xoops cbb forum to punBB (the first script) :
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

http://www.llaumgui.com/post/Version-finale-du-script-de-migration-de-Xoops-vers-punBB 


TODO:
-----
	* translate French comment in English
	* check the correct mapping of conversion from old cbb table to fluxbb table
	* create documentation
	* check if fluxbb permit multiple groups on one member
