# -*- coding: utf-8 -*-
##j## BOF

"""
File functions class to use some advanced locking mechanisms.

@internal   We are using epydoc (JavaDoc style) to automate the
            documentation process for creating the Developer's Manual.
            Use the following line to ensure 76 character sizes:
----------------------------------------------------------------------------
@author     direct Netware Group
@copyright  (C) direct Netware Group - All rights reserved
@package    ext_core
@subpackage file
@since      v0.1.00
@license    http://www.direct-netware.de/redirect.php?licenses;w3c
            W3C (R) Software License
"""
"""n// NOTE
----------------------------------------------------------------------------
Extended Core: File
Working with a file abstraction layer
----------------------------------------------------------------------------
(C) direct Netware Group - All rights reserved
http://www.direct-netware.de/redirect.php?ext_core_file

This work is distributed under the W3C (R) Software License, but without any
warranty; without even the implied warranty of merchantability or fitness
for a particular purpose.
----------------------------------------------------------------------------
http://www.direct-netware.de/redirect.php?licenses;w3c
----------------------------------------------------------------------------
#echo(extCoreFileVersion)#
extCore_file/#echo(__FILEPATH__)#
----------------------------------------------------------------------------
NOTE_END //n"""

from os import path
import os,stat,time

try:
#
	import fcntl
	_direct_file_locking_alternative = False
#
except ImportError: _direct_file_locking_alternative = True

try: _unicode_object = { "type": unicode,"str": unicode.encode }
except: _unicode_object = { "type": bytes,"str": bytes.decode }

