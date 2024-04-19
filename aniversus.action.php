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

    public function playPlayerCard()
    {
        self::setAjaxMode();
        $card_id = self::getArg( "card_id", AT_posint, true );
        $card_type = self::getArg( "card_type", AT_posint, true );
        $player_id = self::getArg( "player_id", AT_alphanum, true );
        $row = self::getArg( "row", AT_posint, true );
        $col = self::getArg( "col", AT_posint, true );
        $this->game->playPlayerCard( $player_id, $card_id, $card_type, $row, $col );
        self::ajaxResponse();
    }

    public function throwCards()
    {
        self::setAjaxMode();
        $card_ids = self::getArg( "card_ids", AT_json, true );
        $this->game->validateJSonAlphaNum($card_ids, 'card_ids');
        $player_id = self::getArg( "player_id", AT_alphanum, true );
        $this->game->throwCards( $player_id, $card_ids );
        self::ajaxResponse();
    }

    public function eightEffect_CardActiveEffect() {
        self::setAjaxMode();
        $top_items = self::getArg( "top_items", AT_json, true );
        $this->game->validateJSonAlphaNum($top_items, 'top_items');
        $bottom_items = self::getArg( "bottom_items", AT_json, true );
        $this->game->validateJSonAlphaNum($bottom_items, 'bottom_items');
        $this->game->eightEffect_CardActiveEffect( $top_items, $bottom_items );
        self::ajaxResponse();
    }
    public function swapField_CardActiveEffect() {
        self::setAjaxMode();
        $row = self::getArg( "row", AT_posint, true );
        $col = self::getArg( "col", AT_posint, true );
        $this->game->swapField_CardActiveEffect( $row, $col );
        self::ajaxResponse();
    }
    public function pickPlayerFromPlaymat2Hand_CardActiveEffect() {
        self::setAjaxMode();
        $row = self::getArg( "row", AT_posint, true );
        $col = self::getArg( "col", AT_posint, true );
        $this->game->pickPlayerFromPlaymat2Hand_CardActiveEffect( $row, $col );
        self::ajaxResponse();
    }
    public function pickPlayerFromDiscardPile_CardActiveEffect() {
        self::setAjaxMode();
        $selected_player = self::getArg( "selected_player", AT_json, true );
        $this->game->validateJSonAlphaNum($selected_player, 'selected_player');
        $this->game->pickPlayerFromDiscardPile_CardActiveEffect( $selected_player );
        self::ajaxResponse();
    }

    public function pickPlayerFromDrawDeck_CardActiveEffect() {
        self::setAjaxMode();
        $selected_player = self::getArg( "selected_player", AT_json, true );
        $this->game->validateJSonAlphaNum($selected_player, 'selected_player');
        $this->game->pickPlayerFromDrawDeck_CardActiveEffect( $selected_player );
        self::ajaxResponse();
    }
    public function intercept_counterattack()
    {
        self::setAjaxMode();
        $this->game->intercept_counterattack();
        self::ajaxResponse();
    }

    public function pass_counterattack()
    {
        self::setAjaxMode();
        $this->game->pass_counterattack();
        self::ajaxResponse();
    }

    public function pass_playerTurn() {
        self::setAjaxMode();
        $this->game->pass_playerTurn();
        self::ajaxResponse();
    }

    public function shoot_playerTurn() {
        self::setAjaxMode();
        $this->game->shoot_playerTurn();
        self::ajaxResponse();
    }

    public function throwCard_throwCard() {
        self::setAjaxMode();
        $card_ids = self::getArg( "card_ids", AT_json, true );
        $this->game->validateJSonAlphaNum($card_ids, 'card_ids');
        $player_id = self::getArg( "player_id", AT_alphanum, true );
        $this->game->throwCard_throwCard( $player_id, $card_ids );
        self::ajaxResponse();
    }


    public function pass_throwCard() {
        self::setAjaxMode();
        $this->game->pass_throwCard();
        self::ajaxResponse();
    }

    public function throwCard_CardActiveEffect() {
        self::setAjaxMode();
        $card_ids = self::getArg( "card_ids", AT_json, true );
        $this->game->validateJSonAlphaNum($card_ids, 'card_ids');
        $player_id = self::getArg( "player_id", AT_alphanum, true );
        $this->game->throwCard_CardActiveEffect( $player_id, $card_ids );
        self::ajaxResponse();
    }
  }
  

