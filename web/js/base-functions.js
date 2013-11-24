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

function showGroupDetail(object_type,object_id) {
	$.getJSON("?do=defaultPage",{ "object_type":object_type,"object_id":object_id },function(payload){
		$.nette.success(payload);
    });
}

function showObjectDetail(object_type,object_id) {
   $.getJSON("?do=defaultPage",{ "object_type":object_type,"object_id":object_id },function(payload){
      $.nette.success(payload);
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
	$.getJSON("?user_id="+user_id+"&resource_id="+resource_id+"&do=userResourceInsert",function(payload) {
		location.href=refresh_path;
	});
}

function userRemove_Resource(user_id,resource_id,refresh_path) {
	$.getJSON("?user_id="+user_id+"&resource_id="+resource_id+"&do=userResourceRemove",function(payload){
		location.href=refresh_path;
	});
}

function groupInsert_Resource(group_id,resource_id,refresh_path) {
   $.getJSON("?group_id="+group_id+"&resource_id="+resource_id+"&do=groupResourceInsert",function(payload){
		location.href=refresh_path;
	});
}

function groupRemove_Resource(group_id,resource_id,refresh_path) {
   $.getJSON("?group_id="+group_id+"&resource_id="+resource_id+"&do=groupResourceRemove",function(payload){
		location.href=refresh_path;
	});
}

function groupInsert_User(group_id,user_id,refresh_path) {
   $.getJSON("?group_id="+group_id+"&user_id="+user_id+"&do=groupUserInsert",function(payload){
      location.href=refresh_path;
   });
}

function groupRemove_User(group_id,user_id,refresh_path) {
   $.getJSON("?group_id="+group_id+"&user_id="+user_id+"&do=groupUserRemove",function(payload){
      location.href=refresh_path;
   });
}
function userInsert_Friend(friend_id,refresh_path) {
   $.getJSON("?friend_id="+friend_id+"&do=userFriendInsert",function(payload){
      location.href=refresh_path;
   });
}

function userRemove_Friend(friend_id,refresh_path) {
   $.getJSON("?friend_id="+friend_id+"&do=userFriendRemove",function(payload){
      location.href=refresh_path;
   });
}

function selectLanguage(language) {
   $.getJSON("?language="+language+"&do=selectLanguage",function(payload){
      location.href=self.location.href;
   });
}

function removeAvatar(user_id) {
   $.getJSON("?do=removeAvatar&user_id="+user_id,function(payload){
      location.href=self.location.href;
   });
}

function removeGroupAvatar(group_id) {
   $.getJSON("?do=removeAvatar&group_id="+group_id,function(payload){
      location.href=self.location.href;
   });
}

function visit(type_id,object_id) {
   $.getJSON("/widget/visit/?type_id="+type_id+"&object_id="+object_id,function(payload){
      //location.href=refresh_path;
   });
}

function moveToTrash(resource_id) {
   if(resource_id != null) {
   	$.post("?do=moveToTrash&resource_id="+resource_id);
   }
}

function moveFromTrash(resource_id) {
   if(resource_id != null) {
      $.post("?do=moveFromTrash&resource_id="+resource_id);
   }
}

function markRead(resource_id) {
	if(resource_id != null) {
   		$.post("?do=markRead&resource_id="+resource_id);
	}
}

function markUnread(resource_id) {
	if(resource_id != null) {
    	$.post("?do=markUnread&resource_id="+resource_id);
	}
}

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
   		if (confirm('Sure to delete?')) {
      		$.post("?report_id="+report_id+"&do=deleteReport",function(){
      			location.href=self.location.href;
      		});
      }
   }
}

function warning(object_type,object_id,warning_type) {
   if(object_type != null && object_id != null) {
      $.post("?object_type="+object_type+"&object_id="+object_id+"&warning_type="+warning_type+"&do=sendWarning");
		//location.href=self.location.href;
		if(object_type == 1) {
         $("#message").html("Warning sent to user");
      } else if(object_type == 2) {
         $("#message").html("Warning sent to group owner");
      } else {
         $("#message").html("Warning sent to resource owner");
      }
   }
}
