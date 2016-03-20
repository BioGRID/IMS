<?php

namespace IMS\app\lib;

/**
 * Blocks
 * This is the base class upon which all other blocks are built. 
 * With this class as the foundation, we build additional classes for
 * actual control of block rendering.
 */
 
use IMS\app\classes\utilities;
 
abstract class Blocks {
	
	private $twig;
	
	/**
	 * Setup TWIG for template handling for the blocks
	 */
	
	public function __construct( ) {
		$loader = new \Twig_Loader_Filesystem( TEMPLATE_PATH );
		$this->twig = new \Twig_Environment( $loader );
	}
	
	/** 
	 * Process a block for embedding into a view.
	 */
	 
	protected function processView( $view, $params, $render = true ) {
	
		$view = $this->twig->render( $view, $params );
		
		if( $render ) {
			echo $view;
			return true;
		} 
		
		return $view;
	}
	
}