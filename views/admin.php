<div class="wrap">
    <h2>Sermon Upload</h2>

    <p><?php _e('For help using this plugin, click the help menu up there a little bit to the right of this and under your name.'); ?></p>

    <input id="sermon_upload_button" type="button" value="Upload Sermon(s)" class="btn" />
    <br />
    <br />

    <form method="post" action="">
      <?php
        if($audio_details !== "") {
      ?>
          <input type="submit" class="btn btn-primary" name="create-all-posts" value="<?php _e('Post all Sermons') ?>" />
          <h4><?php _e('Sermons listed by file name and shown with the sermon title.'); ?></h4>
      <?php
        } else {
      ?>
          <p class="well well-small">No sermons to post.</p>
      <?php
        }
      ?>
      <ul class="unstyled">
        <?php echo $audio_details; ?>
      </ul>
    </form>
  </div>

<?php echo $modals; ?>