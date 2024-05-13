<?php
//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
    /* In this space, you can put any utility methods useful for your game logic */
//////////////////////////////////////////////////////////////////////////////
trait AniversusUtils {
    // SECTION DEBUG function
    function pickCard2Hand($card_type) {
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $typeofCard = $this->getCardinfoFromCardsInfo($card_type)['type'];
        $cards = $player_deck->getCardsOfTypeInLocation($typeofCard, $card_type, 'deck');
        if (empty($cards)) {
            throw new BgaUserException( self::_("There is no this type card in the deck") );
        }
        $card = array_shift($cards);
        $player_deck->moveCard($card['id'], 'hand', $player_id);
        self::notifyPlayer( $player_id, 'cardDrawn', '', array(
            'cards' => array($card),
        ) );
    }
    function pickCardFromDiscard2Hand($card_type, $player_id) {
        $player_deck = $this->getActivePlayerDeck($player_id);
        $typeofCard = $this->getCardinfoFromCardsInfo($card_type)['type'];
        $cards = $player_deck->getCardsOfTypeInLocation($typeofCard, $card_type, 'discard');
        if (empty($cards)) {
            throw new BgaUserException( self::_("There is no this type card in the deck") );
        }
        $card = array_shift($cards);
        $player_deck->moveCard($card['id'], 'hand');
        self::notifyPlayer( $player_id, 'cardDrawn', '', array(
            'cards' => array($card),
        ) );
    }
    function actionup() {
        $player_id = self::getActivePlayerId();
        $sql = "UPDATE player SET player_action = player_action + 1 WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->updatePlayerBoard($player_id);
    }
    function getEnergy($energy) {
        $player_id = self::getActivePlayerId();
        $sql = "UPDATE player SET player_productivity = player_productivity + $energy WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->updatePlayerBoard($player_id);
    }


    // !SECTION DEBUG function


    
    // ANCHOR getActivePlayerDeck
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
    // ANCHOR getNonActivePlayerDeck
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
    // ANCHOR getCardinfoFromCardsInfo
    // $card_id = card_type
    function getCardinfoFromCardsInfo($card_id) {
        return current(array_filter($this->cards_info, function($card) use ($card_id){
            return $card['id'] == $card_id;
        }));
    }

    // ANCHOR find_elements_by_key_value
    function find_elements_by_key_value($array, $searchKey, $searchValue) {
        $results = array();
        foreach ($array as $key => $value) {
            if (isset($value[$searchKey]) && $value[$searchKey] == $searchValue) {
                $results[$key] = $value;
            }
        }
        return $results;
    }

