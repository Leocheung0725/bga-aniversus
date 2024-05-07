{OVERALL_GAME_HEADER}


<div id="gamefield">
    <div id="rolldice-area" class="rolldice-area-class"></div>
	<div class="playmat_field">
        <div class="playmat_counter_container">
            <div class="playmat_counter_single">
                <div class="element_token element_productivity_token" id="playermat_roductivity_image_opponent"></div>
                <div class="counter aniversus_counter" id="playermat_productivity_opponent"></div>
            </div>
            <div class="playmat_counter_single">
                <div class="element_token element_power_token" id="playermat_power_image_opponent"></div>
                <div class="counter aniversus_counter" id="playermat_power_opponent"></div>
            </div>
        </div>
        <div class="player_playmat reverse" id="player_playmat_opponent">
                <!-- BEGIN playeronplaymat_opponent -->
            <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_opponent -->
        </div>
        <div class="discardPile_field" id="discardPile_field_opponent"></div>
    </div>
    <div class="playmat_field">
        <div class="playmat_counter_container">
            <div class="playmat_counter_single">
                <div class="element_token element_power_token" id="playermat_power_image_me"></div>
                <div class="counter aniversus_counter" id="playermat_power_me"></div>
            </div>
            <div class="playmat_counter_single">
                <div class="element_token element_productivity_token" id="playermat_roductivity_image_me"></div>
                <div class="counter aniversus_counter" id="playermat_productivity_me"></div>
            </div>
        </div>
        <div class="player_playmat" id="player_playmat_me">
                <!-- BEGIN playeronplaymat_me -->
                <div class="playerCardOnPlaymat playerOnPlaymat_row_{row} playerOnPlaymat_col_{col}" id="playerOnPlaymat_{role}_{row}_{col}"></div>
                <!-- END playeronplaymat_me -->
        </div>
        <div class="discardPile_field" id="discardPile_field_me"></div>
    </div>
</div>
<div id="tempstock-area"></div>
<div id="myhand_wrap" class="whiteblock myhandblock">
    <h3 class="myhand_text">{MY_HAND}</h3>
    <div id="myhand" class="playertablecard"></div>
</div>

<script type="text/javascript">


var jstpl_cardsOnTable = '<div class="js-cardsontable" id="cardsOnTable_${player_id}_${card_id}" style="background-position:${x}px ${y}px"></div>';

var jstpl_tempCardStock = '<div class="tempStockClass" id="tempStock"><div class="tempCardMessageClass" id="tempCardMessage">${message}</div><div class="tempCardStockClass myhandblock" id="tempCardStock"></div><div class="btn_div_class" id="btn_div"><button class="tempStockButtonClass" id="tempStockButton">${buttonText}</button></div></div>';

var jstpl_cardToolTip = 
'<div class=\'tooltip-main\'>' +
    '<div class="tooltip-container">' +
        '<div class="tooltip-description">' +
            '<ul class=\'no-bullets\'>' +     
            '<li class=\'li-item title\'><h1>${card_name}</h1></li>' +
            '<hr>' +
            '<li class=\'li-item type\'><p>Card Type : ${card_type}</p></li>' +
            '<li class=\'li-item\'><span>Cost <div class=\'cost-inline-image\'></div> : ${card_cost}</span></li>' +
            '<li class=\'li-item\'><span>Productivity <div class=\'productivity-inline-image\'></div> : ${card_productivity}</span></li>' +
            '<li class=\'li-item\'><span>Power <div class=\'power-inline-image\'></div> : ${card_power}</span></li>' +
            '<hr>' +
            // '<li class=\'li-item\'><span>Description :</span></li>' +
            '<li class=\'li-item description\'><p>${card_description}</p></li>' +
            '</ul>' + 
        '</div>' +
        '<div class="tooltip-image" style="background-position:${x}px ${y}px">' +
        '</div>' +
    '</div>' +
'</div>';

var jstpl_player_board =
'<div class="playerboard-main">' +
    '<div class="playerboard_container">' +
        '<div class="playerboard_icon">' +
        '<div><div class="element_token element_productivity_token" id="playerboard_productivity_image"></div><div class="counter aniversus_counter" id="player_productivity_${player_id}"></div></div>' +
        '<div><div class="element_token element_power_token" id="playerboard_power_image"></div><div class="counter aniversus_counter" id="player_power_${player_id}"></div></div>' +
        '<div><div class="element_token element_action_token" id="playerboard_action_image"></div><div class="counter aniversus_counter" id="player_action_${player_id}"></div></div>' +
        '</div>' +
        '<div class="playerboard_icon">' +
        '<div class="secondrow"><div class="element_token element_hand_token" id="playerboard_hand_image"></div><div class="counter aniversus_counter" id="player_hand_${player_id}"></div></div>' +
        '<div class="secondrow"><div class="element_token element_draw_token" id="playerboard_draw_image"></div><div class="counter aniversus_counter" id="player_deck_${player_id}"></div></div>' +
        '</div>' +
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
