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
// $this->card_team = array(
//   1 => array( "team_name" => clienttranslate("cat"),
//               "team_name_tr" => self::_('cat')),
//   // 2 => array( "team_name" => clienttranslate("squirrel"),
//   //             "team_name_tr" => self::_('squirrel')),
// );

// card_function = array(0 => "function", 1 => "player", 2 => "training", 3 => "skill", 4 => "action")
// when the card power is > 0 and productivity is 0, it's a forward player card, if the card power is 0 and productivity is > 0, it's a productivity player card,
// if the card power is > 0 and productivity is > 0, it's a allround player card


*/
$cards_info_CSVdata = <<<CSV
id,type,cost,productivity,power,function,name,nbr,team,css_position
1,Function,1,0,0,"Draw 3 cards, then discard 1 card from your hand.",Scouting,3,basic,0
2,Function,0,0,0,Play a card without paying its cost. (DOES NOT count as an action),Gambit Play,2,basic,1
3,Function,1,0,0,"Double the effect of a function card. (Play this card first, then the function card)",Double effect,2,basic,2
4,Function,0,0,0,"Dismiss 1 opponent's forward player. (This card can be played during opponent's SHOOTING phase, which DOES NOT count as an action)",Red Card,3,basic,3
5,Function,0,0,0,"Ineffective opponent's function card. (DOES NOT count as an action, and can be played during opponent’s turn)",Intercept,4,basic,4
6,Function,2,0,0,"Choose 1 card, at random, from your opponent's hand and discard it.",Disruption,3,basic,5
7,Function,0,0,0,Gain 2 energy in this round.,energy up,2,basic,6
8,Function,1,0,0,"Look at the top 5 cards from your draw deck, then put them back in any order either on top of or at the bottom of your draw deck.",Tactical Reshuffle,2,basic,7
9,Function,0,0,0,UNPLAYABLE until one of your cards is dismissed or discarded by opponent. Gain 2 energy and draw 2 cards next round. (DOES NOT count as an action),Comeback,2,basic,8
10,Function,1,0,0,Opponent -2 energy next round.,Energy drain,1,basic,9
11,Function,1,0,0,Select 1 player from the field and exchange them with a player from your discard pile.,Player swap,1,basic,10
12,Training,0,0,0,Play Resilience onto a player to protect them from any negative effects from your opponent's cards.,Resilience,2,basic,11
13,Function,1,0,0,Get extra 2 actions in your next round,Action up,2,basic,12
51,Player,2,2,2,,Nadine,2,squirrel,13
52,Player,1,1,1,,Natalie,4,squirrel,14
53,Function,2,0,0,This card can only be played when you have 3 cards or fewer in your hand. Draw 3 cards.,Refill,1,squirrel,15
54,Function,0,0,0,Power + 2 this round,Power Play,1,squirrel,16
55,Function,2,0,0,Your opponent cannot draw cards next round.,Restriction,2,squirrel,17
56,Function,1,0,0,"Draw 2 cards, then discard 2 cards from all your hand cards.",Substitution,2,squirrel,18
57,Player,3,0,2,"When Jeffrey comes into play, search your discard pile for 3 cards and put them in your hand.",Jeffrey,2,squirrel,19
58,Player,5,0,3,The player in the same position on the opponent's field -2 power. (for as long as Sergio is in play),Sergio,3,squirrel,20
59,Player,0,2,0,The opponent's productivity player (same position) becomes ineffective. (for as long as Antonio is on the field),Antonio,2,squirrel,21
60,Player,2,0,1,"Multiple Marco Bros cards can be played in the same player slot. Marco Bros x1 = 1 power ,Marco Bros x2 = 3 power, Marco Bros x3 = 6 power.",Marco Bros,4,squirrel,22
61,Player,1,0,1,Becomes stronger when working with Aaron.,Paul,2,squirrel,23
62,Player,4,0,4,All Pauls on the field gain +1 power.,Aaron,1,squirrel,24
63,Player,0,2,0,"When Jude is placed, the productivity player in the same position on opponent's side must leave the field to discard pile.",Jude,2,squirrel,25
64,Player,0,2,2,"When Ceci is placed, discard 1 card from your hand.",Ceci,3,squirrel,26
101,Training,2,0,0,"If put it on a productivity player, Productivity +1. If put it on a forward player,  Power +1.(The cost applies wherever it is placed)",Empowerment,4,cat,27
102,Player,2,2,4,Anthony can only be placed if there is a TRAINING CARD on the field already.,Anthony,2,cat,28
103,Player,3,0,1,"For every 2 forward players on the opponent's team, your team gains +1 power.",Sandra,2,cat,29
104,Player,6,0,6,You can only have 2 other players in your forward row when Timo is in play.,Timo,1,cat,30
105,Function,3,0,0,Your opponent skips 1 round.,Suspension,2,cat,31
106,Player,0,0,2,Leo can only be placed if you have 3 or more forward players on the field.,Leo,2,cat,32
107,Player,4,0,3,Cannot be targeted by any FUNCTION cards.,James,2,cat,33
108,Function,1,0,0,Return 1 card from the field to your hand.,Tactical Change,2,cat,34
109,Player,0,3,0,"When Harry is placed, discard 2 cards from your hand.",Harry,2,cat,35
110,Player,0,1,0,Gain an additional 1 productivity for every 2 forward players you have.,Roberto,2,cat,36
111,Player,2,0,1,The player in the same position on the opponent's field -1 power. (for as long as Lucia is in play),Lucia,2,cat,37
112,Function,0,0,0,"Discard 1 hand card to search for 1 card from your draw deck and put to your hand. Then, shuffle your deck.",Deck Dive,2,cat,38
113,Player,2,2,2,,Rachel,2,cat,39
114,Player,1,1,1,,Alex,4,cat,40
CSV;