    // ANCHOR encodePlayerLocation
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
    // ANCHOR decodePlayerLocation
    function decodePlayerLocation($location_arg) {
        /*
        To decode location_arg 1
        $position = decodeLocation(1); // returns ['row' => 1, 'column' => 1]
        To decode location_arg 8
        $position = decodeLocation(8); // returns ['row' => 2, 'column' => 3]
        */
        $location_arg = intval($location_arg);
        $num_columns = 5;
        $row = intval(($location_arg - 1) / $num_columns) + 1;
        $column = ($location_arg - 1) % $num_columns + 1;
        return array('row' => $row, 'col' => $column);
    }
    // ANCHOR findLargestLocationArg
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
    // ANCHOR endEffect
    function endEffect($end_type) {
        if ($end_type == 'normal') {
            $sql = "UPDATE playing_card SET disabled = TRUE WHERE disabled = FALSE";
            self::DbQuery( $sql );
            $this->updatePlayerBoard(self::getActivePlayerId());
            $this->gamestate->nextState( "playerTurn" );
        } else if ($end_type == 'activeplayerEffect') {
            $this->gamestate->nextState( "cardActiveEffect" );
        }
    }
    // ANCHOR playCard2Discard
    function playCard2Discard( $player_id, $card_id, $from = 'hand', $opponent = false) {
        $player_deck = !($opponent) ? $this->getActivePlayerDeck($player_id) : $this->getNonActivePlayerDeck($player_id);
        // check whether the card is in the from location
        $card = $player_deck->getCard($card_id);
        if ($card['location'] != $from) {
            throw new BgaUserException( self::_("The card is not in the {$from}") );
        }
        // get discard pile cards list
        $discard_pile_cards = $player_deck->getCardsInLocation('discard');
        // find the next location_arg for the discard pile
        $largest_location_arg = $this->findLargestLocationArg($discard_pile_cards);
        $player_deck->moveCard($card_id, 'discard', $largest_location_arg + 1);
    }
    // ANCHOR getNonActivePlayerId
    function getNonActivePlayerId() {
        $active_player_id = self::getActivePlayerId();
        $sql = "SELECT player_id FROM player WHERE player_id != $active_player_id";
        $non_active_player_id = self::getUniqueValueFromDB( $sql );
        return $non_active_player_id;
    }
    // ANCHOR countPlayerBoard
    function countPlayerBoard($player_id) {
        // get two decks
        $player_deck = $this->getActivePlayerDeck($player_id);
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        // get all information of the cards on the playmat for two players
        $allPlayerCards_me = $player_deck->getCardsInLocation('playmat');
        $allPlayerCards_opponent = $opponent_deck->getCardsInLocation('playmat');
        $total_power = 0;
        $total_productivity_limit = 0;
        foreach ($allPlayerCards_me as $card) {
            $card_info = $this->getCardinfoFromCardsInfo($card_type);
            $position = $this->decodePlayerLocation($card['location_arg']);
            $row = $position['row'];
            $column = $position['col'];
            if ( $row == 1 ) {
                $total_power += $card_info['power'];
            } else if ( $row == 2 ) {
                $total_productivity_limit += $card_info['productivity'];
            }
            switch ($card['type_arg']) {
                case 1:
                    break;
                default:
                    break;
            }
        }
    }
    // ANCHOR addShootingNumbersFromSmallestNumber
    function addShootingNumbersFromSmallestNumber($shooting_numbers) {
        // Find the smallest number not already in the array
        for ($i = 2; $i <= 12; $i++) {
            if (!in_array($i, $shooting_numbers)) {
                // Add the smallest number to the array
                $shooting_numbers[] = $i;
                break;
            }
        }
        return json_encode($shooting_numbers);
    }

