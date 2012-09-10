/*- coding: utf-8 -*/
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
NOTE_END //n*/
/**
* File functions class to use some advanced locking mechanisms.
*
* @internal   We are using javadoc to automate the documentation process for
*             creating the Developer's Manual. All sections including these
*             special comments will be removed from the release source code.
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

package de.direct_netware;

import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.IOException;
import java.io.RandomAccessFile;
import java.net.URL;
import java.nio.ByteBuffer;
import java.nio.channels.FileChannel;
import java.nio.channels.FileLock;
import java.nio.channels.OverlappingFileLockException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collections;
import java.util.Date;

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
public class directFile
{
/**
	* Error notice: It is save to ignore it
*/
	public static int E_NOTICE = 1;
/**
	* Warning type: Could create trouble if ignored
*/
	public static int E_WARNING = 2;
/**
	* Error type: An error occured and was handled
*/
	public static int E_ERROR = 4;

/**
	* chmod to set when creating a new file
*/
	protected int Chmod;
/**
	* Debug message container
*/
	public ArrayList Debug;
/**
	* Function to be called for logging exceptions and other errors
*/
	protected directFileErrorRunnable errorCallback;
/**
	* True if file is opened read-only
*/
	protected boolean Readonly;
/**
	* Resource to the opened file
*/
	protected RandomAccessFile Resource;
/**
	* Resource file object
*/
	protected File ResourceFile;
/**
	* Filename for the resource pointer
*/
	protected String ResourceFilePathname;
/**
	* Current locking mode
*/
	protected FileLock ResourceLock;
/**
	* Current UNIX timestamp
*/
	protected Date Time;
/**
	* Retries before timing out
*/
	protected int TimeoutCount;
/**
	* umask to set before creating a new file
*/
	protected int Umask;

/* -------------------------------------------------------------------------
Construct the class
------------------------------------------------------------------------- */

/**
	* Constructor directFile (-1,-1,-1,5,false)
	*
	* @since v0.1.00
*/
	public directFile () { this (-1,-1,-1,5,false); }
/**
	* Constructor directFile (fUmask,-1,-1,5,false)
	*
	* @param fUmask umask to set before creating a new file
	* @since v0.1.00
*/
	public directFile (int fUmask) { this (fUmask,-1,-1,5,false); }
/**
	* Constructor directFile (fUmask,fChmod,-1,5,false)
	*
	* @param fUmask umask to set before creating a new file
	* @param fChmod chmod to set when creating a new file
	* @since v0.1.00
*/
	public directFile (int fUmask,int fChmod) { this (fUmask,fChmod,-1,5,false); }
/**
	* Constructor directFile (fUmask,fChmod,fTime,5,false)
	*
	* @param fUmask umask to set before creating a new file
	* @param fChmod chmod to set when creating a new file
	* @param fTime Current UNIX timestamp
	* @since v0.1.00
*/
	public directFile (int fUmask,int fChmod,int fTime) { this (fUmask,fChmod,fTime,5,false); }
/**
	* Constructor directFile (fUmask,fChmod,fTime,fTimeoutCount,false)
	*
	* @param fUmask umask to set before creating a new file
	* @param fChmod chmod to set when creating a new file
	* @param fTime Current UNIX timestamp
	* @param fTimeoutCount Retries before timing out
	* @since v0.1.00
*/
	public directFile (int fUmask,int fChmod,int fTime,int fTimeoutCount) { this (fUmask,fChmod,fTime,fTimeoutCount,false); }
