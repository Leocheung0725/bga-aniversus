<?php
trait AniversusStateArgs {
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
    // ANCHOR argCardEffect
    function argCardEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );

        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
        );
    }
    // ANCHOR argCardActiveEffect
    function argCardActiveEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT * FROM playing_card WHERE disabled = FALSE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        // throw button: 1
        $button_list = array();
        switch ($playing_card_info['card_type_arg']) {
            case 1:
                $message = "must discard a card from the hand";
                $button_list[] = 1;
                break;
            case 56:
                $message = "must discard 2 cards from the hand";
                $button_list[] = 1;
                break;
            default:
                $card_effect = "You can play a card from your hand to the playmat";
                break;
        }
        $button_list = json_encode($button_list);
        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
            'message' => $message,
            'button_list' => $button_list,
        );
    }
    // ANCHOR argThrowCard
    function argThrowCard()
    {
        // it must return the array
        $player_id = self::getActivePlayerId();
        $player_deck = $this->getActivePlayerDeck($player_id);
        $player_handCardNumber = $player_deck->countCardInLocation('hand', $player_id);
        $throwNumber = $player_handCardNumber - 5;
        if ($throwNumber <= 0) {
            $message = "don't have to throw any card from hand";
        } else {
            $message = "must throw {$throwNumber} card(s) from hand";
        }
        return array(
            "message" => $message,
            "thrownumber" => $throwNumber,  
        );
    }
}