<h2><?php _e( 'General', 'wicked-folders' ); ?></h2>
<table class="form-table">
    <tr>
        <th scope="row">
            <?php _e( 'Enable folders for:', 'wicked-folders' ); ?>
        </th>
        <td>
            <?php if ( $is_pro_active && $attachment_post_type ) : ?>
                <label>
                    <input type="checkbox" name="post_type[]" value="<?php echo $attachment_post_type->name; ?>"<?php if ( in_array( $attachment_post_type->name, $enabled_posts_types ) ) echo ' checked="checked"'; ?>/>
                    <?php echo $attachment_post_type->label; ?>
                </label>
                <br />
            <?php endif; ?>
            <?php foreach ( $post_types as $post_type ) : ?>
                <?php
                    if ( 'attachment' == $post_type->name ) continue;
                    if ( ! $post_type->show_ui ) continue;
                    // Currently no support for custom post types that aren't
                    // registered as top-level menu items
                    if ( is_string( $post_type->show_in_menu ) ) continue;
                ?>
                <label>
                    <input type="checkbox" name="post_type[]" value="<?php echo $post_type->name; ?>"<?php if ( in_array( $post_type->name, $enabled_posts_types ) ) echo ' checked="checked"'; ?>/>
                    <?php echo $post_type->label; ?>
                </label>
                <br />
            <?php endforeach; ?>
            <?php if ( ! $is_pro_active && $attachment_post_type && Wicked_Folders::is_upsell_enabled() ) : ?>
                <label>
                    <input type="checkbox" name="post_type[]" value="<?php echo $attachment_post_type->name; ?>" disabled="disabled" />
                    <?php echo $attachment_post_type->label; ?>
                    <em>(<?php _e( '<a href="https://wickedplugins.com/plugins/wicked-folders/" target="_blank">Upgrade to Wicked Folders Pro</a> to manage media using folders' ); ?>)</em>
                </label>
            <?php endif; ?>
            <p class="description"><?php _e( 'Control which post types folders are enabled for.', 'wicked-folders' ); ?></p>
        </td>
    </tr>
</table>
<h2><?php _e( 'Dynamic Folders', 'wicked-folders' ); ?></h2>
<p><?php _e( 'Dynamic folders are generated on the fly based on your content.  They are useful for finding content based on things like date, author, etc.', 'wicked-folders' ); ?></p>
<table class="form-table">
    <tr>
        <th scope="row">
            <?php _e( 'Enable dynamic folders for:', 'wicked-folders' ); ?>
        </th>
        <td>
            <?php if ( $is_pro_active && $attachment_post_type ) : ?>
                <label>
                    <input type="checkbox" name="dynamic_folder_post_type[]" value="<?php echo $attachment_post_type->name; ?>"<?php if ( in_array( $attachment_post_type->name, $dynamic_folders_enabled_posts_types ) ) echo ' checked="checked"'; ?>/>
                    <?php echo $attachment_post_type->label; ?>
                </label>
                <br />
            <?php endif; ?>
            <?php foreach ( $post_types as $post_type ) : ?>
                <?php
                    if ( 'attachment' == $post_type->name ) continue;
                    if ( ! $post_type->show_ui ) continue;
                    // Currently no support for custom post types that aren't
                    // registered as top-level menu items
                    if ( is_string( $post_type->show_in_menu ) ) continue;
                ?>
                <label>
                    <input type="checkbox" name="dynamic_folder_post_type[]" value="<?php echo $post_type->name; ?>"<?php if ( in_array( $post_type->name, $dynamic_folders_enabled_posts_types ) ) echo ' checked="checked"'; ?>/>
                    <?php echo $post_type->label; ?>
                </label>
                <br />
            <?php endforeach; ?>
            <p class="description"><?php _e( 'Control which post types dynamic folders are enabled for.', 'wicked-folders' ); ?></p>
        </td>
    </tr>
    <?php /* ?>
    <th scope="row">
        <?php _e( 'Tree View', 'wicked-folders' ); ?>
    </th>
    <td>
        <label>
            <input type="checkbox" name="show_folder_contents_in_tree_view" value="1"<?php if ( $show_folder_contents_in_tree_view ) echo ' checked="checked"'; ?>/>
            <?php _e( 'Show folder contents in tree view', 'wicked-folders' ); ?>
        </label>
        <p class="description"><?php _e( "When checked, the tree view will display each folder's items in addition to its sub folders.", 'wicked-folders' ); ?></p>
    </td>
    <?php */ ?>
</table>
<?php if ( $is_pro_active ) : ?>
    <h2><?php _e( 'Wicked Folders Pro', 'wicked-folders' ); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="wicked-folders-pro-license-key"><?php _e( 'License Key', 'wicked-folders' ); ?></label>
            </th>
            <td>
                <input type="text" id="wicked-folders-pro-license-key" class="regular-text" name="wicked_folders_pro_license_key" value="<?php echo $license_key; ?>" />
                <div><?php echo $license_status; ?></div>
            </td>
        </tr>
    </table>
<?php endif; ?>
<p class="submit">
    <input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes' ); ?>" type="submit" />
</p>
