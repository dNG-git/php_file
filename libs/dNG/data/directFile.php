<?php
//j// BOF

/*n// NOTE
----------------------------------------------------------------------------
file.php
Working with a file abstraction layer
----------------------------------------------------------------------------
(C) direct Netware Group - All rights reserved
http://www.direct-netware.de/redirect.php?php;file

This Source Code Form is subject to the terms of the Mozilla Public License,
v. 2.0. If a copy of the MPL was not distributed with this file, You can
obtain one at http://mozilla.org/MPL/2.0/.
----------------------------------------------------------------------------
http://www.direct-netware.de/redirect.php?licenses;mpl2
----------------------------------------------------------------------------
#echo(phpFileVersion)#
#echo(__FILEPATH__)#
----------------------------------------------------------------------------
NOTE_END //n*/
/**
* File functions class to use some advanced locking mechanisms.
*
* @internal  We are using ApiGen to automate the documentation process for
*            creating the Developer's Manual. All sections including these
*            special comments will be removed from the release source code.
*            Use the following line to ensure 76 character sizes:
* ----------------------------------------------------------------------------
* @author    direct Netware Group
* @copyright (C) direct Netware Group - All rights reserved
* @package   file.php
* @since     v0.1.00
* @license   http://www.direct-netware.de/redirect.php?licenses;mpl2
*            Mozilla Public License, v. 2.0
*/

/*#ifdef(PHP5n) */
namespace dNG\data;

/* #\n*/
/* -------------------------------------------------------------------------
All comments will be removed in the "production" packages (they will be in
all development packets)
------------------------------------------------------------------------- */

//j// Functions and classes

