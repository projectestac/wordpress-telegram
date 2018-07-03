(function( $ ) {
	'use strict';
	$(document).ready(function() {
	    var app1 = {};

	    app1.pasteHtmlAtCaret = function(html, selectPastedContent) {
		    var sel, range;
		    if (window.getSelection) {
		        // IE9 and non-IE
		        sel = window.getSelection();
		        if (sel.getRangeAt && sel.rangeCount) {
		            range = sel.getRangeAt(0);
		            range.deleteContents();

		            // Range.createContextualFragment() would be useful here but is
		            // only relatively recently standardized and is not supported in
		            // some browsers (IE9, for one)
		            var el = document.createElement("div");
		            el.innerHTML = html;
		            var frag = document.createDocumentFragment(), node, lastNode;
		            while ( (node = el.firstChild) ) {
		                lastNode = frag.appendChild(node);
		            }
		            var firstNode = frag.firstChild;
		            range.insertNode(frag);
		            
		            // Preserve the selection
		            if (lastNode) {
		                range = range.cloneRange();
		                range.setStartAfter(lastNode);
		                if (selectPastedContent) {
		                    range.setStartBefore(firstNode);
		                } else {
		                    range.collapse(true);
		                }
		                sel.removeAllRanges();
		                sel.addRange(range);
		            }
		        }
		    } else if ( (sel = document.selection) && sel.type != "Control") {
		        // IE < 9
		        var originalRange = sel.createRange();
		        originalRange.collapse(true);
		        sel.createRange().pasteHTML(html);
		        if (selectPastedContent) {
		            range = sel.createRange();
		            range.setEndPoint("StartToStart", originalRange);
		            range.select();
		        }
		    }
		}

		if ( 'function' == typeof $().emojioneArea ) {
			if (window.matchMedia('(max-width: 800px)').matches) {
		        var pos = 'top';
		    } else {
		        var pos = 'left';
		    }
			var e = $('#wptelegram_message_template').emojioneArea({
				container: "#wptelegram_message_template-container",
				hideSource: true,
				pickerPosition: pos,
				tonesStyle: 'radio',
			    });
		}
		$('.wptelegram-tag').click(function () {
		    if ( 'function' == typeof $().emojioneArea )
		    	$('.emojionearea-editor')[0].focus();
			    var val = this.innerText;
			    app1.pasteHtmlAtCaret(val,true);
		});
		var send_to = $('#wptelegram_meta_box #send_to');
		var template = $('#wptelegram_meta_box #message_template');
		
		var chat_ids = $('.wptelegram_send_to');
		chat_ids.hide();
	    $('input[type=radio][name=wptelegram_send_message]').change(function() {
	        if (this.value == 'yes') {
	            send_to.show(300);
	            template.show(300);
	        }
	        else{
	        	send_to.hide(300);
	            template.hide(300);
	        }
	    });
	    $('#wptelegram_send_to_all').change(function() {
	        if(this.checked) {
	            chat_ids.hide(300);
	        }
	        else{
	        	chat_ids.show(300);
	        }
	    });


	    app1.file_html = function( file ) {
			var icon;
			if (typeof file.sizes != 'undefined') {
				var keys = Object.keys(file.sizes);
				icon = file.sizes[keys[keys.length-1]].url;
			} else {
				icon = file.icon;
			}
			var row =  $('<tr/>', {id: 'wptg-file-'+file.id });

			//thumb
			var thumb = $('<img/>',{src: icon,alt:file.alt}).css('maxWidth', "20px");
		    row.append($('<td/>').append(thumb));

			//size
		    row.append($('<td/>').append(file.filesize));

		    //name
		    var filename = file.filename;
		    if (filename.length>30) {
			    filename = filename.replace(/^(.{1,25}).+?(.{3,5}\.[^_\W]+?)$/u, '$1...$2');
		    }
		    var name = $('<a/>', {text:filename,href: file.url,target:'_blank',title:file.filename});
		    row.append($('<td/>').append(name));

		    // remove
		    var remove = $('<a/>', {text:'❌',href:'#',class:'wptg-file-remove' })
		    row.append($('<td/>').append(remove));

			var type;
			switch (file.type) {
			    case 'video':
			    case 'audio':
			        type = file.type;
			        break; 
			    case 'image':
			        type = 'photo';
			        break;
			    default: 
			        type = 'document';
			}
		    row.append($('<input/>',{type:'hidden',name:'wptelegram_send_files['+file.id+'][type]',value:type}));
		    row.append($('<input/>',{type:'hidden',name:'wptelegram_send_files['+file.id+'][url]',value:file.url}));

		    return row;
		};


		var frame,
			metaBox = $('#wptelegram_meta_box.postbox'),
			FileUploadBtn = metaBox.find('.wptelegram-file-upload'),
			RemoveAllBtn = metaBox.find( '.wptelegram-file-remove-all'),
			fileContainer = metaBox.find( '.wptelegram-files-container table');

		FileUploadBtn.on( 'click', function( event ){
			event.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media({
				multiple: 'add'
			});

		    frame.on( 'select', function() {
				var files = frame.state().get('selection').toJSON();

				$.each( files, function( index, file ) {
					if($('#wptg-file-'+file.id).length == 0){
						fileContainer.append( app1.file_html(file) );
					}
				});

				app1.bind_file_remove();
				RemoveAllBtn.removeClass( 'hidden' );
		    });
		    frame.open();
		});

		app1.bind_file_remove = function () {
			metaBox.find('.wptg-file-remove').on( 'click', function( event ){
				event.preventDefault();
				$(this).closest('[id^="wptg-file-"]').remove();

				if ($('[id^="wptg-file-"]').length==0) {
					app1.remove_all(event);
				}
			});
		}

		app1.remove_all = function (event) {
			event.preventDefault();

			fileContainer.html('');
			RemoveAllBtn.addClass( 'hidden' );
		}

		RemoveAllBtn.on( 'click', app1.remove_all);
	});
})(jQuery);