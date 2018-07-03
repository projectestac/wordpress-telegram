<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://t.me/WPTelegram
 * @since      1.0.0
 *
 * @package    WPTelegram
 * @subpackage WPTelegram/admin/partials
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can('manage_options') ) die;
if ( ! is_admin() ) die;
?>
<script type="text/javascript">
$ = jQuery;
var baseURL = 'https://api.telegram.org/bot';
function sendAjaxRequest( bot_token, endpoint, data, callback, section ){

    // use proxy
    var script_url = $('input[name="wptelegram_proxy[script_url]"]').val();
    var newData = {}, url;
    if (script_url) {
        url = script_url;
        newData.bot_token = bot_token;
        newData.method = endpoint;
        newData.args = JSON.stringify( data );
    }else{
        url = baseURL + bot_token + '/' + endpoint;
        newData = data;
    }

     $.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        crossDomain:true,
        data: newData,
        complete: function( jqXHR ){
          window[callback]( jqXHR, data.chat_id, section );
        }
    });
}
function validateToken(section) {
    var bot_token = $('input[name="wptelegram_'+section+'[bot_token]"]').val();
    var regex = new RegExp(/^\d{9}:[\w-]{35}$/);

    if ( regex.test( bot_token ) || '' == bot_token ) {
        $('#wptelegram-'+section+'-token-err').addClass("hidden");
        return true;
    }
    else{
        $('#wptelegram-'+section+'-test').addClass("hidden");
        $('#wptelegram-'+section+'-token-err').removeClass("hidden");
        $('#wptelegram-'+section+'-chat-list').text('');
        $('#wptelegram-'+section+'-mem-count').addClass("hidden");
        return false;
    }
}
function getMe(section) {
    var bot_token = $('input[name="wptelegram_'+section+'[bot_token]"]').val();
    if( '' != bot_token ) {
        if ( 'function' === typeof $('#checkbot').prop ){
            $('#checkbot').prop('disabled', true);
            $('#checkbot').text('<?php echo __("Please wait...", "wptelegram") ?> ');
        }
        sendAjaxRequest( bot_token, 'getMe', {}, 'fillBotInfo', section );
        if ( 'function' === typeof $('#checkbot').prop ){
            $('#checkbot').prop('disabled', false);
            $('#checkbot').text('<?php echo __("Test Token", "wptelegram") ?>');
        }
    }
    else {
        alert(' <?php  echo __("Bot Token is empty", "wptelegram") ?>') 
    }
}
function fillBotInfo( jqXHR,chat_id, section ) {
    $('#wptelegram-'+section+'-token-err').addClass("hidden");
    $('#wptelegram-'+section+'-test').removeClass("hidden");

    if ( undefined == jqXHR  || '' == jqXHR.responseText ) {
        $('#bot-info').text('');
        $('#bot-info').append('<span style="color:#f10e0e;"><?php  echo __("Error: Could not connect", "wptelegram"); ?></span>');
    }
    else if ( true == JSON.parse( jqXHR.responseText ).ok ){
        var result = JSON.parse( jqXHR.responseText ).result;
        
        $('#bot-info').text( result.first_name + ' ' + ( undefined == result.last_name ? ' ' :  result.last_name ) + '(@' + result.username + ')' );
    }
    else{
        $('#bot-info').text('error: '+ jqXHR.status + ' (' + jqXHR.statusText + ')');
    }
}
function getChatInfo(section) {
    if ( ! validateToken(section) ) {
        return;
    }
    var bot_token = $('input[name="wptelegram_'+section+'[bot_token]"]').val();
    var chat_ids = $('input[name="wptelegram_'+section+'[chat_ids]"]').val();
    if( '' != bot_token && '' != chat_ids ) {
        chat_ids = chat_ids.replace(' ','').split(',');
        $('#wptelegram-'+section+'-chat-list').text('');
        for ( var i = 0; i < chat_ids.length; i++ ) {
            if ( '' != chat_ids[i] ) {
                data = {
                    chat_id: chat_ids[i]
                };
                sendAjaxRequest( bot_token, 'getChatMembersCount', data,'fillList', section );
            }
        }
    }
}
function fillList( jqXHR, chat_id, section ){
    if ( undefined == jqXHR || '' == jqXHR.responseText ) {
        return;
    }
    $("#wptelegram-"+section+"-mem-count").removeClass("hidden");
    
    if ( true == JSON.parse( jqXHR.responseText ).ok ){
        var result = JSON.parse( jqXHR.responseText ).result;
        
        var li = '<li class="wptelegram-'+section+'-temp-items">' + chat_id + ': <b style="color:#bb0f3b;">' + result + '</b></li>';
    }
    else{
        var li = '<li class="wptelegram-'+section+'-temp-items">' + chat_id + ': <b style="color:#f10e0e;">error ' + jqXHR.status + ' (' + JSON.parse( jqXHR.responseText ).description + ')' + '</b></li>';
    }
    $('#wptelegram-'+section+'-chat-list').append(li);
}
function sendMessage(section) {
    var bot_token = $('input[name="wptelegram_telegram[bot_token]"]').val();
    var chat_ids = $('input[name="wptelegram_'+section+'[chat_ids]"]').val();
    if (!validateToken('telegram')) {
        alert(' <?php  echo __("Bot Token is invalid", "wptelegram"); ?>');
    }
    else if( '' != bot_token && '' != chat_ids ) {
        chat_ids = chat_ids.replace(' ','').split(',');
        var text = prompt('<?php echo __("A message will be sent to the chat_ids. You can modify the text below", "wptelegram"); ?>','<?php echo __("This is a test message", "wptelegram"); ?>');

        if ( null != text ) {
            var btn = '#wptelegram-chat-ids-btn';
            if ( 'function' === typeof $(btn).prop ){
                $(btn).prop('disabled', true);
                $(btn).text('<?php echo __("Please wait...", "wptelegram"); ?> ');
            }
            $('.wptelegram-temp-rows').remove();
            for ( var i = 0; i < chat_ids.length; i++ ) {
                if ( '' != chat_ids[i] ) {
                    data = {
                        chat_id: chat_ids[i],
                        text: text
                    };
                    sendAjaxRequest( bot_token, 'sendMessage', data,'fillTable', section );
                }
            }
            if ( 'function' === typeof $(btn).prop ){
                $(btn).prop('disabled', false);
                $(btn).text('<?php echo __("Send Test", "wptelegram"); ?>');
            }
        }
    }
    else {
        alert('<?php  echo __("Bot Token or chat_ids is empty", "wptelegram"); ?>');
    }
}
function fillTable( jqXHR, chat_id, section ){
    
    $("#wptelegram-"+section+"-chat-table").removeClass("hidden");

    var col1 = '<td>' + chat_id + '</td>';

    if ( undefined == jqXHR || '' == jqXHR.responseText ) {
        col2 = '<td colspan="3"><span style="color:#f10e0e;"><?php  echo __("Error: Could not connect", "wptelegram"); ?></span></td>';
        var col3 = col4 = '';
    }
    else if ( true == JSON.parse( jqXHR.responseText ).ok ){
        var result = JSON.parse(jqXHR.responseText).result;
        if ( undefined == result.chat.title ) {
            title = result.chat.first_name + ' ' + ( undefined == result.chat.last_name ? '' :  result.chat.last_name);
        }
        else{
            title = result.chat.title;
        }
        var col2 = '<td>' + title + '</td>';
        var col3 = '<td>' + result.chat.type + '</td>';
        var col4 = '<td><?php  echo __("Success", "wptelegram"); ?></td>';
    }
    else{
        var col2 = '<td>' + JSON.parse( jqXHR.responseText ).description + '</td>';
        var col3 = '<td>error: ' + jqXHR.status + '</td>';
        var col4 = '<td><?php  echo __("Failure", "wptelegram"); ?></td>';
    }
    var tr = '<tr class="wptelegram-'+section+'-temp-rows">'
            + col1
            + col2
            + col3
            + col4
            + '</tr>';
    $('#wptelegram-'+section+'-chat-table tbody').append(tr);
}
function validate_attach_image(option, value) {
    $('#wptg-attach-'+option).addClass('notice notice-'+value);
}

