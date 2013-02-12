<?php

/**
 * Creates the admin page for the plugin
 * 
 */
function mp3_admin() {
  /**
   * Add the ID3 library.  Adding it here so it's only used as needed
   * http://wordpress.org/support/topic/plugin-blubrry-powerpress-podcasting-plugin-conflict-with-mp3-to-post-plugin?replies=1#post-2833002
   */
  require_once('getid3/getid3.php');
  require_once('mp3-to-post.php');

  ?>
    <style>
      .sermon_list_item { cursor: pointer; }
      .sermon_list_item ul { display:none; padding: 4px; }
    </style>

  <div class="wrap">
    <h2>Sermon Upload</h2>
    <?php
			// load our variables in to an array
      $mp3ToPostOptions = unserialize(get_option('mp3-to-post'));
      create_folder($mp3ToPostOptions['folder_path']); 
    ?>
    <p><?php _e('For help using this plugin, click the help menu up there a little bit to the right of this and under your name.'); ?></p>

    <input id="sermon_upload_button" type="button" value="Upload Sermon" class="button-secondary" />
    <br />
    <br />

    <form method="post" action="">
      <input type="submit" class="button-primary" name="create-all-posts" value="<?php _e('Post all Sermons') ?>" />
    
      <?php      
        // Outputs messages
        if (isset($_POST) && count($_POST) != 0) {
          $messages = mp3_to_post($mp3ToPostOptions['folder_path']);

          $message_count = count($messages);
          $i = 0;
          while ($i < $message_count) {
            if($messages[$i]['error']) {
              echo '<div class="error">' . $messages[$i]['message'] . '</div>';
            }
            else {
              echo '<div class="updated">' . $messages[$i]['message'] . '</div>';
            }
          $i++;
          }
        }
        // end POST check
      ?>
      <h3><?php _e('Sermons listed by file name and shown with the sermon title.'); ?></h3>
      <ol>
        <?php
        // get files
        $mp3Files = mp3_array($mp3ToPostOptions['folder_path']);
        // list files and details
        foreach ($mp3Files as $file) {
          $filePath = $mp3ToPostOptions['folder_path'].'/'.$file;
          $id3Details = get_ID3($filePath);

          //Get the date from the file name minus the extention
          $file_length = strlen(substr($file, 0, strpos($file, ".")));

          if($file_length >= 8) {
            $file_date = substr($file, 0, 8);

            if(is_numeric($file_date)) {
              $file_year = substr($file_date, 0, 4);
              $file_month = substr($file_date, 4, 2);
              $file_days = substr($file_date, 6, 2);
              $file_date = $file_year . '-' . $file_month . '-' . $file_days;   
            }           
            else
              $file_date = time();

            $file_time = strtotime($file_date);
            
            $display_date = date('F j, Y', $file_time);
          }
          else {
            $display_date = date('F j, Y', time()); 
          }

          echo '<li class="sermon_list_item"><strong>' . $id3Details['title'] . '</strong>
              <input type="submit" class="button-secondary" name="'. $file . '" value="'; 
          _e('Post'); 
          echo '" />
              <ul class="stuffbox postbox">
                
                <li><strong>Speaker:</strong> '.$id3Details['speaker'].'</li>
                <li><strong>Comments:</strong> '.$id3Details['comment'].'</li>
                <li><strong>Publish Date:</strong> '. $display_date .'</li>
                <li><strong>Category:</strong> '.$id3Details['category'].'</li>
                <li><strong>Length:</strong> '.$id3Details['length'].'</li>
                <li><strong>Album:</strong> '.$id3Details['album'].'</li>
                <li><strong>Year:</strong> '.$id3Details['year'].'</li>
                <li><strong>Bitrate:</strong> '.$id3Details['bitrate'].'</li>
                <li><strong>File name:</strong> '.$file.'</li>

              </ul>
          </li>
          ';
        }
        ?>
      </ol>
    </form>
  </div>

    <script type="text/javascript">
      jQuery(".sermon_list_item").click(function () {
          jQuery(this).children('ul').toggle('slow');
      });
    </script>
  <?php 
}
// end mp3_admin

/**
 * Gets the ID3 info of a file
 * 
 * @param $filePath
 * String, base path to the mp3 file
 * 
 * @return array
 * Keyed array with title, comment and category as keys.  
 */
