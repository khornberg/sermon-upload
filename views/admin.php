<div class="wrap">
    <h2>Sermon Upload</h2>

    <p><?php _e('For help using this plugin, click the help menu up there a little bit to the right of this and under your name.'); ?></p>

    <input id="sermon_upload_button" type="button" value="Upload Sermon" class="button-secondary" />
    <br />
    <br />

    <form method="post" action="">
      <input type="submit" class="button-primary" name="create-all-posts" value="<?php _e('Post all Sermons') ?>" />
      <h3><?php _e('Sermons listed by file name and shown with the sermon title.'); ?></h3>
      <ol>
        <?php echo $audio_details; ?>
      </ol>
    </form>
  </div>