class direct_file (object):
#
	"""
Get file objects to work with files easily.

@author     direct Netware Group
@copyright  (C) direct Netware Group - All rights reserved
@package    ext_core
@subpackage file
@since      v1.0.0
@license    http://www.direct-netware.de/redirect.php?licenses;w3c
            W3C (R) Software License
	"""

	E_NOTICE = 1
	"""
Error notice: It is save to ignore it
	"""
	E_WARNING = 2
	"""
Warning type: Could create trouble if ignored
	"""
	E_ERROR = 4
	"""
Error type: An error occured and was handled
	"""

	chmod = None
	"""
chmod to set when creating a new file
	"""
	debug = None
	"""
Debug message container
	"""
	error_callback = None
	"""
Function to be called for logging exceptions and other errors
	"""
	readonly = False
	"""
True if file is opened read-only
	"""
	resource = None
	"""
Resource to the opened file
	"""
	resource_file_pathname = ""
	"""
Filename for the resource pointer
	"""
	resource_file_size = -1
	"""
File size of the resource pointer
	"""
	resource_lock = "r"
	"""
Current locking mode
	"""
	time = -1
	"""
Current UNIX timestamp
	"""
	timeout_count = 5
	"""
Retries before timing out
	"""
	umask = None
	"""
umask to set before creating a new file
	"""

	"""
----------------------------------------------------------------------------
Construct the class
----------------------------------------------------------------------------
	"""

	def __init__ (self,default_umask = None,default_chmod = None,current_time = -1,timeout_count = 5,debug = False):
	#
		"""
Constructor __init__ (direct_file)

@param default_umask umask to set before creating a new file
@param default_chmod chmod to set when creating a new file
@param current_time Current UNIX timestamp
@param timeout_count Retries before timing out
@param debug Debug flag
@since v0.1.00
		"""

		if (debug): self.debug = [ "file/#echo(__FILEPATH__)# -file.__init__ (direct_file)- (#echo(__LINE__)#)" ]
		else: self.debug = None

		if (default_chmod == None): self.chmod = default_chmod
		else:
		#
			default_chmod = int (default_chmod,8)
			self.chmod = 0

			if ((1000 & default_chmod) == 1000): self.chmod |= stat.S_ISVTX
			if ((2000 & default_chmod) == 2000): self.chmod |= stat.S_ISGID
			if ((4000 & default_chmod) == 4000): self.chmod |= stat.S_ISUID
			if ((0o100 & default_chmod) == 0o100): self.chmod |= stat.S_IXUSR
			if ((0o200 & default_chmod) == 0o200): self.chmod |= stat.S_IWUSR
			if ((0o400 & default_chmod) == 0o400): self.chmod |= stat.S_IRUSR
			if ((0o010 & default_chmod) == 0o010): self.chmod |= stat.S_IXGRP
			if ((0o020 & default_chmod) == 0o020): self.chmod |= stat.S_IWGRP
			if ((0o040 & default_chmod) == 0o040): self.chmod |= stat.S_IRGRP
			if ((0o001 & default_chmod) == 0o001): self.chmod |= stat.S_IXOTH
			if ((0o002 & default_chmod) == 0o002): self.chmod |= stat.S_IWOTH
			if ((0o004 & default_chmod) == 0o004): self.chmod |= stat.S_IROTH
		#

		self.error_callback = None
		self.readonly = False
		self.resource = None
		self.resource_file_pathname = ""
		self.resource_file_size = -1
		self.resource_lock = "r"

		if (current_time < 0): self.time = -1
		else: self.time = current_time

		if (timeout_count == None): self.timeout_count = 5
		else: self.timeout_count = timeout_count

		self.umask = default_umask
	#

	def __del__ (self):
	#
		"""
Destructor __del__ (direct_file)

@since v0.1.00
		"""

		self.del_direct_file ()
	#

	def del_direct_file (self):
	#
		"""
Destructor del_direct_file (direct_file)

@since v0.1.00
		"""

		self.close ()
		self.resource = None
	#

	def close (self,delete_empty = True):
	#
		"""
Closes an active file session.

@param  delete_empty If the file handle is valid, the file is empty and
        this parameter is true then the file will be deleted.
@return (bool) True on success
@since  v0.1.00
		"""

		global _direct_file_locking_alternative
		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.close (delete_empty)- (#echo(__LINE__)#)")
		f_return = False

		if (self.resource != None):
		#
			f_file_position = self.get_position ()

			if ((not self.readonly) and (delete_empty) and (not f_file_position)):
			#
				self.read (1)
				f_file_position = self.get_position ()
			#

			self.resource.close ()
			f_return = True

			if ((self.resource_lock == "w") and (_direct_file_locking_alternative)):
			#
				f_lock_pathname_os = path.normpath ("{0}.lock".format (self.resource_file_pathname))

				if (path.exists (f_lock_pathname_os)):
				#
					try: os.unlink (f_lock_pathname_os)
					except: pass
				#
			#

			if ((not self.readonly) and (delete_empty) and (f_file_position < 0)):
			#
				f_file_pathname_os = path.normpath (self.resource_file_pathname)
				f_return = True

				try: os.unlink (f_file_pathname_os)
				except: f_return = False
			#

			self.readonly = False
			self.resource = None
			self.resource_file_pathname = ""
			self.resource_file_size = -1
		#

		return f_return
	#

	def eof_check (self):
	#
		"""
Checks if the pointer is at EOF.

@return (bool) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.eof_check ()- (#echo(__LINE__)#)")

		if ((self.resource == None) or (self.resource.tell () == self.resource_file_size)): return True
		else: return False
	#

	def get_handle (self):
	#
		"""
Returns the file pointer.

@return (mixed) File handle on success; false on error
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.get_handle ()- (#echo(__LINE__)#)")

		if (self.resource == None): return False
		else: return self.resource
	#

	def get_position (self):
	#
		"""
Returns the current offset.

@return (mixed) Offset on success; false on error
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.get_position ()- (#echo(__LINE__)#)")

		if (self.resource == None): return False
		else: return self.resource.tell ()
	#

	def lock (self,lock_mode):
	#
		"""
Changes file locking if needed.

@param  lock_mode The requested file locking mode ("r" or "w").
@return (bool) True on success
@since  v0.1.00
		"""

		global _unicode_object
		if (type (lock_mode) == _unicode_object['type']): lock_mode = _unicode_object['str'] (lock_mode,"utf-8")

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.lock ({0})- (#echo(__LINE__)#)".format (lock_mode))
		f_return = False

		if (self.resource == None): self.trigger_error ("file/#echo(__FILEPATH__)# -file.lock ()- (#echo(__LINE__)#) reporting: File resource invalid",self.E_WARNING)
		else:
		#
			if ((lock_mode == "w") and (self.readonly)): self.trigger_error ("file/#echo(__FILEPATH__)# -file.lock ()- (#echo(__LINE__)#) reporting: File resource is in readonly mode",self.E_NOTICE)
			elif (lock_mode == self.resource_lock): f_return = True
			else:
			#
				f_timeout_count = self.timeout_count

				while (f_timeout_count > 0):
				#
					if (self.locking (lock_mode)):
					#
						f_return = True
						f_timeout_count = -1

						if (lock_mode == "w"): self.resource_lock = "w"
						else: self.resource_lock = "r"
					#
					else:
					#
						f_timeout_count -= 1
						time.sleep (1);
					#
				#

				if (f_timeout_count > -1): self.trigger_error ("file/#echo(__FILEPATH__)# -file.lock ()- (#echo(__LINE__)#) reporting: File lock change failed",self.E_ERROR)
			#
		#

		return f_return
	#

	def locking (self,lock_mode,file_pathname = ""):
	#
		"""
Runs flock or an alternative locking mechanism.

@param  lock_mode The requested file locking mode ("r" or "w").
@param  file_pathname Alternative path to the locking file (used for
        _direct_file_locking_alternative)
@return (bool) True on success
@since  v0.1.00
		"""

		global _direct_file_locking_alternative,_unicode_object
		if (type (lock_mode) == _unicode_object['type']): lock_mode = _unicode_object['str'] (lock_mode,"utf-8")
		if (type (file_pathname) == _unicode_object['type']): file_pathname = _unicode_object['str'] (file_pathname,"utf-8")

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.locking ({0},{1})- (#echo(__LINE__)#)".format (lock_mode,file_pathname))
		f_return = False

		if (len (file_pathname) < 1): file_pathname = self.resource_file_pathname
		f_lock_pathname_os = path.normpath ("{0}.lock".format (file_pathname))

		if ((len (file_pathname) > 0) and (self.resource != None)):
		#
			if ((lock_mode == "w") and (self.readonly)): f_return = False
			elif (_direct_file_locking_alternative):
			#
				f_locked_check = path.exists (f_lock_pathname_os)

				if (f_locked_check):
				#
					f_locked_check = False

					if (((self.time < 0) and ((time.time () - self.timeout_count) < (path.getmtime (f_lock_pathname_os)))) or ((self.time - self.timeout_count) < path.getmtime (f_lock_pathname_os))):
					#
						try: os.unlink (f_lock_pathname_os)
						except: pass
					#
					else: f_locked_check = True
				#

				if (lock_mode == "w"):
				#
					if ((f_locked_check) and (self.resource_lock == "w")): f_return = True
					elif (not f_locked_check):
					#
						try:
						#
							file(f_lock_pathname_os,"w").close ()
							f_return = True
						#
						except: pass
					#
				#
				elif ((f_locked_check) and (self.resource_lock == "w")):
				#
					try:
					#
						os.unlink (f_lock_pathname_os)
						f_return = True
					#
					except: pass
				#
				elif (not f_locked_check): f_return = True
			#
			else:
			#
				if (lock_mode == "w"): f_operation = fcntl.LOCK_EX
				else: f_operation = fcntl.LOCK_SH

				try:
				#
					fcntl.flock (self.resource,f_operation)
					f_return = True
				#
				except: pass
			#
		#

		return f_return
	#

	def read (self,bytes = 0,timeout = -1):
	#
		"""
Reads from the current file session.

@param  bytes How many bytes to read from the current position (0 means until
        EOF)
@param  timeout Timeout to use (defaults to construction time value)
@return (mixed) Data on success; false on error
@since  v0.1.00
		"""

		global _unicode_object
		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.read ({0:d},{1:d})- (#echo(__LINE__)#)".format (bytes,timeout))

		f_return = False

		if (self.lock ("r")):
		#
			f_bytes_unread = bytes

			try:
			#
				if (bytes == _unicode_object['type']): f_return = bytes ()
				else: f_return = ""
			#
			except: f_return = ""

			if (self.time < 0): f_timeout_time = time.time ()
			else: f_timeout_time = self.time

			if (timeout < 0): f_timeout_time += self.timeout_count
			else: f_timeout_time += timeout

			while (((f_bytes_unread > 0) or (bytes == 0)) and (not self.eof_check ()) and (f_timeout_time > (time.time ()))):
			#
				if ((f_bytes_unread > 4096) or (bytes == 0)): f_part_size = 4096
				else: f_part_size = f_bytes_unread

				f_return += self.resource.read (f_part_size)
				if (bytes > 0): f_bytes_unread -= f_part_size
			#

			if ((f_bytes_unread > 0) or ((bytes == 0) and (self.eof_check ()))): self.trigger_error ("file/#echo(__FILEPATH__)# -file.read ()- (#echo(__LINE__)#) reporting: Timeout occured before EOF",self.E_ERROR)
		#

		return f_return
	#

	def resource_check (self):
	#
		"""
Returns true if the file resource is available.

@return (bool) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.resource_check ()- (#echo(__LINE__)#)")

		if (self.resource == None): return False
		else: return True
	#

	def seek (self,offset):
	#
		"""
Seek to a given offset.

@param  offset Seek to the given offset
@return (bool) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.seek ({0:d})- (#echo(__LINE__)#)".format (offset))

		if (self.resource == None): return False
		else:
		#
			self.resource.seek (offset)
			return True
		#
	#

	def set_trigger (self,py_function = None):
	#
		"""
Set a given function to be called for each exception or error.

@param py_function Python function to be called
@since v0.1.00
		"""

		self.error_callback = py_function
	#

	def trigger_error (self,message,message_type = None):
	#
		"""
Calls a user-defined function for each exception or error.

@param message Error message
@param message_type Error type
@since v0.1.00
		"""

		if (message_type == None): message_type = self.E_NOTICE
		if (self.error_callback != None): self.error_callback (message,message_type)
	#

	def truncate (self,new_size):
	#
		"""
Truncates the active file session.

@param  new_size Cut file at the given byte position
@return (bool) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.truncate ({0:d})- (#echo(__LINE__)#)".format (new_size))

		if (self.lock ("w")):
		#
			self.resource.truncate (new_size)
			self.resource_file_size = new_size
			return True
		#
		else: return False
	#

	def open (self,file_pathname,readonly = False,file_mode = "a+b"):
	#
		"""
Opens a file session.

@param  file_pathname Path to the requested file
@param  readonly Open file in readonly mode
@param  file_mode Filemode to use
@return (bool) True on success
@since  v0.1.00
		"""

		global _unicode_object
		if (type (file_pathname) == _unicode_object['type']): file_pathname = _unicode_object['str'] (file_pathname,"utf-8")
		if (type (file_mode) == _unicode_object['type']): file_mode = _unicode_object['str'] (file_mode,"utf-8")

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.open ({0},readonly,{1})- (#echo(__LINE__)#)".format (file_pathname,file_mode))

		if (self.resource == None):
		#
			f_created_check = True
			f_file_pathname_os = path.normpath (file_pathname)
			f_return = True

			if (readonly): self.readonly = True
			else: self.readonly = False

			if (path.exists (f_file_pathname_os)): f_created_check = False
			elif (not self.readonly):
			#
				if (self.umask != None): os.umask (int (self.umask,8))
			#
			else: f_return = False

			if (f_return):
			#
				try: self.resource = open (f_file_pathname_os,file_mode)
				except: pass
			#
			else: self.trigger_error ("file/#echo(__FILEPATH__)# -file.open ()- (#echo(__LINE__)#) reporting: Failed opening {0} - file does not exist".format (file_pathname),self.E_NOTICE)

			if (self.resource == None):
			#
				if (f_created_check):
				#
					try: os.unlink (f_file_pathname_os)
					except: pass
				#
			#
			else:
			#
				if ((self.chmod != None) and (f_created_check)): os.chmod (f_file_pathname_os,self.chmod)
				self.resource_file_pathname = file_pathname

				if (self.lock ("r")): self.resource_file_size = path.getsize (f_file_pathname_os)
				else:
				#
					self.close (f_created_check)
					self.resource = None
				#
			#
		#
		else: f_return = False

		return f_return
	#

	def write (self,data,timeout = -1):
	#
		"""
Write content to the active file session.

@param  data (Over)write file with the data content at the current position
@param  timeout Timeout to use (defaults to construction time value)
@return (bool) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file.write (data,{0:d})- (#echo(__LINE__)#)".format (timeout))
		f_return = False

		if (self.lock ("w")):
		#
			f_bytes_unwritten = len (data)
			f_bytes_written = self.resource.tell ()

			if (f_bytes_written + f_bytes_unwritten > self.resource_file_size): f_new_size = f_bytes_written + f_bytes_unwritten - self.resource_file_size
			else: f_new_size = 0

			f_bytes_written = 0
			f_return = True

			if (self.time < 0): f_timeout_time = time.time ()
			else: f_timeout_time = self.time

			if (timeout < 0): f_timeout_time += self.timeout_count
			else: f_timeout_time += timeout

			while ((f_return) and (f_bytes_unwritten > 0) and (f_timeout_time > (time.time ()))):
			#
				if (f_bytes_unwritten > 4096): f_part_size = 4096
				else: f_part_size = f_bytes_unwritten

				try:
				#
					self.resource.write (data[f_bytes_written:(f_bytes_written + f_part_size)])
					f_bytes_unwritten -= f_part_size
					f_bytes_written += f_part_size
				#
				except: f_return = False
			#

			if (f_bytes_unwritten > 0):
			#
				f_return = False
				self.resource_file_size = path.getsize (path.normpath (self.resource_file_pathname))
				self.trigger_error ("file/#echo(__FILEPATH__)# -file.write ()- (#echo(__LINE__)#) reporting: Timeout occured before EOF",self.E_ERROR)
			#
			elif (f_new_size > 0): self.resource_file_size = f_new_size
		#

		return f_return
	#
#

##j## EOF