<?php
/*
  Plugin Name: Sermon Upload
  Plugin URI: https://github.com/khornberg/sermon-upload
  Description: Uploads sermons for Woodland Presbyterian Church. Based off of MP3 to Post plugin by Paul Sheldrake. Creates posts using ID3 information in MP3 files.
  Author: Kyle Hornberg
  Version: 1.0.2
  Author URI:
 */

/**
 * Disable download_shortcode's url rewritting
 * */
// add_filter( 'fds_rewrite_urls', '__return_false' );


/**
 * Variables, store them in the options array to grab as necessary
 */
$uploadsDetails = wp_upload_dir();
$mp3FolderName = 'mp3-to-post';
$folderPath = $uploadsDetails['basedir'] . '/' . $mp3FolderName;
$base_path = parse_url($uploadsDetails['baseurl'], PHP_URL_PATH);

$mp3ToPostOptions = array(
  'folder_name' => $mp3FolderName,
  'folder_path' => $folderPath,
  'base_url_path' => $base_path,
 );
update_option('mp3-to-post', serialize($mp3ToPostOptions));

//TODO mp3ToPostOptions accessed from mp3-to-post-admin

require_once 'mp3-to-post-admin.php';

/* add the menu item */
add_action('admin_menu', 'mp3_admin_actions');

/* create the menu item and link to to an admin function */
function mp3_admin_actions()
{
  add_posts_page("Sermon Upload", "Sermon Upload", "upload_files", "sermon-upload", "mp3_admin");
}


/*
  Set up the media uploader
  http://www.webmaster-source.com/2010/01/08/using-the-wordpress-uploader-in-your-plugin-or-theme/
*/

/* load scripts and styles only for the sermon upload page */
if (isset($_GET['page']) && $_GET['page'] == 'sermon-upload') {
  // Adds the media uploader to the Sermon Upload page
  add_action('admin_print_scripts', 'mp3_admin_scripts');
  add_action('admin_print_styles', 'mp3_admin_styles');
}

/* add scripts for the file uploader */
function mp3_admin_scripts()
{
  wp_enqueue_script('media-upload');
  wp_enqueue_script('thickbox');
  wp_register_script('mp3-upload', WP_PLUGIN_URL.'/mp3-to-post/mp3-script.js', array('jquery','media-upload','thickbox'));
  wp_enqueue_script('mp3-upload');
}

/* add style for the file uploader */
function mp3_admin_styles()
{
  wp_enqueue_style('thickbox');
}


/*
  Customize the media uploader
  Replaces the "Insert into Post" text with "Upload Sermon"
  http://wp.tutsplus.com/tutorials/creative-coding/how-to-integrate-the-wordpress-media-uploader-in-theme-and-plugin-options/
*/

function sermon_upload_options_setup()
{
  global $pagenow;
  if ('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow) {
    // Now we'll replace the 'Insert into Post Button' inside Thickbox
    add_filter( 'gettext', 'replace_thickbox_text', 1, 3 );
  }
}

function replace_thickbox_text($translated_text, $text, $domain)
{
  if ('Insert into Post' == $text) {
    $referer = strpos( wp_get_referer(), 'sermon-upload' );
    if ($referer != '') {
      return __( 'Upload Sermon' );
    }
  }

  return $translated_text;
}

// Customizes the media uploader
add_action( 'admin_init', 'sermon_upload_options_setup' );

/*
  Changes the location of uploads
*/

function sermon_upload_pre_upload($file)
{
  add_filter('upload_dir', 'sermon_upload_custom_upload_dir');

  return $file;
}

function sermon_upload_post_upload($fileinfo)
{
  remove_filter('upload_dir', 'sermon_upload_custom_upload_dir');

  return $fileinfo;
}

function sermon_upload_custom_upload_dir($path)
{
  if (!empty($path['error'])) { return $path; } //error; do nothing.
  // Default set to the mp3FolderName
  $customdir = '/mp3-to-post';
  $path['path']    = str_replace($path['subdir'], '', $path['path']); //remove default subdir (year/month)
  $path['url']   = str_replace($path['subdir'], '', $path['url']);
  $path['subdir']  = $customdir;
  $path['path']   .= $customdir;
  $path['url']  .= $customdir;
  if (!wp_mkdir_p( $path['path'])) {
    return array('error' => sprintf(__('Unable to create directory %s. Is the parent directory writable by the server?'), $path['path']));
  }

  return $path;
}

  $referer = strpos( wp_get_referer(), 'sermon-upload' );
  if ($referer != '') {
    // Changes the upload folder for the media uploader
    add_filter('wp_handle_upload_prefilter', 'sermon_upload_pre_upload');
    add_filter('wp_handle_upload', 'sermon_upload_post_upload');
  }

