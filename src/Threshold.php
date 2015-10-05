<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * PHP version of CPAN Nagios::Plugin
 *
 * Nagios_Plugin and its associated Nagios_Plugin_* modules are a family of php
 * modules to streamline writing Nagios plugins. The main end user modules are 
 * Nagios_Plugin, providing an object-oriented interface to the entire 
 * Nagios::Plugin_* collection.
 *
 * The purpose of the collection is to make it as simple as possible for 
 * developers to create plugins that conform the Nagios Plugin guidelines 
 * (http://nagiosplug.sourceforge.net/developer-guidelines.html).
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Monitoring
 * @package    Nagios_Plugin
 * @author     Cyril Feraudet <cyril@feraudet.com>
 * @copyright  2010 SynapsIT
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    SVN: $Id:$
 * @link       http://pear.php.net/package/Nagios_Plugin
 */

class Nagios_Plugin_Threshold
{
	var $warning;
	var $critical;
	var $min;
	var $max;
	
	function __construct($warning, $critical, $min = '', $max = '')
	{
		$this->set_thresholds($warning, $critical, $min, $max);
	}
	
	function set_thresholds($warning, $critical, $min = '', $max = '')
	{
		$this->warning = trim($warning);
		$this->critical = trim($critical);
		$this->min = trim($min);
		$this->max = trim($max);
	}
	
	function get_status($value)
	{
		if($this->critical != '' && $this->check_threshold($value, $this->critical))
			return CRITICAL;
		if($this->warning != '' && $this->check_threshold($value, $this->warning))
			return WARNING;
		return OK;
	}

	function check_threshold($value, $threshold)
	{
		if(is_numeric($threshold)) // 10      < 0 or > 10, (outside the range of {0 .. 10})
			return $value < 0 || $value > $threshold ? true : false;

		if(ereg("^([0-9]+):$", $threshold, $regs)) // 	< 10, (outside {10 .. })
			return $value < $regs[1] ? true : false;

		if(ereg("^~:([0-9]+)$", $threshold, $regs)) // > 10, (outside the range of {- .. 10})
			return $value > $regs[1] ? true : false;

		if(ereg("^([0-9]+):([0-9]+)$", $threshold, $regs)) // < 10 or > 20, (outside the range of {10 .. 20})
			return $value < $regs[1] || $value > $regs[2] ? true : false;

		if(ereg("^@([0-9]+):([0-9]+)$", $threshold, $regs)) //  10 and  20, (inside the range of {10 .. 20})
			return $value > $regs[1] && $value < $regs[2] ? true : false;
		return false;
	}

}
