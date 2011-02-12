var bwb_maps = [];
var bwb_marker;
var bwb_markers = [];
var bwb_infowindows = [];
var bwbcnt = 0;

function BWBPS_GMaps() {

	this.codeAddress = function(map, address_ele_id) {
	
		var address = document.getElementById(address_ele_id).value;
		var geocoder = new google.maps.Geocoder();

		geocoder.geocode( { 'address': address}, function(results, status) {
		  if (status == google.maps.GeocoderStatus.OK) {
			map.setCenter(results[0].geometry.location);
			map.setZoom(8);
			
			if(typeof(bwb_marker) == "object"){ bwb_marker.setMap(null); }
			
			bwb_marker = new google.maps.Marker({
				map: map, 
				position: results[0].geometry.location
			});
			
			photosmash.setLatLngEdit( bwb_marker.getPosition() )
			
		  } else {
			alert("Geocode was not successful for the following reason: " + status);
		  }
		});
	}

	this.showMap = function(map_id, mylat, mylng, zoom) {
		
		zoom = zoom || 8;
		
	    var myLatlng = new google.maps.LatLng(mylat, mylng);
	    var myOptions = {
	      zoom: 8,
	      center: myLatlng,
	      mapTypeId: google.maps.MapTypeId.ROADMAP
	    };
	    
	    var map = new google.maps.Map(document.getElementById( map_id ), myOptions);
						        
	    return map;
	        
	  };

	
	//Takes an array of locations (not markers, but title, lat, long, infowindow html)
	this.setMarkers = function(map, locations) {
		/*
	    var image = new google.maps.MarkerImage('images/beachflag.png',
	      new google.maps.Size(20, 32),
	      new google.maps.Point(0,0),
	      new google.maps.Point(0, 32));
	    var shadow = new google.maps.MarkerImage('images/beachflag_shadow.png',
	      new google.maps.Size(37, 32),
	      new google.maps.Point(0,0),
	      new google.maps.Point(0, 32));
	    var shape = {
	      coord: [1, 1, 1, 20, 18, 20, 18 , 1],
	      type: 'poly'
	    };
	    */
	    var bounds = new google.maps.LatLngBounds();
	    for (var i = 0; i < locations.length; i++) {
	      var loc = locations[i];
	      var myLatLng = new google.maps.LatLng(loc[1], loc[2]);
		  var infowindow = new google.maps.InfoWindow();
		  infowindow.setContent( loc[3] );
		  
			var marker = new google.maps.Marker({
				position: myLatLng,
				map: map,
				title: loc[0]
		    });
			
			google.maps.event.addListener(marker, 'click', function(){ infowindow.open(map, marker); });
		    
		    /*
		    var marker = new google.maps.Marker({
		        position: myLatLng,
		        map: map,
		        shadow: shadow,
		        icon: image,
		        shape: shape,
		        title: beach[0],
		        zIndex: beach[3]
		    });
		    */
		    
		    bounds.extend(myLatLng);
		 }
		    /*map.fitBounds(bounds);
		    var z = map.getZoom();
		    if(z < 14){ map.setZoom(14); }
		 */

	};
	
	
	this.addMarkerWithInfoWindow = function(map, lat, lng, infotext, map_num) {
	
		if(isNaN(lat)){ lat = 0; }
		if(isNaN(lng)){ lng = 0; }
		
		lat = parseFloat(lat);
		lng = parseFloat(lng);
		
		var infowindow = new google.maps.InfoWindow();
		infowindow.setContent(infotext);
		
		var location = new google.maps.LatLng(lat, lng);
		var marker = new google.maps.Marker({
			position: location,
			map: map
		});
		
		if(lat == 0 && lng == 0){
			map.setZoom(3);
		} else {
			if(map.getZoom() < 11 ){
				map.setZoom(11);
			}
		}
		
		map.setCenter(location);
		google.maps.event.addListener(marker, 'click', function(){ infowindow.open(map, marker); });

		bwb_infowindows[map_num].push(infowindow);

		return marker;
		//markersArray.push(marker);
	}

	
	this.addMarker = function(map, location) {
		
		var marker = new google.maps.Marker({
			position: location,
			map: map
		});
	  
		if(location.lat() == 0 && location.lng() == 0){
			map.setZoom(3);
		} else {
			
			if(map.getZoom() < 11 ){
				map.setZoom(11);
			}
		}
	  
	  return marker;
	  //markersArray.push(marker);
	}
	
	this.simpleMarker = function(map, lat, lng) {
	
		if(isNaN(lat)){ lat = 0; }
		if(isNaN(lng)){ lng = 0; }
		
		lat = parseFloat(lat);
		lng = parseFloat(lng);
			
		var location = new google.maps.LatLng(lat, lng);
		var marker = new google.maps.Marker({
			position: location,
			map: map
		});
		
		if(lat == 0 && lng == 0){
			map.setZoom(3);
		} else {
			if(map.getZoom() < 11 ){
				map.setZoom(11);
			}
		}
		
		map.setCenter(location);
		  
		return marker;
		//markersArray.push(marker);
	}
	
	this.clearMarker = function(marker){
		if(typeof(marker) == "object"){ marker.setMap(null); }
	}
	
	this.saveLatLng = function(image_id, lat, lng, map_nonce){
	
		if(!image_id ){
			alert('Select image to edit above by clicking on the Globe icon.');
			return;
		}
		
		if( !confirm("Save image location (" + lat + ", " + lng + ")?")){ return; }
	
		var _data = {};
		
		_data['action'] = 'savelatlng';
		
		_data['image_id'] = image_id;
		
		_data['_ajax_nonce'] = map_nonce;
		
		_data['lat'] = lat;
		_data['lng'] = lng;
			
		try{
			jQuery('.ps_savemsg').show();
		}catch(err){}
				
		jQuery.ajax({
			type: 'POST',
			url: bwbpsAjaxURL,
			data : _data,
			dataType: 'json',
			success: function(data) {
				bwb_gmap.saveLatLngSuccess(data, image_id);
			}
		});
		
		return false;
	
	}

	this.saveLatLngSuccess = function (data, image_id){
		jQuery('.ps_savemsg').hide();
		if(data == -1){
			alert('Security failed: nonce.');
			return;
		}
				
		if(data.status == 1)
		{
			alert(data.message);
			jQuery("#geolong_" + image_id).val(data.lng);
			jQuery("#geolat_" + image_id).val(data.lat);
		} else {
			alert(data.message);
		}
	}
	
	this.geocodeAddress = function(address, lat_ele, lng_ele){
		var geocoder = new google.maps.Geocoder();

		geocoder.geocode( { 'address': address}, function(results, status) {
		  if (status == google.maps.GeocoderStatus.OK) {
			
			jQuery("#" + lat_ele).val(results[0].geometry.location.lat());
			jQuery("#" + lng_ele).val(results[0].geometry.location.lng());
				
			
		  } else {
			alert("Geocode was not successful for the following reason: " + status);
		  }
		});
	}
	
	this.getFormAddress = function(frm_pfx){
		var addr = "";
		
		if( jQuery("#" + frm_pfx + "bwbps_address").val() !== undefined ){
			addr = jQuery("#" + frm_pfx + "bwbps_address").val();
		}
		if( jQuery("#" + frm_pfx + "bwbps_locality").val() !== undefined ){
			addr += ", " + jQuery("#" + frm_pfx + "bwbps_locality").val();
		}
		if( jQuery("#" + frm_pfx + "bwbps_region").val() !== undefined ){
			addr += ", " + jQuery("#" + frm_pfx + "bwbps_region").val();
		}
		if( jQuery("#" + frm_pfx + "bwbps_country").val() !== undefined ){
			addr += ", " + jQuery("#" + frm_pfx + "bwbps_country").val();
		}
		if( jQuery("#" + frm_pfx + "bwbps_postal_code").val() !== undefined ){
			addr += ", " + jQuery("#" + frm_pfx + "bwbps_postal_code").val();
		}
		alert(addr);
		return addr;
	}
	
	this.showInfoWindow = function(imap, icnt){
	
		bwb_infowindows[imap][icnt].open(bwb_maps[imap], bwb_markers[imap][icnt]);
	
	}

}


var bwb_gmap = new BWBPS_GMaps();