<?php
/**
 * Plugin Name: Sermon Upload
 * Plugin URI: https://github.com/khornberg/sermon-upload
 * Description: Uploads sermons for Woodland Presbyterian Church. Based off of MP3 to Post plugin by Paul  * Sheldrake. Creates posts using ID3 information in MP3 files.
 * Version: 1.1
 * Author: Kyle Hornberg
 * Author URI: https://github.com/khornberg
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

    /**
     * Location of folder containing mp3s, sermons, or files
     * Default is mp3-to-post
     */
    protected $mp3FolderName = 'mp3-to-post';

    protected $uploadsDetails = array();
    
    /**
     * Path to the folder containing mp3s, sermons, or files
     *
     */
    protected $folderPath = "";

    /**
     * Base URL path
     *
     */
    protected $base_path = "";

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
        // add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
        // add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_scripts' ) );

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

        // filter posts
        add_filter( 'posts_where', array( $this, 'filter_title_like_posts_where') , 10, 2 );

        // create folder if it doesn't already exist
        self::create_folder();

        // Add Sermon Upload to the posts page
        add_action( 'admin_menu', array( $this, 'action_add_menu_page' ) );

        // TODO Display messages NOT running at a time when messages are there
        //add_action( 'admin_notices', array( $this, 'display_notices') );

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
        wp_enqueue_style( 'bootstrap', plugins_url( 'sermon-upload/css/bootstrap.min.css' ) );
        wp_enqueue_style( 'bootstrap-datepicker', plugins_url( 'sermon-upload/css/datepicker.css' ) );
        wp_enqueue_style( 'sermon-upload-admin-styles', plugins_url( 'sermon-upload/css/admin.css' ) );
        wp_enqueue_style( 'thickbox' );

    } // end register_admin_styles

    /**
     * Registers and enqueues admin-specific JavaScript.
     */
    public function register_admin_scripts()
    {
        wp_enqueue_script( 'bootstrap', plugins_url( 'sermon-upload/js/bootstrap.min.js' ) );
        wp_enqueue_script( 'bootstrap-datepicker', plugins_url( 'sermon-upload/js/bootstrap-datepicker.js' ) );
        wp_enqueue_script( 'sermon-upload-admin-script', plugins_url( 'sermon-upload/js/admin.js' ) );
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
     * Set/Get Variables
     *---------------------------------------------*/

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
     * Sets the folder where the mp3 sem_get(key)files are located at
     *
     */
    public function set_folder_path()
    {
        // $uploadsDetails = self::get_upload_details();

        $this->folderPath = $this->uploadsDetails['basedir'] . '/' . $this->mp3FolderName;
    }

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
     * Sets the messages array
     *
     * @param message as string
     * @param type as string
     * Type default is '' for a warning message (yellow)
     * Type value of 'error' results in an error message (red)
     * Type value of 'success' results in an success message (green)
     */
    public function set_message( $message, $type = '' )
    {
        $this->messages[] = array( "message" => $message, "type" => $type);
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
                $this->set_message('Could not make the folder for you to put your files in, please check your permissions. <br />Attempted to create folder at ' . $this->folderPath, 'error');
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
    public function audio_to_post()
    {
        // get an array of mp3 files
        $mp3Files = $this->mp3_array( $this->folderPath );

        // check of there are files to process
        if ( count( $mp3Files ) == 0 ) {
            $this->set_message( 'There are no usable files in ' . $this->folderPath . '.' );
            return;
        }

        $post_all = isset( $_POST['create-all-posts'] );
        $sermon_file_name = $_POST['filename'];

        // loop through all the files and create posts
        if ($post_all) {
            $limit = count( $mp3Files );
            $sermon_to_post = 0;
        } else {
            $sermon_to_post = array_search( $sermon_file_name, $mp3Files, true );

            if ($sermon_to_post === false) {
                $this->set_message( 'Sermon could not be found in the folder of your uploads. Please check and ensure it is there.', 'error' );
                return;
            } elseif ( !is_numeric( $sermon_to_post ) ) {
                $this->set_message( 'Key in mp3 files array is not numeric for ' . $mp3Files[$sermon_to_post] . '."', 'error' );
                return;
            }
            $limit = $sermon_to_post + 1;

        }
        for ($i=$sermon_to_post; $i < $limit; $i++) {

            // Analyze file and store returned data in $ThisFileInfo
            $filePath = $this->folderPath . '/' . $mp3Files[$i];

            // TODO This may be redundent could just send via POST; security vunerablity?
            // Sending via post will not write the changes the to the file.
            // May be useful for changing/setting the publish date
            $audio = $this->get_ID3($filePath);

            $date = $this->dates($mp3Files[$i]);

            // check if we have a title
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
                        'post_title'  => $audio['title'],
                        'post_author' => 1,
                        'post_name'   => $audio['title'],
                        'post_date'   => $date['file_date'],
                        'post_status' => 'publish'
                    );
                    // Insert the post!!
                    $postID = wp_insert_post( $my_post );

                    // If the category/genre is set then update the post
                    if ( !empty( $audio['category'] ) ) {
                        $category_ID = get_cat_ID( $audio['category'] );
                        // if a category exists
                        if ($category_ID) {
                            $categories_array = array( $category_ID );
                            wp_set_post_categories( $postID, $categories_array );
                        }
                        // if it doesn't exist then create a new category
                        else {
                            $new_category_ID = wp_create_category( $audio['category'] );
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
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $wpFileInfo['file'] ) ),
                        'post_content'   => '', //TODO Add ID3 info???
                        'post_status'    => 'inherit'
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
                    <p>Text: ' . (isset($audio['comment']) && !is_null($audio['comment'])) ? $audio['comment'] : "" . '</p>
                    <p>Speaker: ' . (is_null($audio['artist']) && !is_null($audio['artist'])) ? $audio['artist'] : "" . '</p>
                    <p>Date: ' . $date['display_date'] . '</p><br />' .
                    do_shortcode( '[download label="Download"]' . $wpFileInfo['file'] . '[/download]' );

                    $updatePost                   = get_post( $postID );
                    $updated_post                 = array();
                    $updated_post['ID']           = $postID;
                    $updated_post['post_content'] = $updatePost->post_content . $content;
                    wp_update_post( $updated_post );

                    $this->set_message( 'Post created: ' . $audio['title'], 'success');
                } else {
                    $this->set_message( 'Post already exists: ' . $audio['title'] );
                }
            } else {
                if (!$title) {
                    $this->set_message( 'The title for the file ' . $sermon_file_name . 'was not set. This is needed to create a post with that title.', 'error' );
                }
            }
        }
    }

    /**
     * Writes data to the file
     *
     * @author James Heinrich <info@getid3.org>
     * Modified for this plugin
     *
     */
    public function write_tags()
    {
        $TaggingFormat = 'UTF-8';

        // require_once 'getid3/getid3.php';
        // Initialize getID3 engine
        $getID3 = new getID3;
        $getID3->setOption(array('encoding'=>$TaggingFormat));

        getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);

        if (isset($_POST['filename'])) {
            $filename = $this->folderPath . '/' . $_POST['filename'];
            $TagFormatsToWrite = array('id3v1', 'id3v2.3');
            $Tagdata = '';

            if (!empty($TagFormatsToWrite)) {
                // echo 'starting to write tag(s)<BR>';

                $tagwriter                 = new getid3_writetags;
                $tagwriter->filename       = $filename;
                $tagwriter->tagformats     = $TagFormatsToWrite;
                // $tagwriter->overwrite_tags = false; //known to be buggy
                $tagwriter->tag_encoding   = $TaggingFormat;
                // if (!empty($_POST['remove_other_tags'])) {
                     $tagwriter->remove_other_tags = false;
                // }

                // $commonkeysarray = array('title', 'artist', 'album', 'year', 'comment');
                $commonkeysarray = array('title', 'artist', 'year', 'album', 'comment', 'genre');
                foreach ($commonkeysarray as $key) {
                    if (!empty($_POST[$key])) {
                        $TagData[strtolower($key)][] = stripslashes($_POST[$key]);
                    }
                }

                // switch ( isset($_FILES['userfile']['error']) ) {
                //     case 1:
                //         $this->set_message( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'error' );
                //         break;
                //     case 2:
                //         $this->set_message( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'error' );
                //         break;
                //     case 3:
                //         $this->set_message( 'The uploaded file was only partially uploaded.', 'error' );
                //         break;
                //     case 4:
                //         $this->set_message( 'No file was uploaded.', 'error' );
                //         break;
                //     case 6:
                //         $this->set_message( 'Missing a temporary folder.', 'error' );
                //         break;
                //     case 7:
                //         $this->set_message( 'Faild to write uploaded file to disk on the server.', 'error' );
                //         break;
                //     case 8:
                //         $this->set_message( 'A PHP extension stopped the file upload.', 'error' );
                //         break;
                // }

                // if (!empty($_FILES['userfile']['tmp_name'])) {
                //     if (in_array('id3v2.4', $tagwriter->tagformats) || in_array('id3v2.3', $tagwriter->tagformats) || in_array('id3v2.2', $tagwriter->tagformats)) {
                //         if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                //             ob_start();
                //             if ($fd = fopen($_FILES['userfile']['tmp_name'], 'rb')) {
                //                 ob_end_clean();
                //                 $APICdata = fread($fd, filesize($_FILES['userfile']['tmp_name']));
                //                 fclose($fd);

                //                 list($APIC_width, $APIC_height, $APIC_imageTypeID) = GetImageSize($_FILES['userfile']['tmp_name']);
                //                 $imagetypes = array(1=>'gif', 2=>'jpeg', 3=>'png', 4=>'jpg');
                //                 if (isset($imagetypes[$APIC_imageTypeID])) {

                //                     $TagData['attached_picture'][0]['data']          = $APICdata;
                //                     $TagData['attached_picture'][0]['picturetypeid'] = $_POST['APICpictureType'];
                //                     $TagData['attached_picture'][0]['description']   = $_FILES['userfile']['name'];
                //                     $TagData['attached_picture'][0]['mime']          = 'image/'.$imagetypes[$APIC_imageTypeID];

                //                 } else {
                //                     $this->set_message( 'Invalid image format (only GIF, JPEG, PNG, JPG) allowed.', true );
                //                 }
                //             } else {
                //                 $errormessage = ob_get_contents();
                //                 ob_end_clean();
                //                 $this->set_message( 'Cannot open '.$_FILES['userfile']['tmp_name'], 'error' );
                //             }
                //         } else {
                //             $this->set_message( !is_uploaded_file($_FILES['userfile']['tmp_name']), 'error' );
                //         }
                //     } else {
                //         $this->set_message( 'WARNING: Can only embed images for ID3v2.' );
                //     }
                // }

                $tagwriter->tag_data = $TagData;

                if ($tagwriter->WriteTags()) {
                    $this->set_message( 'Successfully wrote tags!', 'success' );
                    if (!empty($tagwriter->warnings)) {
                        $this->set_message( 'There were some warnings:'.implode('<BR><BR>', $tagwriter->warnings) );
                    }
                } else {
                    $this->set_message( 'FAILED to write tags: '.implode('<BR><BR>', $tagwriter->errors), 'error' );
                }
            } else {
                $this->set_message( 'WARNING: no tag formats selected for writing - nothing written.', 'error' );
            }

            // renames file
            if ( isset( $_POST['inputFileName'] ) && ($_POST['inputFileName'] !== $_POST['filename']) ) {
            rename( $filename, $this->folderPath . '/' . $_POST['inputFileName'] );
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
    public function dates( $filename )
    {
        //Get the date from the file name minus the extention
        $file_length = strlen( substr( $filename, 0, strpos( $filename, "." ) ) );

        if ($file_length >= 8 && is_numeric($file_length)) {
            $file_date = substr( $filename, 0, 8 );

            if ( is_numeric( $file_date ) ) {
                $file_year  = substr( $file_date, 0, 4 );
                $file_month = substr( $file_date, 4, 2 );
                $file_days  = substr( $file_date, 6, 2 );
                $file_date  = $file_year . '-' . $file_month . '-' . $file_days . ' ' . '06:00:00';
            } else {
                $file_date = time();
            }

            $file_time = strtotime( $file_date );

            if($file_time) {
                $display_date = date( 'F j, Y', $file_time );
            } else {
                $display_date = date( 'F j, Y', time()) ;
                $this->set_message( 'The publish date for ' . $filename . ' could not be determined. It will be published ' . $display_date . ' if you do not change it.' );
            }
        } else {
            $display_date = date( 'F j, Y', time() );
            $file_date = time();
            $this->set_message( 'The publish date for ' . $filename . ' could not be determined. It will be published ' . $display_date . ' if you do not change it.' );
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

        $imageWidth = "";
        $imageHeight = "";
        /**
         * Optional: copies data from all subarrays of [tags] into [comments] so
         * metadata is all available in one location for all tag formats
         * meta information is always available under [tags] even if this is not called
         */
        getid3_lib::CopyTagsToComments( $ThisFileInfo );

        $tags = array('title' => sanitize_text_field( $ThisFileInfo['filename'] ), 'genre' => '', 'artist' => '', 'album' => '', 'year' => '');

        foreach ($tags as $key => $tag) {
            if ( array_key_exists($key, $ThisFileInfo['tags']['id3v2']) ) {
                $value = sanitize_text_field( $ThisFileInfo['tags']['id3v2'][$key][0] );
                $tags[$key] = $value;
            }
        }

        if ( isset($ThisFileInfo['comments_html']['comment']) ) {
            $value = sanitize_text_field( $ThisFileInfo['comments_html']['comment'][0] );
            $tags['comment'] = $value;
        }

        $tags['bitrate'] = sanitize_text_field( $ThisFileInfo['bitrate'] );
        $tags['length'] = sanitize_text_field( $ThisFileInfo['playtime_string'] );

        if ( isset($ThisFileInfo['comments']['picture'][0]) ) {
            $pictureData = $ThisFileInfo['comments']['picture'][0];
            $imageinfo = array();
            $imagechunkcheck = getid3_lib::GetDataImageSize($pictureData['data'], $imageinfo);
            $imageWidth = "150"; //$imagechunkcheck[0];
            $imageHeight = "150"; //$imagechunkcheck[1];
            $tags['image'] = '<img src="data:'.$pictureData['image_mime'].';base64,'.base64_encode($pictureData['data']).'" width="'.$imageWidth.'" height="'.$imageHeight.'" class="img-polaroid">';
        }

        return $tags;
    }

    /**
     * Display the sermon upload page
     *
     */
    public function display_plugin_page()
    {
        // Posts the audio files
        if ( isset( $_POST ) ) {
            if ( isset($_POST['post']) || isset($_POST['create-all-posts']) ) {
                $this->audio_to_post();
            } elseif ( isset($_POST['filename']) ) {
                $this->write_tags();
            }
        }

        $mp3Files = $this->mp3_array( $this->folderPath );

        $audio_details = "";
        $modals ="";
        // list files and details
        foreach ($mp3Files as $file) {
            $filePath       = $this->folderPath.'/'.$file;
            $id3Details     = $this->get_ID3( $filePath );
            $date           = $this->dates( $file );
            $audio_details .= $this->display_file_details( $id3Details, $file, $date['display_date'] );
            $modals        .= $this->create_modal( $id3Details, $file, $date['display_date'] );
        }

        self::display_notices();

        require_once 'views/admin.php';
    }

    /**
     * Displays administrative warnings and errors through the 'admin_notices' action
     *
     */
    public function display_notices()
    {
        $message_count = count( $this->messages );
        $i = 0;
        while ($i < $message_count) {
            $type = ($this->messages[$i]['type'] == '') ? '' : " alert-" . $this->messages[$i]['type'];

            echo '<div class="alert' . $type . '"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $this->messages[$i]['message'] . '</div>';
            $i++;
        }
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
        $displayTitle    = empty($id3Details['title']) ? $file : $id3Details['title'];
        $displaySpeaker  = empty($id3Details['artist']) ? '&nbsp;' : $id3Details['artist'];
        $displayText     = empty($id3Details['comment']) ? '&nbsp;' : $id3Details['comment'];
        $displayCategory = empty($id3Details['genre']) ? '&nbsp;' : $id3Details['genre'];
        $displayAlbum    = empty($id3Details['album']) ? '&nbsp;' : $id3Details['album'];
        $displayYear     = empty($id3Details['year']) ? '&nbsp;' : $id3Details['year'];
        $displayLength   = empty($id3Details['length']) ? '&nbsp;' : $id3Details['length'];
        $displayBitrate  = empty($id3Details['bitrate']) ? '&nbsp;' : $id3Details['bitrate'];
        $displayImage    = empty($id3Details['image']) ? '&nbsp;' : $id3Details['image'];
        $fileUnique      = str_replace('.', '_', str_replace(' ', '_', $file));

        $info = '<li class="sermon_dl_item">
            <form method="post" action="">
            <div class="btn-group">
                <input type="submit" class="btn btn-primary" name="'. $file . '" value="' . __('Post') . '" />
                <input type="hidden" name="filename" value="' . $file . '">
                <input type="hidden" name="post" value="Post">
                <button type="button" id="details-' . $fileUnique . '" class="btn">' . __('Details') . '</button>
                <button type="button" data-toggle="modal" data-target="#edit-' . $fileUnique . '" class="btn">' . __('Edit') . '</button>
            </div>
            <span class="add-on"><b>' . $displayTitle . '</b></span>
            </form>
            <dl id="dl-details-' . $fileUnique . '" class="dl-horizontal">
                <dt>Speaker:      </dt><dd>' . $displaySpeaker . '</dd>
                <dt>Bible Text:   </dt><dd>' . $displayText . '</dd>                
                <dt>Publish Date: </dt><dd>' . $display_date .'</dd>
                <dt>Category:     </dt><dd>' . $displayCategory . '</dd>
                <dt>Album:        </dt><dd>' . $displayAlbum . '</dd>
                <dt>Year:         </dt><dd>' . $displayYear . '</dd>
                <dt>Length:       </dt><dd>' . $displayLength . '</dd>
                <dt>Bitrate:      </dt><dd>' . $displayBitrate . '</dd>
                <dt>File name:    </dt><dd>' . $file . '</dd>
                <dt>Picture:      </dt><dd>' . $displayImage . '</dd>
        </dl>
        </li>';

        return $info;
    }

    /**
     * Create modals
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
    public function create_modal( $id3Details, $file, $display_date )
    {
        $displayTitle    = empty($id3Details['title']) ? $file : $id3Details['title'];
        $displaySpeaker  = empty($id3Details['artist']) ? '' : $id3Details['artist'];
        $displayText     = empty($id3Details['comment']) ? '' : $id3Details['comment'];
        $displayCategory = empty($id3Details['genre']) ? '' : $id3Details['genre'];
        $displayAlbum    = empty($id3Details['album']) ? '' : $id3Details['album'];
        $displayYear     = empty($id3Details['year']) ? '' : $id3Details['year'];
        $displayLength   = empty($id3Details['length']) ? '' : $id3Details['length'];
        $displayBitrate  = empty($id3Details['bitrate']) ? '' : $id3Details['bitrate'];

        $fileUnique      = str_replace('.', '_', str_replace(' ', '_', $file));

        // $ordinals     = array(__('first'),__('second'),__('third'));
        // $seriesSpace  = strpos($displayText, ' ');
        // $seriesString = substr($displayText, 0, $seriesSpace);

        // if( is_numeric($seriesString) || in_array($seriesString, $ordinals) ) {
        //         $seriesSecondSpace  = substr($displayText, strpos($displayText, ' '));
        //         $seriesSecondString = strpos($seriesSecondSpace, ' ');
        //         $displaySeries      = substr($displayText, 0, $seriesSecondString);
        // } else {
            $displaySeries = '';//substr($displayText, strpos($displayText, ' '));          
        // }

        // Picture controls
        $selectPicture = '<input type="file" id="Picture" name="userfile" accept="image/jpeg, image/gif, image/png, image/jpg" class="input-xlarge input-block-level" disabled>';
        $displayPicture = empty($id3Details['image']) ? $selectPicture : $selectPicture . '<br /><br />' . $id3Details['image'];

        $APICtypes = getid3_id3v2::APICPictureTypeLookup('', true);
        $pictureOptions = '';
        foreach ($APICtypes as $key => $value) {
            $pictureOptions .= '<option value="'.htmlentities($key, ENT_QUOTES).'">'.htmlentities($value).'</option>';
        }

        $modal = '
           <!-- Edit Modal -->
            <div id="edit-' . $fileUnique . '" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidder="true" style="display: none">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3 id="modalLabel">Edit Sermon Information</h3>
              </div>
              <div class="modal-body">
                <div class="row-fluid">
                  <div class="span9">
                    <form class="form-horizontal" enctype="multipart/form-data" method="post" action="">
                      <div class="control-group">
                        <label class="control-label" for="inputTitle">Title</label>
                        <div class="controls">
                          <input type="text" id="inputTitle" name="title" placeholder="Title of Sermon" class="input-xlarge input-block-level" value="' . $displayTitle . '">
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputArtist">Speaker</label>
                        <div class="controls">
                          <input type="text" id="inputArtist" name="artist" placeholder="Joseph H. Steele III" class="input-xlarge input-block-level" data-provide="typeahead" data-items="4" data-source="["Joseph H. Steele III", "Dr. Ralph Davis"]" value="' . $displaySpeaker . '">
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputComment">Bible Text</label>
                        <div class="controls">
                          <input type="text" id="inputComment" name="comment" placeholder="Genesis 1:1" class="input-xlarge input-block-level" value="' . $displayText . '">
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputPublishDate">Publish Date</label>
                        <div class="controls">
                          <input type="text" id="inputPublishDate" name="publishdate" data-date="' . date('d/m/Y', time()) . '" date-date-format="dd/mm/yyyy" data-date-autoclose="true" class="input-xlarge input-block-level" value="' . $display_date . '" disabled>
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputGenre">Category</label>
                        <div class="controls">
                          <input type="text" id="inputGenre" name="genre" placeholder="Sermon" class="input-xlarge input-block-level" data-provide="typeahead" data-items="4" data-source="["Sermon", "Guest Speaker"]" value="' . $displayCategory . '">
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputAlbum">Album</label>
                        <div class="controls">
                          <input type="text" id="inputAlbum" name="album" placeholder="Woodland Presbyterian Church" class="input-xlarge input-block-level" data-provide="typeahead" data-items="4" data-source="["Woodland Presbyterian Church"]" value="' . $displayAlbum . '">
                        </div>
                      </div>
                       <div class="control-group">
                        <label class="control-label" for="inputSeries">Series</label>
                        <div class="controls">
                          <input type="text" id="inputSeries" name="series" class="input-xlarge input-block-level" placeholder="Genesis - Not yet implemented" disabled value="' . $displaySeries . '">
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="Year">Year</label>
                        <div class="controls">
                          <input type="text" id="Year" name="year" data-date="' . date('Y', time()) . '" date-date-format="yyyy" data-date-autoclose="true" data-date-startView="decade" class="input-xlarge input-block-level" placeholder="' . date('Y', time()) . '" value="' . $displayYear . '">
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for"Picture"><a href="#" data-toggle="tooltip" title="Must us a gif, png, or jpeg file.">Picture</a></label>
                        <div class="controls">
                            ' . $displayPicture . '
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for"PictureOptions">Picture Type</label>
                        <div class="controls">
                            <select id="PictureOptions" name="APICpictureType" class="input-xlarge input-block-level">' . $pictureOptions . '</select>
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputLength">Length</label>
                        <div class="controls">
                          <span class="input-xlarge uneditable-input input-block-level" id="inputLength">' . $displayLength . ' </span>
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputBitrate">Bitrate</label>
                        <div class="controls">
                          <span class="input-xlarge uneditable-input input-block-level" id="inputBitrate">' . $displayBitrate . ' </span>
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="inputFileName">File name</label>
                        <div class="controls">
                          <input type="text" class="input-xlarge uneditable-input input-block-level" id="inputFileName" name="inputFileName" placeholder="24-10-1985 Awesome Sermon" value="' . $file . '">
                        </div>
                      </div>
                      <input type="hidden" name="filename" value="' . $file . '">
                      <input type="submit" class="btn btn-primary pull-right" name="update" value="Update File" /> 
                    </form>
                  </div>
                </div>
              </div>

              <!--<div class="modal-footer">
                <button class="btn" data-dismiss="modal" aria-hidden="true">Save</button>
             
              </div>-->
 
            </div>';

        return $modal;
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

        $sermon_help_upload = '<p>' . __( 'Upload a sermon by clicking the "Upload Sermon" button. To finish the upload, in the media upload box, click "Upload Sermon" or close the dialog box.' ) . '</p>' .
            '<p>' . __( 'The sermons will appear in the sermon list area below this help area.') . '</p>' .
            '<p>' . __( 'There are three buttons next to each sermon.' ) . '</p>' .
            '<p>' . __( 'Click the "Post" button to post the individual sermon.' ) . '</p>'.
            '<p>' . __( 'Click the "Details" button view the details (ID3 information) about the individual sermon.' ) . '</p>'.
            '<p>' . __( 'Click the "Edit" button to edit the information about the individual sermon.' ) . '</p>'.
            '<p>' . __( 'Click the "Post all Sermons" button to post all sermons.' ) . '</p>';

            $sermon_help_technical_details = '<p>' . __( 'Files are uploaded to ' ) . $this->folderPath . ' and moved on posting to'. $this->base_path . '.</p>' .
            '<p>' . __( 'This plugin only searchs for mp3 files. By changing the function mp3_only in mp3-to-post.php one can have other file types or modify the mp3_array function.' ) . '</p>' .
            '<p>' . __( 'This plugin is entirely based off of the <a href="http://www.fractured-state.com/2011/09/mp3-to-post-plugin/">mp3-to-post plugin</a> and would not be possible without Paul\'s original efforts. Also a big thanks to James the creator of the <a href="http://www.getid3.org">getID3</a> library.' ) . '</p>';

            $sermon_help_preparing = '<p>' . __( 'Files must be named in the format of YYYYMMDD and either an a for AM or p for PM. For example, a sermon preached today in the morning or evening must have a file name like <strong>' ) . substr( date( 'Ymda' ), 0, -1 ) . __( '</strong> The a or p is optional. This is the date the post will be published. It is okay if it is a long time ago.' ) . '</p>'  .
            '<p>' . __( 'For a sermon to be correctly posted ensure each sermon has the following ID3 information filled in: <br />
                <strong>Title</strong> is the title of the sermon, example: Put Off Lying and Anger <br />
                <strong>Genre</strong> is Sermon <br />
                <strong>Comment</strong> is the bible text, example: Ephesians 4:25-27 <br />' ) .
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
        add_menu_page( "Sermon Upload", "Sermon Upload", "upload_files", "sermon-upload", array( $this, "display_plugin_page" ), plugins_url( 'sermon-upload/img/glyphicons_071_book_admin_menu.png' ), '7' );;
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
        $customdir      = '/mp3-to-post';
        $path['path']   = str_replace( $path['subdir'], '', $path['path'] ); //remove default subdir (year/month)
        $path['url']    = str_replace( $path['subdir'], '', $path['url'] );
        $path['subdir'] = $customdir;
        $path['path']  .= $customdir;
        $path['url']   .= $customdir;
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

new SermonUpload();

//sdg
