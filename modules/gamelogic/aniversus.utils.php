<?php
//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
    /* In this space, you can put any utility methods useful for your game logic */
//////////////////////////////////////////////////////////////////////////////
trait AniversusUtils {
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

    function endEffect($end_type) {
        if ($end_type == 'normal') {
            $sql = "UPDATE playing_card SET disabled = TRUE WHERE disabled = FALSE AND player_id = $player_id";
            self::DbQuery( $sql );
            $this->gamestate->nextState( "playerTurn" );
        } else if ($end_type == 'activeplayerEffect') {
            $this->gamestate->nextState( "cardActiveEffect" );
        }
    }

    function playFunctionCard2Discard($player_id, $card_id) {
        $player_deck = $this->getActivePlayerDeck($player_id);
        // get discard pile cards list
        $discard_pile_cards = $player_deck->getCardsInLocation('discard');
        // find the next location_arg for the discard pile
        $largest_location_arg = $this->findLargestLocationArg($discard_pile_cards);
        $player_deck->moveCard($card_id, 'discard', $largest_location_arg + 1);
    }

    function getNonActivePlayerId() {
        $active_player_id = self::getActivePlayerId();
        $sql = "SELECT player_id FROM player WHERE player_id != $active_player_id";
        $non_active_player_id = self::getUniqueValueFromDB( $sql );
        return $non_active_player_id;
    }

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

    function addShootingNumbersFromSmallestNumber($shooting_numbers) {
        // Find the smallest number not already in the array
        for ($i = 1; $i <= 12; $i++) {
            if (!in_array($i, $shooting_numbers)) {
                // Add the smallest number to the array
                $shooting_numbers[] = $i;
                break;
            }
        }
        return json_encode($shooting_numbers);
    }

    public function getStateName() {
        $state = $this->gamestate->state();
        return $state['name'];
    }

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

}