    // ANCHOR findDiceRollsForSum
    function findDiceRollsForSum($sum) {
        // Check if the sum is within the valid range for two dice rolls.
        if ($sum < 2 || $sum > 12) {
            throw new InvalidArgumentException("Sum must be between 2 and 12.");
        }
    
        // Start with the smallest possible roll (1) for the first die.
        $firstDie = 1;
        // Calculate what the second die needs to be to reach the sum.
        $secondDie = $sum - $firstDie;
    
        // Adjust the dice rolls until both are within the range [1, 6].
        while ($secondDie > 6) {
            $firstDie++;
            $secondDie--;
        }
    
        return array(
            'first' => $firstDie, 
            'second' => $secondDie
        );
    }
    // ANCHOR getStateName
    public function getStateName() {
        $state = $this->gamestate->state();
        return $state['name'];
    }
    // ANCHOR validateJSonAlphaNum
    public function validateJSonAlphaNum($value, $argName = 'unknown')
    {
        if (is_array($value)) {
            foreach ($value as $key => $v) {
            $this->validateJSonAlphaNum($key, $argName);
            $this->validateJSonAlphaNum($v, $argName);
            }
            return true;
        }
        if (is_int($value)) {
            return true;
        }
        $bValid = preg_match("/^[_0-9a-zA-Z- ]*$/", $value) === 1;
        if (!$bValid) {
            throw new BgaSystemException("Bad value for: $argName", true, true, FEX_bad_input_argument);
        }
        return true;
    }
    // ANCHOR updatePlayerBoard
    public function updatePlayerBoard($player_id)
    {
        // Refresh the player board by using lastest data (Fetch the data from database again this time) 
        $sql = "select player_score, player_action, player_productivity, player_team, player_power, player_status from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        // get the deck cards number 
        $player_deck = $this->getActivePlayerDeck($player_id);
        $player_handCardNumber = $player_deck->countCardInLocation('hand', $player_id);
        $player_deckCardNumber = $player_deck->countCardInLocation('deck');
        $player_status = json_decode($player['player_status'], true);
        $mycannotdraw = self::countStatusOccurrence($player_status, 55);
        $mysuspension = self::countStatusOccurrence($player_status, 105);
        $myactionup = self::countStatusOccurrence($player_status, 13);
        $myenergydeduct = self::countStatusOccurrence($player_status, 10);
        $mycomeback = self::countStatusOccurrence($player_status, 9);
        $opponent_id = $this->getNonActivePlayerId();
        $sql2 = "select player_score, player_action, player_productivity, player_team, player_power, player_status from player where player_id = $opponent_id";
        $opponent = self::getNonEmptyObjectFromDB( $sql2 );
        $opponent_deck = $this->getActivePlayerDeck($opponent_id);
        $opponent_handCardNumber = $opponent_deck->countCardInLocation('hand', $opponent_id);
        $opponent_deckCardNumber = $opponent_deck->countCardInLocation('deck');
        $oplayer_status = json_decode($opponent['player_status'], true);
        $opcannotdraw = self::countStatusOccurrence($oplayer_status, 55);
        $opsuspension = self::countStatusOccurrence($oplayer_status, 105);
        $opactionup = self::countStatusOccurrence($oplayer_status, 13);
        $openergydeduct = self::countStatusOccurrence($oplayer_status, 10);
        $opcomeback = self::countStatusOccurrence($oplayer_status, 9);
        self::notifyAllPlayers( "updatePlayerBoard", "", array(
            'player_id' => $player_id,
            'player_productivity' => $player['player_productivity'],
            'player_action' => $player['player_action'],
            'player_score' => $player['player_score'],
            'player_power' => $player['player_power'],
            'player_handCardNumber' => $player_handCardNumber,
            'player_deckCardNumber' => $player_deckCardNumber,
            'cannotdraw' => $mycannotdraw,
            'suspension' => $opsuspension,
            'actionup' => $myactionup,
            'energydeduct' => $myenergydeduct,
            'comeback' => $mycomeback,
        ) );

        self::notifyAllPlayers( "updatePlayerBoard", "", array(
            'player_id' => $opponent_id,
            'player_productivity' => $opponent['player_productivity'],
            'player_action' => $opponent['player_action'],
            'player_score' => $opponent['player_score'],
            'player_power' => $opponent['player_power'],
            'player_handCardNumber' => $opponent_handCardNumber,
            'player_deckCardNumber' => $opponent_deckCardNumber,
            'cannotdraw' => $opcannotdraw,
            'suspension' => $mysuspension,
            'actionup' => $opactionup,
            'energydeduct' => $openergydeduct,
            'comeback' => $opcomeback,
        ) );
    }
    // ANCHOR getLogCardBackgroundPosition
    function getLogCardBackgroundPosition($card_type) {
        $type2css = $this->card_type_arg2css_position;
        $position = $type2css[$card_type];
        $card_width = 62; 
        $card_height = 87;
        $columns = 10;
        $row = floor($position / $columns);
        $column = $position % $columns;
        $x = $column * $card_width;
        $y = $row * $card_height;
        return array(
            'x' => -$x, 
            'y' => -$y);
    }

