/**
 * @copyright 2013 mycitizen.net, GPLv3
 * @author Mycitizen.net
 */

var baseUri = '';

$(document).ready(function(){
	baseUri = $('#baseUri').val();
});
 
function updateUser(user_id) {
	var enabled = 1;
	if($("#enabled-"+user_id).attr('checked')) {
		enabled = 1;
	} else {
		enabled = 0;
	}
	var values = {
		"access_level" : $("#access_level-"+user_id).val(),
		"enabled" : enabled
	}
	$.getJSON("?do=userAdministration",{ "user_id":user_id,"values":values },function(payload){
		return payload;
    });
}

function showObjectDefault(object_type,object_id) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?do=defaultPage",{ "object_type":object_type,"object_id":object_id });
}

function showObjectDetail(object_type,object_id,url) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON(url+"&do=detailPage",{ "object_type":object_type,"object_id":object_id });
	window.history.pushState("void", "Title", url);
}

function changePageUrl(name,page,url) {
	$("#progressbar").delay(2000).fadeIn(1000);
	var urlq = url + (url.split('?')[1] ? '&':'?') + "do=changePage";
	$.getJSON(urlq,{ "name": name, "page": page },function(payload){
		if (payload) {
			for (var id in payload.snippets) {
				$("#" + id).html(payload.snippets[id]);
			}
		}
	});
}

function addNewTag_User(user_id,tag_id) {
	if(tag_id != null) {
		if(user_id != null) {
			$.post("?do=insertTag&tag_id="+tag_id+"&user_id="+user_id);
		} else {
    		$.post("?do=insertTag&tag_id="+tag_id);
		}
	}
}

function removeTag_User(tag_id,user_id) {
	if(tag_id != null) {
		if(user_id != null) {
			$.post("?do=removeTag&tag_id="+tag_id+"&user_id="+user_id);
		} else {
			$.post("?do=removeTag&tag_id="+tag_id);
		}
	}
}

function addNewTag_Group(group_id,tag_id) {
   if(tag_id != null) { 
		$.post("?group_id="+group_id+"&do=insertTag&tag_id="+tag_id);
	}
}

function removeTag_Group(group_id,tag_id) {
   if(tag_id != null) {
		$.post("?group_id="+group_id+"&do=removeTag&tag_id="+tag_id);
	}
}

function addNewTag_Resource(resource_id,tag_id) {
   if(tag_id != null) { 
		$.post("?resource_id="+resource_id+"&do=insertTag&tag_id="+tag_id);
	}
}

function removeTag_Resource(resource_id,tag_id) {
   if(tag_id != null) {
		$.post("?resource_id="+resource_id+"&do=removeTag&tag_id="+tag_id);
	}
}

function userInsert_Resource(user_id,resource_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?user_id="+user_id+"&resource_id="+resource_id+"&do=userResourceInsert",function(payload) {
		if (refresh_path!='') location.href=refresh_path;
		$("#progressbar").fadeOut(1000);
	});
}

function userRemove_Resource(user_id,resource_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?user_id="+user_id+"&resource_id="+resource_id+"&do=userResourceRemove",function(payload){
		if (refresh_path!='') location.href=refresh_path;
		$("#progressbar").fadeOut(1000);
	});
}

/*
function groupInsert_Resource(group_id,resource_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?group_id="+group_id+"&resource_id="+resource_id+"&do=groupResourceInsert",function(payload){
		location.href=refresh_path;
	});
}


function groupRemove_Resource(group_id,resource_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?group_id="+group_id+"&resource_id="+resource_id+"&do=groupResourceRemove",function(payload){
		location.href=refresh_path;
	});
}
*/
function groupInsert_User(group_id,user_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?group_id="+group_id+"&user_id="+user_id+"&do=groupUserInsert",function(payload){
      location.href=refresh_path;
   });
}

function groupRemove_User(group_id,user_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?group_id="+group_id+"&user_id="+user_id+"&do=groupUserRemove",function(payload){
      location.href=refresh_path;
   });
}
function userInsert_Friend(friend_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?friend_id="+friend_id+"&do=userFriendInsert",function(payload){
      location.href=refresh_path;
   });
}

function userRemove_Friend(friend_id,refresh_path) {
	$("#progressbar").delay(2000).fadeIn(1000);
   $.getJSON("?friend_id="+friend_id+"&do=userFriendRemove",function(payload){
      location.href=refresh_path;
   });
}

