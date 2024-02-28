{OVERALL_GAME_HEADER}


<div id="gamefield">
	<div class="playmat_field whiteblock">
        <div class="player_playmat" id="player_playmat_opponent">
                <!-- BEGIN playeronplaymat_opponent -->
            <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_opponent -->
        </div>
        <div class="onTableCards_field">
            <!-- BEGIN discardPile_opponent -->
            <div class="discardPile" id="cardsOnTable_{role}_{id}"></div>
            <!-- END discardPile_opponent -->
        </div>
    </div>
    <div class="playmat_field whiteblock">
        <div class="player_playmat" id="player_playmat_me">
                <!-- BEGIN playeronplaymat_me -->
                <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_me -->
        </div>
        <div class="onTableCards_field">
            <!-- BEGIN discardPile_me -->
            <div class="discardPile" id="discardPile_{role}_{id}"></div>
            <!-- END discardPile_me -->
        </div>
    </div>
</div>

<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand" class="playertablecard"></div>
</div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_cardsOnTable = '<div class="js-cardsontable" id="cardsOnTable_${player_id}_${card_id}" style="background-position:${x}px ${y}px"></div>';

</script>  

{OVERALL_GAME_FOOTER}