/**
* Get file objects to work with files easily.
*
* @author    direct Netware Group
* @copyright (C) direct Netware Group - All rights reserved
* @package   file.php
* @since     v0.1.00
* @license   http://www.direct-netware.de/redirect.php?licenses;mpl2
*            Mozilla Public License, v. 2.0
*/
class directFile
{
/**
	* @var mixed $chmod chmod to set when creating a new file
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $chmod;
/**
	* @var object $event_handler The EventHandler is called whenever debug messages
	*      should be logged or errors happened.
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $event_handler;
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
	* @var integer $timeout_retries Retries before timing out
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $timeout_retries;
/**
	* @var mixed $umask umask to set before creating a new file
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $umask;
/**
	* @var boolean $use_lock_file True to write lock files
*/
	/*#ifndef(PHP4) */protected/* #*//*#ifdef(PHP4):var:#*/ $use_lock_file = false;

/* -------------------------------------------------------------------------
Construct the class using old and new behavior
------------------------------------------------------------------------- */

/**
	* Constructor (PHP5+) __construct (directFile)
	*
	* @param mixed $umask umask to set before creating a new file
	* @param mixed $chmod chmod to set when creating a new file
	* @param integer $timeout_retries Retries before timing out
	* @param object $event_handler EventHandler to use
	* @since v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function __construct($umask = NULL, $chmod = NULL, $timeout_retries = 5, $event_handler = NULL)
	{
		if ($event_handler !== NULL) { $event_handler->debug("#echo(__FILEPATH__)# -file->__construct(directFile)- (#echo(__LINE__)#)"); }

		$this->chmod = $chmod;
		$this->event_handler = $event_handler;
		$this->readonly = false;
		$this->resource_file_pathname = "";
		$this->resource_lock = "r";
		$this->timeout_retries = $timeout_retries;
		$this->umask = $umask;
	}
/*#ifdef(PHP4):
/**
	* Constructor (PHP4) directFile
	*
	* @param mixed $umask umask to set before creating a new file
	* @param mixed $chmod chmod to set when creating a new file
	* @param integer $timeout_retries Retries before timing out
	* @param object $event_handler EventHandler to use
	* @since v0.1.00
*\/
	function directFile($umask = NULL, $chmod = NULL, $timeout_retries = 5, $event_handler = NULL) { $this->__construct($umask, $chmod, $timeout_retries, $event_handler); }
:#\n*/
/**
	* Destructor (PHP5+) __destruct (directFile)
	*
	* @since v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function __destruct()
	{
		$this->close();
		$this->resource = NULL;
	}

/**
	* Closes an active file session.
	*
	* @param  boolean $delete_empty If the file handle is valid, the file is
	*         empty and this parameter is true then the file will be deleted.
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function close($delete_empty = true)
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->close(+delete_emtpy)- (#echo(__LINE__)#)"); }
		$return = false;

		if (is_resource($this->resource))
		{
			$file_position = $this->getPosition();

			if ((!$this->readonly) && $delete_empty && (!$file_position))
			{
				$this->read(1);
				$file_position = $this->getPosition();
			}

			$return = fclose($this->resource);

			if ($this->resource_lock == "w" && $this->use_lock_file && file_exists($this->resource_file_pathname.".lock")) { @unlink($this->resource_file_pathname.".lock"); }

			if ((!$this->readonly) && $delete_empty && (!$file_position))
			{
				$return = false;
				if (is_writable($this->resource_file_pathname)) { $return = @unlink($this->resource_file_pathname); }
			}

			$this->readonly = false;
			$this->resource = NULL;
			$this->resource_file_pathname = "";
		}

		return $return;
	}

/**
	* Checks if the pointer is at EOF.
	*
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function eofCheck()
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->eofCheck()- (#echo(__LINE__)#)"); }

		if (is_resource($this->resource)) { return feof($this->resource); }
		else { return true; }
	}

/**
	* Returns the file pointer.
	*
	* @return mixed File handle on success; false on error
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function &getHandle()
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->getHandle()- (#echo(__LINE__)#)"); }

		if (is_resource($this->resource)) { $return =& $this->resource; }
		else { $return = false; }

		return $return;
	}

/**
	* Returns the current offset.
	*
	* @return mixed Offset on success; false on error
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function getPosition()
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->getPosition()- (#echo(__LINE__)#)"); }

		if (is_resource($this->resource)) { return ftell($this->resource); }
		else { return false; }
	}

/**
	* This method can be used to check the use of lock files.
	*
	* @return boolean Use lock file active
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function getUseLockFile()
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->getUseLockFile()- (#echo(__LINE__)#)"); }
		return $this->use_lock_file;
	}

/**
	* Changes file locking if needed.
	*
	* @param  string $mode The requested file locking mode ("r" or "w").
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function lock($mode)
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->lock($mode)- (#echo(__LINE__)#)"); }
		$return = false;

		if (is_resource($this->resource))
		{
			if ($mode == "w" && $this->readonly)
			{
				if ($this->event_handler !== NULL) { $this->event_handler->error("#echo(__FILEPATH__)# -file->lock()- reporting: File resource is in readonly mode"); }
			}
			elseif ($mode == $this->resource_lock) { $return = true; }
			else
			{
				$timeout_retries = $this->timeout_retries;

				do
				{
					if ($this->locking($mode))
					{
						$return = true;
						$timeout_retries = -1;
						$this->resource_lock = (($mode == "w") ? "w" : "r");
					}
					else
					{
						$timeout_retries--;
						sleep(1);
					}
				}
				while ($timeout_retries > 0);

				if ($timeout_retries > -1 && $this->event_handler !== NULL) { $this->event_handler->error("#echo(__FILEPATH__)# -file->lock()- reporting: File lock change failed"); }
			}
		}
		elseif ($this->event_handler !== NULL) { $this->event_handler->warning("#echo(__FILEPATH__)# -file->lock()- reporting: File resource invalid"); }

		return $return;
	}

/**
	* Runs flock or an alternative locking mechanism.
	*
	* @param  string $mode The requested file locking mode ("r" or "w").
	* @param  string $file_pathname Alternative path to the locking file
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */protected /* #*/function locking($mode, $file_pathname = "")
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->locking($mode, $file_pathname)- (#echo(__LINE__)#)"); }

		$return = false;
		if (!strlen($file_pathname)) { $file_pathname = $this->resource_file_pathname; }

		if (strlen($file_pathname) && is_resource($this->resource))
		{
			if ($mode == "w" && $this->readonly) { $return = false; }
			elseif ($this->use_lock_file)
			{
/* -------------------------------------------------------------------------
Cached file system statistics are great - but not in this case where we
want to delete unneeded files.
------------------------------------------------------------------------- */

				clearstatcache();
				$is_locked = file_exists($file_pathname.".lock");

				if ($is_locked)
				{
					$is_locked = false;
					$locked_mtime = filemtime($file_pathname.".lock");

					if ($locked_mtime)
					{
						if ((time() - $this->timeout_retries) < $locked_mtime) { @unlink($file_pathname.".lock"); }
						else { $is_locked = true; }
					}
				}

				if ($mode == "w")
				{
					if ($is_locked && $this->resource_lock == "w") { $return = true; }
					elseif (!$is_locked) { $return = @touch($file_pathname.".lock"); }
				}
				elseif ($is_locked && $this->resource_lock == "w") { $return = @unlink($file_pathname.".lock"); }
				elseif (!$is_locked) { $return = true; }
			}
			else { $return = @flock($this->resource, (($mode == "w") ? LOCK_EX : LOCK_SH)); }
		}

		return $return;
	}

/**
	* Reads from the current file session.
	*
	* @param  integer $bytes How many bytes to read from the current position
	*         (0 means until EOF)
	* @param  integer $timeout Timeout to use (defaults to construction time
	*         value)
	* @return mixed Data on success; false on error
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function read($bytes = 0, $timeout = -1)
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->read($bytes, $timeout)- (#echo(__LINE__)#)"); }
		$return = false;

		if ($this->lock("r"))
		{
			$bytes_unread = $bytes;
			$return = "";
			$timeout_time = (time() + (($timeout < 0) ? $this->timeout_retries : $timeout));

			do
			{
				$part_size = (($bytes_unread > 4096 || (!$bytes)) ? 4096 : $bytes_unread);
				$return .= fread($this->resource, $part_size);
				if ($bytes) { $bytes_unread -= $part_size; }
			}
			while (($bytes_unread > 0 || (!$bytes)) && (!feof($this->resource)) && time() < $timeout_time);

			if ($bytes_unread > 0 || ((!$bytes) && (!feof($this->resource))))
			{
				$return = false;
				if ($this->event_handler !== NULL) { $this->event_handler->error("#echo(__FILEPATH__)# -file->read()- reporting: Timeout occured before EOF"); }
			}
		}

		return $return;
	}

/**
	* Returns true if the file resource is available.
	*
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function resourceCheck()
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->resourceCheck()- (#echo(__LINE__)#)"); }

		if (is_resource($this->resource)) { return true; }
		else { return false; }
	}

/**
	* Seek to a given offset.
	*
	* @param  integer $offset Seek to the given offset
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function seek($offset)
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->seek($offset)- (#echo(__LINE__)#)"); }

		if (is_resource($this->resource)) { return fseek($this->resource, $offset); }
		else { return false; }
	}

/**
	* Sets the EventHandler.
	*
	* @param object $event_handler EventHandler to use
	* @since v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function setEventHandler($event_handler)
	{
		if ($event_handler !== NULL) { $event_handler->debug("#echo(__FILEPATH__)# -file->setEventHandler(+event_handler)- (#echo(__LINE__)#)"); }
		$this->event_handler = $event_handler;
	}

/**
	* This method can be used to activate the use of lock files.
	*
	* @param boolean $use_lock_file Use lock file
	* @since v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function setUseLockFile($use_lock_file)
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->setUseLockFile(+use_lock_file)- (#echo(__LINE__)#)"); }
		$this->use_lock_file = $use_lock_file;
	}

/**
	* Truncates the active file session.
	*
	* @param  integer $new_size Cut file at the given byte position
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function truncate($new_size)
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->truncate($new_size)- (#echo(__LINE__)#)"); }

		if ($this->lock("w")) { return ftruncate($this->resource, $new_size); }
		else { return false; }
	}

/**
	* Opens a file session.
	*
	* @param  string $file_pathname Path to the requested file
	* @param  boolean $readonly Open file in readonly mode
	* @param  string $file_mode File mode to use
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function open ($file_pathname, $readonly = false, $file_mode = "a+b")
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->open($file_pathname, +readonly, $file_mode)- (#echo(__LINE__)#)"); }

		if (is_resource($this->resource)) { $return = false; }
		else
		{
			$exists = false;
			$return = true;
			$this->readonly = ($readonly ? true : false);

			if (file_exists($file_pathname)) { $exists = true; }
			elseif (!$this->readonly)
			{
				if ($this->umask != NULL) { umask(intval($this->umask, 8)); }
			}
			else { $return = false; }

			if ($return) { $this->resource = @fopen($file_pathname, $file_mode); }
			elseif ($this->event_handler !== NULL) { $this->event_handler->warning("#echo(__FILEPATH__)# -file->open()- reporting: Failed opening $file_pathname - file does not exist"); }

			if (is_resource($this->resource))
			{
				if ($this->chmod != NULL && (!$exists)) { chmod($file_pathname, (intval($this->chmod, 8))); }
				$this->resource_file_pathname = $file_pathname;
				@stream_set_timeout($this->resource, $this->timeout_retries);

				if (!$this->lock("r"))
				{
					$this->close(!$exists);
					$this->resource = NULL;
				}
			}
			else
			{
				$this->resource_file_pathname = "";
				if ((!$exists) && (!$this->readonly)) { @unlink($file_pathname); }
			}
		}

		return $return;
	}

/**
	* Write content to the active file session.
	*
	* @param  string $data (Over)write file with the $data content at the
	*         current position
	* @param  integer $timeout Timeout to use (defaults to construction time
	*         value)
	* @return boolean True on success
	* @since  v0.1.00
*/
	/*#ifndef(PHP4) */public /* #*/function write($data, $timeout = -1)
	{
		if ($this->event_handler !== NULL) { $this->event_handler->debug("#echo(__FILEPATH__)# -file->write(+data, $timeout)- (#echo(__LINE__)#)"); }
		$return = false;

		if ($this->lock("w"))
		{
			$bytes_unwritten = strlen($data);
			$bytes_written = 0;
			$timeout_time = (time() + (($timeout < 0) ? $this->timeout_retries : $timeout));

			do
			{
				$part_size = (($bytes_unwritten > 4096) ? 4096 : $bytes_unwritten);
				$return = fwrite($this->resource, substr($data, $bytes_written, $part_size), $part_size);

				if ($return)
				{
					$bytes_unwritten -= $part_size;
					$bytes_written += $part_size;
				}
			}
			while ($return && $bytes_unwritten > 0 && time() > $timeout_time);

			if ($bytes_unwritten > 0)
			{
				$return = false;
				if ($this->event_handler !== NULL) { $this->event_handler->error("#echo(__FILEPATH__)# -file->write()- reporting: Timeout occured before EOF"); }
			}
			else { $return = true; }
		}

		return $return;
	}
}

//j// EOF