function selectLanguage(language) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?language="+language+"&do=selectLanguage",function(payload){
   		var url = self.location.href;
   		url = url.split('#')[0];
   		url = url.replace(/\?language=[0-9]+&/i,"?");
   		url = url.replace(/&language=[0-9]+/i,"");
   		url = url.replace(/\?language=[0-9]+/i,"");
		location.href=url;
   });
}

function clearFilter(name) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?do=clearFilter&name="+name,function(){
		location.href=self.location.href;
   });
}

function removeAvatar(user_id) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?do=removeAvatar&user_id="+user_id,function(payload){
		location.href=self.location.href;
   });
}

function removeGroupAvatar(group_id) {
	$("#progressbar").delay(2000).fadeIn(1000);
	$.getJSON("?do=removeAvatar&group_id="+group_id,function(payload){
    	location.href=self.location.href;
   });
}

function moveToTrash(resource_id) {
	if(resource_id != null) {
		$.post("?do=moveToTrash&resource_id="+resource_id);
	}
	if (typeof reloadAbstract === 'function') { 
		reloadAbstract();
	}
}

function moveFromTrash(resource_id) {
	if(resource_id != null) {
    	$.post("?do=moveFromTrash&resource_id="+resource_id);
	}
	if (typeof reloadAbstract === 'function') { 
		reloadAbstract();
	}
}

function markRead(resource_id) {
	if(resource_id != null) {
   		$.post("?do=markRead&resource_id="+resource_id);
	}
	if (typeof reloadAbstract === 'function') { 
		reloadAbstract();
	}
}

function markUnread(resource_id) {
	if(resource_id != null) {
    	$.post("?do=markUnread&resource_id="+resource_id);
	}
	if (typeof reloadAbstract === 'function') { 
		reloadAbstract();
	}
}

/* used? */
function deactivate(object_type,object_id) {
   if(object_type != null && object_id != null) {
      $.post("?object_type="+object_type+"&object_id="+object_id+"&do=disableObject");
		if(object_type == 1) {
			$("#message").html("User with id "+object_id+" deactivated!");
		} else if(object_type == 2) {
			$("#message").html("Group with id "+object_id+" deactivated!");
		} else {
			$("#message").html("Resource with id "+object_id+" deactivated!");
		}

   }
}

/* used? */
function revokepermission(object_type,object_id) {
   if(object_type != null && object_id != null) {
      $.post("?object_type="+object_type+"&object_id="+object_id+"&do=revokePermission");
		if(object_type == 1) {
         $("#message").html("User's permission revoked");
      } else if(object_type == 2) {
         $("#message").html("Owner's permission revoked");
      } else {
         $("#message").html("Owner's permission revoked");
      }
   }
}

function deletereport(report_id) {
	if(report_id != null) {
   		if (confirm('Are you sure to delete this report?')) {
   			$("#progressbar").delay(2000).fadeIn(1000);
      		$.post("?report_id="+report_id+"&do=deleteReport",function(){
      			location.href=self.location.href;
      		});
      }
   }
}

function warning(object_type,object_id,warning_type) {
   if(object_type != null && object_id != null) {
      $.post("?object_type="+object_type+"&object_id="+object_id+"&warning_type="+warning_type+"&do=sendWarning");
		if(object_type == 1) {
         $("#message").html("Warning sent to user");
      } else if(object_type == 2) {
         $("#message").html("Warning sent to group owner");
      } else {
         $("#message").html("Warning sent to resource owner");
      }
   }
}

function removeMessageNow(id) {
	$.post("?message_id="+id+"&do=removeMessage", function(data) {
		if (data == "true") {
			$('#totrash-'+id).hide();
			$('#chat_message_'+id).slideUp('normal');	
		}
	});
}


/*
 *	http://www.quirksmode.org
 *	Cookie to prevent alerts from popping up 
 */
function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

/* http://www.quirksmode.org */
function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

/*
 *	jQuery-related
 *
 */

