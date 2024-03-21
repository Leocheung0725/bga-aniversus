<?php
trait AniversusPlayerActions {
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in aniversus.action.php)
    */
    public function playFunctionCard( $player_id, $card_id, $card_type ) {
        //ANCHOR - playFUnctionCard
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
        $this->playFunctionCard2Discard($player_id, $card_id);
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
        $sql = "select player_score, player_action, player_productivity, player_power from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        self::notifyAllPlayers( "updatePlayerBoard", "", array(
            'player_id' => $player_id,
            'player_productivity' => $player['player_productivity'],
            'player_action' => $player['player_action'],
            'player_score' => $player['player_score'],
            'player_power' => $player['player_power'],
        ) );
        // update database that what card the active player has played
        $sql = "UPDATE playing_card SET "
        . "card_id = " . intval($card_id) . ", "
        . "card_type = '" . addslashes($card_info['type']) . "', "
        . "card_type_arg = " . intval($card_info['id']) . ", "
        . "card_launch = TRUE, "
        . "card_status = 'validating', "
        . "disabled = FALSE "
        . "WHERE player_id = " . intval($player_id);
        self::DbQuery( $sql );
        // check whether the opponent has counter attack card in hand
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        if (empty($opponent_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' ))) {
            // Go to launch state, the card effect would be done
            $this->gamestate->nextState( "launch" );
        } else {
            // Go to counterAttack state
            $this->gamestate->nextState( "changeActivePlayer_counterattack" );
        }
    }

    public function playPlayerCard($player_id, $card_id, $card_type, $row, $col) {
        // ANCHOR - playPlayerCard
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

    public function throwCards( $player_id, $card_ids ) {
        // ANCHOR - throwCards
        // Ensure that the $card_ids is actually an array
        if (!is_array($card_ids)) {
            throw new BgaUserException("Invalid card IDs.");
        }
        // checking the action
        self::checkAction( 'throwCards' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        foreach ($card_ids as $card_id) {
            $this->playFunctionCard2Discard($player_id, $card_id);
            $card = $player_deck->getCard($card_id);
            self::notifyAllPlayers( "playFunctionCard", clienttranslate( '${player_name} throws ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $this->cards_info[$card['type_arg'] - 1 ]['name'],
                'card_id' => $card_id,
                'card_type' => $card['type_arg'],
            ) );
        }
        $state_name = $this->getStateName();
        switch ($state_name) {
            case 'cardActiveEffect':
                $this->gamestate->nextState( "playerTurn" );
                break;
            default:
                break;
        }
    }


    public function intercept_counterattack() {
        // ANCHOR - intercept_counterattack
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'intercept_counterattack' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $intercept_cards = $player_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' );
        if (empty($intercept_cards)) {
            throw new BgaUserException( self::_("You do not have any intercept card in hand") );
        }
        $intercept_card = array_shift($intercept_cards);
        $this->playFunctionCard2Discard($player_id, $intercept_card['id']);
        self::notifyAllPlayers( "playFunctionCard", clienttranslate( '${player_name} intercepts the card' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_id' => $intercept_card['id'],
            'card_type' => $intercept_card['type_arg'],
        ) );

        // UPDATE the player_card database
        // check whether the opponent has counter attack card in hand
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        if (empty($opponent_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' ))) {
            // Opponent does not have a counter attack card in hand
            // UPDATE the playing_card database to reverse card_launch and set card_status to "validated"
            $sql = "UPDATE `playing_card` SET `card_launch` = NOT `card_launch`, `card_status` = 'validated'
            WHERE `disabled` = FALSE";
            self::DbQuery($sql);
            $this->gamestate->nextState( "changeActivePlayer_counterattack" );
        } else {
            // Opponent has a counter attack card in hand
            // UPDATE the playing_card database just to reverse card_launch
            $sql = "UPDATE `playing_card` SET `card_launch` = NOT `card_launch` WHERE `disabled` = FALSE";
            self::DbQuery($sql);
            // Go to counterAttack state
            $this->gamestate->nextState("changeActivePlayer_counterattack");
        }
    }


    public function pass_counterattack() {
        // ANCHOR - pass_counterattack
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'pass_counterattack' );
        $sql = "UPDATE `playing_card` SET `card_status` = 'validated' WHERE `disabled` = FALSE";
        self::DbQuery($sql);
        $this->gamestate->nextState( "changeActivePlayer_counterattack" );
    }

    public function pass_playerTurn() {
        // ANCHOR - pass_playerTurn
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'pass_playerTurn' );
        $this->gamestate->nextState( "throwCard" );
    }
}