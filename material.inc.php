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
$cards_info_CSVdata = <<<CSV
id,type,cost,productivity,power,function,name,nbr,team,css_position
1,Function,1,0,0,"Draw 3 cards, then discard 1 card from your hand.",Scouting,3,basic,1
2,Function,0,0,0,Play a card without paying its cost. (DOES NOT count as an action),Gambit Play,2,basic,2
3,Function,1,0,0,"Double the effect of a function card. (Play this card first, then the function card)",Double effect,2,basic,3
4,Function,0,0,0,"Dismiss 1 opponent's forward player. (This card can be played during opponent's SHOOTING phase, which DOES NOT count as an action)",Red Card,3,basic,4
5,Function,0,0,0,"Ineffective opponent's function card. (DOES NOT count as an action, and can be played during opponent’s turn)",Intercept,4,basic,5
6,Function,2,0,0,"Choose 1 card, at random, from your opponent's hand and discard it.",Disruption,3,basic,6
7,Function,0,0,0,Gain 2 energy in this round.,energy up,2,basic,7
8,Function,1,0,0,"Look at the top 5 cards from your draw deck, then put them back in any order either on top of or at the bottom of your draw deck.",Tactical Reshuffle,2,basic,8
9,Function,0,0,0,UNPLAYABLE until one of your cards isdismissed or discarded by opponent. Gain 2 energy and draw 2 cards next round. (DOES NOT count as an action),Comeback,2,basic,9
10,Function,1,0,0,Opponent -2 energy next round.,Energy drain,1,basic,10
11,Function,1,0,0,Select 1 player from the field and exchange them with a player from your discard pile.,Player swap,1,basic,11
12,Training,0,0,0,Play Resilience onto a player to protect them from any negative effects from your opponent's cards.,Resilience,2,basic,12
13,Function,1,0,0,Get extra 2 actions in your next round,Action up,2,basic,13
51,Player,2,2,2,,Nadine,2,squirrel,14
52,Player,1,1,1,,Natalie,4,squirrel,15
53,Function,2,0,0,This card can only be played when you have 3 cards or fewer in your hand. Draw 3 cards.,Refill,1,squirrel,16
54,Function,0,0,0,Power + 2 this round,Power Play,1,squirrel,17
55,Function,2,0,0,Your opponent cannot draw cards next round.,Restriction,2,squirrel,18
56,Function ,1,0,0,"Draw 2 cards, then discard 2 cards from all your hand cards.",Substitution,2,squirrel,19
57,Player,3,0,2,"When Jeffrey comes into play, search your discard pile for 3 cards and put them in your hand.",Jeffrey,2,squirrel,20
58,Player,5,0,3,The player in the same position on the opponent's field -2 power. (for as long as Sergio is in play),Sergio,3,squirrel,21
59,Player,0,2,0,The opponent's productivity player (same position) becomes ineffective. (for as long as Antonio is on the field),Antonio,2,squirrel,22
60,Player,2,0,1,"Multiple Marco Bros cards can be played in the same player slot. Marco Bros x1 = 1 power ,Marco Bros x2 = 3 power, Marco Bros x3 = 6 power.",Marco Bros,4,squirrel,23
61,Player,1,0,1,Becomes stronger when working with Aaron.,Paul,2,squirrel,24
62,Player,4,0,4,All Pauls on the field gain +1 power.,Aaron,1,squirrel,25
63,Player,0,2,0,"When Jude is placed, the productivity player in the same position on opponent's side must leave the field to discard pile.",Jude,2,squirrel,26
64,Player,0,2,2,"When Ceci is placed, discard 1 card from your hand.",Ceci,3,squirrel,27
101,Training,2,1,1,"If put it on a productivity player, Productivity +1. If put it on a forward player,  Power +1.(The cost applies wherever it is placed)",Empowerment,4,cat,28
102,Player,2,2,4,Anthony can only be placed if there is a TRAINING CARD on the field already.,Anthony,2,cat,29
103,Player,3,0,1,"For every 2 forward players on the opponent's team, your team gains +1 power.",Sandra,2,cat,30
104,Player,6,0,6,You can only have 2 other players in your forward row when Timo is in play.,Timo,1,cat,31
105,Function,3,0,0,Your opponent skips 1 round.,Suspension,2,cat,32
106,Player,0,0,2,Leo can only be placed if you have 3 or more forward players on the field.,Leo,2,cat,33
107,Player,4,0,3,Cannot be targeted by any FUNCTION cards.,James,2,cat,34
108,Function,1,0,0,Return 1 card from the field to your hand.,Tactical Change,2,cat,35
109,Player,0,3,0,"When Harry is placed, discard 2 cards from your hand.",Harry,2,cat,36
110,Player,0,1,0,Gain an additional 1 productivity for every 2 forward players you have.,Roberto,2,cat,37
111,Player,2,0,1,The player in the same position on the opponent's field -1 power. (for as long as Lucia is in play),Lucia,2,cat,38
112,,0,0,0,"Discard 1 hand card to search for 1 card from your draw deck and put to your hand. Then, shuffle your deck.",Deck Dive,2,cat,39
113,Player,2,2,2,,Rachel,2,cat,40
114,Player,1,1,1,,Alex,4,cat,41
CSV;


$this->gameConstants = array(
  "cat_team_id" => 1,
  "squirrel_team_id" => 2,
);


// $this->card_team = array(
//   1 => array( "team_name" => clienttranslate("cat"),
//               "team_name_tr" => self::_('cat')),
//   // 2 => array( "team_name" => clienttranslate("squirrel"),
//   //             "team_name_tr" => self::_('squirrel')),
// );

// card_function = array(0 => "function", 1 => "player", 2 => "training", 3 => "skill", 4 => "action")
// when the card power is > 0 and productivity is 0, it's a forward player card, if the card power is 0 and productivity is > 0, it's a productivity player card,
// if the card power is > 0 and productivity is > 0, it's a allround player card
require_once('modules/aniversus_utils.php');

$this->cards_info = read_card_infos($cards_info_CSVdata);