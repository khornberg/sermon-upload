<?php
/**
 * Plugin Name: Sermon Upload
 * Plugin URI: https://github.com/khornberg/sermon-upload
 * Description: Uploads sermons for Woodland Presbyterian Church. Based off of MP3 to Post plugin by Paul  * Sheldrake. Creates posts using ID3 information in MP3 files.
 * Version: 1.0.2
 * Author: Kyle Hornberg
 * Author URI:
 * Author Email:
 * License:
 *
 *  Copyright 2013 Kyle Hornberg
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License, version 2, as
 *   published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
*/

class SermonUpload
{
    /*--------------------------------------------*
     * Constructor
     *--------------------------------------------*/

    /**
     * Initializes the plugin by setting localization, filters, and administration functions.
     */
    public function __construct()
    {
        // Load plugin text domain
        add_action( 'init', array( $this, 'plugin_textdomain' ) );

        // Register admin styles and scripts
        add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

        // Register site styles and scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_scripts' ) );

        // Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
        // register_activation_hook( __FILE__, array( $this, 'activate' ) );
        // register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        // register_uninstall_hook( __FILE__, array( $this, 'uninstall' ) );

        /*
         *
         * The second parameter is the function name located within this class. See the stubs
         * later in the file.
         *
         * For more information:
         * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
         */

        include_once 'getid3/getid3.php';

        self::set_upload_details();
        self::set_folder_path();
        self::set_base_path();

        // Customizes the media uploader
        add_action( 'admin_init', array( $this, 'action_replace_thickbox_text' ) );
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'filter_sermon_upload_pre_upload' ) );
        add_filter( 'wp_handle_upload', array( $this, 'filter_sermon_upload_post_upload' ) );

        // Adds help menu to plugin
        add_action( 'current_screen', array( $this, 'action_add_help_menu' ) );

        add_filter( 'posts_where', array( $this, 'filter_title_like_posts_where') , 10, 2 );

        self::create_folder();

        // Add Sermon Upload to the posts page
        add_action( 'admin_menu', array( $this, 'action_add_menu_page' ) );

        // TODO Display messages NOT Running at a time when messages are there
        //add_action( 'admin_notices', array( $this, 'display_admin_notices') );

    } // end constructor

    /**
     * Fired when the plugin is activated.
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
     */
    public function activate( $network_wide )
    {
        // TODO: Define activation functionality here
    } // end activate

    /**
     * Fired when the plugin is deactivated.
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
     */
    public function deactivate( $network_wide )
    {
        // TODO: Define deactivation functionality here
    } // end deactivate

    /**
     * Fired when the plugin is uninstalled.
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
     */
    public function uninstall( $network_wide )
    {
        // TODO: Define uninstall functionality here
    } // end uninstall

    /**
     * Loads the plugin text domain for translation
     */
    public function plugin_textdomain()
    {
        // TODO: replace "plugin-name-locale" with a unique value for your plugin
        $domain = 'plugin-name-locale';
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

    } // end plugin_textdomain

    /**
     * Registers and enqueues admin-specific styles.
     */
    public function register_admin_styles()
    {
        // TODO: Change 'plugin-name' to the name of your plugin
        wp_enqueue_style( 'sermon-upload-admin-styles', plugins_url( 'sermon-upload/css/admin.css' ) );
        wp_enqueue_style( 'thickbox' );

    } // end register_admin_styles

    /**
     * Registers and enqueues admin-specific JavaScript.
     */
    public function register_admin_scripts()
    {
        // TODO: Change 'plugin-name' to the name of your plugin
        wp_enqueue_script( 'sermon-upload-admin-script', plugins_url( 'sermon-upload/js/admin.js' ) );
        // TODO: This may work without ahving to load jquery wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'media-upload' );
        wp_enqueue_script( 'thickbox' );

    } // end register_admin_scripts

    /**
     * Registers and enqueues plugin-specific styles.
     */
    public function register_plugin_styles()
    {
        // TODO: Change 'plugin-name' to the name of your plugin
        wp_enqueue_style( 'plugin-name-plugin-styles', plugins_url( 'plugin-name/css/display.css' ) );

    } // end register_plugin_styles

    /**
     * Registers and enqueues plugin-specific scripts.
     */
    public function register_plugin_scripts()
    {
        // TODO: Change 'plugin-name' to the name of your plugin
        wp_enqueue_script( 'plugin-name-plugin-script', plugins_url( 'plugin-name/js/display.js' ) );

    } // end register_plugin_scripts

    /*--------------------------------------------*
     * Variables
     *---------------------------------------------*/

    /**
     * Location of folder containing mp3s, sermons, or files
     * Default is mp3-to-post
     */
    protected $mp3FolderName = 'mp3-to-post';


    protected $uploadsDetails = array();
    /**
     * Word Press method returns an array of directions to the upload directory
     *
     */
    public function set_upload_details()
    {
        $uploadsDetails = wp_upload_dir();

        $this->uploadsDetails = $uploadsDetails;
    }

    /**
     * Path to the folder containing mp3s, sermons, or files
     *
     */
    protected $folderPath = "";

    /**
     * Sets the folder where the mp3 sem_get(key)files are located at
     *
     */
    public function set_folder_path()
    {
        // $uploadsDetails = self::get_upload_details();

        $this->folderPath = $this->uploadsDetails['basedir'] . '/' . $this->mp3FolderName;
    }

    /**
     * Base URL path
     *
     */
    protected $base_path = "";

    /**
     * Sets the base path
     * 
     *
     */
    public function set_base_path()
    {
        $this->base_path = parse_url( $this->uploadsDetails['baseurl'], PHP_URL_PATH );
    }

    /**
     * Messages to be displayed
     * @var array
     * 
     * Two dimensions
     * [numeric index]
     * |--[message @string]
     * |--[error @booleans]
     * 
     */
    protected $messages = array();

    /**
     * Sets the messages array
     * 
     * @param message as string
     * @param error as boolean
     * Error default is 'false' for a warning message (yellow)
     * Error value of 'true' results in an error message (red)
     */
    public function set_message( $message, $error = false )
    {
        $this->messages[] = array( "message" => $message, "error" => $error);
    }

    public function clear_messages()
    {
        $this->messages = array();
    }

    /*--------------------------------------------*
     * Core Functions
     *---------------------------------------------*/

    /**
     * Creates a folder based on the path provided
     *
     * @param unknown $folderpath
     */
    public function create_folder()
    {
        // check if directory exists and makes it if it isn't
        if ( !is_dir( $this->folderPath ) ) {
            if ( !mkdir( $this->folderPath, 0777 ) ) {
                $this->set_message('Could not make the folder for you to put your files in, please check your permissions. <br />Attempted to create folder at ' . $this->folderPath, true);
            }
        }
    }

    /**
     * Gives an array of mp3 files to turn in to posts
     *
     * @param unknown $folderPath
     *
     * @return $array
     *  Returns an array of mp3 file names from the directory created by the plugin
     */
    public function mp3_array( $folderPath )
    {
        // scan folders for files and get id3 info
        $mp3Files = array_slice( scandir( $folderPath ), 2 ); // cut out the dots..
        // filter out all the non mp3 files
        $mp3Files = array_filter( $mp3Files, "self::mp3_only" );
        // sort the files
        sort( $mp3Files );

        return $mp3Files;
    }
    /**
     * Takes a string and only returns it if it has '.mp3' in it.
     *
     * @param unknown $string
     *   A string, possibly containing .mp3
     *
     * @return
     *   Returns a string.  Only if it contains '.mp3' or it returns FALSE
     */
    public function mp3_only( $filename )
    {
        $findme = '.mp3';
        $pos = strpos( $filename, $findme );

        if ($pos !== false) {
            return $filename;
        } else {
            return FALSE;
        }
    }

    /**
     * Creates a post from an mp3 file.
     *
     *
     * @param unknown $path
     *  The base path to the folder containing the audio files to convert to posts
     *
     */
    public function audio_to_post( $folderPath )
    {
        // get an array of mp3 files
        $mp3Files = $this->mp3_array( $this->folderPath );

        // check of there are files to process
        if ( count( $mp3Files ) == 0 ) {
            $this->set_message( 'There are no files to process' );
            return;
        }

        // get file name of the sermon to post
        // remove the last _ of the key value so that it can be searched for
        $sermon_file_name = key( $_POST );
        $sermon_file_name = str_replace( '_', '.', $sermon_file_name );

        // loop through all the files and create posts
        if ($sermon_file_name == 'create-all-posts') {
            $limit = count( $mp3Files );
            $sermon_to_post = 0;
        } else {
            $sermon_to_post = array_search( $sermon_file_name, $mp3Files, true );
            if ($sermon_to_post === false) {
                $this->set_message( 'Sermon could not be found in the folder of your uploads. Please check and ensure it is there.' );
                return;
            } elseif ( !is_numeric( $sermon_to_post ) ) {
                $this->set_message( "Key in mp3 files array is not numeric for $mp3Files[$sermon_to_post]." );
                return;
            }
            $limit = $sermon_to_post + 1;

        }
        for ($i=$sermon_to_post; $i < $limit; $i++) {

            // Analyze file and store returned data in $ThisFileInfo
            $filePath = $this->folderPath . '/' . $mp3Files[$i];

            // TODO This may be redundent could just send via POST; security vunerablity?
            $audio = $this->get_ID3($filePath);

            $date = $this->dates($mp3Files[$i]);

            // check if we have a title and a comment
            if ($audio['title']) {

                // check if post exists by search for one with the same title
                $searchArgs = array(
                    'post_title_like' => $audio['title']
                );
                $titleSearchResult = new WP_Query( $searchArgs );

                // If there are no posts with the title of the mp3 then make the post
                if ($titleSearchResult->post_count == 0) {
                    // create basic post with info from ID3 details
                    $my_post = array(
                        'post_title' => $audio['title'],
                        'post_author' => 1,
                        'post_name' => $audio['title'],
                        'post_date' => $date['file_date'],
                        'post_status' => 'publish'
                    );
                    // Insert the post!!
                    $postID = wp_insert_post( $my_post );

                    // If the category/genre is set then update the post
                    if ( !empty( $category ) ) {
                        $category_ID = get_cat_ID( $category );
                        // if a category exists
                        if ($category_ID) {
                            $categories_array = array( $category_ID );
                            wp_set_post_categories( $postID, $categories_array );
                        }
                        // if it doesn't exist then create a new category
                        else {
                            $new_category_ID = wp_create_category( $category );
                            $categories_array = array( $new_category_ID );
                            wp_set_post_categories( $postID, $categories_array );
                        }
                    }

                    // move the file to the right month/date directory in wordpress
                    $wpFileInfo = wp_upload_bits( basename( $filePath ), null, file_get_contents( $filePath ) );
                    // if moved correctly delete the original
                    if ( empty( $wpFileInfo['error'] ) ) {
                        unlink( $filePath );
                    }

                    // add the mp3 file to the post as an attachment
                    $wp_filetype = wp_check_filetype( basename( $wpFileInfo['file'] ), null );
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $wpFileInfo['file'] ) ),
                        'post_content' => '', //TODO Add ID3 info???
                        'post_status' => 'inherit'
                    );
                    $attach_id = wp_insert_attachment( $attachment, $wpFileInfo['file'], $postID );

                    // you must first include the image.php file
                    // for the function wp_generate_attachment_metadata() to work
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $wpFileInfo['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    // add the link to the attachment to the post
                    $attachmentLink = wp_get_attachment_link( $attach_id, 'thumbnail', FALSE, FALSE, 'Download file' );

                    // content of the post to be published
                    $content = '[audio] <br />
                    <p>Text: ' . $audio['comment'] . '</p>
                    <p>Speaker: ' . $audio['artist'] . '</p>
                    <p>Date: ' . $date['display_date'] . '</p><br />' .
                    do_shortcode( '[download label="Download"]' . $wpFileInfo['file'] . '[/download]' );

                    $updatePost = get_post( $postID );
                    $updated_post = array();
                    $updated_post['ID'] = $postID;
                    $updated_post['post_content'] = $updatePost->post_content . $content;
                    wp_update_post( $updated_post );

                    $this->set_message( 'Post created: ' . $audio['title'] );
                } else {
                    $this->set_message( 'Post already exists: ' . $audio['title'], true );
                }
            } else {
                if(!$title) {
                    $this->set_message( 'The title for the file ' . $sermon_file_name . 'was not set. This is needed to create a post with that title.', true );
                }
            }
        }
    }

    /**
     * Determines the date to publish the post
     * 
     * @param unknown $filename
     * String, file name
     * 
     * @return array
     * Keyed array with display_date, file_date 
     */
    public function dates( $filename ){

        //Get the date from the file name minus the extention
        $file_length = strlen( substr( $filename, 0, strpos( $filename, "." ) ) );

        if ($file_length >= 8) {
            $file_date = substr( $filename, 0, 8 );

            if ( is_numeric( $file_date ) ) {
                $file_year = substr( $file_date, 0, 4 );
                $file_month = substr( $file_date, 4, 2 );
                $file_days = substr( $file_date, 6, 2 );
                $file_date = $file_year . '-' . $file_month . '-' . $file_days . ' ' . '06:00:00';
            } else
                $file_date = time();

            $file_time = strtotime( $file_date );

            $display_date = date( 'F j, Y', $file_time );
        } else {
            $display_date = date( 'F j, Y', time() );
            $this->set_message( $title . "'s publish date could not be determined so it will be published today." );
        }

        return array(
            'display_date' => $display_date,
            'file_date' => $file_date,
            );
    }

    /**
     * Gets the ID3 info of a file
     *
     * @param unknown $filePath
     * String, base path to the mp3 file
     *
     * @return array
     * Keyed array with title, comment and category as keys.
     */
    public function get_ID3( $filePath )
    {
        // Initialize getID3 engine
        $get_ID3 = new getID3;
        $ThisFileInfo = $get_ID3->analyze( $filePath );

        /**
         * Optional: copies data from all subarrays of [tags] into [comments] so
         * metadata is all available in one location for all tag formats
         * meta information is always available under [tags] even if this is not called
         */
        getid3_lib::CopyTagsToComments( $ThisFileInfo );

        $tags = array('title' => $ThisFileInfo['filename'], 'comment' => '', 'genre' => '', 'artist' => '', 'album' => '', 'year' => '');

        foreach ($tags as $key => $tag) {
            if( array_key_exists($key, $ThisFileInfo['tags']['id3v2']) ) {
                $value = sanitize_text_field($ThisFileInfo['tags']['id3v2'][$key][0]);
                $tags[$key] = $value;
            }
        }

        $tags['bitrate'] = $ThisFileInfo['bitrate'];
        $tags['length'] = $ThisFileInfo['playtime_string'];

        return $tags;
    }

    /**
     * Display the administrative page
     * 
     */
    public function display_admin_page()
    {
        // Posts the audio files
        if ( isset( $_POST ) && count( $_POST ) != 0 ) {
            $this->audio_to_post( $this->folderPath );
            // self::display_admin_notices();
        }

        $mp3Files = $this->mp3_array( $this->folderPath );

        $audio_details = "";
        // list files and details
        foreach ($mp3Files as $file) {
            $filePath = $this->folderPath.'/'.$file;
            $id3Details = $this->get_ID3( $filePath );
            $date = $this->dates( $file );
            $audio_details .= $this->display_file_details( $id3Details, $file, $date['display_date'] );

        }

        require_once 'views/admin.php';

        self::display_admin_notices();
    }   

    /**
     * Displays administrative warnings and errors through the 'admin_notices' action
     * 
     */
    public function display_admin_notices()
    {
        $message_count = count( $this->messages );
        $i = 0;
        while ($i < $message_count) {
            if ($this->messages[$i]['error']) {
                echo '<div class="error">' . $this->messages[$i]['message'] . '</div>';
            } else {
                echo '<div class="updated">' . $this->messages[$i]['message'] . '</div>';
            }
            $i++;
        }

        // After displaying messages, clear the array
        //$this->clear_messages();
    }

    /**
     * Display file details
     * 
     * @param array $id3Details
     * Array generated by get_ID3()
     *
     * @param string $file
     * File name
     *
     * @param string $display_date
     * Date of the file taken from the file name
     * 
     * @return string
     * Returns a string formated for display
     * 
     */
    public function display_file_details( $id3Details, $file, $display_date )
    {
        $info = '<li class="sermon_list_item"><strong>' . $id3Details['title'] . '</strong>
              <input type="submit" class="button-secondary" name="'. $file . '" value="'; 
        $info .= __('Post'); 
        $info .= '" />
              <ul class="stuffbox postbox">
                
                <li><strong>Speaker:</strong> '.$id3Details['artist'].'</li>
                <li><strong>Comments:</strong> '.$id3Details['comment'].'</li>
                <li><strong>Publish Date:</strong> '. $display_date .'</li>
                <li><strong>Category:</strong> '.$id3Details['genre'].'</li>
                <li><strong>Length:</strong> '.$id3Details['length'].'</li>
                <li><strong>Album:</strong> '.$id3Details['album'].'</li>
                <li><strong>Year:</strong> '.$id3Details['year'].'</li>
                <li><strong>Bitrate:</strong> '.$id3Details['bitrate'].'</li>
                <li><strong>File name:</strong> '.$file.'</li>

              </ul>
          </li>
          ';

        return $info;
    }

    /**
     * NOTE:  Actions are points in the execution of a page or process
     *        lifecycle that WordPress fires.
     *
     *    WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
     *    Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
     *
     */

    public function action_replace_thickbox_text()
    {
        global $pagenow;
        if ('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow) {
            // Now we'll replace the 'Insert into Post Button' inside Thickbox
            add_filter( 'gettext', array( $this, 'filter_replace_thickbox_text' ), 1, 3 );
        }
    }

    /*
          Adds help menus items for Sermon upload
    */
    public function action_add_help_menu()
    {
        // $uploadsDetails = wp_upload_dir();
        // $folderPath = $uploadsDetails['basedir'] . '/mp3-to-post';
        // $base_path = parse_url( $uploadsDetails['baseurl'], PHP_URL_PATH );

        $sermon_help_upload = '<p>' . __( 'Upload a sermon by clicking the "Upload Sermon" button. To finish the upload, in the media upload box, click "Upload Sermon" or close the box.' ) . '</p>' .
            '<p>' . __( 'The sermons will appear in the sermon list area below. Clicking a sermon name will show more details about the sermon. If there is an error with this data, edit the file\'s ID3 information and upload again ensuring you overwrite the file.' ) . '</p>' .
            '<p>' . __( 'Click the "Post" button next to the sermon title to post that individual sermon.' ) . '</p'.
            '<p>' . __( 'Click the "Post all Sermons" button to post all sermons.' ) . '</p>';

            $sermon_help_technical_details = '<p>' . __( 'Files are uploaded to ' ) . $this->folderPath . ' and moved on posting to'. $this->base_path . '.</p>' .
            '<p>' . __( 'This plugin only searchs for mp3 files. By changing the function mp3_only in mp3-to-post.php one can have other file types or modify the mp3_array function.' ) . '</p>' .
            '<p>' . __( 'This plugin is entirely based off of the <a href="http://www.fractured-state.com/2011/09/mp3-to-post-plugin/">mp3-to-post plugin</a> and would not be possible without Paul\'s original efforts. Also a big thanks to James the creator of the <a href="http://www.getid3.org">getID3</a> library.' ) . '</p>';

        $sermon_help_preparing = '<p>' . __( 'Files must be named in the format of YYYYMMDD and either an a for AM or p for PM. For example, a sermon preached today in the morning or evening must have a file name like <strong>' ) . substr( date( 'Ymda' ), 0, -1 ) . __( '</strong> The a or p is optional. This is the date the post will be published. It is okay if it is a long time ago.' ) . '</p>'  .
            '<p>' . __( 'This plugin creates posts from the ID3 information in each file. The ID3 information can be edited in most any music program such as iTunes, Windows Media Player, etc. and through the properties/Get Info menu.' ) . '</p>' .
            '<p>' . __( 'For a sermon to be correctly posted ensure each sermon has the following ID3 information filled in: <br />
          <strong>Title</strong> is the title of the sermon, example: Put Off Lying and Anger <br />
          <strong>Genre</strong> is Sermon <br />
          <strong>Album</strong> is Woodland Presbyterian Church <br />
          <strong>Artist</strong> is Joseph H. Steele III <br />
          <strong>Comment</strong> is the bible text, example: Ephesians 4:25-27 <br />
            ' ) .
            '<p>' . __( 'If the <strong>genre</strong> is set on the file, that will be turned in to the category. If more than one genre is set only the first one is used.  If the genre is not set, the category on the post is set to the default option.' ) . '</p>';

        get_current_screen()->add_help_tab( array(
                'id'      => 'sermon',
                'title'   => __( 'Uploading Sermons' ),
                'content' => $sermon_help_upload,
            )
        );

        get_current_screen()->add_help_tab( array(
                'id'      => 'sermon2',
                'title'   => __( 'Preparing Sermons for Upload' ),
                'content' => $sermon_help_preparing,
            )
        );

        get_current_screen()->add_help_tab( array(
                'id'      => 'sermon3',
                'title'   => __( 'Technical Details' ),
                'content' => $sermon_help_technical_details,
            )
        );
    }

    /**
     * Adds Sermon Upload page to Posts Page on the Admin menu
     *
     */
    public function action_add_menu_page()
    {
        // Create the menu item for users with "upload_files" ability
        add_menu_page( "Sermon Upload", "Sermon Upload", "upload_files", "sermon-upload", array( $this, "display_admin_page" ), plugins_url( 'sermon-upload/img/glyphicons_071_book_admin_menu.png' ), '7' );;
    }



    /**
     * NOTE:  Filters are points of execution in which WordPress modifies data
     *        before saving it or sending it to the browser.
     *
     *    WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
     *    Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
     *
     */

    public function filter_replace_thickbox_text( $translated_text, $text, $domain )
    {
        if ('Insert into Post' == $text) {
            $referer = strpos( wp_get_referer(), 'sermon-upload' );
            if ($referer != '') {
                return __( 'Upload Sermon' );
            }
        }

        return $translated_text;
    }

    /*
      Changes the location of uploads
    */

    public function filter_sermon_upload_pre_upload( $file )
    {
        add_filter( 'upload_dir', array( $this , 'sermon_upload_custom_upload_dir' ) );

        return $file;
    }

    public function filter_sermon_upload_post_upload( $fileinfo )
    {
        remove_filter( 'upload_dir', array( $this , 'sermon_upload_custom_upload_dir' ) );

        return $fileinfo;
    }

    public function sermon_upload_custom_upload_dir( $path )
    {
        if ( !empty( $path['error'] ) ) { return $path; } //error; do nothing.
        // Default set to the mp3FolderName
        $customdir = '/mp3-to-post';
        $path['path']    = str_replace( $path['subdir'], '', $path['path'] ); //remove default subdir (year/month)
        $path['url']   = str_replace( $path['subdir'], '', $path['url'] );
        $path['subdir']  = $customdir;
        $path['path']   .= $customdir;
        $path['url']  .= $customdir;
        if ( !wp_mkdir_p( $path['path'] ) ) {
            return array( 'error' => sprintf( __( 'Unable to create directory %s. Is the parent directory writable by the server?' ), $path['path'] ) );
        }

        return $path;
    }

    /**
     * Adds a select query that lets you search for titles more easily using WP Query
     */
    public function filter_title_like_posts_where( $where, &$wp_query )
    {
        global $wpdb;
        if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' .
                esc_sql( like_escape( $post_title_like ) ) . '%\'';
        }

        return $where;
    }

} // end class

// TODO: Update the instantiation call of your plugin to the name given at the class definition
new SermonUpload();

// $referer = strpos( wp_get_referer(), 'sermon-upload' );
// if ($referer != '')
// {
// Changes the upload folder for the media uploader
// add_filter('wp_handle_upload_prefilter', 'filter_sermon_upload_pre_upload');
// add_filter('wp_handle_upload', 'filter_sermon_upload_post_upload');
// }