function get_ID3($filePath) {
  // Initialize getID3 engine
  $get_ID3 = new getID3;
  $ThisFileInfo = $get_ID3->analyze($filePath);

  /**
   * Optional: copies data from all subarrays of [tags] into [comments] so
   * metadata is all available in one location for all tag formats
   * meta information is always available under [tags] even if this is not called 
   */
  getid3_lib::CopyTagsToComments($ThisFileInfo);

  $title = $ThisFileInfo['tags']['id3v2']['title'][0];
  $comment = $ThisFileInfo['tags']['id3v2']['comments'][0];
  $category = $ThisFileInfo['tags']['id3v2']['genre'][0];  
  $speaker = $ThisFileInfo['comments_html']['artist'][0];
  $album = $ThisFileInfo['tags']['id3v2']['album'][0];
  $year = $ThisFileInfo['tags']['id3v2']['year'][0];
  $bitrate = $ThisFileInfo['bitrate'];
  $playtime_string = $ThisFileInfo['playtime_string'];

  $details = array(
    'title' => $title,
    'comment' => $comment,
    'category' => $category,
    'speaker' => $speaker,
    'album' => $album,
    'year' => $year,
    'bitrate' => $bitrate,
    'length' => $playtime_string,
  );
  
  return $details;
}

/*
  Adds help menus items for Sermon upload
  */
function add_help_menu() {

  $uploadsDetails = wp_upload_dir();
  $folderPath = $uploadsDetails['basedir'] . '/mp3-to-post';
  $base_path = parse_url($uploadsDetails['baseurl'], PHP_URL_PATH);

  $sermon_help_upload = '<p>' . __('Upload a sermon by clicking the "Upload Sermon" button. To finish the upload, in the media upload box, click "Upload Sermon" or close the box.') . '</p>' .
    '<p>' . __('The sermons will appear in the sermon list area below. Clicking a sermon name will show more details about the sermon. If there is an error with this data, edit the file\'s ID3 information and upload again ensuring you overwrite the file.') . '</p>' .
    '<p>' . __('Click the "Post" button next to the sermon title to post that individual sermon.') . '</p'.
    '<p>' . __('Click the "Post all Sermons" button to post all sermons.') . '</p>';

  $sermon_help_technical_details = '<p>' . __('Files are uploaded to ') . $folderPath . ' and moved on posting to'. $base_path . '.</p>' .
    '<p>' . __('This plugin only searchs for mp3 files. By changing the function mp3_only in mp3-to-post.php one can have other file types or modify the mp3_array function.') . '</p>' .
    '<p>' . __('This plugin is entirely based off of the <a href="http://www.fractured-state.com/2011/09/mp3-to-post-plugin/">mp3-to-post plugin</a> and would not be possible without Paul\'s original efforts. Also a big thanks to James the creator of the <a href="http://www.getid3.org">getID3</a> library.') . '</p>';

  $sermon_help_preparing = '<p>' . __('Files must be named in the format of YYYYMMDD and either an a for AM or p for PM. For example, a sermon preached today in the morning or evening must have a file name like <strong>') . substr(date('Ymda'), 0, -1) . __('</strong> The a or p is optional. This is the date the post will be published. It is okay if it is a long time ago.') . '</p>'  .
    '<p>' . __('This plugin creates posts from the ID3 information in each file. The ID3 information can be edited in most any music program such as iTunes, Windows Media Player, etc. and through the properties/Get Info menu.') . '</p>' .
    '<p>' . __('For a sermon to be correctly posted ensure each sermon has the following ID3 information filled in: <br />
      <strong>Title</strong> is the title of the sermon, example: Put Off Lying and Anger <br />
      <strong>Genre</strong> is Sermon <br />
      <strong>Album</strong> is Woodland Presbyterian Church <br />
      <strong>Artist</strong> is Joseph H. Steele III <br />
      <strong>Comment</strong> is the bible text, example: Ephesians 4:25-27 <br />
        ') . 
    '<p>' . __('If the <strong>genre</strong> is set on the file, that will be turned in to the category. If more than one genre is set only the first one is used.  If the genre is not set, the category on the post is set to the default option.') . '</p>';

  get_current_screen()->add_help_tab( array(
    'id'      => 'sermon',
    'title'   => __('Uploading Sermons'),
    'content' => $sermon_help_upload,
    ) 
  );

  get_current_screen()->add_help_tab( array(
    'id'      => 'sermon2',
    'title'   => __('Preparing Sermons for Upload'),
    'content' => $sermon_help_preparing,
    ) 
  );

  get_current_screen()->add_help_tab( array(
    'id'      => 'sermon3',
    'title'   => __('Technical Details'),
    'content' => $sermon_help_technical_details,
    ) 
  );
}

//sdg