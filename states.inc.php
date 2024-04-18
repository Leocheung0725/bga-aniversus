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
 * states.inc.php
 *
 * Aniversus game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!
// define contants for state ids
if (!defined('stateEndGame')) { // ensure this block is only invoked once, since it is included multiple times
    define("stateNewHand", 2);
    define("stateCardDrawing", 21);
    define("statePlayerTurn", 22);
    define("stateCounterattack", 30);
    define("stateCardEffect", 31);
    define("stateCardActiveEffect", 32);
    define("stateChangeActivePlayer_counterattack", 33);
    define("stateShoot", 23);
    define("stateThrowCard", 24);
    define("statePlayerEndTurn", 25);
    define("stateEndHand", 90);
    define("stateEndGame", 99);
 }


 
$machinestates = array(
    // Please do not modify. -----------------------------------------------------------
    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" =>  stateNewHand )
    ),
    // Please do not modify. -----------------------------------------------------------
    
    // Note: ID=2 => your first state
    // Game setup section
    2 => array(
        "name" => "newHand",
        "description" => clienttranslate('Game setup: dealing cards to players'),
        "type" => "game",
        "action" => "stNewHand",
        "updateGameProgression" => true,
        "transitions" => array( "playerTurn" => statePlayerTurn )
    ),
    // SECTION normal process section
    21 => array(
        "name" => "cardDrawing",
        "description" => clienttranslate('${actplayer} is Drawing cards.'),
        "type" => "game",
        "action" => "stCardDrawing",
        "transitions" => array( "playerTurn" => statePlayerTurn, "playerEndTurn" => statePlayerEndTurn )
    ),

    22 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or shoot or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or shoot or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playFunctionCard", "playPlayerCard", "pass_playerTurn", "shoot_playerTurn" ),
        "transitions" => array( "changeActivePlayer_counterattack" => stateChangeActivePlayer_counterattack , "launch" => stateCardEffect,
        "shoot" => stateShoot, "throwCard" => stateThrowCard )
    ),

    23 => array(
        "name" => "shoot",
        "description" => clienttranslate('Shoot'),
        "type" => "game",
        "action" => "stShoot",
        "transitions" => array( "throwCard" => stateThrowCard, "endHand" => stateEndHand )
    ),

    24 => array(
        "name" => "throwCard",
        "description" => clienttranslate('${actplayer} ${message}'),
        "descriptionmyturn" => clienttranslate('${you} ${message}'),
        "type" => "activeplayer",
        "args" => "argThrowCard",
        "possibleactions" => array( "throwCard_throwCard", "pass_throwCard", "throwCards" ),
        "transitions" => array( "playerEndTurn" => statePlayerEndTurn )
    ),


    25 => array(
        "name" => "playerEndTurn",
        "type" => "game",
        "action" => "stPlayerEndTurn",
        "transitions" => array( "cardDrawing" => stateCardDrawing, "endHand" => stateEndHand )
    ),
    // !SECTION normal process section


    // SECTION playerTurn section 
    30 => array(
        "name" => "counterattack",
        "description" => clienttranslate('${actplayer} must counterattack or pass'),
        "descriptionmyturn" => clienttranslate('${you} must counterattack or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "intercept_counterattack", "pass_counterattack" ),
        "transitions" => array( "changeActivePlayer_counterattack" => stateChangeActivePlayer_counterattack )
    ),

    31 => array(
        "name" => "cardEffect",
        "type" => "game",
        "action" => "stCardEffect",
        "args" => "argCardEffect",
        "transitions" => array( "playerTurn" => statePlayerTurn, "cardActiveEffect" => stateCardActiveEffect, "cardEffect" => stateCardEffect)
    ),

    32 => array(
        "name" => "cardActiveEffect",
        "description" => clienttranslate('${actplayer} ${message}'),
        "descriptionmyturn" => clienttranslate('${you} ${message}'),
        "type" => "activeplayer",
        "action" => "stCardActiveEffect",
        "args" => "argCardActiveEffect",
        "possibleactions" => array( "throwCard_CardActiveEffect", "throwCards", "eightEffect_CardActiveEffect" ),
        "transitions" => array( "playerTurn" => statePlayerTurn, "cardEffect" => stateCardEffect )
    ),
    
    33 => array(
        "name" => "changeActivePlayer_counterattack",
        "description" => clienttranslate(''),
        "type" => "game",
        "action" => "stChangeActivePlayer",
        "transitions" => array( "counterattack" => stateCounterattack, "cardEffect" => stateCardEffect, "playerTurn" => statePlayerTurn )
    ),
    // !SECTION playerTurn section 

    // End hand section
    
    90 => array(
        "name" => "endHand",
        "description" => clienttranslate('End of hand'),
        "type" => "game",
        "action" => "stEndHand",
        "transitions" => array( "endGame" => stateEndGame )
    ),
    














    // Please do not modify. -----------------------------------------------------------
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )
    // Please do not modify. -----------------------------------------------------------

);


