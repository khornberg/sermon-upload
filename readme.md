# Sermon Upload
- Contributors: paulsheldrake, kyle hornberg
- Tags: mp3, podcasting, id3, podcast, podcaster, audio, music, spokenword
- Requires at least: 3.0
- Tested up to: 3.4.1
- Stable tag: 1.1
- License: GPLv3

Creates posts using MP3 ID3 information.

## Description

Does everything that the original [MP3-to-post](http://www.fractured-state.com/2011/09/mp3-to-post-plugin/) plugin does. 
Added ability to change the tags of the files uploaded.
This is customized for my local church.


## Installation

1. Upload the plugin directory to the `/wp-content/plugins/` directory via FTP or `git clone https://github.com/khornberg/sermon-upload`
2. Activate the plugin through the 'Plugins' menu in WordPress


### TODO
- Update to getID3 1.9.5
- Change to bootstrap version
- Code comments
- Plugin options
- Connect to post meta data (e.g. series, tags, etc.)
- Add meta data options to edit post page
- Add support for other audio formats and id3 versions
- Get picture uploads working
- Fix modal UI submit button position (error submitting the forms if in the footer)
- Change "Save all changes" in media modal to "Upload Sermons"
- Override "Save all changes" button to close the medial modal and refesh the page
- Update media loader box with ID3 data

### Changelog 

#### 1.1
* Added ability to edit tags in the browser
* Changed instructions
* Changed UI (modal, bootstrap, buttons)

#### 1.0.2
* Restructured code to the [Wordpress boilderplate plugin](https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate)
* Publish specific post
* Errors/Post information
* File errors

#### 1.0.1
* Changed metadata about plugin.
* Adds menu item and link to Posts instead of Settings.
* Display additional data on admin page.
* Customizes the post for Woodland PCA.

#### 1.0
* Initial commit
