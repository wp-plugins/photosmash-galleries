var $j = jQuery.noConflict();
var bwbpsActiveGallery = 0;
var displayedGalleries = "";
var bwbpsUploadStatus = false;
var bwbpsAddedImages =0;

$j.fn.tagName = function() {
    return this.get(0).tagName;
}

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

	
	
	$j('.bwbps_uploadform').submit(function() { 
		$j('#bwbps_message').html('');
		bwbpsAjaxLoadImage(this);
		return false; 
	});
//	onsubmit="bwbpsSubmitForm(\'' . $g["pfx"] . '\' ); return false;" enctype="multipart/form-data"
	
	//make sure the upload form radio button is on Select file
	$j(".init_radio").attr("checked","checked");
	
	
	//Add OnClick to the Mass Update Buttons in the PhotoSmash Settings form
	if($j('#bwbps_gen_settingsform').val() == '1'){
		
		bwbpsAddPSSettingsMassUpdateActions();
	}
});

function bwbpsAjaxLoadImage(myForm){

	var form_pfx = myForm.id;
	form_pfx = form_pfx.replace("bwbps_uploadform", "");


	var options = { 
		beforeSubmit:  function(){bwbpsVerifyUploadRequest(form_pfx);},
		success: function(data, statusText){ bwbpsUploadSuccess(data, statusText, form_pfx); } , 
		failure: function(){alert('failed');},
		url:      bwbpsAjaxUpload,
		dataType:  'json'
	}; 

	$j(myForm).ajaxSubmit(options); 
	return false;
}

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

function bwbpsVerifyUploadRequest(form_pfx) { 
	var fileToUploadValue;
		
	$j('#' + form_pfx + 'bwbps_imgcaption').val($j('#' + form_pfx + 'bwbps_imgcaptionInput').val());
			
	if($j('#' + form_pfx + 'bwbpsSelectURLRadio').attr('checked')){
		
		fileToUploadValue = true;
	} else {
		fileToUploadValue = $j('#' + form_pfx + 'bwbps_uploadfile').val();		
	}
	
	if ( !bwbpsVerifyFileFilled(form_pfx) ) { 
		$j('#' + form_pfx + 'bwbps_message').html('<b>VALIDATION ERROR: Please select a file.</b>'); 
		return false; 
	} 

	$j('#' + form_pfx + 'bwbps_submitBtn').attr('disabled','disabled');

	$j('#' + form_pfx + 'bwbps_imgcaptionInput').attr('disabled','disabled');
	$j('#' + form_pfx + 'bwbps_result').html('');

	return true;
} 


/*
 *	Determine if the File Field is required, and if so, is it filled
 *
 */
function bwbpsVerifyFileFilled(form_pfx){
	if( $j('#' + form_pfx + 'bwbps_allownoimg').val() == 1 ){ return true; }
	var filetype = $j('input:radio[name=' + form_pfx + 'bwbps_filetype]:checked').val();
	
	var bFilled = false;
	switch (filetype){
		case 0 :	//Image
			bFilled = $j('#' + form_pfx + 'bwbps_uploadfile').val();
			break;
		
		case 1 :	//Image URL
			bFilled = $j('#' + form_pfx + 'bwbps_uploadurl').val();
			if(bFilled){
				$j('#' + form_pfx + 'bwbps_uploadfile').val("");
			}
			break;
		
		case 2 :	//Image Direct Link
			bFilled = $j('#' + form_pfx + 'bwbps_uploaddl').val();
			if(bFilled){
				$j('#' + form_pfx + 'bwbps_uploadfile').val("");
			}
			break;
			
		case 3 :	//Image URL
			bFilled = $j('#' + form_pfx + 'bwbps_uploadyt').val();
			break;
		
		case 4 :	//Image URL
			bFilled = $j('#' + form_pfx + 'bwbps_uploadvid').val();
			break;
		
		case 5 :	//Image for File 2
			bFilled = $j('#' + form_pfx + 'bwbps_uploadfile2').val();
			break;
			
		case 6 :	//Image URL for File 2
			bFilled = $j('#' + form_pfx + 'bwbps_uploadurl2').val();
			if(bFilled){
				$j('#' + form_pfx + 'bwbps_uploadfile2').val("");
			}
			break;
		
		default :
			bFilled = true;
	}
	if( bFilled ){ return true; } else { return false; }
}

