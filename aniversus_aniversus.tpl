{OVERALL_GAME_HEADER}


<div id="gamefield">
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
    <div id="rolldice-area" class="rolldice-area-class"></div>
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
    <div class="myhand_bar">
        <div><h3 class="myhand_text">{MY_HAND}</h3></div>
        <div class="status_bar">
            <div id="cannotdraw_container">
                <div class="status_token status_cannotdraw_token" id="status_cannotdraw_image"></div>
                <div class="counter aniversus_counter" id="status_cannotdraw_counter"></div>
            </div>
            <div id="suspension_container">
                <div class="status_token status_suspension_token" id="status_suspension_image"></div>
                <div class="counter aniversus_counter" id="status_suspension_counter"></div>
            </div>
            <div id="actionup_container">
                <div class="status_token status_actionup_token" id="status_actionup_image"></div>
                <div class="counter aniversus_counter" id="status_actionup_counter"></div>
            </div>
            <div id="energydeduct_container">
                <div class="status_token status_energydeduct_token" id="status_energydeduct_image"></div>
                <div class="counter aniversus_counter" id="status_energydeduct_counter"></div>
            </div>
            <div id="comeback_container">
                <div class="status_token status_comeback_token" id="status_comeback_image"></div>
                <div class="counter aniversus_counter" id="status_comeback_counter"></div>
            </div>
        </div>
    </div>
    <div id="myhand" class="playertablecard"></div>
</div>

<script type="text/javascript">


var jstpl_cardsOnTable = '<div class="js-cardsontable" id="${card_id}" style="background-position:${x}px ${y}px"></div>';

var jstpl_tempCardStock = '<div class="tempStockClass" id="tempStock"><div class="tempCardMessageClass" id="tempCardMessage">${message}</div><div class="tempCardStockClass" id="tempCardStock"></div><div class="btn_div_class" id="btn_div"><button class="tempStockButtonClass" id="tempStockButton">${buttonText}</button></div></div>';

var jstpl_cardToolTip = 
'<div class=\'tooltip-main\'>' +
    '<div class="tooltip-container">' +
        '<div class="tooltip-description">' +
            '<div class=\'no-bullets\'>' +     
                '<div class=\'li-item title\'><h1>${card_name}</h1></div>' +
                '<hr>' +
                '<li class=\'li-item type\'><p>Card Type : ${card_type}</p></li>' +
                '<div class="tooltip_ability_container">' +
                    '<div class="tooltip_ability_left">' +
                        '<div class=\'li-item\'><span class="span_center"><div class=\'cost-inline-image\'></div> Cost</span></div>' +
                        '<div class=\'li-item\'>${card_cost}</div>' +
                        '<div class=\'li-item\'><span class="span_center"><div class=\'power-inline-image\'></div> Power</span></div>' +
                        '<div class=\'li-item\'>${card_power}</div>' +
                    '</div>' +
                    '<div class=\'li-item ortext\'>Or</div>' +
                    '<div class="tooltip_ability_right">' +
                        '<div class=\'li-item\'><span class="span_center"><div class=\'productivity-inline-image\'></div> Productivity</span></div>' +
                        '<div class=\'li-item\'>${card_productivity}</div>' +
                    '</div>' +
                '</div>' +
                '<hr>' +
                '<div class=\'li-item description\'>${card_description}</div>' +
            '</div>' + 
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
        '<div class="playerboard_shootingNum_container">' +
            '<div class="element_token element_shootNum_token" id="playerboard_shootNum_image"></div>' +
            '<div class="playerboard_shootNum" id="player_shootNum_${player_id}"></div>' +
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
