<?php
/**
 * File: $Id$
 *
 * Base class for regexp QA checks.
 *
 * @package qachecks
 * @copyright (C) 2004 by Ninth Avenue Software Pty Ltd
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * This class is especially for QA checks which are only regular expressions.
 * All the QACheckRegexps will be run together, allowing them to be done with
 * a single parse of the file rather than once for each regular expression.
 */
class QACheckRegexp extends QACheck
{

	/**
	 * Array of regexps to run.
	 * @access public
	 */
	var $regexps = array();

	/**
	 * Array of line numbers where the regexp failed.
	 * @access private
	 */
	var $failedlines = array();

} 
?>
