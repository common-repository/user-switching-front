<?php
if ( ! function_exists( 'esc_html_e' ) ) {
    die ( 'No !' );
}
?>
<input class="usf-inputs adminbar-input" placeholder="<?php esc_html_e( 'Search Users', 'user-switching-front' ); ?>" name="s_users" id="admin-bar-search-users" type="text" value="" maxlength="50">
<label for="admin-bar-search-users" class="usf-inputs screen-reader-text"><?php esc_html_e( 'Search Users', 'user-switching-front' ); ?></label>
<div id="s_users"></div>
