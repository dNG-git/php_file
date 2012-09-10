<?php
//j// BOF

/*n// NOTE
----------------------------------------------------------------------------
Extended Core: File
Working with a file abstraction layer
----------------------------------------------------------------------------
(C) direct Netware Group - All rights reserved
http://www.direct-netware.de/redirect.php?ext_core_file

This Source Code Form is subject to the terms of the Mozilla Public License,
v. 2.0. If a copy of the MPL was not distributed with this file, You can
obtain one at http://mozilla.org/MPL/2.0/.
----------------------------------------------------------------------------
http://www.direct-netware.de/redirect.php?licenses;mpl2
----------------------------------------------------------------------------
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
* @license    http://www.direct-netware.de/redirect.php?licenses;mpl2
*             Mozilla Public License, v. 2.0
*/
/*#ifdef(PHP5n) */

namespace dNG;
/* #\n*/

/* -------------------------------------------------------------------------
All comments will be removed in the "production" packages (they will be in
all development packets)
------------------------------------------------------------------------- */

//j// Functions and classes

if (!defined ("CLASS_directFile"))
{
/**
* Get file objects to work with files easily.
*
* @author     direct Netware Group
* @copyright  (C) direct Netware Group - All rights reserved
* @package    ext_core
* @subpackage file
* @since      v0.1.00
* @license    http://www.direct-netware.de/redirect.php?licenses;mpl2
*             Mozilla Public License, v. 2.0
*/
class directFile
{
/**
	* @var mixed $chmod chmod to set when creating a new file
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $chmod;
/**
	* @var array $debug Debug message container
*/
	/*#ifndef(PHP4) */public/* #*//*#ifdef(PHP4):var:#*/ $debug;
/**
	* @var boolean $debugging True if we should fill the debug message
	*      container
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $debugging;
/**
	* @var boolean $readonly True if file is opened read-only
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $readonly;
/**
	* @var resource $resource Resource to the opened file
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $resource;
/**
	* @var string $resource_file_pathname Filename for the resource pointer
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $resource_file_pathname;
/**
	* @var string $resource_lock Current locking mode
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $resource_lock;
/**
	* @var integer $time Current UNIX timestamp
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $time;
/**
	* @var integer $timeout_count Retries before timing out
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $timeout_count;
/**
	* @var mixed $umask umask to set before creating a new file
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $umask;

/* -------------------------------------------------------------------------
Construct the class using old and new behavior
------------------------------------------------------------------------- */

/**
	* Constructor (PHP5+) __construct (directFile)
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
		if ($this->debugging) { $this->debug = array ("directFile/#echo(__FILEPATH__)# -File->__construct (directFile)- (#echo(__LINE__)#)"); }

		$this->chmod = $f_chmod;
		$this->readonly = false;
		$this->resource = NULL;
		$this->resource_file_pathname = "";
		$this->resource_lock = "r";
		$this->time = (($f_time < 0) ? time () : $f_time);
		$this->timeout_count = $f_timeout_count;
		$this->umask = $f_umask;
	}
/*#ifdef(PHP4):
/**
	* Constructor (PHP4) directFile
	*
	* @param mixed $f_umask umask to set before creating a new file
	* @param mixed $f_chmod chmod to set when creating a new file
	* @param integer $f_time Current UNIX timestamp
	* @param integer $f_timeout_count Retries before timing out
	* @param boolean $f_debug Debug flag
	* @since v0.1.00
*\/
	function directFile ($f_umask = NULL,$f_chmod = NULL,$f_time = -1,$f_timeout_count = 5,$f_debug = false) { $this->__construct ($f_umask,$f_chmod,$f_time,$f_timeout_count,$f_debug); }
:#\n*/
/**
	* Destructor (PHP5+) __destruct (directFile)
	*
	* @since v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function __destruct ()
	{
		$this->close ();
		$this->resource = NULL;
	}

/**
	* Closes an active file session.
	*
	* @param  boolean $f_delete_empty If the file handle is valid, the file is
	*         empty and this parameter is true then the file will be deleted.
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function close ($f_delete_empty = true)
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->close (+f_delete_emtpy)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if (is_resource ($this->resource))
		{
			$f_file_position = $this->getPosition ();

			if ((!$this->readonly)&&($f_delete_empty)&&(!$f_file_position))
			{
				$this->read (1);
				$f_file_position = $this->getPosition ();
			}

			$f_return = fclose ($this->resource);

			if (($this->resource_lock == "w")&&(USE_file_locking_alternative))
			{
				if (file_exists ($this->resource_file_pathname.".lock")) { @unlink ($this->resource_file_pathname.".lock"); }
			}

			if ((!$this->readonly)&&($f_delete_empty)&&(!$f_file_position))
			{
				$f_return = false;
				if (is_writable ($this->resource_file_pathname)) { $f_return = @unlink ($this->resource_file_pathname); }
			}

			$this->readonly = false;
			$this->resource = NULL;
			$this->resource_file_pathname = "";
		}

		return $f_return;
	}

/**
	* Checks if the pointer is at EOF.
	*
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function eofCheck ()
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->eofCheck ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return feof ($this->resource); }
		else { return true; }
	}

/**
	* Returns the file pointer.
	*
	* @return mixed File handle on success; false on error
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function &getHandle ()
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->getHandle ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { $f_return =& $this->resource; }
		else { $f_return = false; }

		return $f_return;
	}

/**
	* Returns the current offset.
	*
	* @return mixed Offset on success; false on error
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function getPosition ()
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->getPosition ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return ftell ($this->resource); }
		else { return false; }
	}

/**
	* Changes file locking if needed.
	*
	* @param  string $f_mode The requested file locking mode ("r" or "w").
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function lock ($f_mode)
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->lock ($f_mode)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if (is_resource ($this->resource))
		{
			if (($f_mode == "w")&&($this->readonly)) { trigger_error ("directFile/#echo(__FILEPATH__)# -File->lock ()- (#echo(__LINE__)#) reporting: File resource is in readonly mode",E_USER_NOTICE); }
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
						$this->resource_lock = (($f_mode == "w") ? "w" : "r");
					}
					else
					{
						$f_timeout_count--;
						sleep (1);
					}
				}
				while ($f_timeout_count > 0);

				if ($f_timeout_count > -1) { trigger_error ("directFile/#echo(__FILEPATH__)# -File->lock ()- (#echo(__LINE__)#) reporting: File lock change failed",E_USER_ERROR); }
			}
		}
		else { trigger_error ("directFile/#echo(__FILEPATH__)# -File->lock ()- (#echo(__LINE__)#) reporting: File resource invalid",E_USER_WARNING); }

		return $f_return;
	}

/**
	* Runs flock or an alternative locking mechanism.
	*
	* @param  string $f_mode The requested file locking mode ("r" or "w").
	* @param  string $f_file_pathname Alternative path to the locking file (used
	*         for USE_file_locking_alternative)
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */protected /* #*/function locking ($f_mode,$f_file_pathname = "")
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->locking ($f_mode,$f_file_pathname)- (#echo(__LINE__)#)"; }

		$f_return = false;
		if (!strlen ($f_file_pathname)) { $f_file_pathname = $this->resource_file_pathname; }

		if ((strlen ($f_file_pathname))&&(is_resource ($this->resource)))
		{
			if (($f_mode == "w")&&($this->readonly)) { $f_return = false; }
			elseif (USE_file_locking_alternative)
			{
/* -------------------------------------------------------------------------
Cached file system statistics are great - but not in this case where we
want to delete unneeded files.
------------------------------------------------------------------------- */

				clearstatcache ();
				$f_locked_check = file_exists ($f_file_pathname.".lock");

				if ($f_locked_check)
				{
					$f_locked_check = false;
					$f_locked_mtime = filemtime ($f_file_pathname.".lock");

					if ($f_locked_mtime)
					{
						if (($this->time - $this->timeout_count) < $f_locked_mtime) { @unlink ($f_file_pathname.".lock"); }
						else { $f_locked_check = true; }
					}
				}

				if ($f_mode == "w")
				{
					if (($f_locked_check)&&($this->resource_lock == "w")) { $f_return = true; }
					elseif (!$f_locked_check) { $f_return = @touch ($f_file_pathname.".lock"); }
				}
				elseif (($f_locked_check)&&($this->resource_lock == "w")) { $f_return = @unlink ($f_file_pathname.".lock"); }
				elseif (!$f_locked_check) { $f_return = true; }
			}
			else { $f_return = @flock ($this->resource,(($f_mode == "w") ? LOCK_EX : LOCK_SH)); }
		}

		return $f_return;
	}

/**
	* Reads from the current file session.
	*
	* @param  integer $f_bytes How many bytes to read from the current position
	*         (0 means until EOF)
	* @param  integer $f_timeout Timeout to use (defaults to construction time
	*         value)
	* @return mixed Data on success; false on error
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function read ($f_bytes = 0,$f_timeout = -1)
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->read ($f_bytes,$f_timeout)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if ($this->lock ("r"))
		{
			$f_bytes_unread = $f_bytes;
			$f_return = "";
			$f_timeout_time = $this->time + (($f_timeout < 0) ? $this->timeout_count : $f_timeout);

			do
			{
				$f_part_size = ((($f_bytes_unread > 4096)||(!$f_bytes)) ? 4096 : $f_bytes_unread);
				$f_return .= fread ($this->resource,$f_part_size);
				if ($f_bytes) { $f_bytes_unread -= $f_part_size; }
			}
			while ((($f_bytes_unread > 0)||(!$f_bytes))&&(!feof ($this->resource))&&($f_timeout_time > (time ())));

			if (($f_bytes_unread > 0)||((!$f_bytes)&&(!feof ($this->resource))))
			{
				$f_return = false;
				trigger_error ("directFile/#echo(__FILEPATH__)# -File->read ()- (#echo(__LINE__)#) reporting: Timeout occured before EOF",E_USER_ERROR);
			}
		}

		return $f_return;
	}

/**
	* Returns true if the file resource is available.
	*
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function resourceCheck ()
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->resourceCheck ()- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return true; }
		else { return false; }
	}

/**
	* Seek to a given offset.
	*
	* @param  integer $f_offset Seek to the given offset
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function seek ($f_offset)
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->seek ($f_offset)- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { return fseek ($this->resource,$f_offset); }
		else { return false; }
	}

/**
	* Truncates the active file session.
	*
	* @param  integer $f_new_size Cut file at the given byte position
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function truncate ($f_new_size)
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->truncate ($f_new_size)- (#echo(__LINE__)#)"; }

		if ($this->lock ("w")) { return ftruncate ($this->resource,$f_new_size); }
		else { return false; }
	}

/**
	* Opens a file session.
	*
	* @param  string $f_file_pathname Path to the requested file
	* @param  boolean $f_readonly Open file in readonly mode
	* @param  string $f_file_mode File mode to use
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function open ($f_file_pathname,$f_readonly = false,$f_file_mode = "a+b")
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->open ($f_file_pathname,+f_readonly,$f_file_mode)- (#echo(__LINE__)#)"; }

		if (is_resource ($this->resource)) { $f_return = false; }
		else
		{
			$f_created_check = true;
			$f_return = true;
			$this->readonly = ($f_readonly ? true : false);

			if (file_exists ($f_file_pathname)) { $f_created_check = false; }
			elseif (!$this->readonly)
			{
				if ($this->umask != NULL) { umask (intval ($this->umask,8)); }
			}
			else { $f_return = false; }

			if ($f_return) { $this->resource = @fopen ($f_file_pathname,$f_file_mode); }
			else { trigger_error ("directFile/#echo(__FILEPATH__)# -File->open ()- (#echo(__LINE__)#) reporting: Failed opening $f_file_pathname - file does not exist",E_USER_NOTICE); }

			if (is_resource ($this->resource))
			{
				if (($this->chmod != NULL)&&($f_created_check)) { chmod ($f_file_pathname,(intval ($this->chmod,8))); }
				$this->resource_file_pathname = $f_file_pathname;
				@stream_set_timeout ($this->resource,$this->timeout_count);

				if (!$this->lock ("r"))
				{
					$this->close ($f_created_check);
					$this->resource = NULL;
				}
			}
			else
			{
				$this->resource_file_pathname = "";
				if ($f_created_check) { @unlink ($f_file_pathname); }
			}
		}

		return $f_return;
	}

/**
	* Write content to the active file session.
	*
	* @param  string $f_data (Over)write file with the $f_data content at the
	*         current position
	* @param  integer $f_timeout Timeout to use (defaults to construction time
	*         value)
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function write ($f_data,$f_timeout = -1)
	{
		if ($this->debugging) { $this->debug[] = "directFile/#echo(__FILEPATH__)# -File->write (+f_data,$f_timeout)- (#echo(__LINE__)#)"; }
		$f_return = false;

		if ($this->lock ("w"))
		{
			$f_bytes_unwritten = strlen ($f_data);
			$f_bytes_written = 0;
			$f_timeout_time = $this->time + (($f_timeout < 0) ? $this->timeout_count : $f_timeout);

			do
			{
				$f_part_size = (($f_bytes_unwritten > 4096) ? 4096 : $f_bytes_unwritten);
				$f_return = fwrite ($this->resource,(substr ($f_data,$f_bytes_written,$f_part_size)),$f_part_size);

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
				trigger_error ("directFile/#echo(__FILEPATH__)# -File->write ()- (#echo(__LINE__)#) reporting: Timeout occured before EOF",E_USER_ERROR);
			}
			else { $f_return = true; }
		}

		return $f_return;
	}
}

/* -------------------------------------------------------------------------
Define this class
------------------------------------------------------------------------- */

define ("CLASS_directFile",true);

if (!defined ("USE_file_locking_alternative")) { define ("USE_file_locking_alternative",false); }
}

//j// EOF
?>