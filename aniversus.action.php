<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Aniversus implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * aniversus.action.php
 *
 * Aniversus main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/aniversus/aniversus/myAction.html", ...)
 *
 */
  
  
  class action_aniversus extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "aniversus_aniversus";
            self::trace( "Complete reinitialization of board game" );
      }
  	}
  	
  	// TODO: defines your action entry points there
    public function playFunctionCard()
    {
        // setAjaxMode is required to make an action call
        self::setAjaxMode();

        // Retrieve arguments
        $card_id = self::getArg( "card_id", AT_posint, true );
        $card_type = self::getArg( "card_type", AT_posint, true );
        $player_id = self::getArg( "player_id", AT_alphanum, true );
        $this->game->playFunctionCard( $player_id, $card_id, $card_type );

        // End of the action
        self::ajaxResponse();
    }

    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  