    // SECTION updatePlayerAbility
    public function updatePlayerAbility($player_id)
    {
        // get the player team player in playmat information 
        $player_deck = $this->getActivePlayerDeck($player_id);
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        $sql = "SELECT player_id FROM player WHERE player_id != $player_id";
        $opponent_id = self::getUniqueValueFromDB( $sql );
        $player_playmat = $player_deck->getCardsInLocation('playmat');
        $opponent_playmat = $opponent_deck->getCardsInLocation('playmat');
        $player_playmatInfo = [];
        $opponent_playmatInfo = [];
        foreach( $player_playmat as $playercardtemp1 ) {
            if (isset($player_playmatInfo[$playercardtemp1['location_arg']])) {
                $player_playmatInfo[$playercardtemp1['location_arg']]['power'] += $this->getCardinfoFromCardsInfo($playercardtemp1['type_arg'])['power'];
                $player_playmatInfo[$playercardtemp1['location_arg']]['productivity'] += $this->getCardinfoFromCardsInfo($playercardtemp1['type_arg'])['productivity'];
                if ( $playercardtemp1['type_arg'] == 12 ) {
                    $player_playmatInfo[$playercardtemp1['location_arg']]['protected'] = true;
                }
            } else {
                if ($playercardtemp1['type_arg'] == 12) {
                    $player_playmatInfo[$playercardtemp1['location_arg']] = [
                        'power' => $this->getCardinfoFromCardsInfo($playercardtemp1['type_arg'])['power'],
                        'productivity' => $this->getCardinfoFromCardsInfo($playercardtemp1['type_arg'])['productivity'],
                        'active' => true,
                        'protected' => true
                    ];
                } else {
                    $player_playmatInfo[$playercardtemp1['location_arg']] = [
                        'power' => $this->getCardinfoFromCardsInfo($playercardtemp1['type_arg'])['power'],
                        'productivity' => $this->getCardinfoFromCardsInfo($playercardtemp1['type_arg'])['productivity'],
                        'active' => true,
                        'protected' => false
                    ];
                }
            }
        }
        foreach( $opponent_playmat as $playercardtemp2 ) {
            if (isset($opponent_playmatInfo[$playercardtemp2['location_arg']])) {
                $opponent_playmatInfo[$playercardtemp2['location_arg']]['power'] += $this->getCardinfoFromCardsInfo($playercardtemp2['type_arg'])['power'];
                $opponent_playmatInfo[$playercardtemp2['location_arg']]['productivity'] += $this->getCardinfoFromCardsInfo($playercardtemp2['type_arg'])['productivity'];
                if ( $playercardtemp2['type_arg'] == 12 ) {
                    $opponent_playmatInfo[$playercardtemp2['location_arg']]['protected'] = true;
                }
            } else {
                if ($playercardtemp2['type_arg'] == 12) {
                    $opponent_playmatInfo[$playercardtemp2['location_arg']] = [
                        'power' => $this->getCardinfoFromCardsInfo($playercardtemp2['type_arg'])['power'],
                        'productivity' => $this->getCardinfoFromCardsInfo($playercardtemp2['type_arg'])['productivity'],
                        'active' => true,
                        'protected' => true
                    ];
                } else {
                    $opponent_playmatInfo[$playercardtemp2['location_arg']] = [
                        'power' => $this->getCardinfoFromCardsInfo($playercardtemp2['type_arg'])['power'],
                        'productivity' => $this->getCardinfoFromCardsInfo($playercardtemp2['type_arg'])['productivity'],
                        'active' => true,
                        'protected' => false
                    ];
                }
            }
        }
        $this->calculatePlayerAbility($player_playmat, $opponent_playmat, $player_playmatInfo, $opponent_playmatInfo);
        $this->calculatePlayerAbility($opponent_playmat, $player_playmat, $opponent_playmatInfo, $player_playmatInfo);
        $total_mypower = 0;
        $total_myproductivity = 0;
        $total_oppopower = 0;
        $total_oppoproductivity = 0;
        foreach ($player_playmatInfo as $card_position => $card) {
            $position = $this->decodePlayerLocation($card_position);
            if ($card['active'] == false) {
                $this->addIneffectiveCard($position['row'], $position['col'], $player_id);
            } else {
                $this->removeIneffectiveCard($position['row'], $position['col'], $player_id);
            }
            if ($card_position <= 5 && $card['active']) {
                $total_mypower += max(0, $card['power']);
            } else if ($card_position > 5 && $card['active']) {
                $total_myproductivity += max(0, $card['productivity']);
            }
        }
        foreach ($opponent_playmatInfo as $card_position => $card) {
            $position = $this->decodePlayerLocation($card_position);
            if ($card['active'] == false) {
                $this->addIneffectiveCard($position['row'], $position['col'], $opponent_id);
            } else {
                $this->removeIneffectiveCard($position['row'], $position['col'], $opponent_id);
            }
            if ($card_position <= 5 && $card['active']) {
                $total_oppopower += max(0, $card['power']);
            } else if ($card_position > 5 && $card['active']) {
                $total_oppoproductivity += max(0, $card['productivity']);
            }
        }
        // speical card effect
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = json_decode(self::getUniqueValueFromDB( $sql ));
        $powerup = $this->countStatusOccurrence($player_status, 54);
        if ($powerup > 0) {
            $total_mypower += $powerup * 2 ;
        }
        // end of speical card effect
        $sql = "UPDATE player SET player_power = $total_mypower, player_productivity_limit = $total_myproductivity WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $sql = "UPDATE player SET player_power = $total_oppopower, player_productivity_limit = $total_oppoproductivity WHERE player_id != $player_id";
        self::DbQuery( $sql );
        $this->updatePlayerBoard($player_id);
        return array(
            strval($player_id) => array(
                'power' => $total_mypower,
                'productivity' => $total_myproductivity,
                'playmatInfo' => $player_playmatInfo
            ),
            strval($opponent_id) => array(
                'power' => $total_oppopower,
                'productivity' => $total_oppoproductivity,
                'playmatInfo' => $opponent_playmatInfo
            )
            );
    }
    // ANCHOR calculatePlayerAbility
    public function calculatePlayerAbility($player_playmat, $opponent_playmat, &$player_playmatInfo, &$opponent_playmatInfo) {
        $marco_bros = 0;
        foreach ($player_playmat as $playercard) {
            $cardInfoInMaterial = $this->getCardinfoFromCardsInfo($playercard['type_arg']);
            $playercard_position = $playercard['location_arg'];
            switch ($playercard['type_arg']) {
                case 58: // The player in the same position on the opponent's field -2 power. (for as long as Sergio is in play)
                    $opponent_thiscard_position = $opponent_playmatInfo[$playercard_position] ?? null;
                    if ($opponent_thiscard_position != null && 
                    !($opponent_playmatInfo[$playercard_position]['protected']) ) { 
                        $opponent_playmatInfo[$playercard_position]['power'] -= 2;
                    }
                    break;
                case 59: // *** The opponent's productivity player (same position) becomes ineffective. (for as long as Antonio is on the field)
                    $opponent_thiscard_position = $opponent_playmatInfo[$playercard_position] ?? null;
                    if ($opponent_thiscard_position != null && 
                    !($opponent_playmatInfo[$playercard_position]['protected']) ) { 
                        $opponent_playmatInfo[$playercard_position]['active'] = false;
                    }
                    break;
                case 60: // 3 squirrels, one squirrel = 1 power, two = 3 power and three = 6 power
                    $marco_bros++;
                    if ($marco_bros == 3) {
                        $player_playmatInfo[$playercard_position]['power'] += 3;
                    } else if ($marco_bros == 2) {
                        $player_playmatInfo[$playercard_position]['power'] += 1;
                    }
                    break;
                case 61: 
                    if (count($this->find_elements_by_key_value($player_playmat, 'type_arg', 62)) >= 1) {
                            $player_playmatInfo[$playercard_position]['power'] += 1;
                    }
                    break;
                case 101:
                    if ( $playercard['location_arg'] <= 5 ) {
                        $player_playmatInfo[$playercard_position]['power'] += 1;
                    } else {
                        $player_playmatInfo[$playercard_position]['productivity'] += 1;
                    }
                    break;
                case 103:
                    $count_opponent_forward_players = 0;
                    foreach ($opponent_playmatInfo as $position_index => $opponentcard) {
                        if ( intval($position_index) <= 5 ) { $count_opponent_forward_players++; }
                    }
                    $player_playmatInfo[$playercard_position]['power'] += floor($count_opponent_forward_players / 2);
                    break;
                case 110:
                    $count_forward_players = 0;
                    foreach ($player_playmatInfo as $position_index => $playercard) {
                        if ( intval($position_index) <= 5 ) { $count_forward_players++; }
                    }
                    $player_playmatInfo[$playercard_position]['productivity'] += floor($count_forward_players / 2);
                    break;
                case 111: // The player in the same position on the opponent's field -1 power. (for as long as Lucia is in play)
                    $opponent_thiscard_position = $opponent_playmatInfo[$playercard_position] ?? null;
                    if ($opponent_thiscard_position != null && 
                    !($opponent_playmatInfo[$playercard_position]['protected']) ) {
                        $opponent_playmatInfo[$playercard_position]['power'] -= 1;
                    }
                    break;
                default:
                    break;
            }
        }
    }
    // !SECTION updatePlayerAbility
    // ANCHOR addStatus2StatusLst
    public function addStatus2StatusLst($player_id, $IsOpponent, $status) {
        if ($IsOpponent) {
            $sql = "SELECT player_status FROM player WHERE player_id != $player_id";
            $player_status = json_decode(self::getUniqueValueFromDB( $sql ), true);
            $player_status[] = $status;
            $sql = "UPDATE player SET player_status =  '".json_encode($player_status)."' WHERE player_id != $player_id";
            self::DbQuery( $sql );
        } else {
            $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
            $player_status = json_decode(self::getUniqueValueFromDB( $sql ), true);
            $player_status[] = $status;
            $sql = "UPDATE player SET player_status =  '".json_encode($player_status)."' WHERE player_id = $player_id";
            self::DbQuery( $sql );
        }
    }