/**
 * Adds a select query that lets you search for titles more easily using WP Query
 */
function title_like_posts_where($where, &$wp_query)
{
  global $wpdb;
  if ($post_title_like = $wp_query->get('post_title_like')) {
    $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' .
      esc_sql(like_escape($post_title_like)) . '%\'';
  }

  return $where;
}
add_filter('posts_where', 'title_like_posts_where', 10, 2);

/*
  Adds help menu to plugin
*/
add_action('current_screen', 'add_help_menu');

/**
 * Takes a string and only returns it if it has '.mp3' in it.
 *
 * @param $string
 *   A string, possibly containing .mp3
 *
 * @return
 *   Returns a string.  Only if it contains '.mp3' or it returns FALSE
 */
function mp3_only($filename)
{
  $findme = '.mp3';
  $pos = strpos($filename, $findme);

  if ($pos !== false) {
    return $filename;
  } else {
    return FALSE;
  }
}

/**
 * Creates a post from an mp3 file.
 *
 * @param $limit
 *  Limits the number of items created at one time.  Use an intager
 *
 * @param $path
 *  The base path to the folder containing the mp3s to convert to posts
 *
 * @return $array
 *   Will provide an array of messages
 */
function mp3_to_post($folderPath)
{
  $messages = array();

  // get file name of the sermon to post
  // remove the last _ of the key value so that it can be searched for
  $sermon_file_name = key($_POST);
  $sermon_file_name = str_replace('_', '.', $sermon_file_name);

  // get an array of mp3 files
  $mp3Files = mp3_array($folderPath);

  // check of there are files to process
  if (count($mp3Files) == 0) {
    $messages[] = array( "message" => 'There are no files to process' );

    return $messages;
  }

  // Initialize getID3 engine
  $getID3 = new getID3;

  // loop through all the files and create posts
  if ($sermon_file_name == 'create-all-posts') {
    $limit = count($mp3Files);
    $sermon_to_post = 0;
  } else {
    $sermon_to_post = array_search($sermon_file_name, $mp3Files, true);
    if ($sermon_to_post === false) {
      $messages[] = array('message' => 'Sermon could not be found in the folder of your uploads. Please check and ensure it is there.', );

      return $messages;
    } elseif (!is_numeric($sermon_to_post)) {
      $messages[] = array('message' => "Key in mp3 files array is not numeric for $mp3Files[$sermon_to_post].", );

      return $messages;
    }
    $limit = $sermon_to_post + 1;

  }
  for ($i=$sermon_to_post; $i < $limit; $i++) {

    // Analyze file and store returned data in $ThisFileInfo
    $filePath = $folderPath . '/' . $mp3Files[$i];
    $ThisFileInfo = $getID3->analyze($filePath);

    /*
      Optional: copies data from all subarrays of [tags] into [comments] so
      metadata is all available in one location for all tag formats
      metainformation is always available under [tags] even if this is not called
     */
    getid3_lib::CopyTagsToComments($ThisFileInfo);
    $title = sanitize_text_field($ThisFileInfo['tags']['id3v2']['title'][0]);
    $comment = sanitize_text_field($ThisFileInfo['tags']['id3v2']['comments'][0]);
    $category = sanitize_text_field($ThisFileInfo['tags']['id3v2']['genre'][0]);
    $speaker = sanitize_text_field($ThisFileInfo['comments_html']['artist'][0]);
    // $album = $ThisFileInfo['tags']['id3v2']['album'][0];
    // $year = $ThisFileInfo['tags']['id3v2']['year'][0];
    $bitrate = $ThisFileInfo['bitrate'];
    $playtime_string = $ThisFileInfo['playtime_string'];

    //Get the date from the file name minus the extention
    $file_length = strlen(substr($mp3Files[$i], 0, strpos($mp3Files[$i], ".")));

    if ($file_length >= 8) {
      $file_date = substr($mp3Files[$i], 0, 8);

      if (is_numeric($file_date)) {
        $file_year = substr($file_date, 0, 4);
        $file_month = substr($file_date, 4, 2);
        $file_days = substr($file_date, 6, 2);
        $file_date = $file_year . '-' . $file_month . '-' . $file_days . ' ' . '06:00:00';
      } else
        $file_date = time();

      $file_time = strtotime($file_date);

      $display_date = date('F j, Y', $file_time);
    } else {
      $display_date = date('F j, Y', time());
      $messages[] = array('message' => $title . "'s publish date could not be determined so it was published today. <br />", );
    }

    // check if we have a title and a comment
    if ($title && $comment) {

      // check if post exists by search for one with the same title
      $searchArgs = array(
        'post_title_like' => $title
      );
      $titleSearchResult = new WP_Query($searchArgs);

      // If there are no posts with the title of the mp3 then make the post
      if ($titleSearchResult->post_count == 0) {
        // create basic post with info from ID3 details
        $my_post = array(
          'post_title' => $title,
          'post_author' => 1,
          'post_name' => $title,
          'post_date' => $file_date,
          'post_status' => 'publish'
        );
        // Insert the post!!
        $postID = wp_insert_post($my_post);

        // If the category/genre is set then update the post
        if (!empty($category)) {
          $category_ID = get_cat_ID($category);
          // if a category exists
          if ($category_ID) {
            $categories_array = array($category_ID);
            wp_set_post_categories($postID, $categories_array);
          }
          // if it doesn't exist then create a new category
          else {
            $new_category_ID = wp_create_category($category);
            $categories_array = array($new_category_ID);
            wp_set_post_categories($postID, $categories_array);
          }
        }

        // move the file to the right month/date directory in wordpress
        $wpFileInfo = wp_upload_bits(basename($filePath), null, file_get_contents($filePath));
        // if moved correctly delete the original
        if (empty($wpFileInfo['error'])) {
          unlink($filePath);
        }

        // add the mp3 file to the post as an attachment
        $wp_filetype = wp_check_filetype(basename($wpFileInfo['file']), null);
        $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
         'post_title' => preg_replace('/\.[^.]+$/', '', basename($wpFileInfo['file'])),
         'post_content' => '', //Add ID3 info???
         'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $wpFileInfo['file'], $postID);

        // you must first include the image.php file
        // for the function wp_generate_attachment_metadata() to work
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $wpFileInfo['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // add the link to the attachment to the post
        $attachmentLink = wp_get_attachment_link($attach_id, 'thumbnail', FALSE, FALSE, 'Download file');

        /*
          Template for content of post

          Bible Reference
          Speaker: speaker
          Date: date
          Length: length

          AUDIO PLAYER

          Download link
        */
        $content = '[audio] <br />
        <p>Text: ' . $comment . '</p>
        <p>Speaker: ' . $speaker . '</p>
        <p>Date: ' . $display_date . '</p><br />' .
        do_shortcode( '[download label="Download"]' . $wpFileInfo['file'] . '[/download]' );

        $updatePost = get_post($postID);
        $updated_post = array();
        $updated_post['ID'] = $postID;
        $updated_post['post_content'] = $updatePost->post_content . $content;
        wp_update_post($updated_post);

        $messages[] = array( "message" =>  $messages['message'] . 'Post created: ' . $title );
      } else {
        $messages[] = array(
          "message" => 'Post already exists: ' . $title,
          "error" => true,
         );
      }
    } else {
      $messages[] = array(
        "message" => 'Either the title or comments are not set in the ID3 information. Make sure they are both set for v1 and v2.',
        "error" => true,
        );
    }

    }
  // return the messages
  return $messages;
  }

/**
 * Creates a folder based on the path provided
 *
 * @param $folderpath
 */
function create_folder($folderPath)
{
  // check if directory exists and makes it if it isn't
  if (!is_dir($folderPath)) {
    if (!mkdir($folderPath, 0777)) {
      echo '<p><strong>Couldnt make the folder for you to put your files in, please check your permissions.</strong></p>';
    }
  }
}

/**
 * Gives an array of mp3 files to turn in to posts
 *
 * @param $folderPath
 *
 * @return $array
 *  Returns an array of mp3 file names from the directory created by the plugin
 */
function mp3_array($folderPath)
{
  // scan folders for files and get id3 info
  $mp3Files = array_slice(scandir($folderPath), 2); // cut out the dots..
  // filter out all the non mp3 files
  $mp3Files = array_filter($mp3Files, "mp3_only");
  // sort the files
  sort($mp3Files);

  return $mp3Files;
}

//sdg
