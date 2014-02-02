var map = null;
function findLocation(address) {
   if(address != "") {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode( { 'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                mapcenter = results[0].geometry.location;
                map.setCenter(mapcenter);
            } else {
            	alert("Nelze nalézt zadanou adresu.");
              	$("[name='search']").val("");
         }
        });

    }
}

function loadData() {
	var mapOptions = {
      zoom: 8,
      center: mapcenter,
      mapTypeId: google.maps.MapTypeId.ROADMAP
   };
   map = new google.maps.Map(document.getElementById("map_canvas"),mapOptions);

	$.getJSON("?do=mapData",{ "user_id":{$user_id} },function(payload){
   	for (var i = 0; i < payload.length;i++) {
         var position = [];
         for(var j =0; j < payload[i].location_data.length;j++) {
         	position.push(new google.maps.Marker({
																	map:map,
                                                	title:payload[i].user_login,
                                                	position:new google.maps.LatLng(parseFloat(payload[i].location_data[j].latitude,10),parseFloat(payload[i].location_data[j].longitude,10))
                                                }));
         }
      }
	});
}