    // ANCHOR removeStatusFromStatusLst
    public function removeStatusFromStatusLst($player_id, $status, $all = true) {
        
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = json_decode(self::getUniqueValueFromDB( $sql ));
        if ($all) {
            $player_status = array_diff($player_status, array($status));
            // Re-index the array to ensure a sequential array
            $player_status = array_values($player_status);
        } else {
            // Find the key of the first occurrence of $status and remove it
            $key = array_search($status, $player_status);
            if ($key !== false) {
                unset($player_status[$key]);
            }
            // Re-index the array since unset() might leave a hole in the numeric array keys
            $player_status = array_values($player_status);
        }
        $sql = "UPDATE player SET player_status =  '".json_encode($player_status)."' WHERE player_id = $player_id";
        self::DbQuery( $sql );
    }
    // ANCHOR countStatusOccurrence
    function countStatusOccurrence($array, $value) {
        $counts = array_count_values($array);
        return isset($counts[$value]) ? $counts[$value] : 0;
    }
    
    // ANCHOR disablePlayingCard
    public function disablePlayingCard() {
        $sql = "UPDATE playing_card SET disabled = TRUE WHERE disabled = FALSE";
        self::DbQuery( $sql );
    }

    // ANCHOR checkDoubleCard
    public function checkDoubleCard($player_id) {
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = json_decode(self::getUniqueValueFromDB( $sql ));
        if (in_array(3, $player_status)) {
            $this->removeStatusFromStatusLst($player_id, 3, false);
            $player_id = self::getActivePlayerId();
            $sql = "UPDATE playing_card SET disabled = FALSE WHERE player_id = $player_id";
            $this->gamestate->nextState( "cardEffect" );
        } else {
            $this->endEffect('normal');
        }
    }
    // ANCHOR checkPlayingCard
    public function checkPlayingCard() {
        // check whether the playing card is disabled, if not, disable it
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getObjectFromDB( $sql );
        if ($playing_card_info != null) {
            $this->disablePlayingCard();
            $playing_card_info_type_arg = $playing_card_info['card_type_arg'];
            throw new BgaUserException( self::_("The player card type arg: ${playing_card_info_type_arg} not yet disabled !!!!!! ") );
        }

    }

