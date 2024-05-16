<?php
trait AniversusPlayerActions {
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in aniversus.action.php)
    */
    public function playFunctionCard( $player_id, $card_id, $card_type ) {
        //ANCHOR - playFunctionCard
        // check that this is player's turn and that it is a "possible action" at this game state
        self::checkAction( 'playFunctionCard' );
        // get the active player id
        $ActivePlayer = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        if ($player_id != $ActivePlayer) {
            throw new BgaUserException( self::_("You are not the active player") );
        }
        // get the active player energy and action number
        $sql = "select player_score, player_action, player_productivity, player_team, player_status from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        $player_status = json_decode($player['player_status'], true);
        $card_info = $this->getCardinfoFromCardsInfo($card_type);
        $card_cost = $card_info['cost'];
        $card_team = $card_info['team'];
        $comsumed_action = 1;
        // handle the status 
        foreach ($player_status as $status) {
            switch ($status) {
                case 2:
                    $card_cost = 0;
                    break;
                default:
            }
        }
        // check whether the user have this card in hand
        $card_deck_info = $player_deck->getCard($card_id);
        if ( $card_deck_info['location'] != 'hand' ) {
            throw new BgaUserException( self::_("This card is not in your hand") );
        }
        // check whether the card belongs to the player's team
        if ($player['player_team'] != $card_team && $card_team != "basic") {
            throw new BgaUserException( self::_("This card does not belong to your team") );
        }
        
        // special handling for some cards
        switch ($card_type) {
            case 2:
                $comsumed_action = 0;
                break;
            case 4:
                $sql = "SELECT * FROM playing_card WHERE player_id = $player_id";
                $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
                if ($playing_card_info['card_info'] == 'redcard') {
                    $comsumed_action = 0;
                }
                $opponent_deck = $this->getNonActivePlayerDeck($player_id);
                $opponent_players = $opponent_deck->getCardsInLocation('playmat');
                $opponent_forward_players = array_filter($opponent_players, function($player) {
                    return $player['location_arg'] <= 5;
                });
                if ( count($opponent_forward_players) <= 0 ) {
                    throw new BgaUserException( self::_("Your opponent does not have any forward player in playmat") );
                }
                break;
            case 5:
                // handle the id:5 card (counter attack card)
                throw new BgaUserException( self::_("This card is a counterattack card, it just can be used for reaction of opponent's function card. Please select other cards to play.") );
                break;
            case 6:
                // this card need the opponent have at least one card in hand
                $opponent_deck = $this->getNonActivePlayerDeck($player_id);
                if ($opponent_deck->countCardInLocation('hand') == 0) {
                    throw new BgaUserException( self::_("Your opponent does not have any card in hand") );
                }
                break;
            case 8:
                // need the player have at least 5 card in deck
                if ($player_deck->countCardInLocation('deck') < 5) {
                    throw new BgaUserException( self::_("You do not have enough cards in deck to play this card") );
                }
                break;
            case 9:
                // handle the id: 9 card, it is a unplayable card
                throw new BgaUserException( self::_("This card is unplayable. Please select other cards to play.") );
                break;
            case 11:
                if ($player_deck->countCardInLocation('playmat') == 0) {
                    throw new BgaUserException( self::_("You do not have any player in playmat") );
                }
                break;
            case 12:
                if ($player_deck->countCardInLocation('playmat') == 0) {
                    throw new BgaUserException( self::_("You do not have any player in playmat") );
                }
                break;
            case 53:
                if ($player_deck->countCardInLocation('hand') > 3 ) {
                    throw new BgaUserException( self::_("You can not have more than 3 cards in hand") );
                }
                break;
            case 108:
                if ($player_deck->countCardInLocation('playmat') == 0) {
                    throw new BgaUserException( self::_("You do not have any player in playmat") );
                }
                break;
            default:
                break;
        }
        // check whether the user have enough action and productivity to play this card
        if ($player['player_action'] - $comsumed_action < 0) {
            throw new BgaUserException( self::_("You do not have enough action to play this card") );
        }
        if ($player['player_productivity'] < $card_cost) {
            throw new BgaUserException( self::_("You do not have enough productivity to play this card") );
        }
        // play the card
        $sql = "UPDATE player SET player_action = player_action - $comsumed_action, player_productivity = player_productivity - $card_cost WHERE player_id = $player_id";
        self::DbQuery( $sql );
        // move the card from hand to discard Pile because this is function card
        $this->playCard2Discard($player_id, $card_id);
        // remove card_free
        if (in_array(2, $player_status)) {
            $this->removeStatusFromStatusLst($player_id, 2, false);
        }
        // Notify all players about the card played
        $log_position = self::getLogCardBackgroundPosition($card_type);
        $log_x = $log_position['x'];
        $log_y = $log_position['y'];
        $card_effect = $card_info['function'];
        $card_name = $card_info['name'];
        $player_name = self::getActivePlayerName();
        $time = time();
        $player_color = self::getPlayerColorById($player_id);
        // give extra time
        self::giveExtraTime($player_id);
        // 
        self::notifyAllPlayers( "playFunctionCard", 
        clienttranslate( 
            "<div class='log_withcard_main'>
                <div><b style='color: #${player_color};'>${player_name}</b> plays ${card_name}: ${card_effect}</div>
                <div class='log_deck cardsOnTable_type_${card_type}' id='logcard_{$time}_{$card_type}' style='background-position:{$log_x}px {$log_y}px'></div>
            </div>" 
            ), array(
                'player_id' => $player_id,
                'player_name' => $player_name,
                'card_name' => $card_name,
                'card_id' => $card_id,
                'card_type' => $card_type,
                'card_effect' => $card_effect,
                'time' => $time,
        ) );
        // Refresh the player board by using lastest data (Fetch the data from database again this time) 
        $this->updatePlayerBoard($player_id);
        // update database that what card the active player has played
        $this->checkPlayingCard();
        // playing_card updating
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
        self::checkAction( 'playPlayerCard' );
        $comsumed_action = 1;
        // get the active player id
        $ActivePlayer = self::getActivePlayerId();
        if ($player_id != $ActivePlayer) { 
            throw new BgaUserException( self::_("You are not the active player") );
        }
        $card_info = $this->getCardinfoFromCardsInfo($card_type);
        $card_cost = $card_info['cost'];
        $card_power = $card_info['power'];
        $card_productivity = $card_info['productivity'];
        // get the active player energy and action number
        $sql = "select player_score, player_action, player_productivity, player_team, player_status from player where player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        $player_status = json_decode($player['player_status'], true);
        $player_deck = $this->getActivePlayerDeck($player_id);
        $actplayer_playmat = $player_deck->getCardsInLocation('playmat');
        $card_category = $card_info['type'];
        // handle the status 
        foreach ($player_status as $status) {
            switch ($status) {
                case 2:
                    $card_cost = 0;
                    break;
                case 104:
                    if ( count($player_deck->getCardsOfTypeInLocation('Player', 104, 'playmat', null)) == 0 ) {
                        $this->removeStatusFromStatusLst($player_id, 104);
                        break;
                    }
                    $count_myself_forward_players = 0;
                    foreach ( $actplayer_playmat as $myselfcard ) {
                        if ( $myselfcard['location_arg'] <= 5 && $myselfcard['type'] == "Player" ) {
                            $count_myself_forward_players++;
                        }
                    }
                    if ($count_myself_forward_players > 2 && $card_category == 'Player' && $row == 1) {
                        throw new BgaUserException( self::_("You can only have 2 other players or less in your forward row when Timo is in play") );
                    }
                    break;
                default:
                    break;
            }
        }
        $card_team = $card_info['team'];
        $opponent_deck = $this->getNonActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        // check whether the user have this card in hand
        $card_deck_info = $player_deck->getCard($card_id);
        if ( $card_deck_info['location'] != 'hand' ) { throw new BgaUserException( self::_("This card is not in your hand") ); }
        // some special handling for some cards
        switch ($card_type) {
            case 64:
                if ($player_deck->countCardInLocation('hand') <= 1 ) {
                    throw new BgaUserException( self::_("You do not have enough cards in hand to play this card") );
                }
                break;
            case 101:
                if ( $row == 1 ) {
                    $card_power = 1;
                } else if ( $row == 2 ) {
                    $card_productivity = 1;
                }
                break;
            case 102:
                if (count($player_deck->getCardsOfTypeInLocation('Training', null, 'playmat', null)) < 1 ) {
                    throw new BgaUserException( self::_("You do not have any training cards on the playmat") );
                }
                break;
            case 104:
                $count_myself_forward_players = 0;
                foreach ( $actplayer_playmat as $myselfcard ) {
                    if ( $myselfcard['location_arg'] <= 5 && $myselfcard['type'] == "Player" ) {
                        $count_myself_forward_players++;
                    }
                }
                if ($count_myself_forward_players > 3) {
                    throw new BgaUserException( self::_("You can only have 2 other players or less in your forward row when Timo is in play") );
                }
                break;
            case 106:
                $count_myself_forward_players = 0;
                foreach ( $actplayer_playmat as $myselfcard ) {
                    if ( $myselfcard['location_arg'] <= 5 && $myselfcard['type'] == "Player" ) {
                        $count_myself_forward_players++;
                    }
                }
                if ($count_myself_forward_players < 2) {
                    throw new BgaUserException( self::_("Leo can only be placed if you have 2 or more forward players on the field") );
                }
                break;
            case 110:
                $count_myself_forward_players = 0;
                foreach ( $actplayer_playmat as $myselfcard ) {
                    if ( $myselfcard['location_arg'] <= 5 && $myselfcard['type'] == "Player" ) {
                        $count_myself_forward_players++;
                    }
                }
                $card_productivity += floor($count_myself_forward_players / 2);
                break;
            default:
                break;
        }
        // get the active player energy and action number
        if ($player['player_team'] != $card_team && $card_team != "basic") {
            throw new BgaUserException( self::_("This card does not belong to your team") );
        }
        if ($player['player_action'] - $comsumed_action < 0) {
            throw new BgaUserException( self::_("You do not have enough action to play this card") );
        }

        // check whether is it empowerment card and the position is occupied by other player
        if ($card_category == 'Player') {
            foreach($actplayer_playmat as $card) {
                if ($card['location_arg'] == $db_position) {
                    $occupied_name = $this->getCardinfoFromCardsInfo($card['type_arg'])['name'];
                    if ( $card_type == 60 ) {
                        $brosNumber = count($player_deck->getCardsOfTypeInLocation('Player', 60, 'playmat', $db_position));
                        if ($brosNumber <= 0) {
                            throw new BgaUserException( self::_("You cannot put the Marco bros with other squirrel.") );
                        }
                        if ($brosNumber >= 3) {
                            throw new BgaUserException( self::_("This position only allows maximum 3 Marco bros here.") );
                        }
                    } else {
                        throw new BgaUserException( self::_("This position is occupied by {$occupied_name}, you can not place another player here") );
                    }
                }
            }
        } else if ($card_category == 'Training') {
            $allCardsInThisPosition = $this->find_elements_by_key_value($actplayer_playmat, 'location_arg', $db_position);
            if ( count($allCardsInThisPosition) == 0 ) {
                throw new BgaUserException( self::_("This position haven't any player here, the training card can't not be played.") );
            }
        }
        
        // play the card
        // minus the action and productivity of player in this round
        if ($row == "1") {
            if (($player['player_productivity'] - $card_cost) < 0 ) {
                throw new BgaUserException( self::_("You do not have enough productivity to play this card") );
            }
            $sql = "
            UPDATE player
            SET player_action = player_action - $comsumed_action, 
            player_productivity = player_productivity - $card_cost,
            player_power = player_power + $card_power
            WHERE player_id = $player_id
            ";
            self::DbQuery( $sql );
        } else if ( $row == "2" ) {
            if ($card_category != 'Training') {
                $card_cost = 0;
            }
            if (($player['player_productivity'] - $card_cost) < 0 ) {
                throw new BgaUserException( self::_("You do not have enough productivity to play this card") );
            }
            if (count($opponent_deck->getCardsOfTypeInLocation('Player', 59, 'playmat', $db_position)) > 0) {
                $card_productivity = 0;
            }
            
            $sql = "
            UPDATE player
            SET player_action = player_action - $comsumed_action, 
            player_productivity = GREATEST(0, player_productivity + $card_productivity - $card_cost), 
            player_productivity_limit = player_productivity_limit + $card_productivity
            WHERE player_id = $player_id
            ";
            self::DbQuery( $sql );
        }
        // give extra time
        self::giveExtraTime($player_id);
        // move the card from hand to playmat
        $player_deck->moveCard($card_id, 'playmat', $db_position);
        $allPlayerInThisPosition = $this->find_elements_by_key_value($player_deck->getCardsInLocation('playmat'), 'location_arg', $db_position);
        $card_effect = $card_info['function'];
        $card_name = $card_info['name'];
        $time = time();
        $player_name = self::getActivePlayerName();
        $log_position = self::getLogCardBackgroundPosition($card_type);
        $log_x = $log_position['x'];
        $log_y = $log_position['y'];
        $player_color = self::getPlayerColorById($player_id);
        self::notifyAllPlayers( "playPlayerCard", clienttranslate( 
            "<div class='log_withcard_main'>
                <div><b style='color: #${player_color};'>${player_name}</b> plays ${card_name}: ${card_effect}</div>
                <div class='log_deck cardsOnTable_type_${card_type}' id='logcard_${time}_${card_type}' style='background-position:${log_x}px ${log_y}px'></div>
            </div>"
            ), array(
            'player_id' => $player_id,
            'player_name' => $player_name,
            'card_name' => $card_name,
            'card_id' => $card_id,
            'card_type' => $card_type,
            'row' => $row,
            'col' => $col,
            'allPlayerInThisPosition' => json_encode($this->getAllUniqueTypeArgsInCardLst($allPlayerInThisPosition)),
            'time' => $time,
        ) );
        // remove card_free
        if (in_array(2, $player_status)) {
            $this->removeStatusFromStatusLst($player_id, 2, false);
        }
        // update database that what card the active player has played
        $this->checkPlayingCard();
        // cat skill
        if ( $player['player_team'] == "cat" ) {
            $additional_card = $player_deck->pickCard( 'deck' , $player_id );
            self::notifyPlayer( $player_id, "cardDrawn", clienttranslate( "Your Animal Skill is activated: For every cat player placed on the field, you draw 1 additional card."), 
            array(
                'cards' => array($additional_card) 
            ));
        }

        // end of cat skill
        // playing_card updating
        $sql = "UPDATE playing_card SET "
        . "card_id = " . intval($card_id) . ", "
        . "card_type = '" . addslashes($card_info['type']) . "', "
        . "card_type_arg = " . intval($card_info['id']) . ", "
        . "card_launch = TRUE, "
        . "card_status = 'validating', "
        . "disabled = FALSE "
        . "WHERE player_id = " . intval($player_id);
        self::DbQuery( $sql );
        // calculate special case
        // Refresh the player board by using lastest data (Fetch the data from database again this time) 
        $playerAbilityInfo = $this->updatePlayerAbility($player_id);
        if ($playerAbilityInfo[$player_id]['power'] >= 10) {
            self::notifyPlayer( $player_id, "enableShootBtnPlayerTurn", "You reach the power 10, you can shoot the goal now!", array() );
        }
        // determine whether need to enter the cardEffect state to do the card effect, if not, just go to playerTurn state and let the player to player next card
        switch ($card_type) {
            case 57:
                $this->gamestate->nextState( "launch" );
                break;
            case 63:
                $sql = "UPDATE playing_card SET card_info = '{$db_position}' WHERE player_id = $player_id";
                self::DbQuery( $sql );
                $this->gamestate->nextState( "launch" );
                break;
            case 64:
                $this->gamestate->nextState( "launch" );
                break;
            case 109:
                $this->gamestate->nextState( "launch" );
                break;
            case 104:
                $this->addStatus2StatusLst($player_id, false, 104);
            default:
                // if no special case, update the playing_card disabled to TRUE
                $sql = "UPDATE playing_card SET disabled = TRUE WHERE player_id = $player_id";
                self::DbQuery( $sql );
        }
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
        $player_name = self::getActivePlayerName();
        foreach ($card_ids as $card_id) {
            $this->playCard2Discard($player_id, $card_id, 'hand');
            $card = $player_deck->getCard($card_id);
            $card_type_arg = $card['type_arg'];
            $position = self::getLogCardBackgroundPosition($card_type_arg);
            $x = $position['x'];
            $y = $position['y'];
            $time = time();
            $card_name = self::getCardinfoFromCardsInfo($card_type_arg)['name'];
            $position = self::getLogCardBackgroundPosition($card_type_arg);
            $player_color = self::getPlayerColorById($player_id);
            self::notifyAllPlayers( "playFunctionCard", clienttranslate( 
            "<div class='log_withcard_main'>
                <div><b style='color: #${player_color};'>${player_name}</b> discards ${card_name}</div>
                <div class='log_deck cardsOnTable_type_${card_type_arg}' id='logcard_${time}_${card_type_arg}' style='background-position:${x}px ${y}px'></div>
            </div>" 
            ), array(
                'player_id' => $player_id,
                'player_name' => $player_name,
                'card_name' => $card_name,
                'card_id' => $card_id,
                'card_type' => $card['type_arg'],
                'time' => $time,
            ) );
        }
        $state_name = $this->getStateName();
        switch ($state_name) {
            case 'throwCard':
                $this->gamestate->nextState( "playerEndTurn" );
                break;
            default:
                break;
        }
    }
    public function redcard_redcard() {
        // ANCHOR - redcard_redcard
        self::checkAction( 'redcard_redcard' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $red_cards = $player_deck->getCardsOfTypeInLocation( 'Function' , 4 , 'hand' );
        $redcard = array_shift($red_cards);
        $sql = "UPDATE playing_card SET card_info = 'redcard' WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->playFunctionCard($player_id, $redcard['id'], $redcard['type_arg']);
    }

    public function intercept_counterattack() {
        // ANCHOR - intercept_counterattack
        self::checkAction( 'intercept_counterattack' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $intercept_cards = $player_deck->getCardsOfTypeInLocation( 'Function' , 5 , 'hand' );
        if (empty($intercept_cards)) {
            throw new BgaUserException( self::_("You do not have any intercept card in hand") );
        }
        $intercept_card = array_shift($intercept_cards);
        $this->playCard2Discard($player_id, $intercept_card['id'], 'hand');
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
    public function pass_redcard() {
        // ANCHOR - pass_redcard
        self::checkAction( 'pass_redcard' );
        $this->checkPlayingCard();
        $player_id = self::getActivePlayerId();
        $sql  = "UPDATE playing_card SET card_info = 'passredcard' WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->gamestate->nextState( "changeActivePlayer_redcard" );
    }
    public function pass_counterattack() {
        // ANCHOR - pass_counterattack
        self::checkAction( 'pass_counterattack' );
        $sql = "UPDATE `playing_card` SET `card_status` = 'validated' WHERE `disabled` = FALSE";
        self::DbQuery($sql);
        $this->gamestate->nextState( "changeActivePlayer_counterattack" );
    }
    public function pass_CardActiveEffect() {
        // ANCHOR - pass_CardActiveEffect
        self::checkAction( 'pass_CardActiveEffect' );
        $this->disablePlayingCard();
        $this->checkPlayingCard();
        self::notifyPlayer( self::getActivePlayerId(), "terminateTempStock", "", array() );
        $this->gamestate->nextState( "playerTurn" );
    }
    
    public function pass_playerTurn() {
        // ANCHOR - pass_playerTurn
        self::checkAction( 'pass_playerTurn' );
        // give extra time
        $player_id = self::getActivePlayerId();
        self::giveExtraTime($player_id);
        $this->gamestate->nextState( "throwCard" );
    }

    public function shoot_playerTurn() {
        // ANCHOR - shoot_playerTurn
        self::checkAction( 'shoot_playerTurn' );
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_power, player_productivity FROM player WHERE player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        $player_power = $player['player_power'];
        $player_productivity = $player['player_productivity'];
        if ($player_power < 10) {
            throw new BgaUserException( self::_("You do not have enough power to shoot (Please ensure you have at least 10 power for shooting.") );
        }
        if ($player_productivity < 2) {
            throw new BgaUserException( self::_("You do not have enough energy to shoot (Please ensure you have at least 2 energy for shooting.") );
        }
        // for add shooting infomation to the database
        $sql = "UPDATE playing_card SET card_info = 'preredcard' WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->gamestate->nextState( "changeActivePlayer_redcard" );
    }
    
    public function throwPlayer_playerTurn() {
        // ANCHOR - throwPlayer_playerTurn
        self::checkAction( 'throwPlayer_playerTurn' );
        $player_id = self::getActivePlayerId();
        $sql = "SELECT * FROM player WHERE player_id = $player_id";
        $player = self::getNonEmptyObjectFromDB( $sql );
        if ( $player['player_action'] <= 0 ) {
            throw new BgaUserException( self::_("You do not have enough action to remove a player") );
        }
        $player_deck = $this->getActivePlayerDeck($player_id);
        if (count($player_deck->getCardsInLocation('playmat')) == 0) {
            throw new BgaUserException( self::_("You do not have any player in playmat") );
        }
        $sql = "UPDATE playing_card SET card_id = 402, card_type = 'status', card_type_arg = '402', disabled = FALSE WHERE player_id = $player_id";
        self::DbQuery( $sql );
        self::giveExtraTime($player_id);
        $this->gamestate->nextState( "cardActiveEffect" );
    }
    public function throwCard_throwCard( $player_id, $card_ids ) {
        // ANCHOR - throwCard_throwCard
        self::checkAction( 'throwCard_throwCard' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $player_handCardNumber = $player_deck->countCardInLocation('hand', $player_id);
        $throwNumber = $player_handCardNumber - 5;
        // if card_ids length is not equal to throwNumber, throw an exception
        if (count($card_ids) != $throwNumber) {
            throw new BgaUserException( self::_("Please ensure that you discard exactly {$throwNumber} card(s) from your hand; selecting more or fewer cards than required is not permitted.") );
        }
        $this->throwCards($player_id, $card_ids);
    }

    public function pass_throwCard() {
        // ANCHOR - pass_throwCard
        self::checkAction( 'pass_throwCard' );
        $this->gamestate->nextState( "playerEndTurn" );
    }

    public function throwCard_CardActiveEffect( $player_id, $card_ids ) {
        // ANCHOR - throwCard_CardActiveEffect
        self::checkAction( 'throwCard_CardActiveEffect' );
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $card_effect_info = self::getNonEmptyObjectFromDB( $sql );
        // $card_id = $card_effect_info['card_id'];
        $card_type_arg = $card_effect_info['card_type_arg'];
        $player_deck = $this->getActivePlayerDeck($player_id);
        switch ($card_type_arg) {
            case 1:
                if (count($card_ids) != 1) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 1 card from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $this->checkDoubleCard($player_id);
                break;
            case 8:
                if ( $card_effect_info['card_status'] = "thrown" ) {
                    throw new BgaUserException( self::_("Invalid operation!") );
                }
                break;
            case 11:
                if ( $card_effect_info['card_status'] = "thrown" ) {
                    throw new BgaUserException( self::_("Invalid operation!") );
                }
                break;
            case 56:
                if (count($card_ids) != 2) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 2 cards from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $this->checkDoubleCard($player_id);
                break;
            case 57:
                if ( $card_effect_info['card_status'] = "thrown" ) {
                    throw new BgaUserException( self::_("Invalid operation!") );
                }
                break;
            case 64:
                if (count($card_ids) != 1) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 1 card from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $this->checkDoubleCard($player_id);
                break;
            case 109:
                if (count($card_ids) != 2) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 2 cards from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $this->checkDoubleCard($player_id);
                break;
            case 112:
                if ( $card_effect_info['card_status'] = "thrown" ) {
                    throw new BgaUserException( self::_("You have already thrown a card, don't do it again!") );
                }
                if (count($card_ids) != 1) {
                    throw new BgaUserException( self::_("Please ensure that you discard exactly 1 card from your hand; selecting more or fewer cards than required is not permitted.") );
                }
                $this->throwCards($player_id, $card_ids);
                $allCardsInDrawDeck = $player_deck->getCardsInLocation('deck');
                $allCardsInDrawDeck_json = json_encode($allCardsInDrawDeck);
                $sql = "UPDATE playing_card SET card_info = '{$allCardsInDrawDeck_json}', card_status = 'thrown' WHERE disabled = FALSE";
                self::DbQuery( $sql );
                self::notifyPlayer( $player_id, "showCardsOnTempStock", "", array(
                    'cards' => $allCardsInDrawDeck,
                    'card_type_arg' => 112,
                ) );
                break;
            case 40512:
                if ( $card_effect_info['card_status'] = "thrown" ) {
                    throw new BgaUserException( self::_("You have already thrown a card, don't do it again!") );
                }
                break;
            default:
                $card_effect = "You can play a card from your hand to the playmat";
        }
    }

    public function getCard_CardActiveEffect( $card_ids ) {
        // ANCHOR - getCard_CardActiveEffect
        self::checkAction( 'getCard_CardActiveEffect' );
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $card_effect_info = self::getNonEmptyObjectFromDB( $sql );
        if (count($card_ids) > 2) {
            throw new BgaUserException( self::_("Please ensure that you select below 2 cards from your discard pile; selecting more cards than required is not permitted.") );
        } else if ( count($card_ids) == 0 ) {
            // end the effect
            self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
            $this->checkDoubleCard($player_id);
        }
        $player_id = $card_effect_info['player_id'];
        $player_deck = $this->getActivePlayerDeck($player_id);
        $cards = [];
        foreach ( $card_ids as $card_id ) {
            $card = $player_deck->getCard($card_id);
            if ( $card['location'] != 'discard' ) {
                throw new BgaUserException( self::_("This card is not in your discard Pile") );
            }
            $player_deck->moveCard($card_id, 'hand', $player_id);
            $cards[] = $player_deck->getCard($card_id);
        }
        self::notifyPlayer( $player_id, "cardDrawn", "", array(
            'cards' => $cards,
        ) );
        // end the effect
        self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
        $this->checkDoubleCard($player_id);
    }

    public function pickCardFromDeck2Hand_CardActiveEffect( $card_ids ) {
        // ANCHOR - pickCardFromDeck2Hand_CardActiveEffect
        self::checkAction( 'pickCardFromDeck2Hand_CardActiveEffect' );
        $player_id = $this->getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $card_effect_info = self::getNonEmptyObjectFromDB( $sql );
        if ( $card_effect_info['card_type_arg'] == 40512 ) {
            if (count($card_ids) != 2) {
                throw new BgaUserException( self::_("Please ensure that you select exactly 2 cards from the deck; selecting more or fewer cards than required is not permitted.") );
            }
            for ($i = 0; $i < count($card_ids); $i++) {
                $card = $player_deck->getCard($card_ids[$i]);
                if ( $card['location'] != 'deck' ) {
                    throw new BgaUserException( self::_("This card is not in your deck") );
                }
                $player_deck->moveCard($card['id'], 'hand', $player_id);
                self::notifyPlayer( $player_id, "cardDrawn", "", array(
                    'cards' => array($card),
                ) );
            }
            self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
            $this->endEffect('normal');

        } else if ( $card_effect_info['card_type_arg'] == 112 ) {
            // card id : 112 - draw 2 cards from the deck
            if (count($card_ids) > 2) {
                throw new BgaUserException( self::_("Please ensure that you select exactly 2 card(s) from the deck; selecting more cards than required is not permitted.") );
            }
            for ($i = 0; $i < count($card_ids); $i++) {
                $card = $player_deck->getCard($card_ids[$i]);
                if ( $card['location'] != 'deck' ) {
                    throw new BgaUserException( self::_("This card is not in your deck") );
                }
                $player_deck->moveCard($card['id'], 'hand', $player_id);
                self::notifyPlayer( $player_id, "cardDrawn", "", array(
                    'cards' => array($card),
                ) );
            }
            // end the effect
            self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
            $this->checkDoubleCard($player_id);
        }
    }

    public function eightEffect_CardActiveEffect( $top_items, $bottom_items) {
        // ANCHOR - eightEffect_CardActiveEffect
        self::checkAction( 'eightEffect_CardActiveEffect' );
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        $card_type_arg = $playing_card_info['card_type_arg'];
        if ( $card_type_arg != 8 ) {
            throw new BgaUserException( self::_("This card does not have an effect that can be activated") );
        } else {
            $random_pick_cards = json_decode($playing_card_info['card_info'], true);
            for ($i = 0; $i < count($bottom_items); $i++) {
                // Filter $random_pick_cards to find all items with the matching type_arg
                $matches = array_filter($random_pick_cards, function($card) use ($bottom_items, $i) {
                    return $card['type_arg'] == $bottom_items[$i]['type'];
                });
                
                // If no matching cards, throw an exception
                if (empty($matches)) {
                    throw new BgaUserException(self::_("Invalid bottom cards selected"));
                }
                
                // Randomly pick one of the matching cards
                $keys = array_keys($matches);
                $random_key = $keys[array_rand($keys)];
                $selected_card = $matches[$random_key];
                
                // Perform your operation with $selected_card here
                // $player_deck = $this->getActivePlayerDeck($playing_card_info['player_id']);
                $player_deck = $playing_card_info['card_id'] == 4058 ? $this->getNonActivePlayerDeck($playing_card_info['player_id']) : $this->getActivePlayerDeck($playing_card_info['player_id']);
                $player_deck->insertCardOnExtremePosition($selected_card['id'], 'deck', true);
                // Now remove the selected item from $random_pick_cards
                unset($random_pick_cards[$random_key]);
            }
            foreach ($top_items as $top_item) {
                // Filter $random_pick_cards to find all items with the matching type_arg
                $matches = array_filter($random_pick_cards, function($card) use ($top_item) {
                    return $card['type_arg'] == $top_item['type'];
                });
                
                // If no matching cards, throw an exception
                if (empty($matches)) {
                    throw new BgaUserException(self::_("Invalid top cards selected Error: playeraction Line at 407"));
                }
                
                // Randomly pick one of the matching cards
                $keys = array_keys($matches);
                $random_key = $keys[array_rand($keys)];
                $selected_card = $matches[$random_key];
                
                // Perform your operation with $selected_card here
                // $player_deck = $this->getActivePlayerDeck($playing_card_info['player_id']);
                $player_deck = $playing_card_info['card_id'] == 4058 ? $this->getNonActivePlayerDeck($playing_card_info['player_id']) : $this->getActivePlayerDeck($playing_card_info['player_id']);
                $player_deck->insertCardOnExtremePosition($selected_card['id'], 'deck', true);
                // Now remove the selected item from $random_pick_cards
                unset($random_pick_cards[$random_key]);
            }
            // notify player
            self::notifyPlayer( $playing_card_info['player_id'], "terminateTempStock", "", array(
                'player_id' => $playing_card_info['player_id'],
            ) );
            $player_id = $playing_card_info['player_id'];
            if ( $playing_card_info['card_id'] == 4058 ) {
                $this->endEffect('normal');
            } else {
                $this->checkDoubleCard($player_id);
            }
        }
    }
    public function throwPlayer_CardActiveEffect( $row, $col ) {
        // ANCHOR - throwPlayer_CardActiveEffect
        self::checkAction( 'throwPlayer_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $player_card = $player_deck->getCardsInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            foreach ($player_card as $card) {
                $this->playCard2Discard($player_id, $card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} removes the card ${card_name}' ), array(
                    'player_id' => $player_id,
                    'card_name' => $this->getCardinfoFromCardsInfo($card['type_arg'])['name'],
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            }
        }
        // update the playing_card status
        $this->disablePlayingCard();
        // deduct the action
        $sql = "UPDATE player SET player_action = player_action - 1 WHERE player_id = $player_id";
        self::DbQuery( $sql );
        // checking
        $this->checkPlayingCard();
        self::notifyPlayer( $player_id, "removePlaymatClickAvailable", "", array() );
        $this->gamestate->nextState( "playerTurn" );
    }

    public function swapField_CardActiveEffect( $row, $col ) {
        // ANCHOR - swapField_CardActiveEffect card_id : 11
        self::checkAction( 'swapField_CardActiveEffect' );
        // stage : field
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $player_card = $player_deck->getCardsInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            // player swap -> throw -> unselectall
            self::notifyPlayer($player_id, "removePlaymatClickAvailable", "", array());
            foreach ($player_card as $card) {
                $this->playCard2Discard($player_id, $card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} swaps the player ${card_name}' ), array(
                    'player_id' => $player_id,
                    'card_name' => $this->getCardinfoFromCardsInfo($card['type_arg'])['name'],
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            }
        }
        // update the playing_card status
        $sql = "UPDATE playing_card SET card_info = 'discard' WHERE disabled = FALSE";
        self::DbQuery( $sql );
        // notify player
        // stage : discard pile
        $all_discard_cards = $player_deck->getCardsInLocation('discard');
        $onlyPlayercard = array_filter($all_discard_cards, function($card) {
            return $card['type'] == 'Player';
        });
        
        // update to database
        $onlyPlayercard_json = json_encode($onlyPlayercard);
        $sql = "UPDATE playing_card SET card_info = '{$onlyPlayercard_json}' WHERE player_id = $player_id";
        self::DbQuery( $sql );
        self::notifyPlayer( $player_id, "showCardsOnTempStock", "", array(
            'cards' => $onlyPlayercard,
            'card_type_arg' => 11,
        ) );
    }

    public function redCardAfterShoot_CardActiveEffect( $row, $col ) {
        // ANCHOR - redCardAfterShoot_CardActiveEffect
        self::checkAction( 'redCardAfterShoot_CardActiveEffect' );
        // stage : field
        $player_id = self::getActivePlayerId();
        $opponent_id = self::getNonActivePlayerId();
        $opponent_deck = $this->getActivePlayerDeck($opponent_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $player_card = $opponent_deck->getCardsInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            $training_cards = $opponent_deck->getCardsOfTypeInLocation( 'Training' , null, 'playmat', $db_position);
            if (empty($training_cards)) {
                $card = array_shift($player_card);
                $this->playCard2Discard($opponent_id, $card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} ejects the player' ), array(
                    'player_id' => $opponent_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            } else {
                $training_card = array_shift($training_cards);
                $this->playCard2Discard($opponent_id, $training_card['id'], 'playmat');
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} remove the opponent training card.' ), array(
                    'player_id' => $opponent_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $training_card['id'],
                    'card_type' => $training_card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
                
            }
        }
        // get the playing_card info
        $sql = "SELECT * FROM playing_card WHERE player_id = $player_id";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        $effect_times = intval($playing_card_info['card_info']);
        if ( $effect_times <= 0) {
            throw new BgaUserException( self::_("This card does not have an effect that can be activated") );
        } else if ( $effect_times == 2 ) {
            $sql = "UPDATE playing_card SET card_info = '1' WHERE player_id = $player_id";
            self::DbQuery( $sql );
        } else if ( $effect_times == 1 ) {
            $sql = "UPDATE playing_card SET card_info = '0' WHERE player_id = $player_id";
            self::DbQuery( $sql );
        }
        $this->updatePlayerBoard($player_id);
        $this->gamestate->nextState( "changeActivePlayer_redcard" );
    }

    public function pickPlayerFromPlaymat2Hand_CardActiveEffect( $row, $col ) {
        // ANCHOR - pickPlayerFromPlaymat2Hand_CardActiveEffect card_id : 108
        self::checkAction( 'pickPlayerFromPlaymat2Hand_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $player_card = $player_deck->getCardsInLocation('playmat', $db_position);
        if (empty($player_card)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            foreach ($player_card as $card) {
                // if ( $card['type'] == "Player" )
                // {
                    $player_deck->moveCard($card['id'], 'hand', $player_id);
                    self::notifyAllPlayers( "movePlayerInPlaymat2Hand", clienttranslate( '${player_name} picks the ${card_name} from the field to hand.' ), array(
                        'player_id' => $player_id,
                        'player_name' => self::getActivePlayerName(),
                        'card_id' => $card['id'],
                        'card_name' => self::getCardinfoFromCardsInfo($card['type_arg'])['name'],
                        'card_type' => $card['type_arg'],
                        'row' => $row,
                        'col' => $col,
                    ) );
                // } else if ( $card['type'] == "Training" ) {
                //     $this->playCard2Discard($player_id, $card['id'], 'playmat');
                //     self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} throws the ${card_name} from the field.' ), array(
                //         'player_id' => $player_id,
                //         'player_name' => self::getActivePlayerName(),
                //         'card_id' => $card['id'],
                //         'card_name' => $this->getCardinfoFromCardsInfo($card['type_arg'])['name'],
                //         'card_type' => $card['type_arg'],
                //         'row' => $row,
                //         'col' => $col,
                //     ) );
                
                // }
            }
        }
        self::notifyPlayer( $player_id, "removePlaymatClickAvailable", "", array() );
        $this->checkDoubleCard($player_id);
    }

    public function pickPlayerFromDiscardPile_CardActiveEffect( $selected_player ) {
        // ANCHOR - pickPlayerFromDiscardPile_CardActiveEffect
        self::checkAction( 'pickPlayerFromDiscardPile_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        // check whether the selected player is in the discard pile
        $player_deck = $this->getActivePlayerDeck($player_id);
        $all_discard_cards = $player_deck->getCardsInLocation('discard');
        $selected_player_card = array_filter($all_discard_cards, function($card) use ($selected_player) {
            return $card['id'] == $selected_player['id'];
        });
        if (empty($selected_player_card)) {
            throw new BgaUserException( self::_("The selected player is not in the discard pile") );
        }
        $selected_player_card = array_shift($selected_player_card);
        $player_deck->moveCard($selected_player_card['id'], 'hand', $player_id);
        $selected_player_card_name = self::getCardinfoFromCardsInfo($selected_player_card['type_arg'])['name'];
        self::notifyPlayer( $player_id, "cardDrawn", 'You get the player ${card_name} from your discard pile.', array(
            'cards' => array($selected_player_card),
            'card_name' => $selected_player_card_name,
        ) );
        // end the effect
        self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
        $this->checkDoubleCard($player_id);
    }

    public function pickPlayerFromDrawDeck_CardActiveEffect( $selected_player ) {
        // ANCHOR - pickPlayerFromDrawDeck_CardActiveEffect
        self::checkAction( 'pickPlayerFromDrawDeck_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        // check whether the selected player is in the discard pile
        $player_deck = $this->getActivePlayerDeck($player_id);
        $all_discard_cards = $player_deck->getCardsInLocation('deck');
        $selected_player_card = array_filter($all_discard_cards, function($card) use ($selected_player) {
            return $card['type_arg'] == $selected_player;
        });
        if (empty($selected_player_card)) {
            throw new BgaUserException( self::_("The selected player is not in your draw deck.") );
        }
        $selected_player_card = array_shift($selected_player_card);
        $player_deck->moveCard($selected_player_card['id'], 'hand', $player_id);
        $selected_player_card_name = self::getCardinfoFromCardsInfo($selected_player_card['type_arg'])['name'];
        self::notifyPlayer( $player_id, "cardDrawn", 'You get the player ${card_name} from your draw deck.', array(
            'cards' => array($selected_player_card),
            'card_name' => $selected_player_card_name,
        ) );
        // end the effect
        self::notifyPlayer( $player_id, "terminateTempStock", "", array() );
        $this->checkDoubleCard($player_id);
    }

    public function redCard_CardActiveEffect( $row, $col ) {
        // ANCHOR - redCard_CardActiveEffect
        self::checkAction( 'redCard_CardActiveEffect' );
        $player_id = self::getActivePlayerId();
        $opponent_id = $this->getNonActivePlayerId($player_id);
        $opponent_deck = $this->getActivePlayerDeck($opponent_id);
        $db_position = $this->encodePlayerLocation($row, $col);
        $opponent_position_cards = $opponent_deck->getCardsInLocation('playmat', $db_position);
        if (empty($opponent_position_cards)) {
            throw new BgaUserException( self::_("There is no player in this position") );
        } else {
            if ( count($this->find_elements_by_key_value($opponent_position_cards, "type_arg", 12)) >= 1 ) {
                throw new BgaUserException( self::_("You cannot remove the player who has the Resillence effect.") );
            }
            if (count($this->find_elements_by_key_value($opponent_position_cards, "type_arg", 107)) >= 1) {
                throw new BgaUserException( self::_("You cannot remove player James, who cannot be targeted by any FUNCTION cards.") );
            } 
            foreach ($opponent_position_cards as $card) {
                $this->playCard2Discard($player_id, $card['id'], 'playmat', true);
                self::notifyAllPlayers( "movePlayerInPlaymat2Discard", clienttranslate( '${player_name} play the Red Card to eject the player' ), array(
                    'player_id' => $opponent_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card['id'],
                    'card_type' => $card['type_arg'],
                    'row' => $row,
                    'col' => $col,
                ) );
            }
        }
        $this->updatePlayerAbility($opponent_id);
        // id : 9 handling 
        if ( count($this->find_elements_by_key_value($opponent_deck->getPlayerHand( $opponent_id ), "type_arg", 9)) >= 1 ) {
            $this->addStatus2StatusLst($opponent_id, false, 9);
            $cards9 = $opponent_deck->getCardsOfTypeInLocation( 'Function' , 9 , 'hand' );
            $card9 = array_shift($cards9);
            $this->playCard2Discard($player_id, $card9['id'], 'hand', true);
            self::notifyAllPlayers( "playFunctionCard", clienttranslate( '${player_name} plays the card ${card_name}' ), array(
                'player_id' => $opponent_id,
                'player_name' => self::getPlayerNameById($opponent_id),
                'card_id' => $card9['id'],
                'card_name' => $this->getCardinfoFromCardsInfo($card9['type_arg'])['name'],
                'card_type' => $card9['type_arg'],
            ) );
        }
        // update the playing_card status
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        self::notifyAllPlayers( "removePlaymatClickAvailable", "", array() );
        if ( $playing_card_info['card_info'] == 'redcard' ) {
            $sql = "SELECT * FROM player WHERE player_id = $opponent_id";
            $opponent_playerInfo = self::getNonEmptyObjectFromDB( $sql );
            $this->gamestate->nextState( "changeActivePlayer_redcard" );
        } else {
            $this->checkDoubleCard($player_id);
        }
        
    }
    // ANCHOR skill_playerTurn
    public function skill_playerTurn() {
        self::checkAction( 'skill_playerTurn' );
        // $player_id = self::getActivePlayerId();
        // $sql = "SELECT player_status, player_team, player_id from player WHERE player_id = $player_id";
        // $player = self::getNonEmptyObjectFromDB( $sql );
        // $player_status = json_decode($player['player_status'], true);
        // $player_team = $player['player_team'];
        // if (in_array(405, $player_status) && $player_team == 'cat') {
        //     throw new BgaUserException( self::_("You have already used the skill") );
        // }
        $this->gamestate->nextState( "skill" );
    }
    // SECTION - skill
    // ANCHOR catPowerUp_skill
    public function catPowerUp_skill() {
        self::checkAction( 'catPowerUp_skill' );
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = self::getUniqueValueFromDB( $sql );
        $player_status = json_decode($player_status, true);
        if (in_array(40512, $player_status)) {
            throw new BgaUserException( self::_("You have already used the skill") );
        }
        $powerupValue = 3;
        $sql = "UPDATE player SET player_power =  player_power + $powerupValue WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->addStatus2StatusLst($player_id, False, 40511);
        $this->addStatus2StatusLst($player_id, False, 40512);
        $this->updatePlayerBoard($player_id);
        $player_name = self::getActivePlayerName();
        self::notifyAllPlayers('broadcast', clienttranslate("${player_name} uses the skill Cat Power Up! Power + {$powerupValue}"), array(
            'player_name' => $player_name,
            'message' => clienttranslate("{$player_name}'s Power + {$powerupValue}"),
            'type' => 'info',
        ) );

        self::giveExtraTime($player_id);
        $this->gamestate->nextState( "playerTurn" );
    }
    // ANCHOR catProductivityUp_skill
    public function catProductivityUp_skill() {
        self::checkAction( 'catProductivityUp_skill' );
        $player_id = self::getActivePlayerId();
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = self::getUniqueValueFromDB( $sql );
        $player_status = json_decode($player_status, true);
        if (in_array(40513, $player_status)) {
            throw new BgaUserException( self::_("You have already used the skill") );
        }
        $productivityupValue = 3;
        $sql = "UPDATE player SET player_productivity =  player_productivity + $productivityupValue WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->addStatus2StatusLst($player_id, False, 40513);
        $this->updatePlayerBoard($player_id);
        $player_name = self::getActivePlayerName();
        self::notifyAllPlayers('broadcast', clienttranslate("${player_name} uses the skill Cat Productivity Up! Productivity +{$productivityupValue}"), array(
            'player_name' => $player_name,
            'message' => clienttranslate("{$player_name}'s Productivity + {$productivityupValue}"),
            'type' => 'info',
        ) );

        self::giveExtraTime($player_id);
        $this->gamestate->nextState( "playerTurn" );
    }
    // ANCHOR squirrelLookAt_skill
    public function squirrelLookAt_skill() {
        $player_id = self::getActivePlayerId();
        // Check whether the player has already used the skill this round 
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = self::getUniqueValueFromDB( $sql );
        $player_status = json_decode($player_status, true);
        if (in_array(4058, $player_status)) {
            throw new BgaUserException( self::_("You have already used the skill") );
        }
        // Deduct productivity
        $sql = "SELECT player_productivity FROM player WHERE player_id = $player_id";
        $player_productivity = self::getUniqueValueFromDB( $sql );
        if ($player_productivity < 2) {
            throw new BgaUserException( self::_("You do not have enough energy to use this skill") );
        }
        $sql = "UPDATE player SET player_productivity = player_productivity - 2 WHERE player_id = $player_id";
        self::DbQuery( $sql );
        $this->updatePlayerBoard($player_id);
        // playing_card updating
        $sql = "UPDATE playing_card SET "
        . "card_id = " . intval(4058) . ", "
        . "card_type = '" . addslashes("Function") . "', "
        . "card_type_arg = " . intval(8) . ", "
        . "card_launch = TRUE, "
        . "card_status = 'validated', "
        . "disabled = FALSE "
        . "WHERE player_id = " . intval($player_id);
        self::DbQuery( $sql );
        $this->addStatus2StatusLst($player_id, False, 4058);
        // notify player
        self::notifyAllPlayers('broadcast', clienttranslate('${player_name} uses the skill Squirrel Deck Peek'), array(
            'player_name' => self::getActivePlayerName(),
            'message' => clienttranslate('The squirrel player uses the skill << Deck Peek >> that it can look at the top 4 cards of the opponent\'s draw deck and rearrange them in any order.'),
            'type' => 'info',
        ) );

        self::giveExtraTime($player_id);
        $this->gamestate->nextState( "cardActiveEffect" );
    }
    // ANCHOR squirrelSearch_skill
    public function squirrelSearch_skill() {
        $player_id = self::getActivePlayerId();
        // Check whether the player has already used the skill this round 
        $sql = "SELECT player_status FROM player WHERE player_id = $player_id";
        $player_status = self::getUniqueValueFromDB( $sql );
        $player_status = json_decode($player_status, true);
        if (in_array(405, $player_status)) {
            throw new BgaUserException( self::_("You have already used the skill") );
        }
        // playing_card updating
        $sql = "UPDATE playing_card SET "
        . "card_id = " . intval(40512) . ", "
        . "card_type = '" . addslashes("Skill") . "', "
        . "card_type_arg = " . intval(40512) . ", "
        . "card_launch = TRUE, "
        . "card_status = 'validated', "
        . "disabled = FALSE "
        . "WHERE player_id = " . intval($player_id);
        self::DbQuery( $sql );

        self::giveExtraTime($player_id);
        $this->addStatus2StatusLst($player_id, False, 405);

        $this->gamestate->nextState( "cardActiveEffect" );
    }
    //ANCHOR - back_skill
    public function back_skill() {
        $this->gamestate->nextState( "playerTurn" );
    }
    // !SECTION - end of skill
}