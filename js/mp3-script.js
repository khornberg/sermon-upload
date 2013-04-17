/*
  Opens Wordpress Media Uploader
  Adds click event listener to the upload button
  Sends URL to media-upload.php
  referer: upload-sermon
  type: audio
  TB_iframe: true //always true
  post_id: 0 //does not assign media to a post
*/
jQuery(document).ready(function() {

jQuery('#sermon_upload_button').click(function() {
	formfield = jQuery('#sermon_upload').attr('name');
	tb_show('Upload a Sermon', 'media-upload.php?referer=sermon-upload&type=audio&TB_iframe=true&post_id=0');
	return false;
});

/*
  Overrides send_to_editor function
  Outputs uploaded media url to an element

  ONLY needed if uploading a single post
*/
window.send_to_editor = function(html) {
	parent.location.reload(1);
 // url = jQuery('img',html).attr('src');
 // if(jQuery(url).length == 0) {
 //    url = jQuery(html).attr('href');
 // }
 // jQuery('#sermon_upload').val(url);
 // tb_remove();
}

/*
  Refreshes the page when the window is closed
*/
jQuery("#TB_closeWindowButton").click(function() {
    parent.location.reload(1);
});


});

//sdg