    // ANCHOR getAllUniqueTypeArgsInCardLst
    public function getAllUniqueTypeArgsInCardLst(array $cards) {
        $typeArgs = array();
        foreach ($cards as $key => $card) {
            // Check if the type_arg of the current card matches the specific type_arg we're looking for
            if (isset($card['type_arg'])) {
                // Add the type_arg to the result array if it's not already in there
                if (!in_array($card['type_arg'], $typeArgs)) {
                    $typeArgs[] = $card['type_arg'];
                }
            }
        }
        return $typeArgs;
    }
    // ANCHOR groupAllSamePositionPlayerFromCardLst
    public function groupAllSamePositionPlayerFromCardLst(array $cards) {
        $outputLst = array();
        foreach ($cards as $key => $value) {
            if (!isset($outputLst[$value['location_arg']])) {
                $outputLst[$value['location_arg']] = array();
            }
            $outputLst[$value['location_arg']][] = $value;
        }
        // Sorting each group with usort
        foreach ($outputLst as &$group) {
            usort($group, function ($a, $b) {
                $typeOrder = array('Player' => 1, 'Training' => 2, 'Function' => 3);
                return $typeOrder[$a['type']] <=> $typeOrder[$b['type']] ?: $a['id'] - $b['id'];
            });
        }
        return $outputLst;
    }