// Callback for successful Ajax image upload
// Displays the image or error messages
function bwbpsUploadSuccess(data, statusText, form_pfx)  {
	
	//This Alternate function is set in PhotoSmash Settings Advanced page
	//If the alternate function returns false...continue with standard function
	if(bwbpsAlternateUploadFunction(data, statusText, form_pfx)){ return false;}

	$j('#' + form_pfx + 'bwbps_submitBtn').removeAttr('disabled');
	$j('#' + form_pfx + 'bwbps_imgcaptionInput').removeAttr('disabled');
	bwbpsUploadStatus = true;
	if (statusText == 'success') {
		if(data == -1){
				alert('nonce');
			//The nonce	 check failed
			$j('#' + form_pfx + 'bwbps_message').html("<span class='error'>Upload failed due to invalid authorization.  Please reload this page and try again.</span>");
			return false;
	 	}
	 	
		if( data.succeed == 'false'){
			//Failed for some reason
			$j('#' + form_pfx + 'bwbps_message').html(data.message); 
			return false;
		}
		
		if (data.db_saved > 0 ) {
			//We got an image back...show it
			$j('#' + form_pfx + 'bwbps_result').html('<img src="' + bwbpsThumbsURL + data.img+'" />'); 
			$j('#' + form_pfx + 'bwbps_message').html('<b>Upload successful!</b>'); 
			
			$j('.bwbps_reset').val('');
			
			//Add the New Images box for custom Layouts			
			var adderdiv;
			
			if(bwbpsCustomLayout && bwbpsAddedImages < 1){
				adderdiv = $j('<div></div>');
				adderdiv.attr('id','bwbps_galcont_' + data.gallery_id).attr('class','bwbps_gallery_div');
				
				var bbtbl = $j('<table></table>');
				var bbtr = $j('<tr></tr>');
				bbtr.appendTo(bbtbl);
				var bbtd = $j('<td></td>');
				
				bbtd.appendTo(bbtr);
				
				var newImgUL = $j('<ul></ul>').attr('class','bwbps_gallery').attr('id','bwbps_stdgal_' + data.gallery_id);
				newImgUL.appendTo(bbtd);
				bbtbl.appendTo(adderdiv);
				adderdiv.insertAfter('#bwbpsInsertBox_' + data.gallery_id);
			}
			
			
			
			var li = $j('<li></li>').attr('class','psgal_' + data.gallery_id).appendTo('#bwbps_stdgal_' + data.gallery_id);
			
			if (data.li_width > 0) {
				li.css('width', data.li_width + '%');
			}else{
				li.css('margin','15px');	
			}	
			
			//Manually set the LI height for Custom Layouts
			if(bwbpsCustomLayout && bwbpsAddedImages < 1){
				li.css('height', data.thumb_height + 20);
			}
			
			bwbpsAddedImages++;
			var imgdiv;
			
			if ($j.browser.msie) {
				imgdiv = $j('<div></div>').css('width', data.thumb_width);
			} else {
				imgdiv = $j('<div></div>').css('width', data.thumb_width).css('margin', 'auto');
			}
			
			var ahref = $j('<a></a>').attr('href', bwbpsImagesURL + data.img).attr('rel',data.imgrel);
			
			$j('<img src="' + bwbpsThumbsURL + data.img+'" />').appendTo(ahref);
					
			if(data.show_imgcaption > 0){
				$j('<br />').appendTo(ahref);
				$j('<span>' + data.image_caption + '</span>').attr('class','bwbps_caption').appendTo(ahref);
			}
			
			ahref.appendTo(imgdiv);
			
			imgdiv.appendTo(li);
			
			bwbps_equalHeight($j('.psgal_' + data.gallery_id));
			
			li.append('&nbsp;');
			
			
		} else {
			$j('#' + form_pfx + 'bwbps_message').html( data.error); 
		}
	} else {
		$j('#' + form_pfx + 'bwbps_message').html('Unknown error!'); 
	}
} 


//Show the Photo Upload Form
function bwbpsShowPhotoUpload(gal_id, post_id, form_pfx){
	if( form_pfx == null ){ form_pfx = ""; }
	bwbpsActiveGallery = gal_id;
	$j('#' + form_pfx + 'bwbps_galleryid').val(gal_id);
	$j('#' + form_pfx + 'bwbps_post_id').val(post_id);
}

function bwbpsShowPhotoUploadNoThickbox(gal_id, post_id, form_pfx){
	bwbpsActiveGallery = gal_id;
	if(!form_pfx){form_pfx = "";}
	$j('#' + form_pfx + 'bwbps_galleryid').val(gal_id);
	$j('#' + form_pfx + 'bwbps_post_id').val(post_id);
	$j('#' + form_pfx + 'bwbps-formcont').hide();
	$j('#' + form_pfx + 'bwbps-formcont').appendTo('#bwbpsFormSpace_' + gal_id);
	
	$j('#bwbpsFormSpace_' + gal_id).show();
	$j('#' + form_pfx + 'bwbps-formcont').show('slow');
}

