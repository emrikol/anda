jQuery(document).ready(function() {
//AJAX Upload
jQuery('.image_upload_button').each(function() {

var clickedObject = jQuery(this);
var clickedID = jQuery(this).attr('id');	
new AjaxUpload(clickedID, {
	  action: dws_anda_admin_url,
	  name: clickedID, // File upload name
	  data: { // Additional data to send
			action: 'dws_anda_ajax_callback',
			type: 'upload',
			data: clickedID },
	  autoSubmit: true, // Submit file after selection
	  responseType: false,
	  onChange: function(file, extension){},
	  onSubmit: function(file, extension){
			clickedObject.text('Uploading'); // change button text, when user selects file	
			this.disable(); // If you want to allow uploading only 1 file at time, you can disable upload button
			interval = window.setInterval(function(){
				var text = clickedObject.text();
				if (text.length < 13){	clickedObject.text(text + '.'); }
				else { clickedObject.text('Uploading'); } 
			}, 200);
	  },
	  onComplete: function(file, response) {
	   
		window.clearInterval(interval);
		clickedObject.text('Upload Image');	
		this.enable(); // enable upload button
		// If there was an error
		//alert(response);
		if(response.search('Upload Error') > -1){
			var buildReturn = '<span class="upload-error">' + response + '</span>';
			jQuery(".upload-error").remove();
			clickedObject.parent().after(buildReturn);
		
		}
		else{
			JSONresponse = jQuery.parseJSON(response) // turn string into JSON object
			var buildReturn = '<input type="hidden" value="' + JSONresponse.file + '" name="dws_anda_localfile" id="dws_anda_localfile" /><img class="hide new-default-avatar" id="image_'+clickedID+'" src="'+JSONresponse.url+'" alt="" />';

			jQuery(".upload-error").remove();
			jQuery("#image_" + clickedID).remove();	
			clickedObject.parent().after(buildReturn);
			jQuery('img#image_'+clickedID).fadeIn();
			clickedObject.next('span').fadeIn();
			clickedObject.parent().prev('input').val(JSONresponse.url);
			jQuery("#dws_anda_add li.hidden").show();
		}
	  }
	});

});

//AJAX Remove (clear option value)
jQuery('.image_reset_button').click(function(){

		var clickedObject = jQuery(this);
		var clickedID = jQuery(this).attr('id');
		var theID = jQuery(this).attr('title');	

		var ajax_url = dws_anda_admin_url;
	
		var data = {
			action: 'dws_anda_ajax_callback',
			type: 'image_reset',
			data: theID
		};
		
		jQuery.post(ajax_url, data, function(response) {
			var image_to_remove = jQuery('#image_' + theID);
			var button_to_hide = jQuery('#reset_' + theID);
			jQuery("#dws_anda_localfile").remove();
			image_to_remove.fadeOut(500,function(){ jQuery(this).remove(); });
			button_to_hide.fadeOut();
			clickedObject.parent().prev('input').val('');
			
			
			
		});
		
		return false; 
		
	});
});