    // ANCHOR noti_unselectAll
    public function noti_unselectAll($player_id) {
        self::notifyPlayer( $player_id, 'unselectAll', '', array() );
    }

    // ANCHOR addIneffectiveCard
    public function addIneffectiveCard($row, $col, $player_id) {
        self::notifyAllPlayers( "addIneffectiveCard", "", array(
            'row' => $row,
            'col' => $col,
            'player_id' => $player_id,
        ) );
        
    }
    // ANCHOR removeIneffectiveCard
    public function removeIneffectiveCard($row, $col, $player_id) {
        self::notifyAllPlayers( "removeIneffectiveCard", "", array(
            'row' => $row,
            'col' => $col,
            'player_id' => $player_id,
        ) );
    }

    // ANCHOR endGameAfterEmptyDeck
    public function endGameAfterEmptyDeck() {
        $sql = "SELECT player_id, player_name, player_score FROM player";
        $players = self::getCollectionFromDb( $sql );
        // 1. if one of the player's deck is empty, the player with the highest score wins
        // 2. if two players' score are the same, the player who plays the last card lost
        $player_id = self::getActivePlayerId();
        $active_player_score = $players[$player_id]['player_score'];
        $non_active_player_id = $this->getNonActivePlayerId();
        $non_active_player_score = $players[$non_active_player_id]['player_score'];
        // $active_player_deck = $this->getActivePlayerDeck($player_id);
        // $non_active_player_deck = $this->getNonActivePlayerDeck($player_id);
        // $active_player_deckCardNumber = $active_player_deck->countCardInLocation('deck');
        // $non_active_player_deckCardNumber = $non_active_player_deck->countCardInLocation('deck');
        self::notifyAllPlayers( "broadcast", clienttranslate( 'The game has ended.' ), array(
            'message' => clienttranslate( 'The game has ended.' ),
            'type' => 'info'
        ) );
        if ($active_player_score == $non_active_player_score) {
            // 2. if two players' score are the same, the player who plays the last card lost
                $sql = "UPDATE player SET player_score = player_score + 1 WHERE player_id = $non_active_player_id";
                self::DbQuery( $sql );
                $this->updatePlayerBoard($player_id);
                $this->gamestate->nextState( "endGame" );
        } else {
            $this->gamestate->nextState( "endGame" );
        }
    }
}