$this->gameConstants = array(
  "cat_team_id" => 1,
  "squirrel_team_id" => 2,
);



require_once("modules/aniversus_utils.php");
$this->cards_info = read_card_infos($cards_info_CSVdata);


$this->id2card_type_arg = [
    1 => 1,
    2 => 1,
    3 => 1,
    4 => 2,
    5 => 2,
    6 => 3,
    7 => 3,
    8 => 4,
    9 => 4,
    10 => 4,
    11 => 5,
    12 => 5,
    13 => 5,
    14 => 5,
    15 => 6,
    16 => 6,
    17 => 6,
    18 => 7,
    19 => 7,
    20 => 8,
    21 => 8,
    22 => 9,
    23 => 9,
    24 => 10,
    25 => 11,
    26 => 12,
    27 => 12,
    28 => 13,
    29 => 13,
    30 => 51,
    31 => 51,
    32 => 52,
    33 => 52,
    34 => 52,
    35 => 52,
    36 => 53,
    37 => 54,
    38 => 55,
    39 => 55,
    40 => 56,
    41 => 56,
    42 => 57,
    43 => 57,
    44 => 58,
    45 => 58,
    46 => 58,
    47 => 59,
    48 => 59,
    49 => 60,
    50 => 60,
    51 => 60,
    52 => 60,
    53 => 61,
    54 => 61,
    55 => 62,
    56 => 63,
    57 => 63,
    58 => 64,
    59 => 64,
    60 => 64,
    61 => 101,
    62 => 101,
    63 => 101,
    64 => 101,
    65 => 102,
    66 => 102,
    67 => 103,
    68 => 103,
    69 => 104,
    70 => 105,
    71 => 105,
    72 => 106,
    73 => 106,
    74 => 107,
    75 => 107,
    76 => 108,
    77 => 108,
    78 => 109,
    79 => 109,
    80 => 110,
    81 => 110,
    82 => 111,
    83 => 111,
    84 => 112,
    85 => 112,
    86 => 113,
    87 => 113,
    88 => 114,
    89 => 114,
    90 => 114,
    91 => 114,
];

$this->card_type_arg2css_position = [
    1 => 0,
    2 => 1,
    3 => 2,
    4 => 3,
    5 => 4,
    6 => 5,
    7 => 6,
    8 => 7,
    9 => 8,
    10 => 9,
    11 => 10,
    12 => 11,
    13 => 12,
    51 => 13,
    52 => 14,
    53 => 15,
    54 => 16,
    55 => 17,
    56 => 18,
    57 => 19,
    58 => 20,
    59 => 21,
    60 => 22,
    61 => 23,
    62 => 24,
    63 => 25,
    64 => 26,
    101 => 27,
    102 => 28,
    103 => 29,
    104 => 30,
    105 => 31,
    106 => 32,
    107 => 33,
    108 => 34,
    109 => 35,
    110 => 36,
    111 => 37,
    112 => 38,
    113 => 39,
    114 => 40,
];

$this->cat_original_shooting_numbers = json_encode([1, 3, 5, 7, 9, 11, 100]);
$this->squirrel_original_shooting_numbers = json_encode([2, 4, 6, 8, 10, 12, 101]);