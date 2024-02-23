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
 * material.inc.php
 *
 * Aniversus game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/
$this->gameConstants = array(
  "cat_team_id" => 1,
  "squirrel_team_id" => 2,
);


$this->card_team = array(
  1 => array( "team_name" => clienttranslate("cat"),
              "team_name_tr" => self::_('cat')),
  // 2 => array( "team_name" => clienttranslate("squirrel"),
  //             "team_name_tr" => self::_('squirrel')),
);

// card_function = array(0 => "function", 1 => "player", 2 => "training", 3 => "skill", 4 => "action")
// when the card power is > 0 and productivity is 0, it's a forward player card, if the card power is 0 and productivity is > 0, it's a productivity player card,
// if the card power is > 0 and productivity is > 0, it's a allround player card
$this->card_types = array(
  0 => array( "card_name" => clienttranslate("LEO"),
              "card_name_tr" => self::_('LEO'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 0,
              "card_function" => 1,
              "cost" => 0,
              "card_ability" => array(
                "power" => 2,
                "productivity" => 0,
                "type" => "forward"
              ),),
  1 => array( "card_name" => clienttranslate("ENERGY UP"),
              "card_name_tr" => self::_('ENERGY UP'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 1,
              "card_function" => 0,
              "cost" => 0,),
  2 => array( "card_name" => clienttranslate("RESILIENCE"),
              "card_name_tr" => self::_('RESILIENCE'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 2,
              "card_function" => 2,
              "cost" => 0,),
  3 => array( "card_name" => clienttranslate("ENERGY DRAIN"),
              "card_name_tr" => self::_('ENERGY DRAIN'),
              "card_team" => 1,
              "nbr" => 1,
              "card_id" => 3,
              "card_function" => 0,
              "cost" => 1,),
  4 => array( "card_name" => clienttranslate("COMEBACK"),
              "card_name_tr" => self::_('COMEBACK'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 4,
              "card_function" => 0,
              "cost" => 0,),
  5 => array( "card_name" => clienttranslate("DOUBLE EFFECT"),
              "card_name_tr" => self::_('DOUBLE EFFECT'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 5,
              "card_function" => 0,
              "cost" => 1,),
  6 => array( "card_name" => clienttranslate("GAMBIT PLAY"),
              "card_name_tr" => self::_('GAMBIT PLAY'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 6,
              "card_function" => 0,
              "cost" => 0,),
  7 => array( "card_name" => clienttranslate("DECK DIVE"),
              "card_name_tr" => self::_('DECK DIVE'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 7,
              "card_function" => 0,
              "cost" => 0,),
  8 => array( "card_name" => clienttranslate("ANTHONY"),
              "card_name_tr" => self::_('ANTHONY'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 8,
              "card_function" => 1,
              "cost" => 2,
              "card_ability" => array(
                  "power" => 4,
                  "productivity" => 2,
                  "type" => "allround"
              ),),
  9 => array( "card_name" => clienttranslate("SANDRA"),
              "card_name_tr" => self::_('SANDRA'),
              "card_team" => 1,
              "nbr" => 2,
              "card_id" => 9,
              "card_function" => 1,
              "cost" => 3,
              "card_ability" => array(
                  "power" => 1,
                  "productivity" => 0,
                  "type" => "forward"
              ),),
  10 => array( "card_name" => clienttranslate("TIMO"),
                "card_name_tr" => self::_('TIMO'),
                "card_team" => 1,
                "nbr" => 1,
                "card_id" => 10,
                "card_function" => 1,
                "cost" => 6,
                "card_ability" => array(
                    "power" => 6,
                    "productivity" => 0,
                    "type" => "forward"
                ),),
  11 => array( "card_name" => clienttranslate("INTERCEPT"),
                "card_name_tr" => self::_('INTERCEPT'),
                "card_team" => 1,
                "nbr" => 4,
                "card_id" => 11,
                "card_function" => 0,
                "cost" => 0,),
  12 => array( "card_name" => clienttranslate("LUCIA"),
                "card_name_tr" => self::_('LUCIA'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 12,
                "card_function" => 1,
                "cost" => 2,
                "card_ability" => array(
                "power" => 1,
                "productivity" => 0,
                "type" => "forward"
                ),),
  13 => array( "card_name" => clienttranslate("TACTICAL CHANGE"),
                "card_name_tr" => self::_('TACTICAL CHANGE'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 13,
                "card_function" => 0,
                "cost" => 1,),
  14 => array( "card_name" => clienttranslate("TACTICAL RESHUFFLE"),
                "card_name_tr" => self::_('TACTICAL RESHUFFLE'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 14,
                "card_function" => 0,
                "cost" => 1,),
  15 => array( "card_name" => clienttranslate("RED CARD"),
                "card_name_tr" => self::_('RED CARD'),
                "card_team" => 1,
                "nbr" => 3,
                "card_id" => 15,
                "card_function" => 0,
                "cost" => 0,),
  16 => array( "card_name" => clienttranslate("ALEX"),
                "card_name_tr" => self::_('ALEX'),
                "card_team" => 1,
                "nbr" => 4,
                "card_id" => 16,
                "card_function" => 1,
                "cost" => 1,
                "card_ability" => array(
                  "power" => 1,
                  "productivity" => 1,
                  "type" => "allround"
                ),),
  17 => array( "card_name" => clienttranslate("ROBERTO"),
                "card_name_tr" => self::_('ROBERTO'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 17,
                "card_function" => 1,
                "cost" => 0,
                "card_ability" => array(
                  "power" => 0,
                  "productivity" => 1,
                  "type" => "productivity"
                ),),
                18 => array( "card_name" => clienttranslate("SUSPENSION"),
                "card_name_tr" => self::_('SUSPENSION'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 18,
                "card_function" => 0,
                "cost" => 3,),
  19 => array( "card_name" => clienttranslate("HARRY"),
                "card_name_tr" => self::_('HARRY'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 19,
                "card_function" => 1,
                "cost" => 0,
                "card_ability" => array(
                    "power" => 0,
                    "productivity" => 3,
                    "type" => "productivity"
                ),),
  20 => array( "card_name" => clienttranslate("ACTION UP"),
                "card_name_tr" => self::_('ACTION UP'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 20,
                "card_function" => 0,
                "cost" => 1,),
  21 => array( "card_name" => clienttranslate("EMPOWERMENT"),
                "card_name_tr" => self::_('EMPOWERMENT'),
                "card_team" => 1,
                "nbr" => 4,
                "card_id" => 21,
                "card_function" => 2,
                "cost" => 2,),
  22 => array( "card_name" => clienttranslate("RACHEL"),
                "card_name_tr" => self::_('RACHEL'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 22,
                "card_function" => 1,
                "cost" => 2,
                "card_ability" => array(
                    "power" => 2,
                    "productivity" => 2,
                    "type" => "allround"
                ),),
  23 => array( "card_name" => clienttranslate("DISRUPTION"),
                "card_name_tr" => self::_('DISRUPTION'),
                "card_team" => 1,
                "nbr" => 3,
                "card_id" => 23,
                "card_function" => 0,
                "cost" => 2,),
  24 => array( "card_name" => clienttranslate("PLAYER SWAP"),
                "card_name_tr" => self::_('PLAYER SWAP'),
                "card_team" => 1,
                "nbr" => 1,
                "card_id" => 24,
                "card_function" => 0,
                "cost" => 1,),
  25 => array( "card_name" => clienttranslate("SCOUTING"),
                "card_name_tr" => self::_('SCOUTING'),
                "card_team" => 1,
                "nbr" => 3,
                "card_id" => 25,
                "card_function" => 0,
                "cost" => 1,),
  26 => array( "card_name" => clienttranslate("JAMES"),
                "card_name_tr" => self::_('JAMES'),
                "card_team" => 1,
                "nbr" => 2,
                "card_id" => 26,
                "card_function" => 1,
                "cost" => 4,
                "card_ability" => array(
                    "power" => 3,
                    "productivity" => 0,
                    "type" => "forward"
                ),),
);