/**
	* Constructor directFile (fUmask,fChmod,fTime,fTimeoutCount,fDebug)
	*
	* @param fUmask umask to set before creating a new file
	* @param fChmod chmod to set when creating a new file
	* @param fTime Current UNIX timestamp
	* @param fTimeoutCount Retries before timing out
	* @param fDebug Debug flag
	* @since v0.1.00
*/
	public directFile (int fUmask,int fChmod,int fTime,int fTimeoutCount,boolean fDebug)
	{
		if (fDebug) { Debug = new ArrayList (Collections.singleton ("file.directFile (new)")); }

		Chmod = fChmod;
		Readonly = false;
		ResourceFilePathname = "";
		Time = ((fTime < 0) ? null : new Date (fTime * 1000));
		TimeoutCount = fTimeoutCount;
		Umask = fUmask;
	}

/**
	* Destructor directFile ().
	*
	* @since  v0.1.00
*/
	public void finalize () { close (true); }

/**
	* Closes an active file session.
	*
	* @return True on success
	* @since  v0.1.00
*/
	public boolean close () { return close (true); }
/**
	* Closes an active file session.
	*
	* @param  fDeleteEmpty If the file handle is valid, the file is empty and this
	*         parameter is true then the file will be deleted.
	* @return True on success
	* @since  v0.1.00
*/
	public boolean close (boolean fDeleteEmpty)
	{
		if (Debug != null) { Debug.add ("file.close (fDeleteEmtpy)"); }
		boolean fReturn = false;

		try
		{
			if ((Resource != null)&&((Readonly)||(!fDeleteEmpty))||(ResourceFile != null))
			{
				long fFilesize = Resource.length ();
				fReturn = true;
				Resource.close ();

				if ((!Readonly)&&(fDeleteEmpty)&&(fFilesize == 0))
				{
					fReturn = false;
					if (ResourceFile.canWrite ()) { fReturn = ResourceFile.delete (); }
				}

				Resource = null;
				ResourceFile = null;
				ResourceFilePathname = "";
				ResourceLock = null;
			}
		}
		catch (Throwable fHandledException) { fReturn = false; }

		return fReturn;
	}

/**
	* Checks if the pointer is at EOF.
	*
	* @return True on success
	* @since  v0.1.00
*/
	public boolean eofCheck ()
	{
		if (Debug != null) { Debug.add ("file.eofCheck ()"); }
		boolean fReturn = false;

		try
		{
			if (Resource != null)
			{
				if ((Resource.getChannel().position ()) >= (Resource.length ())) { fReturn = true; }
				else { fReturn = false; }
			}
			else { fReturn = true; }
		}
		catch (Throwable fHandledException) { fReturn = false; }

		return fReturn;
	}

/**
	* Returns the file object.
	*
	* @return File object on success; false on error
	* @since  v0.1.00
*/
	public File getFileHandle ()
	{
		if (Debug != null) { Debug.add ("file.getFileHandle ()"); }

		if (ResourceFile != null) { return ResourceFile; }
		else { return null; }
	}

/**
	* Returns the resource to the opened file.
	*
	* @return File handle on success; false on error
	* @since  v0.1.00
*/
	public RandomAccessFile getHandle ()
	{
		if (Debug != null) { Debug.add ("file.getHandle ()"); }

		if (Resource != null) { return Resource; }
		else { return null; }
	}

/**
	* Returns the current offset.
	*
	* @return Offset on success
	* @since  v0.1.00
*/
	public int getPosition ()
	{
		if (Debug != null) { Debug.add ("file.getPosition ()"); }
		int fReturn = -1;

		try
		{
			if (Resource != null) { fReturn = (int)Resource.getChannel().position (); }
		}
		catch (Throwable fHandledException) { fReturn = -1; }

		return fReturn;
	}

