var $j = jQuery.noConflict();
var bwbpsActiveGallery = 0;
var displayedGalleries = "";
var bwbpsUploadStatus = false;

$j(document).ready(function() { 
	//Show and hide the Loading icon on Ajax start/end
	$j("#bwbps_loading")
	.ajaxStart(function(){
		$j(this).show();
	})
	.ajaxComplete(function(){
		$j(this).hide();
		if(	bwbpsUploadStatus == false)
			{
				$j("#bwbps_submitBtn").removeAttr('disabled');
				$j("#bwbps_imgcaptionInput").removeAttr('disabled');
				$j('#bwbps_message').html("The maximum file size allowed is 400k.  Please resize and try again.");
			}
		bwbpsUploadStatus = false;
	});

	
	var options = { 
		beforeSubmit:  bwbpsVerifyUploadRequest,  
		success:      bwbpsUploadSuccess , 
		failure: function(){alert('failed');},
		url:       bwbpsAjaxUpload,
		dataType:  'json'
	}; 
	$j('#bwbps_uploadform').submit(function() { 
		$j('#bwbps_message').html(''); 
		$j(this).ajaxSubmit(options); 
		return false; 
	});
	
	//make sure the upload form radio button is on Select file
	$j("#bwbpsSelectFileRadio").attr("checked","checked");
}); 

$j(window).bind("load",  psSetGalleryHts);

function psSetGalleryHts(){
	if(displayedGalleries == '') return;
	var ps = displayedGalleries.split('|');
	
	var i=0;
	var icnt = ps.length;
	for(i=0;i < icnt;i++){
		if(ps[i] != ""){
			bwbps_equalHeight($j(".psgal_" + ps[i]));
		}
	}
}

function bwbpsVerifyUploadRequest(formData, jqForm, options) { 
	var fileToUploadValue;
	
	if($j('#bwbpsSelectURLRadio').attr("checked")){
		$j('#bwbps_uploadfile').val("");
		fileToUploadValue = true;
	} else {
		fileToUploadValue = $j('#bwbps_uploadfile').val();		
	}
	
	if (!fileToUploadValue) { 
		$j('#bwbps_message').html('Please select a file.'); 
		return false; 
	} 
	$j("#bwbps_imgcaption").val($j("#bwbps_imgcaptionInput").val());
	$j("#bwbps_submitBtn").attr('disabled','disabled');
	$j("#bwbps_imgcaptionInput").attr('disabled','disabled');
	$j('#bwbps_result').html('');
	
	return true;
} 


// Callback for successful Ajax image upload
// Displays the image or error messages
function bwbpsUploadSuccess(data, statusText)  { 
	$j("#bwbps_submitBtn").removeAttr('disabled');
	$j("#bwbps_imgcaptionInput").removeAttr('disabled');
	bwbpsUploadStatus = true;
	if (statusText == 'success') {
		if(data == -1){
				alert('nonce');
			//The nonce	 check failed
			$j('#bwbps_message').html("<span class='error'>Upload failed due to invalid authorization.  Please reload this page and try again.</span>");
			return false;
	 	}
	 	
		if( data.succeed == 'false'){
			//Failed for some reason
			$j('#bwbps_message').html(data.message); 
			return false;
		}
		
		if (data.img != '') {
			//We got an image back...show it
			$j('#bwbps_result').html('<img src="' + bwbpsThumbsURL + data.img+'" />'); 
			$j('#bwbps_message').html('<b>Upload successful!</b>'); 
			

			
			var li = $j('<li></li>').attr('class','psgal_' + data.gallery_id).appendTo('#bwbps_gal_' + data.gallery_id);
			
			if (data.li_width > 0) {
				li.css('width', data.li_width + '%');
			}else{
				li.css('margin','15px');	
			}	
			
			var imgdiv;
			
			if ($j.browser.msie) {
				imgdiv = $j('<div></div>').css('width', data.thumb_width).css('margin', 'auto');
			} else {
				imgdiv = $j('<div></div>').css('width', data.thumb_width);
			}
			
			var ahref = $j('<a></a>').attr('href', bwbpsImagesURL + data.img).attr('rel',data.imgrel);
			
			$j('<img src="' + bwbpsThumbsURL + data.img+'" />').appendTo(ahref);
					
			if(data.show_imgcaption > 0){
				$j('<br />').appendTo(ahref);
				$j('<span>' + data.image_caption + '</span>').attr('class','bwbps_caption').appendTo(ahref);
			}
			
			ahref.appendTo(imgdiv);
			
			imgdiv.appendTo(li);
			
			bwbps_equalHeight($j(".psgal_" + data.gallery_id));
			
			li.append('&nbsp;');
			
			
		} else {
			$j('#bwbps_message').html( data.error); 
		}
	} else {
		$j('#bwbps_message').html('Unknown error!'); 
	}
} 


