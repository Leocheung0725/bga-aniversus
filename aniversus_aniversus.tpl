{OVERALL_GAME_HEADER}


<div id="gamefield">
    <div id="rolldice-area" class="rolldice-area-class"></div>
	<div class="playmat_field whiteblock">
        <div class="player_playmat reverse" id="player_playmat_opponent">
                <!-- BEGIN playeronplaymat_opponent -->
            <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_opponent -->
        </div>
        <div class="discardPile_field" id="discardPile_field_opponent"></div>
    </div>
    <div class="playmat_field whiteblock">
        <div class="player_playmat" id="player_playmat_me">
                <!-- BEGIN playeronplaymat_me -->
                <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_me -->
        </div>
        <div class="discardPile_field" id="discardPile_field_me"></div>
    </div>
</div>
<div id="tempstock-area"></div>
<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand" class="playertablecard"></div>
</div>

<script type="text/javascript">


var jstpl_cardsOnTable = '<div class="js-cardsontable" id="cardsOnTable_${player_id}_${card_id}" style="background-position:${x}px ${y}px"></div>';

var jstpl_tempCardStock = '<div class="tempStockClass" id="tempStock"><div class="tempCardMessageClass" id="tempCardMessage">${message}</div><div id="tempCardStock"></div><button class="tempStockButtonClass" id="tempStockButton">${buttonText}</button></div>';

var jstpl_cardToolTip = 
'<div class=\'tooltip-main\'>' +
'<div>' +
    '<ul class=\'no-bullets\'>' +     
    '<li class=\'li-item title\'><h1>${card_name}</h1></li>' +
    '<hr>' +
    '<li class=\'li-item type\'><p>Card Type : ${card_type}</p></li>' +
    '<li class=\'li-item\'><span>Cost <div class=\'cost-inline-image\'></div> : ${card_cost}</span></li>' +
    '<li class=\'li-item\'><span>Productivity <div class=\'productivity-inline-image\'></div> : ${card_productivity}</span></li>' +
    '<li class=\'li-item\'><span>Power <div class=\'power-inline-image\'></div> : ${card_power}</span></li>' +
    '<hr>' +
    '<li class=\'li-item\'><span>Description :</span></li>' +
    '<li class=\'li-item description\'><p>${card_description}</p></li>' +
    '</ul>' + 
'</div>' +
'</div>';

var jstpl_player_board =
'<div class="playerboard-main">' +
    '<div>' +
        '<ul class="no-bullets">' +     
        '<li class="li-item"><span>Productivity <div class="productivity-inline-image"></div> : <div class="counter" id="player_productivity_${player_id}"></div></span></li>' +
        '<li class="li-item"><span>Power <div class="power-inline-image"></div> : <div class="counter" id="player_power_${player_id}"></div></span></li>' +
        '<li class="li-item"><span>Action: <div class="counter" id="player_action_${player_id}"></div></span></li>' +
        '<li class="li-item"><span>Hand Card(s): <div class="counter" id="player_hand_${player_id}"></div></span></li>' +
        '<li class="li-item"><span>Deck Card(s): <div class="counter" id="player_deck_${player_id}"></div></span></li>' +
        '</ul>' + 
    '</div>' +
'</div>';

var jstpl_content = 
'<div id="${id}">' +
    '${content}' +
'</div>';

var jstpl_rollValue = 
'<span id="rollValue_${player_id}">${rollValue}</span>';

</script>  

{OVERALL_GAME_FOOTER}
