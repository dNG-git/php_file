<?php
//j// BOF

/*n// NOTE
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
$Id: file.php,v 1.3 2009/05/11 20:29:32 s4u Exp $
#echo(extCoreFileVersion)#
extCore_file/#echo(__FILEPATH__)#
----------------------------------------------------------------------------
NOTE_END //n*/
/**
* File functions class to use some advanced locking mechanisms.
*
* @internal   We are using phpDocumentor to automate the documentation process
*             for creating the Developer's Manual. All sections including
*             these special comments will be removed from the release source
*             code.
*             Use the following line to ensure 76 character sizes:
* ----------------------------------------------------------------------------
* @author     direct Netware Group
* @copyright  (C) direct Netware Group - All rights reserved
* @package    ext_core
* @subpackage file
* @since      v0.1.00
* @license    http://www.direct-netware.de/redirect.php?licenses;w3c
*             W3C (R) Software License
*/

/* -------------------------------------------------------------------------
All comments will be removed in the "production" packages (they will be in
all development packets)
------------------------------------------------------------------------- */

//j// Functions and classes

/* -------------------------------------------------------------------------
Testing for required classes
------------------------------------------------------------------------- */

if (!defined ("CLASS_direct_file"))
{
//c// direct_file
/**
* Abstraction layer for file operations including auto-delete if empty and
* alternative locking.
*
* @author     direct Netware Group
* @copyright  (C) direct Netware Group - All rights reserved
* @package    ext_core
* @subpackage file
* @since      v0.1.00
* @license    http://www.direct-netware.de/redirect.php?licenses;w3c
*             W3C (R) Software License
*/
class direct_file
{
/**
	* @var mixed $chmod chmod to set when creating a new file
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$chmod;
/**
	* @var array $debug Debug message container 
*/
	/*#ifndef(PHP4) */public /* #*//*#ifdef(PHP4):var :#*/$debug;
/**
	* @var boolean $debugging True if we should fill the debug message
	*      container 
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$debugging;
/**
	* @var boolean $readonly True if file is opened read-only
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$readonly;
/**
	* @var resource $resource Resource to the opened file
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$resource;
/**
	* @var string $resource_file_path Filename for the resource pointer
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$resource_file_path;
/**
	* @var string $resource_lock Current locking mode
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$resource_lock;
/**
	* @var integer $time Current UNIX timestamp
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$time;
/**
	* @var integer $timeout_count Retries before timing out
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$timeout_count;
/**
	* @var mixed $umask umask to set before creating a new file
*/
	/*#ifndef(PHP4) */protected /* #*//*#ifdef(PHP4):var :#*/$umask;

/* -------------------------------------------------------------------------
Construct the class using old and new behavior
------------------------------------------------------------------------- */

	//f// direct_file->__construct () and direct_file->direct_file ()
/**
	* Constructor (PHP5+) __construct (direct_file)
	*
	* @param mixed $f_umask umask to set before creating a new file
	* @param mixed $f_chmod chmod to set when creating a new file
	* @param integer $f_time Current UNIX timestamp
	* @param integer $f_timeout_count Retries before timing out
	* @param boolean $f_debug Debug flag
	* @since v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function __construct ($f_umask = NULL,$f_chmod = NULL,$f_time = -1,$f_timeout_count = 5,$f_debug = false)
	{
		$this->debugging = $f_debug;

		if ($this->debugging) { $this->debug = array ("file/#echo(__FILEPATH__)# -file->__construct (direct_file)- (#echo(__LINE__)#)"); }
		$this->chmod = $f_chmod;
		$this->readonly = false;
		$this->resource = NULL;
		$this->resource_file_path = "";
		$this->resource_lock = "r";

		if ($f_time < 0) { $this->time = time (); }
		else { $this->time = $f_time; }

		$this->timeout_count = $f_timeout_count;
		$this->umask = $f_umask;
	}
/*#ifdef(PHP4):
/**
	* Constructor (PHP4) direct_file (direct_file)
	*
	* @param mixed $f_umask umask to set before creating a new file
	* @param mixed $f_chmod chmod to set when creating a new file
	* @param integer $f_time Current UNIX timestamp
	* @param integer $f_timeout_count Retries before timing out
	* @param boolean $f_debug Debug flag
	* @since v0.1.00
*\/
	function direct_file ($f_umask = NULL,$f_chmod = NULL,$f_time = -1,$f_timeout_count = 5,$f_debug = false) { $this->__construct ($f_umask,$f_chmod,$f_time,$f_timeout_count,$f_debug); }
:#\n*/
	//f// direct_file->__destruct ()
/**
	* Destructor (PHP5+) __destruct (direct_file)
	*
	* @since v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function __destruct ()
	{
		$this->close ();
		$this->resource = NULL;
		$this->resource_file_path = "";
		$this->resource_lock = "r";
	}

	//f// direct_file->close ($f_delete_empty = true)
/**
	* Closes an active file session.
	*
	* @param  boolean $f_delete_empty If the file handle is valid, the file is
	*         empty and this parameter is true then the file will be deleted.
	* @uses   direct_file::get_position()
	* @uses   direct_file::seek()
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function close ($f_delete_empty = true)
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->close (+f_delete_emtpy)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if (is_resource ($this->resource))
		{
			$f_file_position = $this->get_position ();

			if ((!$this->readonly)&&($f_delete_empty)&&(!$f_file_position))
			{
				$this->read (1);
				$f_file_position = $this->get_position ();
			}

			$f_return = fclose ($this->resource);

			if (($this->resource_lock == "w")&&(USE_file_locking_alternative))
			{
				if (file_exists ($this->resource_file_path.".lock")) { @unlink ($this->resource_file_path.".lock"); }
			}

			if ((!$this->readonly)&&($f_delete_empty)&&(!$f_file_position))
			{
				$f_return = false;
				if (is_writable ($this->resource_file_path)) { $f_return = @unlink ($this->resource_file_path); }
			}

			$this->resource = NULL;
			$this->resource_file_path = "";
		}

		return $f_return;
	}

	//f// direct_file->eof_check ()
/**
	* Checks if the pointer is at EOF.
	*
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function eof_check ()
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->eof_check ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return feof ($this->resource); }
		else { return true; }
	}

	//f// direct_file->get_handle ()
/**
	* Returns the file pointer.
	*
	* @return mixed File handle on success; false on error
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function &get_handle ()
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->get_handle ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { $f_return =& $this->resource; }
		else { $f_return = false; }

		return $f_return;
	}

	//f// direct_file->get_position ()
/**
	* Returns the current offset.
	*
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function get_position ()
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->get_position ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return ftell ($this->resource); }
		else { return false; }
	}

	//f// direct_file->lock ($f_mode)
/**
	* Changes file locking if needed.
	*
	* @param  string $f_mode The requested file locking mode ("r" or "w").
	* @uses   direct_file::locking()
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function lock ($f_mode)
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->lock ($f_mode)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if (is_resource ($this->resource))
		{
			if (($f_mode == "w")&&($this->readonly)) { trigger_error ("file/#echo(__FILEPATH__)# -file->lock ()- (#echo(__LINE__)#) reports: File resource is in readonly mode",E_USER_NOTICE); }
			elseif ($f_mode == $this->resource_lock) { $f_return = true; }
			else
			{
				$f_timeout_count = $this->timeout_count;

				do
				{
					if ($this->locking ($f_mode))
					{
						$f_return = true;
						$f_timeout_count = -1;

						if ($f_mode == "w") { $this->resource_lock = "w"; }
						else { $this->resource_lock = "r"; }
					}
					else
					{
						$f_timeout_count--;
						sleep (1);
					}
				}
				while ($f_timeout_count > 0);

				if ($f_timeout_count > -1) { trigger_error ("file/#echo(__FILEPATH__)# -file->lock ()- (#echo(__LINE__)#) reports: File lock change failed",E_USER_ERROR); }
			}
		}
		else { trigger_error ("file/#echo(__FILEPATH__)# -file->lock ()- (#echo(__LINE__)#) reports: File resource invalid",E_USER_WARNING); }

		return $f_return;
	}

	//f// direct_file->locking ($f_mode,$f_file_path = "")
/**
	* Runs flock or an alternative locking mechanism.
	*
	* @param  string $f_mode The requested file locking mode ("r" or "w").
	* @param  string $f_file_path Alternative path to the locking file (used for
	*         USE_file_locking_alternative)
	* @uses   direct_basic_functions::set_debug_result()
	* @uses   USE_file_locking_alternative
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */protected /* #*/function locking ($f_mode,$f_file_path = "")
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->locking ($f_mode,$f_file_path)- (#echo(__LINE__)#)"; }

		$f_return = false;
		if (!strlen ($f_file_path)) { $f_file_path = $this->resource_file_path; }

		if ((strlen ($f_file_path))&&(is_resource ($this->resource)))
		{
			if (($f_mode == "w")&&($this->readonly)) { $f_return = false; }
			elseif (USE_file_locking_alternative)
			{
/* -------------------------------------------------------------------------
Cached file system statistics are great - but not in this case where we
want to delete unneeded files.
------------------------------------------------------------------------- */

				clearstatcache ();
				$f_locked_check = file_exists ($f_file_path.".lock");

				if ($f_locked_check)
				{
					$f_locked_check = false;
					$f_locked_mtime = filemtime ($f_file_path.".lock");

					if ($f_locked_mtime)
					{
						if (($this->time - $this->timeout_count) < $f_locked_mtime) { @unlink ($f_file_path.".lock"); }
						else { $f_locked_check = true; }
					}
				}

				if ($f_mode == "w")
				{
					if (($f_locked_check)&&($this->resource_lock == "w")) { $f_return = true; }
					elseif ($f_locked_check) { $f_return = false; }
					else { $f_return = @touch ($f_file_path.".lock"); }
				}
				else
				{
					if (($f_locked_check)&&($this->resource_lock == "w")) { $f_return = @unlink ($f_file_path.".lock"); }
					elseif ($f_locked_check) { $f_return = false; }
					else { $f_return = true; }
				}
			}
			else
			{
				if ($f_mode == "w") { $f_return = @flock ($this->resource,LOCK_EX); }
				else { $f_return = @flock ($this->resource,LOCK_SH); }
			}
		}

		return $f_return;
	}

	//f// direct_file->read ($f_bytes = 0,$f_timeout = -1)
/**
	* Reads from the current file session.
	*
	* @param  integer $f_bytes How many bytes to read from the current position
	*         (0 means until EOF)
	* @param  integer $f_timeout Timeout to use (defaults to construction time
	*         value)
	* @uses   direct_file::lock()
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function read ($f_bytes = 0,$f_timeout = -1)
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->read ($f_bytes,$f_timeout)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if ($this->lock ("r"))
		{
			$f_bytes_unread = $f_bytes;
			$f_return = "";

			if ($f_timeout < 0) { $f_timeout_time = ($this->time + $this->timeout_count); }
			else { $f_timeout_time = ($this->time + $f_timeout); }

			do
			{
				if (($f_bytes_unread > 4096)||(!$f_bytes)) { $f_part_size = 4096; }
				else { $f_part_size = $f_bytes_unread; }

				$f_return .= fread ($this->resource,$f_part_size);
				if ($f_bytes) { $f_bytes_unread -= $f_part_size; }
			}
			while ((($f_bytes_unread > 0)||(!$f_bytes))&&(!feof ($this->resource))&&($f_timeout_time > (time ())));

			if (($f_bytes_unread > 0)||((!$f_bytes)&&(!feof ($this->resource))))
			{
				$f_return = false;
				trigger_error ("file/#echo(__FILEPATH__)# -file->read ()- (#echo(__LINE__)#) reports: Timeout occured before EOF",E_USER_ERROR);
			}
		}

		return $f_return;
	}

	//f// direct_file->resource_check ()
/**
	* Returns true if the file resource is available.
	*
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function resource_check ()
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->resource_check ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return true; }
		else { return false; }
	}

	//f// direct_file->seek ($f_offset)
/**
	* Seek to a given offset.
	*
	* @param  integer $f_offset Seek to the given offset
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function seek ($f_offset)
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->seek ($f_offset)- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return fseek ($this->resource,$f_offset); }
		else { return false; }
	}

	//f// direct_file->truncate ($f_new_size)
/**
	* Truncates the active file session.
	*
	* @param  integer $f_new_size Cut file at the given byte position
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function truncate ($f_new_size)
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->truncate ($f_new_size)- (#echo(__LINE__)#)"; }

		if ($this->lock ("w")) { return ftruncate ($this->resource,$f_new_size); }
		else { return false; }
	}

	//f// direct_file->open ($f_file_path,$f_readonly = false,$f_file_mode = "a+b")
/**
	* Opens a file session.
	*
	* @param  string $f_file_path Path to the requested file
	* @param  boolean $f_readonly Open file in readonly mode
	* @param  string $f_file_mode Filemode to use
	* @uses   direct_file::locking()
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function open ($f_file_path,$f_readonly = false,$f_file_mode = "a+b")
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->open ($f_file_path,+f_readonly,$f_file_mode)- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { $f_return = false; }
		else
		{
			$f_created_check = true;
			if ($f_readonly) { $this->readonly = true; }
			$f_return = true;

			if (file_exists ($f_file_path)) { $f_created_check = false; }
			elseif (!$this->readonly)
			{
				if ($this->umask != NULL) { umask (intval ($this->umask,8)); }
			}
			else { $f_return = false; }

			if ($f_return) { $this->resource = @fopen ($f_file_path,$f_file_mode); }
			else { trigger_error ("file/#echo(__FILEPATH__)# -file->open ()- (#echo(__LINE__)#) reports: Failed opening $f_file_path - file does not exist",E_USER_NOTICE); }

			if (is_resource ($this->resource))
			{
				if (($this->chmod != NULL)&&($f_created_check)) { chmod ($f_file_path,(intval ($this->chmod,8))); }
				$this->resource_file_path = $f_file_path;
				@stream_set_timeout ($this->resource,$this->timeout_count);

				if (!$this->lock ("r"))
				{
					$this->close ($f_created_check);
					$this->resource = NULL;
				}
			}
			else
			{
				$this->resource_file_path = "";
				if ($f_created_check) { @unlink ($f_file_path); }
			}
		}

		return $f_return;
	}

	//f// direct_file->write ($f_data,$f_timeout = -1)
/**
	* Write content to the active file session.
	*
	* @param  string $f_data (Over)write file with the $f_data content at the
	*         current position
	* @param  integer $f_timeout Timeout to use (defaults to construction time
	*         value)
	* @uses   direct_file::lock()
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function write ($f_data,$f_timeout = -1)
	{
		if ($this->debugging) { $this->debug[] = "file/#echo(__FILEPATH__)# -file->write (+f_data,$f_timeout)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if ($this->lock ("w"))
		{
			if ($f_timeout < 0) { $f_timeout_time = ($this->time + $this->timeout_count); }
			else { $f_timeout_time = ($this->time + $f_timeout); }

			$f_bytes_unwritten = strlen ($f_data);
			$f_bytes_written = 0;

			do
			{
				if (($f_bytes_unwritten > 4096)||(!$f_bytes)) { $f_part_size = 4096; }
				else { $f_part_size = $f_bytes_unwritten; }

				$f_return .= fwrite ($this->resource,(substr ($f_data,$f_bytes_written,$f_part_size)),$f_part_size);

				if ($f_return)
				{
					$f_bytes_unwritten -= $f_part_size;
					$f_bytes_written += $f_part_size;
				}
			}
			while (($f_return)&&($f_bytes_unwritten > 0)&&($f_timeout_time > (time ())));

			if ($f_bytes_unwritten > 0)
			{
				$f_return = false;
				trigger_error ("file/#echo(__FILEPATH__)# -file->write ()- (#echo(__LINE__)#) reports: Timeout occured before EOF",E_USER_ERROR);
			}
		}

		return $f_return;
	}
}

/* -------------------------------------------------------------------------
Define this class
------------------------------------------------------------------------- */

define ("CLASS_direct_file",true);
}

//j// EOF
?>