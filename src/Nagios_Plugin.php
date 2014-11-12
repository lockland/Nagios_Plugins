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
 
 ChangeLog
 Versão 1.0-9
 - passar todos parametros para long caso exista o long (Cleber - OpServices) 11/08/13
 - retonar array com os argumentos (Cleber - OpServices) 11/08/13
 - Adicionado metodo de verificação de argumentos requiridos (Sidney - OpServices) 21/08/13 
 - Ajuste no método de Agumentos requiridos (Cléber - OpServices) 04/09/13
 */

require_once('Getopt.php');
require_once('Threshold.php');

define(OK,0);
define(WARNING,1);
define(CRITICAL,2);
define(UNKNOWN,3);
define(DEPENDENT,4);


class Nagios_Plugin
{
	var $np_shortname;
	var $np_usage;
	var $np_blurb;
	var $np_version;
	var $np_extra;
	var $np_url;
	var $np_license;
	var $np_plugin;
	var $np_timeout;
	var $np_messages;
	var $np_opts;
	var $np_args;
	var $np_threshold;
	var $np_typeargs;

	function __construct($args = array())
	{
		global $argv;
		$this->np_shortname = isset($args['shortname']) ? $args['shortname'] : basename($argv[0]);
		$this->np_shortname = isset($args['plugin']) ? $args['plugin'] : $this->np_shortname;
		$this->np_messages = array(OK => array(), WARNING => array(), CRITICAL => array(), UNKNOWN => array(), DEPENDENT => array());
		$this->np_perfdata = array();
		$this->np_usage = isset($args['usage']) ? $args['usage'] : 'Usage: %s';
		$this->np_usage = sprintf($this->np_usage,$this->np_shortname);
		$this->np_version = isset($args['version']) ? $args['version'] : 0;
		$this->np_timeout = isset($args['timeout']) ? $args['timeout'] : 15;
		set_time_limit($this->np_timeout);
		//$this->np_license = isset($args['license']) ? $args['license'] : "This nagios plugin is free software, and comes with ABSOLUTELY\nNO WARRANTY. It may be used, redistributed and/or modified under\nthe terms of the GNU General Public Licence (see\nhttp://www.fsf.org/licensing/licenses/gpl.txt";
		$this->np_extra = isset($args['extra']) ? $args['extra'] : '';
		$this->np_blurb = isset($args['blurb']) ? $args['blurb'] : '';
		$this->np_url = isset($args['url']) ? $args['url'] : '';
		$this->np_args = array();
		$this->np_typeargs = array('i' => 'INTEGER', 's' => 'STRING');
		$this->add_arg('?|usage','Print usage information');
		$this->add_arg('help|h','Print detailed help screen');
		$this->add_arg('version|V','Print version information');
		$this->add_arg('timeout|t=i',sprintf('Seconds before plugin times out (default: %s)',$this->np_timeout));
		$this->add_arg('verbose|v','Show details for command-line debugging');
	}

	function add_arg($spec, $help, $required = 0, $label = '')
	{
		$opts = $type = $firstopt = $secondopt = '';
		list($opts,$type) = explode('=',$spec);
		list($firstopt,$secondopt) = explode('|',$opts);
		foreach(array($firstopt,$secondopt) as $opt)
		{
			if(strlen($opt) == 1)
				$this->np_args[$opts]['short'] = $opt;
			elseif($opt != '')
				$this->np_args[$opts]['long'] = $opt;
		}
		$this->np_args[$opts]['help'] = $help;
		$this->np_args[$opts]['label'] = $label;
		$this->np_args[$opts]['type'] = $type;
		$this->np_args[$opts]['required'] = $required;

	}

	function getopts()
	{
		global $argv;
		$cg = new Console_Getopt();
		$args = $cg->readPHPArgv();
		array_shift($args);

		$shortOpts = '';
		$longOpts  = array();
		foreach($this->np_args as $opts)
		{
			if(isset($opts['short']))
				$shortOpts .= $opts['short'].($opts['type'] != '' ? ':' : '');
			if(isset($opts['long']))
				$longOpts[] = $opts['long'].($opts['type'] != '' ? '=' : '');
		}
		$params = $cg->getopt2($args, $shortOpts, $longOpts);
		if (PEAR::isError($params)) {
			echo 'Error: ' . $params->getMessage() . "\n";
			exit(3);
		}

		$params = $this->condense_arguments($params);

        /**passar todos parametros para long**/        
        $all_args = $this->np_args;
        foreach( $all_args as $x => $y ){
            if( $y['short'] != null ) $arrLong[$y['short']] = $y['short'];
            if( $y['long'] != null and $y['short'] ) $arrLong[$y['short']] = $y['long'];
            if( $y['short'] == null and $y['long'] ) $arrLong[$y['long']] = $y['long'];
        }
        
        foreach($params as $k => $v ){
     		if( $arrLong[$k] ){
     			$params_long[$arrLong[$k]] = $v;
     		}elseif(in_array($k, $arrLong)){
     			$params_long[$k] = $v;
     		}
	    }
	    
        $params = $params_long;
        /** fim **/
        
		if(count($params) > 0 ){
		    foreach($params as $key => $val){
			    $this->opts[$key] = $val != '' ? $val : true;
			    $this->$key = $val != '' ? $val : true;
		    }
		}

		if(isset($this->h) || isset($this->help))
		        $this->print_help();

		if(isset($this->V) || isset($this->version))
		        $this->print_version();

		if(isset($this->usage) || isset($this->{'?'}))
		        $this->print_usage();
		/**
		 * This code added by Sidney Souza - sidney.souza@opservices.com.br
		 * The below code going to validate if the required parameter was declared
		 */
		foreach ( $this->np_args as $param=>$arg){
		    if ($arg['required'] === 1 && !isset( $this->opts[ $arg['long'] ] )){
		        if(isset($arg['long'])){
		            $par =  "--".$arg['long'] ; 
		        }else{
		            $par =  "-".$arg['short'] ; 
		        }
    			$this->nagios_exit(UNKNOWN, "Error: Console_Getopt: option requires an argument ".$par );
    		}

		} // end code added by Sidney Souza


	}

