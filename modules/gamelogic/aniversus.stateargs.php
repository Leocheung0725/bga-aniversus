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

    function argCardEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT card_id, card_type, card_type_arg, player_id, card_status, card_location FROM playing_card WHERE active = TRUE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );

        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
            'card_location' => $playing_card_info['card_location'],

        );
    }

    function argCardActiveEffect()
    {
        // it must return the array
        // fetch playing_card table to get the card id and card type by active player id
        $sql = "SELECT card_id, card_type, card_type_arg, player_id, card_status, card_location FROM playing_card WHERE active = TRUE";
        $playing_card_info = self::getNonEmptyObjectFromDB( $sql );
        switch ($playing_card_info['card_type_arg']) {
            case `1`:
                $message = "must discard a card from the hand";
                break;
            default:
                $card_effect = "You can play a card from your hand to the playmat";
                break;
        }
        return array(
            'card_id' => $playing_card_info['card_id'],
            'card_type' => $playing_card_info['card_type'],
            'card_type_arg' => $playing_card_info['card_type_arg'],
            'player_id' => $playing_card_info['player_id'],
            'card_status' => $playing_card_info['card_status'],
            'card_location' => $playing_card_info['card_location'],
            'message' => $message,
        );
    
    }
}