/*

1. << transition >> argument specify « paths » between game states, ie how you can jump from one
game state to another (你如何從一個遊戲狀態跳轉到另一個)

for example :
11 => array(
"name" => "nextPlayer",
"type" => "game",
"action" => "stNextPlayer",
"updateGameProgression" => true,
"transitions" => array( "nextTurn" => 10, "cantPlay" => 11, "endGame" => 99 )
),
If the current game state is « nextPlayer », the next game state could be
one of the following : 10 (playerTurn), 11 (nextPlayer, again), or 99 (gameEnd).
The strings used as keys for the transitions argument defines the name of the transitions. The
transition name is used when you are jumping from one game state to another in your PHP
可能係當你係PHP轉換遊戲狀態時，你會用到transition name ->> nextTurn, cantPlay, endGame
E.g. $this->gamestate->nextState( 'nextTurn' ); 之後會跳到下一個遊戲狀態 (10)

2. << Description >> 
10 => array(
"name" => "playerTurn",
"description" => clienttranslate('${actplayer} must play a disc'),
"descriptionmyturn" => clienttranslate('${you} must play a disc'),
"type" => "activeplayer",
"args" => "argPlayerTurn",
"possibleactions" => array( 'playDisc' ),
"transitions" => array( "playDisc" => 11, "zombiePass" => 11 )
),

When you specify a « description » argument in your game state, this description is displayed in
the status bar.
e.g. Souriseduesert must play a disc
When you specify a « descriptionmyturn » argument in your game state, this description takes
over the « description » argument in the status bar when the current player is active.
e.g. You must play a disc

3 Javascript methods are directly linked with gamestate :
« onEnteringState » is called when we jump into a game state.
« onLeavingState » is called when we are leaving a game state.
« onUpdateActionButtons » allows you to add some player action button in the status bar
depending on current game state.



4. « possibleactions » defines what game actions are possible for the active(s) player(s) during
the current game state. Obviously, « possibleactions » argument is specified for activeplayer and
multipleactiveplayer game states.
Once you define an action as « possible », it's very easy to check that a player is authorized to
do some game action, which is essential to ensure your players can't cheat :
From your PHP code :
function playDisc( $x, $y )
{
// Check that this player is active and that this action is possible at this moment
self::checkAction( 'playDisc' );
From your Javacript code :
if( this.checkAction( 'playDisc' ) ) // Check that this action is possible at this moment
«action» defines a PHP method to call each time the game state become the current game
state. With this method, you can do some automatic actions.
A method called with a « action » argument is called « game state reaction method ». By
convention, game state reaction method are prefixed with « st ».

In Reversi example, the stNextPlayer method :
● Active the next player
● Check if there is some free space on the board. If there is not, it jumps to the gameEnd state.
● Check if some player has no more disc on the board (=> instant victory => gameEnd).
● Check that the current player can play, otherwise change active player and loop on this
gamestate (« cantPlay » transition).
● If none of the previous things happened, give extra time to think to active player and go to the
« playerTurn » state (« nextTurn » transition).


5. you can specify a method name as the « args » argument for your game
state. This method must get some piece of information about the game (ex : for Reversi, the
possible moves) and return them. Thus, this data can be transmitted to the clients and used by the clients to display it.
你可以為你的遊戲狀態指定一個方法名稱作為「args」參數。這個方法必須獲取一些關於遊戲的資訊（例如：對於黑白棋，可能的移動）並返回它們。


6. When you specify « updateGameProgression » in a game state, you are telling the BGA
framework that the « game progression » indicator must be updated each time we jump into this
game state.
Consequently, your « getGameProgression » method will be called each time we jump into this
game state.

*/