/**
	* Changes file locking if needed.
	*
	* @param  fMode The requested file locking mode ('r' or 'w').
	* @return True on success
	* @since  v0.1.00
*/
	public boolean lock (char fMode)
	{
		if (Debug != null) { Debug.add ("file.lock (" + fMode + ")"); }
		boolean fReturn = false;

		try
		{
			if ((Resource != null)&&(ResourceFile != null))
			{
				if ((fMode == 'w')&&(Readonly)) { triggerError ("file.lock () reporting: File resource is in readonly mode",E_NOTICE); }
				else if ((ResourceLock != null)&&(fMode == 'r')&&(ResourceLock.isShared ())) { fReturn = true; }
				else if ((ResourceLock != null)&&(fMode == 'w')&&(!ResourceLock.isShared ())) { fReturn = true; }
				else
				{
					int fTimeoutCount = TimeoutCount;

					do
					{
						if ((ResourceLock != null)&&(ResourceLock.isValid ())) { ResourceLock.release (); }

						try
						{
							if (fMode == 'r') { ResourceLock = Resource.getChannel().tryLock (0,Integer.MAX_VALUE,true); }
							else if (fMode == 'w') { ResourceLock = Resource.getChannel().tryLock (0,Integer.MAX_VALUE,false); }

							if (ResourceLock == null) { fTimeoutCount--; }
							else
							{
								fReturn = true;
								fTimeoutCount = -1;
							}
						}
						catch (OverlappingFileLockException fHandledException) { fTimeoutCount--; }

						if (!fReturn) { Thread.sleep (1000); }
					}
					while (fTimeoutCount > 0);

					if (fTimeoutCount > -1) { triggerError ("file.lock () reporting: File lock change failed",E_ERROR); }
				}
			}
			else { triggerError ("file.lock () reporting: File resource invalid",E_WARNING); }
		}
		catch (Throwable fHandledException) { fReturn = false; }

		return fReturn;
	}

/**
	* Reads from the current file session.
	*
	* @return True on success
	* @since  v0.1.00
*/
	public byte[] read () throws IOException { return read (0,-1); }
/**
	* Reads from the current file session.
	*
	* @param  fBytes How many bytes to read from the current position (0 means
	*         until EOF)
	* @return True on success
	* @since  v0.1.00
*/
	public byte[] read (int fBytes) throws IOException { return read (fBytes,-1); }
/**
	* Reads from the current file session.
	*
	* @param  fBytes How many bytes to read from the current position (0 means
	*         until EOF)
	* @param  fTimeout Timeout to use (defaults to construction time value)
	* @return True on success
	* @since  v0.1.00
*/
	public byte[] read (int fBytes,int fTimeout) throws IOException
	{
		if (Debug != null) { Debug.add ("file.read (" + fBytes + "," + fTimeout + ")"); }
		byte[] fReturn = null;

		if (lock ('r'))
		{
			int fBytesUnread = fBytes;
			long fFilesize = Resource.length ();
			int fBytesMax = ((fFilesize < Integer.MAX_VALUE) ? (int)fFilesize : Integer.MAX_VALUE);

			if ((fBytes < 1)||(fBytesMax < fBytesUnread))
			{
				fBytes = fBytesMax;
				fBytesUnread = fBytesMax;
			}

			fReturn = new byte[fBytes];

			int fBytesRead = 0;
			FileChannel fFileChannel = Resource.getChannel ();
			ByteBuffer fPartBytes = null;
			int fPartBytesRead = 0;
			if (fTimeout < 0) { fTimeout = TimeoutCount; }
			Date fTimeoutTime = ((Time == null) ? new Date (new Date().getTime () + (fTimeout * 1000)) : new Date (Time.getTime () + (fTimeout * 1000)));

			do
			{
				fPartBytes = (((fBytesUnread > 4096)||(fBytes < 0)) ? ByteBuffer.allocate (4096) : ByteBuffer.allocate ((int)fBytesUnread));
				fPartBytesRead = fFileChannel.read (fPartBytes);

				if (fPartBytesRead > 0)
				{
					fPartBytes.rewind ();
					fPartBytes.get (fReturn,fBytesRead,fPartBytesRead);

					fBytesRead += fPartBytesRead;
					fBytesUnread -= fPartBytesRead;

					if (fBytesUnread < 1) { fPartBytesRead = -1; }
				}
			}
			while (((fBytesUnread > 0)||(fBytes < 1))&&(fPartBytesRead > -1)&&(fTimeoutTime.after (new Date ())));

			if ((fBytesUnread > 0)||((fBytes < 0)&&(fPartBytesRead > -1)))
			{
				fReturn = null;
				triggerError ("file.read () reporting: Timeout occured before EOF",E_ERROR);
			}
		}

		return fReturn;
	}