//Show the Photo Upload Form
function bwbpsShowPhotoUpload(gal_id){
	bwbpsActiveGallery = gal_id;
	$j('#bwbps_galleryid').val(gal_id);
}

//Toggle File or URL field in upload
function bwbpsToggleFileOrURL(bShowUrl){
	if(bShowUrl){
		$j("#bwbps_uploadfile").hide();
		$j("#bwbps_uploadurlspan").fadeIn("slow");
	}else{
		$j("#bwbps_uploadurlspan").hide();
		$j("#bwbps_uploadfile").fadeIn("slow");
	}
	return false;
}


//Reset the height of all the LI's to be the same
function bwbps_equalHeight(group) {
    tallest = 0;
    group.each(function() {
        thisHeight = $j(this).height();
        if(thisHeight > tallest) {
            tallest = thisHeight;
        }
    });
    group.height(tallest);
}

function bwbpsConfirmDeleteGallery(){
	var fieldname = jQuery("#bwbpsGalleryDDL option:selected").text();
	return confirm('Do you want to delete Gallery: ' + fieldname + '?');
}

//Moderate/Delete Image
function bwbpsModerateImage(action, image_id)
{
	var imgid = parseInt('' + image_id);
	var myaction = false;
	
	if(action == 'bury'){ myaction = "delete";}
	if(action == 'approve'){ myaction = "approve";}
	if(action == 'savecaption'){ myaction = 'savecaption';}
	if(!myaction){ alert('Invalid action.'); return false;}
	
	if(!confirm('Do you want to ' + myaction + ' this image (id: ' + imgid + ')?')){ return false;}
	
	var _moderate_nonce = $j("#_moderate_nonce").val();
	
	var image_caption = '';
	if(action == 'savecaption'){ image_caption = $j('#imgcaption_' + imgid).val(); }
	
	try{
		$j('#ps_savemsg').show();
	}catch(err){}
	
	$j.ajax({
		type: 'POST',
		url: bwbpsAjaxURL,
		data: { 'action': myaction,
       'image_id': imgid,
       '_ajax_nonce' : _moderate_nonce,
       'image_caption' : image_caption
       },
		dataType: 'json',
		success: function(data) {
			bwbpsModerateSuccess(data, imgid);
		}
	});
	return false;
}

// Callback for successful Ajax image moderation
function bwbpsModerateSuccess(data, imgid)  { 
		try{
			$j('#ps_savemsg').hide();
		}catch(err){}
		if(data == -1){
				alert('nonce');
			//The nonce	 check failed
			$j('#psmod_' + imgid).html("fail: security"); 
			return false;
	 	}
	 	
		if( data.status == 'false' || data.status == 0){
			//Failed for some reason
			$j('#psmod_' + imgid).html("update: fail"); 
			return false;
		} else {
			//this one passed
			$j('#psmod_' + imgid).html( data.action); 
			if(data.deleted == 'deleted'){
				$j('#psimage_' + imgid).html('');				
			}
			$j('#psimg_' + imgid).removeClass('ps-moderate');
			return false;
		}
}