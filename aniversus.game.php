<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * Aniversus implementation : © Cheung Wai Kei, Leo  leocheung1718@gmail.com
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
require_once("modules/gamelogic/aniversus.utils.php");
require_once("modules/gamelogic/aniversus.stateaction.php");
require_once("modules/gamelogic/aniversus.playeraction.php");
require_once("modules/gamelogic/aniversus.stateargs.php");
class Aniversus extends Table
{
    use AniversusUtils;
    use AniversusStateActions;
    use AniversusPlayerActions;
    use AniversusStateArgs;
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

        // ANCHOR: setupNewGame
        
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        // $gameinfos = self::getGameinfos();
        $default_colors = array( "E96043", "5743E9" );

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, 
        player_name, player_avatar, player_team, player_productivity_limit, 
        player_productivity, player_action_limit, player_action, player_power, shooting_number,
        player_status) VALUES ";
        $values = array();
        $player_team = array("cat","squirrel");
        shuffle($player_team);
        // Define the shooting numbers for each team
        $shooting_numbers = array(
            "cat" => $this->cat_original_shooting_numbers,
            "squirrel" => $this->squirrel_original_shooting_numbers
        );
        foreach( $players as $player_id => $player )
        {
            // Retrieve and remove the first color from the default colors array.
            $color = array_shift($default_colors);

            // Retrieve and remove the first team from the player team array.
            $team = array_shift($player_team);
            // Get the shooting number JSON string for the team
            $shootingNumberJson = $shooting_numbers[$team];
            // Create the player's data string for insertion into the database.
            // Be sure to properly escape strings to prevent injection attacks.
            $playerNameEscaped = addslashes($player['player_name']);
            $playerAvatarEscaped = addslashes($player['player_avatar']);
            // player_status array setup
            $player_status = json_encode([]);
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
                10,
                '{$shootingNumberJson}',
                '{$player_status}'
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
        // ANCHOR getAllDatas
        
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
        $opponent_player_id = $this->getNonActivePlayerId();
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_team team, player_power, player_productivity_limit, player_productivity, player_action_limit, player_action FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
        $current_player = $result['players'][$current_player_id];
        $result['current_player'] = $current_player;
        // get the player hand card number
        $result['hand_card_number'][$current_player_id] = count($this->getActivePlayerDeck($current_player_id)->countCardInLocation( 'hand', $current_player_id ));
        // get the opponent hand card number
        $result['hand_card_number'][$opponent_player_id] = $this->getActivePlayerDeck($opponent_player_id)->countCardInLocation( 'hand', $opponent_player_id );
        // Gather all information about current game situation (visible by player $current_player_id).
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

        // check playing_card status
        $sql = "SELECT * FROM playing_card";
        $playing_card = self::getCollectionFromDb( $sql );
        // let playing_card be the key of the player_id
        $result['playing_card'] = [];
        foreach ($playing_card as $card) {
            $result['playing_card'][$card['player_id']] = $card;
        }
        // Get the current game state
        $result['gamestate_name'] = $this->getStateName();
        
        // Cards in the draw deck
        return $result;
    }

    /*
       // ANCHOR getGameProgression
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // compute and return the game progression

        return 0;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        // ANCHOR zombieTurn:
        
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
    // ANCHOR upgradeTableDb
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