$(document).ready(function(){
	$('.user-info').mouseenter(function(){
		$('.hidden-menu').filter(":hidden").slideDown("normal", function(){
			$(this).fadeTo("normal", 1.00, function() {
				$(this).clearQueue();
			});
		});
	});

	$('.header').mouseleave(function(){
		$('.hidden-menu').filter(":visible").fadeTo("normal", 0.00, function(){
			$(this).slideUp("normal", function() {
				$(this).clearQueue();
			});
		});
		
	});

	$(document).on('click', 'a.ajax', function(){
		if (this.href.search('toggleChat')>=0) {
			var url = self.location.href;
			var urlq = url + (url.split('?')[1] ? '&':'?') + "do=clickLink&toggleChat";
		} else {
			var url = this.href;
			var urlNoHash = url.split('#')[0];
			var urlq = urlNoHash + (urlNoHash.split('?')[1] ? '&':'?') + "do=clickLink";
		}
		
		$("#progressbar").delay(2000).fadeIn(1000);
		$.ajax(urlq,
			function(payload){
			if (payload) {
				for (var id in payload.snippets) {
					$("#" + id).html(payload.snippets[id]);
				}
			}
		});
		url=url.replace('&toggleChat', '');
		url=url.replace('toggleChat&', ''); // ?toggleChat&abc=xyz
		window.history.pushState(null, null, url);
		return false;
	});

	$(document).on('submit', 'form.ajax', function(event){
		event.preventDefault();
		$.fancybox.close();
		var url = self.location.href;
		var urlNoHash = url.split('#')[0];
		var urlq = urlNoHash + (urlNoHash.split('?')[1] ? '&':'?') + "do=ajaxfilter";
		var submitName = $("input[type=submit][clicked=true]").attr('name');
		var $inputs = $('form.ajax :input');
		var values = {};
		$("#progressbar").delay(2000).fadeIn(1000);
		
		$inputs.each(function() {
			if ($(this).attr('type') == 'checkbox') {
				if ($(this).is(':checked')) {
					values[this.name] = 1;
				} else {
					values[this.name] = 0;
				}
			} else if ($(this).attr('type') == 'radio') {
				if ($(this).is(':checked')) {
					values[this.name] = $(this).val();
				}		
			} else {
			   values[this.name] = $(this).val();
			}
		});
		var mapData = $('#mapcontainer').val();
		if (mapData) {
			values['mapdata'] = mapData;
		}
		values['submittedby'] = submitName;
	
		if (submitName) {
			$.post(urlq,values,function(payload){
				if (payload) {
					for (var id in payload.snippets) {
						$("#" + id).html(payload.snippets[id]);
					}
				}
			});
		}
		return false;
	});
	
	$("a.active").prop("href", "javascript:void(0);");
	$("a.active").css("cursor", "default");

	$(".tags-fancybox").fancybox({
		closeBtn : true,
		leftRatio: 0.15,
		topRatio: 0,
		maxWidth: 500,
		helpers : {
			overlay : {
				css: {'background' : 'rgba(200, 200, 200, .5)'}
			},
			title : null
		},
	});

	$(".fancybox").fancybox({
		closeBtn : true,
		helpers : {
			overlay : {
				css: {'background' : 'rgba(200, 200, 200, .5)'}
			},
			title : null
		},
	});
	
	$('.fancybox-image').fancybox({
		type:'image',
		closeBtn : true,
		helpers : {
			overlay : {
				css: {'background' : 'rgba(200, 200, 200, .5)'}
			},
			title : null
		}
		});
	
	$("#filter_box_a").fancybox({
		minWidth: 1000,
		minHeight: 550,
		closeBtn: true,
		helpers: {
			overlay: {
				css: {'background' : 'rgba(200, 200, 200, .5)'}
			},
			title: null
		},
	});
});


	
$(function() {
	$("#show_activity_header").fancybox({
		minWidth:800,
		minHeight:500,
		closeBtn : true,
		helpers : {
			overlay : {
				css: {'background' : 'rgba(200, 200, 200, .5)'}
			},
			title : null
		},
		afterLoad: function() {
			var latest = $("input[name='latest_items_header']").prop('checked')?'1':'0';
			loadActivity("#load-more-header-1", 2, latest, 'header');
		}
	});
});

$(document).ready(function(){
	$("input[name='latest_items_header']").change(function(){
		var latest = $(this).prop('checked')?'1':'0';
		loadActivity("#load-more-header-1", 2, latest, 'header');
	});
});

var _popStateEventCount = 0;
		
$(window).on('popstate', function (event) {
	// prevent webkit (Safari, Chrome) to reload in a loop
    this._popStateEventCount++;
    var isWebkit = 'WebkitAppearance' in document.documentElement.style;
    if (isWebkit && this._popStateEventCount == 1) {
        return;
    }
   	$("#progressbar").delay(2000).fadeIn(1000);
	// classic reload
	location.href = document.location.href

	// dreams of the future
	//$.get('?do=historyback');
});