function wptelegramSelect(){
  $("select").each(function (){
    if(! $(this).hasClass('no-fancy'))
      $(this).select2();
  });
}
(function ($) {
    'use strict';
    $(document).ready(function() {
        wptelegramSelect();
        if (! $('input[name="wptelegram_telegram[bot_token]"]').length ) {
            return;
        }
        var from_terms = $('select[name="wptelegram_wordpress[from_terms][]"]');
        var terms = $('select[name="wptelegram_wordpress[terms][]"]');
        var from_authors = $('select[name="wptelegram_wordpress[from_authors][]"]');
        var authors = $('select[name="wptelegram_wordpress[authors][]"]');
        var f_image = $('input[type="checkbox"][name="wptelegram_message[send_featured_image]"]')[0];
        var parse_mode = $('input[type="radio"][name="wptelegram_message[parse_mode]"]');
        var image_position = $('input[type="radio"][name="wptelegram_message[image_position]"]');
        var attach_image = $('input[type="checkbox"][name="wptelegram_message[attach_image]"]');
        var image_style = $('input[type="radio"][name="wptelegram_message[image_style]"]');
        var caption_source = $('input[type="radio"][name="wptelegram_message[caption_source]"]');
        if ( 'all' == from_terms.find("option:selected").val() ) {
            terms.closest( 'tr' ).hide();
        }
        if ( 'all' == from_authors.find("option:selected").val() ) {
            authors.closest( 'tr' ).hide();
        }
        if (f_image.checked) {
            image_position.closest( 'tr' ).show();
            image_style.closest( 'tr' ).show();
        } else{
            image_position.closest( 'tr' ).hide();
            image_style.closest( 'tr' ).hide();
        }
        if ( 'after' == image_position.filter(':checked').val() && f_image.checked ) {
            attach_image.closest( 'tr' ).show();
        } else{
            attach_image.closest( 'tr' ).hide();
        }
        if ( 'with_caption' == image_style.filter(':checked').val() && f_image.checked ) {
            caption_source.closest( 'tr' ).show();
        } else{
            caption_source.closest( 'tr' ).hide();
        }

        $(from_terms).change(function() {
            if ( 'all' == from_terms.find("option:selected").val() ) {
                terms.closest( 'tr' ).hide(300);
            }
            else{
                terms.closest( 'tr' ).show(300);
            }
        });

        $(from_authors).change(function() {
            if ( 'all' == from_authors.find("option:selected").val() ) {
                authors.closest( 'tr' ).hide(300);
            }
            else{
                authors.closest( 'tr' ).show(300);
            }
        });

        $(f_image).change(function() {
            if (this.checked) {
                image_position.closest( 'tr' ).show(300);
                image_style.closest( 'tr' ).show(300);
                if ( 'with_caption' == image_style.filter(':checked').val() ) {
                    caption_source.closest( 'tr' ).show(300);
                }
                if ( 'after' == image_position.filter(':checked').val() ) {
                    attach_image.closest( 'tr' ).show(300);
                }
            }
            else{
                image_position.closest( 'tr' ).hide(300);
                attach_image.closest( 'tr' ).hide(300);
                image_style.closest( 'tr' ).hide(300);
                caption_source.closest( 'tr' ).hide(300);
            }
        });

        $(image_position).change(function() {
            if ('after' == image_position.filter(':checked').val()) {
                attach_image.closest( 'tr' ).show(300);
            }
            else{
                attach_image.closest( 'tr' ).hide(300);
            }
        });

        $(attach_image).change(function() {
            $('.wptg-attach').removeClass('notice notice-success notice-error');
            if (this.checked) {
                if ( 'none' != parse_mode.filter(':checked').val() ) {
                    validate_attach_image('parse', 'success');
                }else{
                    $(attach_image).attr('checked', false);
                    validate_attach_image('parse', 'error');
                }
                if ( 'with_caption' == image_style.filter(':checked').val() ) {
                    $(attach_image).attr('checked', false);
                    validate_attach_image('caption', 'error');
                }else{
                    validate_attach_image('caption', 'success');
                }
                var preview = $('#wptelegram_message_misc_disable_web_page_preview');
                if ( preview.is(':checked') ) {
                    $(attach_image).attr('checked', false);
                    validate_attach_image('preview', 'error');
                }else{
                    validate_attach_image('preview', 'success');
                }
            }
        });

        $(image_style).change(function() {
            if ('with_caption' == image_style.filter(':checked').val()) {
                caption_source.closest( 'tr' ).show(300);
            }
            else{
                caption_source.closest( 'tr' ).hide(300);
            }
        });
    });
})(jQuery)
</script>