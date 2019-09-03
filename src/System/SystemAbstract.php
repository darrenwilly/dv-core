<?php
namespace DV\System ;

abstract class SystemAbstract
{
	
	const WINDOWS = 'WINDOWS' ;
	
	const LINUX = 'LINUX' ;
	
	const UNIX = 'UNIX' ;
	
	protected $wmi = null ;
	
	
	
	public function __construct()
	{
		$this->init() ;
	}
	
	
	abstract public function init() ;


}