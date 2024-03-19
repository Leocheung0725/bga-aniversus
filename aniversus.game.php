<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * Aniversus implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * aniversus.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );

class Aniversus extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        // declear Game State Labels, you can set the value by using self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        self::initGameStateLabels( array( 
            'currentPlayerTeam' => 10,
            'isNextRoundAction' => 11,
            'currentAction' => 12,
        ) );
        
        // Build the card deck in the constructor
        // ----- Leo

        // Initialize the Deck component for Cat deck
        $this->catDeck = self::getNew("module.common.deck"); // this is related to the deck of cards
        $this->catDeck->init("cat_deck"); // this is related to the sql table name (用返一開始CREATED DATABASE 個名 係DBMODEL.SQL)

        // Initialize the Deck component for Squirrel deck
        $this->squirrelDeck = self::getNew("module.common.deck");
        $this->squirrelDeck->init("squirrel_deck");
    }
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "aniversus";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    

        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        // self::setGameStateInitialValue( 'winning_score', 2 ); // leo
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
        
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        // $gameinfos = self::getGameinfos();
        $default_colors = array( "E96043", "5743E9" );

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, 
        player_name, player_avatar, player_team, player_productivity_limit, 
        player_productivity, player_action_limit, player_action, player_power) VALUES ";
        $values = array();
        $player_team = array("cat","squirrel");
        shuffle($player_team);
        foreach( $players as $player_id => $player )
        {
            // Retrieve and remove the first color from the default colors array.
            $color = array_shift($default_colors);

            // Retrieve and remove the first team from the player team array.
            $team = array_shift($player_team);
        
            // Create the player's data string for insertion into the database.
            // Be sure to properly escape strings to prevent injection attacks.
            $playerNameEscaped = addslashes($player['player_name']);
            $playerAvatarEscaped = addslashes($player['player_avatar']);
        
            // Structure the data in a tuple format for SQL insertion.
            $values[] = "(
                '{$player_id}',
                '$color',
                '{$player['player_canal']}',
                '{$playerNameEscaped}',
                '{$playerAvatarEscaped}',
                '$team',
                0,
                100,
                0,
                100,
                0
            )";
        }
        $sql .= implode( ',' , $values );
        self::DbQuery( $sql );
        // Initialize the playing_card
        $sql = "INSERT INTO playing_card (player_id) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player ) {
            $values[] = "(
                '{$player_id}'
            )";
        }
        $sql .= implode( ',' , $values );
        self::DbQuery( $sql );
        // self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
        // send material.inc.php Static information to client
        $result['cards_info'] = $this->cards_info;
        $result['card_type_arg2css_position'] = $this->card_type_arg2css_position;
        $result['id2card_type_arg'] = $this->id2card_type_arg;
        // Get information about current player
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_team team, player_power, player_productivity_limit, player_productivity, player_action_limit, player_action FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        $current_player = $result['players'][$current_player_id];
        $result['current_player'] = $current_player;
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        // Cards in player hand
        $result['hand'] = $this->getActivePlayerDeck($current_player_id)->getCardsInLocation( 'hand', $current_player_id );
        // common information, create a new array to store the common information
        foreach ($result['players'] as $player_id => $player) {
            // Initialize the discard pile and playmat arrays for each player
            $result['players'][$player_id]['discardpile'] = array();
            $result['players'][$player_id]['playmat'] = array();
            // Discard pile information
            // order by location_arg ( the larger location arg is the top card in the discard pile)
            $discardPileCards = $this->getActivePlayerDeck($player_id)->getCardsInLocation('discard', null, "card_location_arg");
            foreach ($discardPileCards as $card) {
                $result['players'][$player_id]['discardpile'][] = $card;
            }
        
            // Playmat information
            $playmatCards = $this->getActivePlayerDeck($player_id)->getCardsInLocation('playmat');
            foreach ($playmatCards as $card) {
                $result['players'][$player_id]['playmat'][] = $card;
            }
        }
        // Cards in the draw deck
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    
    /*
        In this space, you can put any utility methods useful for your game logic
    */
    // Debug function to get all cards in a deck
    // $player_id = self::getActivePlayerId();
    function getActivePlayerDeck($player_id) {
        // get the player team information and determine which deck would be used
        $sql = "SELECT player_id, player_name, player_color, player_team FROM player";
        $players = self::getCollectionFromDb( $sql );
        // get active player team
        $active_player_team = $players[$player_id]['player_team'];
        if ($active_player_team == 'cat') {
            return $this->catDeck;
        } else {
            return $this->squirrelDeck;
        }
    }
    // $player_id = self::getActivePlayerId();
    function getNonActivePlayerDeck($player_id) {
        // get the player team information and determine which deck would be used
        $sql = "SELECT player_id, player_name, player_color, player_team FROM player";
        $players = self::getCollectionFromDb( $sql );
        // get active player team
        $active_player_team = $players[$player_id]['player_team'];
        if ($active_player_team == 'cat') {
            return $this->squirrelDeck;
        } else {
            return $this->catDeck;
        }
    }
    // $card_id = card_type
    function getCardinfoFromCardsInfo($card_id) {
        return current(array_filter($this->cards_info, function($card) use ($card_id){
            return $card['id'] == $card_id;
        }));
    }

    function encodePlayerLocation($row, $column) {
        /*
        To encode row 1, column 1
        $location_arg = encodeLocation(1, 1); // returns 1
        To encode row 2, column 3
        $location_arg = encodeLocation(2, 3); // returns 8
        */
        $num_columns = 5;
        return ($row - 1) * $num_columns + $column;
    }
    
    function decodePlayerLocation($location_arg) {
        /*
        To decode location_arg 1
        $position = decodeLocation(1); // returns ['row' => 1, 'column' => 1]
        To decode location_arg 8
        $position = decodeLocation(8); // returns ['row' => 2, 'column' => 3]
        */
        $num_columns = 5;
        $row = intval(($location_arg - 1) / $num_columns) + 1;
        $column = ($location_arg - 1) % $num_columns + 1;
        return array('row' => $row, 'col' => $column);
    }

    function findLargestLocationArg($cards) {
        // Check if the input is null or an empty array
        if (is_null($cards) || empty($cards)) {
            return 0;
        }
    
        // Initialize an array to hold location_arg values
        $locationArgs = [];
    
        // Iterate through each card and collect the location_arg values
        foreach ($cards as $card) {
            if (isset($card['location_arg'])) {
                $locationArgs[] = $card['location_arg'];
            }
        }
    
        // If locationArgs is empty, return 0, otherwise return the max value
        return !empty($locationArgs) ? max($locationArgs) : 0;
    }

    function endEffect($player_id, $end_type) {
        if ($end_type == 'normal') {
            $sql = "UPDATE playing_card SET active = FALSE WHERE active = TRUE AND player_id = $player_id";
            self::DbQuery( $sql );
            $this->gamestate->changeActivePlayer( $player_id );
            $this->gamestate->nextState( "playerTurn" );
        } else if ($end_type == 'active') {
            $this->gamestate->changeActivePlayer( $player_id );
            $this->gamestate->nextState( "cardActiveEffect" );
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in aniversus.action.php)
    */

    public function playFunctionCard( $player_id, $card_id, $card_type ) {
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'playFunctionCard' );
        // get the active player id
        $ActivePlayer = self::getActivePlayerId();

        if ($player_id != $ActivePlayer) {
            throw new BgaUserException( self::_("You are not the active player") );
        }
        $card_info = $this->getCardinfoFromCardsInfo($card_type);
        $card_cost = $card_info['cost'];
        // write a function to handle the cost free
        /////
        ///  .....
        ////
        $card_team = $card_info['team'];
        $player_deck = $this->getActivePlayerDeck($player_id);
        // check whether the user have this card in hand
        $card_deck_info = $player_deck->getCard($card_id);
        if ( $card_deck_info['location'] != 'hand' ) {
            throw new BgaUserException( self::_("This card is not in your hand") );
        }
        // get the active player energy and action number
        $sql = "select player_score, player_action, player_productivity, player_team from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        if ($player['player_team'] != $card_team && $card_team != "basic") {
            throw new BgaUserException( self::_("This card does not belong to your team") );
        }
        if ($player['player_action'] <= 0) {
            throw new BgaUserException( self::_("You do not have enough action to play this card") );
        }
        if ($player['player_productivity'] < $card_cost) {
            throw new BgaUserException( self::_("You do not have enough productivity to play this card") );
        }
        // play the card
        $sql = "
        UPDATE player
        SET player_action = player_action - 1, player_productivity = player_productivity - $card_cost
        WHERE player_id = $player_id
        ";
        self::DbQuery( $sql );
        // move the card from hand to discard Pile because this is function card
        // get discard pile cards list
        $discard_pile_cards = $player_deck->getCardsInLocation('discard');
        // find the next location_arg for the discard pile
        $largest_location_arg = $this->findLargestLocationArg($discard_pile_cards);
        $player_deck->moveCard($card_id, 'discard', $largest_location_arg + 1);
        // Notify all players about the card played
        self::notifyAllPlayers( "playFunctionCard", clienttranslate( '${player_name} plays ${card_name} : ${card_effect}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_info['name'],
            'card_id' => $card_id,
            'card_type' => $card_type,
            'card_effect' => $card_info['function'],
        ) );
        // Refresh the player board by using lastest data (Fetch the data from database again this time) 
        $sql = "select player_score, player_action, player_productivity, player_team from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        self::notifyAllPlayers( "updatePlayerBoard", "", array(
            'player_id' => $player_id,
            'player_productivity' => $player['player_productivity'],
            'player_action' => $player['player_action'],
            'player_score' => $player['player_score'],
        ) );
        // update database that what card the active player has played
        $sql = "UPDATE playing_card SET "
        . "card_id = " . intval($card_id) . ", "
        . "card_type = '" . addslashes($card_info['type']) . "', "
        . "card_type_arg = " . intval($card_info['id']) . ", "
        . "card_location = 'discard', "
        . "card_status = TRUE, "
        . "active = TRUE "
        . "WHERE player_id = " . intval($player_id);
        self::DbQuery( $sql );
        // check whether the opponent has counter attack card in hand
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        if (empty($opponent_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' ))) {
            // Go to launch state, the card effect would be done
            $this->activeNextPlayer();
            $this->gamestate->nextState( "launch" );
        } else {
            // Go to counterAttack state
            $this->gamestate->nextState( "counterattack" );
        }
    }

    public function playPlayerCard($player_id, $card_id, $card_type, $row, $col) {
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'playPlayerCard' );
        // get the active player id
        $ActivePlayer = self::getActivePlayerId();
        if ($player_id != $ActivePlayer) {
            throw new BgaUserException( self::_("You are not the active player") );
        }
        $card_info = $this->getCardinfoFromCardsInfo($card_type);
        $card_cost = $card_info['cost'];
        $card_team = $card_info['team'];
        $player_deck = $this->getActivePlayerDeck($player_id);
        // check whether the user have this card in hand
        $card_deck_info = $player_deck->getCard($card_id);
        if ( $card_deck_info['location'] != 'hand' ) {
            throw new BgaUserException( self::_("This card is not in your hand") );
        }
        // get the active player energy and action number
        $sql = "select player_score, player_action, player_productivity, player_team from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        if ($player['player_team'] != $card_team && $card_team != "basic") {
            throw new BgaUserException( self::_("This card does not belong to your team") );
        }
        if ($player['player_action'] <= 0) {
            throw new BgaUserException( self::_("You do not have enough action to play this card") );
        }
        // play the card
        // minus the action and productivity of player in this round
        if ($row == "1") {
            if ($player['player_productivity'] < $card_cost) {
                throw new BgaUserException( self::_("You do not have enough productivity to play this card") );
            }
            $card_power = $card_info['power'];
            $sql = "
            UPDATE player
            SET player_action = player_action - 1, 
            player_productivity = player_productivity - $card_cost,
            player_power = player_power + $card_power
            WHERE player_id = $player_id
            ";
            self::DbQuery( $sql );
        } else if ( $row == "2" ) {
            $card_productivity = $card_info['productivity'];
            $sql = "
            UPDATE player
            SET player_action = player_action - 1, 
            player_productivity = player_productivity + $card_productivity, 
            player_productivity_limit = player_productivity_limit + $card_productivity
            WHERE player_id = $player_id
            ";
            self::DbQuery( $sql );
        }
        // move the card from hand to playmat
        $player_deck->moveCard($card_id, 'playmat', $this->encodePlayerLocation($row, $col));
        self::notifyAllPlayers( "playPlayerCard", clienttranslate( '${player_name} plays ${card_name} : ${card_effect}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_info['name'],
            'card_id' => $card_id,
            'card_type' => $card_type,
            'card_effect' => $card_info['function'],
            'row' => $row,
            'col' => $col,
        ) );
        // Refresh the player board by using lastest data (Fetch the data from database again this time) 
        $sql = "select player_score, player_action, player_productivity, player_team, player_power from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        self::notifyAllPlayers( "updatePlayerBoard", "", array(
            'player_id' => $player_id,
            'player_productivity' => $player['player_productivity'],
            'player_action' => $player['player_action'],
            'player_score' => $player['player_score'],
            'player_power' => $player['player_power'],
        ) );
    }

    public function throwCards( $card_ids ) {
        // Ensure that the $card_ids is actually an array
        if (!is_array($card_ids)) {
            throw new BgaUserException("Invalid card IDs.");
        }
        // checking the action
        // self::checkAction( 'throwCard' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        foreach ($card_ids as $card_id) {
            $player_deck->moveCard($card_id, 'discard');
            $card = $player_deck->getCard($card_id);
            self::notifyAllPlayers( "cardThrown", clienttranslate( '${player_name} throws ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $this->cards_info[$card['type_arg']]['name'],
                'card_id' => $card_id,
                'card_type_arg' => $card['type_arg'],
            ) );
        }
    }
    public function intercept_counterattack() {
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'intercept_counterattack' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
    }


    public function pass_counterattack() {
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'pass_counterattack' );
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_id FROM playing_card WHERE active = TRUE";
        $active_player_id = self::getUniqueValueFromDB( $sql );
        if ($player_id != $active_player_id) {
            $sql = "UPDATE playing_card SET card_status = TRUE WHERE active = TRUE";
            $this->gamestate->nextState( "cardEffect" );
        } else {
            $sql = "UPDATE playing_card SET active = FALSE, card_status = FALSE WHERE active = TRUE";
            $this->gamestate->nextState( "playerTurn" );
        }
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */
    function argCardEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT card_id, card_type, card_type_arg, player_id, card_status, card_location FROM playing_card WHERE active = TRUE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );

        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
            'card_location' => $playing_card_info['card_location'],

        );
    }

    function argCardActiveEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT card_id, card_type, card_type_arg, player_id, card_status, card_location FROM playing_card WHERE active = TRUE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        switch ($playing_card_info['card_type_arg']) {
            case `1`:
                $message = "must discard a card from the hand";
                break;
            default:
                $card_effect = "You can play a card from your hand to the playmat";
                break;
        }
        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
            'card_location' => $playing_card_info['card_location'],
            'message' => $message,
        );
    
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////
    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /* Example
    function stMyGameState()
    {   
        // Do some stuff ...
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */
    function stNewHand() {
        try {
            // Red color player plays first
            // Find who is first player
            $sql = "SELECT player_id, player_name, player_color, player_team FROM player";
            // $players = self::loadPlayersBasicInfos();   // !! We must only return information visible by this player !!
            $players = self::getCollectionFromDb( $sql );
            // Do something with this information
            foreach ($players as $player_id => $player_info) {
                if ($player_info['player_color'] == "E96043") {
                    $first_player = $player_info;
                } else {
                    $second_player = $player_info;
                }
            }
            // Set red player as active player, red player has to play first
            // we would use this to deal initial cards to players ( 7 cards for first player, 8 cards for second player)
            // $players = $this->loadPlayersBasicInfos();
            // Create basic cards (IDs 1 to 13) for both decks
            $basicCards = array();
            foreach ($this->cards_info as $id => $card_info) {
                if ($card_info['id'] >= 1 && $card_info['id'] <= 13) {
                    $basicCards[] = array('type' => $card_info['type'], 'type_arg' => $card_info['id'], 'nbr' => $card_info['nbr']);
                }
            }
            // Create special cards for Cat deck (IDs 101 to 114)
            $catSpecialCards = array();
            foreach ($this->cards_info as $id => $card_info) {
                if ($card_info['id'] >= 101 && $card_info['id'] <= 114) {
                    $catSpecialCards[] = array('type' => $card_info['type'], 'type_arg' => $card_info['id'], 'nbr' => $card_info['nbr']);
                }
            }
            // Create special cards for Squirrel deck (IDs 51 to 64)
            $squirrelSpecialCards = array();
            foreach ($this->cards_info as $id => $card_info) {
                if ($card_info['id'] >= 51 && $card_info['id'] <= 64) {
                    $squirrelSpecialCards[] = array('type' => $card_info['type'], 'type_arg' => $card_info['id'], 'nbr' => $card_info['nbr']);
                }
            }
            // Add cards to Cat deck
            $this->catDeck->createCards($basicCards, 'deck');
            $this->catDeck->createCards($catSpecialCards, 'deck');
            // Add cards to Squirrel deck
            $this->squirrelDeck->createCards($basicCards, 'deck');
            $this->squirrelDeck->createCards($squirrelSpecialCards, 'deck');
            // Shuffle both decks
            $this->catDeck->shuffle('deck');
            $this->squirrelDeck->shuffle('deck');
            // Determine who is cat team and who is squirrel team
            $first_player_deck = ($first_player['player_team'] == 'cat') ? 'catDeck' : 'squirrelDeck';
            $second_player_deck = ($second_player['player_team'] == 'cat') ? 'catDeck' : 'squirrelDeck';
            // Deal cards to players
            $cards_to_first_player = $this->{$first_player_deck}->pickCards( 7, 'deck', $first_player['player_id'] );
            self::notifyPlayer($first_player['player_id'], 'newHand', '', array('cards' => $cards_to_first_player) );
            $cards_to_second_player = $this->{$second_player_deck}->pickCards( 8, 'deck', $second_player['player_id'] );
            self::notifyPlayer($second_player['player_id'], 'newHand', '', array('cards' => $cards_to_second_player) );
            // Activate first player (which is in general a good idea :) )
            $this->gamestate->changeActivePlayer( $first_player['player_id'] );
            // switch to next state
            $this->gamestate->nextState( "playerTurn" );
        } catch ( Exception $e ) {
            // logging does not actually work in game init :(
            // but if you calling from php chat it will work
            $this->error("Fatal error while creating game");
            $this->dump('err', $e);
        }
    }

    function stCardDrawing() {
        // get active player team
        $active_player = self::getActivePlayerId();
        $active_player_team = $this->getActivePlayerDeck($active_player);
        // Draw a card from the deck
        $pick_cards = $active_player_team->pickCards( 2, 'deck', $active_player );
        // Notify the player about the card drawn
        self::notifyPlayer($active_player, 'cardDrawn', '', $pick_cards);
        // switch to next state
        $this->gamestate->nextState( "playerTurn" );
    }

    function stCardEffect() {
        // determine what card effect should be done / finished
        $sql = "SELECT card_id, card_type, card_type_arg, player_id, card_status, card_location FROM playing_card WHERE active = TRUE";
        $card_effect_info = self::getNonEmptyObjectFromDB( $sql );
        $player_id = $card_effect_info['player_id'];
        $player_deck = $this->getActivePlayerDeck($card_effect_info['player_id']);
        switch ($card_type_arg) {
            case "1":
                // do something
                $card_num = 3;
                $picked_cards_list = $player_deck->pickCards( $card_num, 'deck', $card_effect_info['player_id'] );
                self::notifyPlayer($player_id, 'cardDrawn', clienttranslate( 'You draw ${card_num} cards' ), [
                    'cards' => $picked_cards_list,
                    'card_num' => $card_num,
                    'player_id' => $player_id,
                ]);
                // endEffect have two type : normal and active
                $this->endEffect($player_id, "active");
                break;
            case "2":
                // do something
                break;
            case "7":
                $sql = "UPDATE player SET player_productivity =  player_productivity + 2 WHERE player_id = $player_id";
                self::DbQuery( $sql );
                $this->endEffect($player_id, "normal");
                break;
            default:
                break;
        }
    }

    function stEndHand() {
        // End the game and do some scoring here

        // ... code the function
        $this->gamestate->nextState( "endGame" );
    }
//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//

    }    
}