/**
	* Returns true if the file resource is available.
	*
	* @return True on success
	* @since  v0.1.00
*/
	public boolean resourceCheck ()
	{
		if (Debug != null) { Debug.add ("file.resourceCheck ()"); }

		if ((Resource != null)&&(ResourceFile != null)) { return true; }
		else { return false; }
	}

/**
	* Seek to a given offset.
	*
	* @param  fOffset Seek to the given offset
	* @return True on success
	* @since  v0.1.00
*/
	public boolean seek (int fOffset)
	{
		if (Debug != null) { Debug.add ("file.seek (" + fOffset + ")"); }
		boolean fReturn = false;

		if (Resource != null)
		{
			try
			{
				Resource.seek (fOffset);
				fReturn = true;
			}
			catch (Throwable fHandledException) { fReturn = false; }
		}

		return fReturn;
	}

/**
	* Truncates the active file session.
	*
	* @param  fNewSize Cut file at the given byte position
	* @return True on success
	* @since  v0.1.00
*/
	public boolean truncate (int fNewSize)
	{
		if (Debug != null) { Debug.add ("file.truncate (" + fNewSize + ")"); }
		boolean fReturn = false;

		if (lock ('w'))
		{
			try
			{
				Resource.setLength (fNewSize);
				fReturn = true;
			}
			catch (Throwable fHandledException) { fReturn = false; }
		}

		return fReturn;
	}

/**
	* Opens a file session.
	*
	* @param  fFilePathname Path to the requested file
	* @return True on success
	* @since  v0.1.00
*/
	public boolean open (String fFilePathname) { return open (fFilePathname,false,"rw+"); }
/**
	* Opens a file session.
	*
	* @param  fFilePathname Path to the requested file
	* @param  fReadonly Open file in readonly mode
	* @return True on success
	* @since  v0.1.00
*/
	public boolean open (String fFilePathname,boolean fReadonly) { return open (fFilePathname,fReadonly,"rw+"); }
/**
	* Opens a file session.
	*
	* @param  fFilePathname Path to the requested file
	* @param  fReadonly Open file in readonly mode
	* @param  fFileMode Filemode to use
	* @return True on success
	* @since  v0.1.00
*/
	public boolean open (String fFilePathname,boolean fReadonly,String fFileMode)
	{
		boolean fReturn = false;

		try { fReturn = (fFilePathname.startsWith ("file:") ? open ((new URL (fFilePathname)),fReadonly,fFileMode) : open ((new URL ("file:" + fFilePathname)),fReadonly,fFileMode)); }
		catch (Throwable fUnhandledException) { }

		return fReturn;
	}
/**
	* Opens a file session.
	*
	* @param  fFileURL URL to the requested file
	* @return True on success
	* @since  v0.1.00
*/
	public boolean open (URL fFileURL) { return open (fFileURL,false,"rw+"); }
/**
	* Opens a file session.
	*
	* @param  fFileURL URL to the requested file
	* @param  fReadonly Open file in readonly mode
	* @return True on success
	* @since  v0.1.00
*/
	public boolean open (URL fFileURL,boolean fReadonly) { return open (fFileURL,fReadonly,"rw+"); }
