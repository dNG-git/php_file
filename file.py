# -*- coding: utf-8 -*-
##j## BOF

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
"""*
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

from os import path
import os,stat,time

try:
#
	import fcntl
	_direct_file_locking_alternative = False
#
except ImportError,g_handled_exception: _direct_file_locking_alternative = True

class direct_file (object):
#
	"""
This class provides a bridge between PHP and XML to read XML on the fly.

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
	resource_file_path = ""
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

	def __init__ (self,f_umask = None,f_chmod = None,f_time = -1,f_timeout_count = 5,f_debug = False):
	#
		"""
Constructor __init__ (direct_file)

@param f_charset Charset to be added as information to XML output
@param f_parse_only Parse data only
@param f_time Current UNIX timestamp
@param f_timeout_count Retries before timing out
@param f_ext_xml_path Path to the XML parser files.
@param f_debug Debug flag
@since v0.1.00
		"""

		if (f_debug): self.debug = [ "file/#echo(__FILEPATH__)# -file->__init__ (direct_file)- (#echo(__LINE__)#)" ]
		else: self.debug = None

		if (f_chmod == None): self.chmod = f_chmod
		else:
		#
			f_chmod = int (f_chmod,8)
			self.chmod = 0

			if ((1000 & f_chmod) == 1000): self.chmod |= stat.S_ISVTX
			if ((2000 & f_chmod) == 2000): self.chmod |= stat.S_ISGID
			if ((4000 & f_chmod) == 4000): self.chmod |= stat.S_ISUID
			if ((0100 & f_chmod) == 0100): self.chmod |= stat.S_IRUSR
			if ((0200 & f_chmod) == 0200): self.chmod |= stat.S_IWUSR
			if ((0400 & f_chmod) == 0400): self.chmod |= stat.S_IXUSR
			if ((0010 & f_chmod) == 0010): self.chmod |= stat.S_IRGRP
			if ((0020 & f_chmod) == 0020): self.chmod |= stat.S_IWGRP
			if ((0040 & f_chmod) == 0040): self.chmod |= stat.S_IXGRP
			if ((0001 & f_chmod) == 0001): self.chmod |= stat.S_IROTH
			if ((0002 & f_chmod) == 0002): self.chmod |= stat.S_IWOTH
			if ((0004 & f_chmod) == 0004): self.chmod |= stat.S_IXOTH
		#

		self.error_callback = None
		self.readonly = False
		self.resource = None
		self.resource_file_path = ""
		self.resource_file_size = -1
		self.resource_lock = "r"

		if (f_time < 0): self.time = time.time ()
		else: self.time = f_time

		if (f_timeout_count == None): self.timeout_count = 5
		else: self.timeout_count = f_timeout_count

		self.umask = f_umask
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

	def close (self,f_delete_empty = True):
	#
		"""
Closes an active file session.

@param  f_delete_empty If the file handle is valid, the file is empty and
        this parameter is true then the file will be deleted.
@return (boolean) True on success
@since  v0.1.00
		"""

		global _direct_file_locking_alternative
		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->close (+f_delete_emtpy)- (#echo(__LINE__)#)")
		f_return = False

		if (self.resource != None):
		#
			f_file_position = self.get_position ()

			if ((not self.readonly) and (f_delete_empty) and (not f_file_position)):
			#
				self.read (1)
				f_file_position = self.get_position ()
			#

			self.resource.close ()
			f_return = True

			if ((self.resource_lock == "w") and (_direct_file_locking_alternative)):
			#
				f_lock_path_os = path.normpath ("%s.lock" % self.resource_file_path)

				if (path.exists (f_lock_path_os)):
				#
					try: os.unlink (f_lock_path_os)
					except Exception,f_unhandled_exception: pass
				#
			#

			if ((not self.readonly) and (f_delete_empty) and (f_file_position < 0)):
			#
				f_file_path_os = path.normpath (self.resource_file_path)
				f_return = True

				try: os.unlink (f_file_path_os)
				except Exception,f_handled_exception: f_return = False
			#

			self.readonly = False
			self.resource = None
			self.resource_file_path = ""
			self.resource_file_size = -1
		#

		return f_return
	#

	def eof_check (self):
	#
		"""
Checks if the pointer is at EOF.

@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->eof_check ()- (#echo(__LINE__)#)")

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

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->get_handle ()- (#echo(__LINE__)#)")

		if (self.resource == None): return False
		else: return self.resource
	#

	def get_position (self):
	#
		"""
Returns the current offset.

@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->get_position ()- (#echo(__LINE__)#)")

		if (self.resource == None): return False
		else: return self.resource.tell ()
	#

	def lock (self,f_mode):
	#
		"""
Changes file locking if needed.

@param  f_mode The requested file locking mode ("r" or "w").
@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->lock (%s)- (#echo(__LINE__)#)" % f_mode)
		f_return = False

		if (self.resource == None): self.trigger_error ("file/#echo(__FILEPATH__)# -file->lock ()- (#echo(__LINE__)#) reporting: File resource invalid",self.E_WARNING)
		else:
		#
			if ((f_mode == "w") and (self.readonly)): self.trigger_error ("file/#echo(__FILEPATH__)# -file->lock ()- (#echo(__LINE__)#) reporting: File resource is in readonly mode",self.E_NOTICE)
			elif (f_mode == self.resource_lock): f_return = True
			else:
			#
				f_timeout_count = self.timeout_count

				while (f_timeout_count > 0):
				#
					if (self.locking (f_mode)):
					#
						f_return = True
						f_timeout_count = -1

						if (f_mode == "w"): self.resource_lock = "w"
						else: self.resource_lock = "r"
					#
					else:
					#
						f_timeout_count -= 1
						time.sleep (1);
					#
				#

				if (f_timeout_count > -1): self.trigger_error ("file/#echo(__FILEPATH__)# -file->lock ()- (#echo(__LINE__)#) reporting: File lock change failed",self.E_ERROR)
			#
		#

		return f_return
	#

	def locking (self,f_mode,f_file_path = ""):
	#
		"""
Runs flock or an alternative locking mechanism.

@param  string $f_mode The requested file locking mode ("r" or "w").
@param  string $f_file_path Alternative path to the locking file (used for
        _direct_file_locking_alternative)
@return (boolean) True on success
@since  v0.1.00
		"""

		global _direct_file_locking_alternative
		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->locking (%s,%s)- (#echo(__LINE__)#)" % ( f_mode,f_file_path ))
		f_return = False

		if (len (f_file_path) < 1): f_file_path = self.resource_file_path
		f_lock_path_os = path.normpath ("%s.lock" % f_file_path)

		if ((len (f_file_path) > 0) and (self.resource != None)):
		#
			if ((f_mode == "w") and (self.readonly)): f_return = False
			elif (_direct_file_locking_alternative):
			#
				f_locked_check = path.exists (f_lock_path_os)

				if (f_locked_check):
				#
					f_locked_check = False

					if ((self.time - self.timeout_count) < path.getmtime (f_lock_path_os)):
					#
						try: os.unlink (f_lock_path_os)
						except Exception,f_unhandled_exception: pass
					#
					else: f_locked_check = True
				#

				if (f_mode == "w"):
				#
					if ((f_locked_check) and (self.resource_lock == "w")): f_return = True
					elif (not f_locked_check):
					#
						try:
						#
							file(f_lock_path_os,"w").close ()
							f_return = True
						#
						except Exception,f_unhandled_exception: pass
					#
				#
				elif ((f_locked_check) and (self.resource_lock == "w")):
				#
					try:
					#
						os.unlink (f_lock_path_os)
						f_return = True
					#
					except Exception,f_unhandled_exception: pass
				#
				elif (not f_locked_check): f_return = True
			#
			else:
			#
				if (f_mode == "w"): f_operation = fcntl.LOCK_EX
				else: f_operation = fcntl.LOCK_SH

				try:
				#
					fcntl.flock (self.resource,f_operation)
					f_return = True
				#
				except Exception,f_unhandled_exception: pass
			#
		#

		return f_return
	#

	def read (self,f_bytes = 0,f_timeout = -1):
	#
		"""
Reads from the current file session.

@param  f_bytes How many bytes to read from the current position (0 means
        until EOF)
@param  f_timeout Timeout to use (defaults to construction time value)
@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->read (%i,%i)- (#echo(__LINE__)#)" % ( f_bytes,f_timeout ))
		f_return = False

		if (self.lock ("r")):
		#
			f_bytes_unread = f_bytes
			f_return = ""

			if (f_timeout < 0): f_timeout_time = self.time + self.timeout_count
			else: f_timeout_time = self.time + f_timeout

			while (((f_bytes_unread > 0) or (f_bytes == 0)) and (not self.eof_check ()) and (f_timeout_time > (time.time ()))):
			#
				if ((f_bytes_unread > 4096) or (f_bytes == 0)): f_part_size = 4096
				else: f_part_size = f_bytes_unread

				f_return += self.resource.read (f_part_size)
				if (f_bytes > 0): f_bytes_unread -= f_part_size
			#

			if ((f_bytes_unread > 0) or ((f_bytes == 0) and (self.eof_check ()))): self.trigger_error ("file/#echo(__FILEPATH__)# -file->lock ()- (#echo(__LINE__)#) reporting: Timeout occured before EOF",self.E_ERROR)
		#

		return f_return
	#

	def resource_check (self):
	#
		"""
Returns true if the file resource is available.

@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->resource_check ()- (#echo(__LINE__)#)")

		if (self.resource == None): return False
		else: return True
	#

	def seek (self,f_offset):
	#
		"""
Seek to a given offset.

@param  f_offset Seek to the given offset
@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->seek (%i)- (#echo(__LINE__)#)" % f_offset)

		if (self.resource == None): return False
		else:
		#
			self.resource.seek (f_offset)
			return True
		#
	#

	def set_trigger (self,f_function = None):
	#
		"""
Set a given function to be called for each exception or error.

@param f_function Python function to be called
@since v0.1.00
		"""

		self.error_callback = f_function
	#

	def trigger_error (self,f_message,f_type = None):
	#
		"""
Calls a user-defined function for each exception or error.

@param f_message Error message
@param f_type Error type
@since v0.1.00
		"""

		if (f_type == None): f_type = self.E_NOTICE
		if (self.error_callback != None): self.error_callback (f_message,f_type)
	#

	def truncate (self,f_new_size):
	#
		"""
Truncates the active file session.

@param  f_new_size Cut file at the given byte position
@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->truncate (%i)- (#echo(__LINE__)#)" % f_new_size)

		if (self.lock ("w")):
		#
			self.resource.truncate (f_new_size)
			self.resource_file_size = f_new_size
			return True
		#
		else: return False
	#

	def open (self,f_file_path,f_readonly = False,f_file_mode = "a+b"):
	#
		"""
Opens a file session.

@param  f_file_path Path to the requested file
@param  f_readonly Open file in readonly mode
@param  f_file_mode Filemode to use
@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->open (%s,+f_readonly,%s)- (#echo(__LINE__)#)" % ( f_file_path,f_file_mode ))

		if (self.resource == None):
		#
			f_created_check = True
			f_file_path_os = path.normpath (f_file_path)
			f_return = True

			if (f_readonly): self.readonly = True
			else: self.readonly = False

			if (path.exists (f_file_path_os)): f_created_check = False
			elif (not self.readonly):
			#
				if (self.umask != None): os.umask (int (self.umask,8))
			#
			else: f_return = False

			if (f_return):
			#
				try: self.resource = file (f_file_path_os,f_file_mode)
				except Exception,f_unhandled_exception: pass
			#
			else: self.trigger_error ("file/#echo(__FILEPATH__)# -file->open ()- (#echo(__LINE__)#) reporting: Failed opening %s - file does not exist" % f_file_path,self.E_NOTICE)

			if (self.resource == None):
			#
				if (f_created_check):
				#
					try: os.unlink (f_file_path_os)
					except Exception,f_unhandled_exception: pass
				#
			#
			else:
			#
				if ((self.chmod != None) and (f_created_check)): os.chmod (f_file_path_os,self.chmod)
				self.resource_file_path = f_file_path

				if (self.lock ("r")): self.resource_file_size = path.getsize (f_file_path_os)
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

	def write (self,f_data,f_timeout = -1):
	#
		"""
Write content to the active file session.

@param  f_data (Over)write file with the f_data content at the current
        position
@param  f_timeout Timeout to use (defaults to construction time value)
@return (boolean) True on success
@since  v0.1.00
		"""

		if (self.debug != None): self.debug.append ("file/#echo(__FILEPATH__)# -file->write (+f_data,%i)- (#echo(__LINE__)#)" % f_timeout)
		f_return = False

		if (self.lock ("w")):
		#
			f_bytes_unwritten = len (f_data)
			f_bytes_written = self.resource.tell ()

			if (f_bytes_written + f_bytes_unwritten > self.resource_file_size): f_new_size = f_bytes_written + f_bytes_unwritten - self.resource_file_size
			else: f_new_size = 0

			f_bytes_written = 0
			f_return = True

			if (f_timeout < 0): f_timeout_time = self.time + self.timeout_count
			else: f_timeout_time = self.time + f_timeout

			while ((f_return) and (f_bytes_unwritten > 0) and (f_timeout_time > (time.time ()))):
			#
				if (f_bytes_unwritten > 4096): f_part_size = 4096
				else: f_part_size = f_bytes_unwritten

				try:
				#
					self.resource.write (f_data[f_bytes_written:(f_bytes_written + f_part_size)])
					f_bytes_unwritten -= f_part_size
					f_bytes_written += f_part_size
				#
				except Exception,f_handled_exception: f_return = False
			#

			if (f_bytes_unwritten > 0):
			#
				f_return = False
				self.resource_file_size = path.getsize (path.normpath (self.resource_file_path))
				self.trigger_error ("file/#echo(__FILEPATH__)# -file->write ()- (#echo(__LINE__)#) reporting: Timeout occured before EOF",self.E_ERROR)
			#
			elif (f_new_size > 0): self.resource_file_size = f_new_size
		#

		return f_return
	#
#

##j## EOF