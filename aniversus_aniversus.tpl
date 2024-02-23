{OVERALL_GAME_HEADER}


<div id="gamefield">
	<div class="playmat_field whiteblock">
        <div class="player_playmat" id="player_playmat_opponent">
                <!-- BEGIN playeronplaymat_opponent -->
            <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_opponent -->
        </div>
        <div class="onTableCards_field">
            <!-- BEGIN cardsontable_opponent -->
            <div class="cardsOnTable" id="cardsOnTable_{role}_{id}"></div>
            <!-- END cardsontable_opponent -->
        </div>
    </div>
    <div class="playmat_field whiteblock">
        <div class="player_playmat" id="player_playmat_me">
                <!-- BEGIN playeronplaymat_me -->
                <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_me -->
        </div>
        <div class="onTableCards_field">
            <!-- BEGIN cardsontable_me -->
            <div class="cardsOnTable" id="cardsOnTable_{role}_{id}"></div>
            <!-- END cardsontable_me -->
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

var jstpl_cardsOnPlaymat_opponent = '<div class="cardsOnPlaymat" id="cardsOnPlaymat_${who}_${player_role}_${position}"></div>';
var jstpl_cardsOnPlaymat_my = '<div class="cardsOnPlaymat" id=""cardsOnPlaymat_${who}_${player_role}_${position}""></div>';

</script>  

{OVERALL_GAME_FOOTER}