/**
	* Opens a file session.
	*
	* @param  fFileURL URL to the requested file
	* @param  fReadonly Open file in readonly mode
	* @param  fFileMode Filemode to use
	* @return True on success
	* @since  v0.1.00
*/
	public boolean open (URL fFileURL,boolean fReadonly,String fFileMode)
	{
		if (Debug != null) { Debug.add ("file.open (fFileURL,fReadonly," + fFileMode + ")"); }
		boolean fReturn = false;

		if ((Resource == null)&&(ResourceFile == null))
		{
			boolean fCreatedCheck = true;
			long fFilesize = 0;
			String fFilePathname = null;
			fReturn = true;

			if (fFileMode.equals ("w")) { fFileMode = "rw"; }
			if (fFileMode.equals ("w+")) { fFileMode = "rw+"; }

			if (fFileURL.getProtocol().equals ("file")) { fFilePathname = fFileURL.getPath (); }
			else
			{
				fCreatedCheck = false;
				fFilePathname = retrieveRemoteFile (fFileURL);
				fReadonly = true;
			}

			if (fFilePathname != null)
			{
				if (fReadonly)
				{
					Readonly = true;

					fFileMode = fFileMode.replaceAll ("\\+","");
					fFileMode = fFileMode.replaceAll ("w","");
				}

				ResourceFile = new File (fFilePathname);

				if (ResourceFile.exists ())
				{
					fCreatedCheck = false;
					fFilesize = ResourceFile.length ();
				}
				else if (!Readonly)
				{
//					if ($direct_settings['swg_umask_change']) { umask (intval ($direct_settings['swg_umask_change'],8)); }
				}
				else { fReturn = false; }
			}
			else { fReturn = false; }

			if ((fReturn)&&(fFilesize < Integer.MAX_VALUE))
			{
				try
				{
					boolean fJumpToEOFCheck = false;

					if (fFileMode.endsWith ("+"))
					{
						fFileMode = fFileMode.substring (0,(fFileMode.length () -1));
						if (fFilesize > 0) { fJumpToEOFCheck = true; }
					}

					Resource = new RandomAccessFile (ResourceFile,fFileMode);
					if (fJumpToEOFCheck) { Resource.seek (fFilesize); }
				}
				catch (Throwable fUnhandledException) { }
			}
			else { } // TODO Indicate error

			if (Resource == null)
			{
				fReturn = false;
				if ((fCreatedCheck)&&(ResourceFile.canWrite ())) { ResourceFile.delete (); }

				ResourceFile = null;
				ResourceFilePathname = "";
				ResourceLock = null;
			}
			else
			{
//				if (($direct_settings['swg_chmod_files_change'])&&($f_tempdata['bool1'])) { chmod ($f_file_path,(intval ($direct_settings['swg_chmod_files_change'],8))); }
				ResourceFilePathname = fFilePathname;

				if (!lock ('r'))
				{
					close (fCreatedCheck);
					// TODO Indicate error
				}
			}
		}

		return fReturn;
	}

/**
	* Retrieve a remote file saving it temporarily until the application is
	* closed.
	*
	* @param  fFileURL URL to the requested file
	* @return Temporary file location on success
	* @since  v0.1.00
*/
	protected String retrieveRemoteFile (URL fFileURL)
	{
		String fReturn = null;

		try
		{
			File fTmpFile = File.createTempFile ("jas",".bin");
			fTmpFile.deleteOnExit ();

			boolean fArraysCheck = false;
			InputStream fInputStream = fFileURL.openStream ();
			FileOutputStream fOutputStream = new FileOutputStream (fTmpFile);
			byte[] fBuffer = new byte[4096];
			int fBufferSize = 0;

			try
			{
				if (Double.parseDouble (System.getProperty ("java.specification.version")) >= 1.6) { fArraysCheck = true; }
			}
			catch (Throwable fUnhandledException) { }

			while (fBufferSize > -1)
			{
				fBufferSize = fInputStream.read (fBuffer);

				if (fBufferSize > 0)
				{
					if (fBuffer.length != fBufferSize)
					{
						if (fArraysCheck) { fBuffer = Arrays.copyOf (fBuffer,fBufferSize); }
						else if (fBuffer.length != fBufferSize)
						{
							byte[] fBufferCopy = new byte[fBufferSize];
							System.arraycopy (fBuffer,0,fBufferCopy,0,(Math.min (fBuffer.length,fBufferSize)));
							fBuffer = fBufferCopy;
						}
					}

					fOutputStream.write (fBuffer);
				}
			}

			fInputStream.close ();
			fOutputStream.close ();
			fReturn = fTmpFile.getPath ();
		}
		catch (Throwable fUnhandledException) {}

		return fReturn;
	}