	function print_version()
	{
		$this->nagios_exit(UNKNOWN, $this->np_shortname.' '.$this->np_version.(isset($this->np_url) ? ' '.$this->np_url : ''));
	}
	
	function print_usage()
	{
		$this->nagios_exit(UNKNOWN, $this->np_usage);
	}

	function print_help()
	{
		$help = $this->np_shortname.' - '.$this->np_version."\n";
		$help .= $this->np_license."\n";
		$help .= (isset($this->np_blurb) && $this->np_blurb != '') ? $this->np_blurb."\n\n" : '';
		$help .= $this->np_usage."\n\n";
		foreach($this->np_args as $arg)
		{
			if(isset($arg['short']) && isset($arg['long']))
				$help .= ' -'.$arg['short'].', --'.$arg['long'];
			else
				$help .= isset($arg['short']) ? ' -'.$arg['short'] : ' --'.$arg['long'];
			$help .= isset($arg['type']) && $arg['type'] != '' ? '='.$this->np_typeargs[$arg['type']] : '';
			$help .= "\n";
			$help .= "   ".$arg['help']."\n";
		}
		$this->nagios_exit(UNKNOWN, $help);
	}
	
	function &condense_arguments($params)
	{
		$new_params = array();
		foreach ($params[0] as $param)
		{
			$new_params[(substr($param[0],0,2) == '--' ? substr($param[0],2) : $param[0])] = $param[1];
		}
		return $new_params;
	}

	function nagios_exit($status, $message)
	{
		echo $message.(count($this->np_perfdata) > 0 ? '|'.implode(' ',$this->np_perfdata) : '')."\n";
		exit($status);
	}

	function add_perfdata($label, $value, $uom = '', $threshold = false, $inverse = false)
	{
		$perfdata = "'$label'=".($inverse != false ? '-' : '')."$value$uom";
		if(!is_object($threshold))
			$threshold = new Nagios_Plugin_Threshold('','');
		$perfdata .= ';'.($inverse != false ? '-' : '').$threshold->warning;
		$perfdata .= ';'.($inverse != false ? '-' : '').$threshold->critical;
		$perfdata .= ';'.($inverse != false ? '-' : '').$threshold->min;
		$perfdata .= ';'.($inverse != false ? '-' : '').$threshold->max;
		$this->np_perfdata[] = $perfdata;
	}

	function add_message($status, $message)
	{
		$this->np_messages[$status][] = $message;
	}

	function check_messages()
	{
		$message = array();
		if(count($this->np_messages[CRITICAL]) > 0)
			$status = CRITICAL;
		elseif(count($this->np_messages[WARNING]) > 0)
			$status = WARNING;
		elseif(count($this->np_messages[UNKNOWN]) > 0)
			$status = UNKNOWN;
		elseif(count($this->np_messages[DEPENDENT]) > 0)
			$status = DEPENDENT;
		$status = isset($status) ? $status : OK;
		return array($status, implode(', ', $this->np_messages[$status]));
	}

	function set_thresholds($warning, $critical)
	{
		$this->np_threshold = new Nagios_Plugin_Threshold($warning, $critical);
	}

	function check_threshold($value, $warning = false, $critical = false)
	{
		if(!is_object($this->np_threshold))
			$this->np_threshold = new Nagios_Plugin_Threshold($warning, $critical);
		elseif($warning !== false || $critical !== false)
			$this->np_threshold->set_thresholds($warning, $critical);
		return $this->np_threshold->get_status($value);
	}

	function threshold()
	{
		return $this->np_threshold;
	}

	function nagios_die($message, $status = UNKNOWN)
	{
		$this->nagios_exit($status, $message);
	}

}

?>

