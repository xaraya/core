<?php
/**
 * File: $Id$
 *
 * Base class for QA checks.
 *
 * @package qachecks
 * @copyright (C) 2004 by Ninth Avenue Software Pty Ltd
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * This class is the base class for our QA checks. 
 */
class QACheck
{

	/**
	 * Checker id.
	 * @access public
	 */
	var $id = 0;

	/**
	 * Set to false to disable this checker.
	 * @access public
	 */
	var $enabled = true;

	/**
	 * Name of the checker.
	 * @access public
	 */
	var $name = '';

	/**
	 * If this check fails, is it fatal?
	 * @access public
	 */
	var $fatal = false;

	/**
	 * Value representing the importance of this check.
	 * @access public
	 */
	var $score = 1;

	/**
	 * This checker only applies to this filetype (php|template|all).
	 * @access public
	 */
	var $filetype = 'all';

	/**
	 * Was this check successful?
	 * @access public
	 */
	var $success = true;

	/**
	 * The full name of file being checked.
	 * @access protected
	 */
	var $filename = null;


	/**
	 * Execute the check.
	 *
	 * @return bool true if the check succeeded, false otherwise
	 * @access public
	 */
	function execute()
	{
        return false;
	}


	/**
	 * Constructor.
	 *
	 * @return void
	 * @access public
	 */
	function QACheck()
	{
	}
}
?>