/**
	* Set a given function to be called for each exception or error.
	*
	* @since v0.1.00
*/
	public void setTrigger () { setTrigger (null); }
/**
	* Set a given function to be called for each exception or error.
	*
	* @param fRunnable Error callback to be used
	* @since v0.1.00
*/
	public void setTrigger (directFileErrorRunnable fRunnable) { errorCallback = fRunnable; }

/**
	* Calls a user-defined function for each exception or error.
	*
	* @param message Error type
	* @since v0.1.00
*/
	protected void triggerError (String message) { triggerError (message,-1); }
/**
	* Calls a user-defined function for each exception or error.
	*
	* @param message Error message
	* @param messageType Error type
	* @since v0.1.00
*/
	protected void triggerError (String message,int messageType)
	{
		if (messageType < 0) { messageType = E_NOTICE; }

		if (errorCallback != null)
		{
			errorCallback.setError (message,messageType);
			errorCallback.run ();
		}
	}

/**
	* Write content to the active file session.
	*
	* @param  fData (Over)write file with the fData content at the current
	*         position
	* @return True on success
	* @since  v0.1.00
*/
	public boolean write (byte[] fData) throws IOException { return write (fData,-1); }
/**
	* Write content to the active file session.
	*
	* @param  fData (Over)write file with the fData content at the current
	*         position
	* @param  fTimeout Timeout to use (defaults to construction time value)
	* @return True on success
	* @since  v0.1.00
*/
	public boolean write (byte[] fData,int fTimeout) throws IOException
	{
		if (Debug != null) { Debug.add ("file.write (fData," + fTimeout + ")"); }
		boolean fReturn = false;

		int fBytesUnwritten = fData.length;

		if ((lock ('w'))&&((Resource.length () + fBytesUnwritten) < Integer.MAX_VALUE))
		{
			int fBytesWritten = 0;
			int fPartBytes = 0;
			fReturn = true;
			if (fTimeout < 0) { fTimeout = TimeoutCount;  }
			Date fTimeoutTime = ((Time == null) ? new Date (new Date().getTime () + (fTimeout * 1000)) : new Date (Time.getTime () + (fTimeout * 1000)));

			do
			{
				fPartBytes = ((fBytesUnwritten > 4096) ? 4096 : fBytesUnwritten);

				Resource.write (fData,fBytesWritten,fPartBytes);
				fBytesUnwritten -= fPartBytes;
				fBytesWritten += fPartBytes;
			}
			while ((fReturn)&&(fBytesUnwritten > 0)&&(fTimeoutTime.after (new Date ())));

			if (fBytesUnwritten > 0)
			{
				fReturn = false;
				triggerError ("file.write () reporting: Timeout occured before EOF",E_ERROR);
			}
		}

		return fReturn;
	}

/**
	* Error callback interface.
	*
	* @author     direct Netware Group
	* @copyright  (C) direct Netware Group - All rights reserved
	* @package    ext_core
	* @subpackage file
	* @since      v0.1.00
	* @license    http://www.direct-netware.de/redirect.php?licenses;mpl2
	*             Mozilla Public License, v. 2.0
*/
	public interface directFileErrorRunnable extends Runnable
	{
/**
		* "setError ()" is called with the error message and type before the callback
		* "run ()" method is called.
		*
		* @param message Error message
		* @param messageType Error type
		* @since  v0.1.00
*/
		public abstract void setError (String message,int messageType);
	}
}

//j// EOF