function bwbpsHideUploadForm(gal_id, form_pfx){
	if(!gal_id){gal_id = "";}
	if( form_pfx == null ){ form_pfx = ""; }
	$j('#' + form_pfx + 'bwbps-formcont').hide('slow');
	$j('#bwbpsFormSpace_' + gal_id).hide('slow');
}

function bwbpsSwitchUploadField(field_id, select_iteration, form_pfx){
	if( select_iteration == null ){ select_iteration = ""; }
	if( form_pfx == null ){ form_pfx = ""; }
	$j( "." + form_pfx + "bwbps_uploadspans" + select_iteration ).hide();
	$j("#" + field_id).fadeIn("slow");
	
}

//Toggle Form Visible setting in PhotoSmash Default Settings Admin page
function bwbpsToggleFormAlwaysVisible(){
	if($j("#bwbps_use_thickbox").attr('checked')){
		$j("#bwbps_formviz").fadeOut('slow');
	} else {
		$j("#bwbps_formviz").fadeIn('slow');
	}
	
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

function bwbpsConfirmResetDefaults(){
	return confirm('Do you want to Reset all PhotoSmash Settings back to Default?');
}

function bwbpsConfirmDeleteGallery(){
	var fieldname = jQuery("#bwbpsGalleryDDL option:selected").text();
	return confirm('Do you want to delete Gallery: ' + fieldname + '?');
}

function bwbpsConfirmCustomForm(){
	var fieldname = jQuery("#bwbpsCFDDL option:selected").text();
	return confirm('Do you want to DELETE Custom Form: ' + fieldname + '?');
}

function bwbpsConfirmGenerateCustomTable(){
	return confirm('Do you want to generate the Table with your Custom Fields?');
}

function bwbpsConfirmDeleteField(completeDelete){
	var fieldname = jQuery("#supple_fieldDropDown option:selected").text();
	if(completeDelete){
		return confirm('Delete field and Drop from Custom Data table?\n\nComplete Delete will delete the field and remove it from the Custom Data Table.  Do you want to completely delete field: ' + fieldname + '?');
	}else{
		return confirm('Delete field (does not drop from table)?\n\nThis removes the field from your list of custom fields.  It does not remove from the Custom Data Table if you have generated the table already.  Do you want to delete field: ' + fieldname + '?');
	}
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
	var image_url = "";
	if(action == 'savecaption'){ 
		image_caption = $j('#imgcaption_' + imgid).val(); 
		image_url = $j('#imgurl_' + imgid).val(); 
	}
	
	try{
		$j('#ps_savemsg').show();
	}catch(err){}
	
	$j.ajax({
		type: 'POST',
		url: bwbpsAjaxURL,
		data: { 'action': myaction,
       'image_id': imgid,
       '_ajax_nonce' : _moderate_nonce,
       'image_caption' : image_caption,
       'image_url' : image_url
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
				alert('Failed due to security: invalid nonce');
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

function bwbpsAddPSSettingsMassUpdateActions(){
	$j('.psmass_update').click( function()
		{
			bwbpsMassUpdateGalleries(this.id);
		}
	);
}

function bwbpsMassUpdateGalleries(id){
	var eleid = id.substring(5, id.length);
	//var val = $j('#' + eleid).val();
	
	var ele = $j("[name=" + eleid + "]");
	
	var tagname = ele.tagName();
	var val = "";
	
	if(tagname = 'input'){
		var attr = ele.attr('type');
		
		switch (attr){
			case "radio" :
				val = $j("input:radio[name=" + eleid + "]:checked").val();
				break;
		
			case "checkbox" :
				val = $j("input[name='" + eleid + "']").attr('checked');
				break;
		
			default :
				val = ele.val();
				break;		
		}

	} else {
		 val = ele.val();
	
	}
	
	
	if(!confirm('Do you want to update ALL GALLERIES ' + ele.attr('name') + ' to: ' + val + '?')){ return false;}
	
	var _moderate_nonce = $j("#_moderate_nonce").val();
	
	var myaction = 'mass_updategalleries';
		
	try{
		$j('#ps_savemsg').show();
	}catch(err){}
	
	$j.ajax({
		type: 'POST',
		url: bwbpsAjaxURL,
		data: { 'action': myaction,
       'field_name': eleid,
       '_ajax_nonce' : _moderate_nonce,
       'field_value' : val
       },
		dataType: 'json',
		success: function(data) {
			bwbpsMassUpdateGalleriesSuccess(data);
		}
	});
	return false;
	
}




function bwbpsMassUpdateGalleriesSuccess(data){
	try{
		$j('#ps_savemsg').hide();
	}catch(err){}
	
	alert(data.message);

}