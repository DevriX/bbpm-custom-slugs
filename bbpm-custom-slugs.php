<?php
/*
Plugin Name: Custom Slugs for bbPress Messages
Plugin URI: https://github.com/elhardoum/bbpm-custom-slugs
Description: Custom Slugs for bbPress Messages WordPress Plugin. <em>(Visit bbPress Messages settings page to adjust slugs)</em>
Author: Samuel Elh
Version: 0.1
Author URI: https://samelh.com
Donate link: https://paypal.me/samelh
*/

add_action('bbpm_init_class', function($class){
    $bcs = bbpm_custom_slugs();

    if ( !empty($bcs['slugs']) )
        $bcs['slugs'] = array_filter($bcs['slugs'], 'trim');

    if ( !$bcs['slugs'] )
        return;

    foreach ( $bcs['slugs'] as $p=>$v ) {
        $class->bases[$p] = $v;
    }
});

function bbpm_custom_slugs() {
    return array(
        'slugs' => wp_parse_args(bbpm_custom_slugs_entries(), array(
            'messages_base' => null,
            'page_base' => null,
            'settings_base' => null,
            'new' => null,
            'with' => null
        )),
        'about' => array(
            'messages_base' => __('Messages (/messages)'),
            'page_base' => __('Pagination (/messages/page/x)'),
            'settings_base' => __('Chat settings (/messages/id/settings/)'),
            'new' => __('New message (/messages/new/)'),
            'with' => __('Redirect to chat (/messages/with/user_data/)')
        )
    );
}

function bbpm_custom_slugs_entries($settings=null) {
    global $bbpm_custom_slugs_entries;

    if ( !is_null($settings) ) {
        $bbpm_custom_slugs_entries = $settings;
    }

    if ( isset($bbpm_custom_slugs_entries) )
        return $bbpm_custom_slugs_entries;

    $bbpm_custom_slugs_entries = (array) get_option('bbpm_custom_slugs_entries', null);

    return $bbpm_custom_slugs_entries;
}

add_action('bbpm_admin_settings_before_advanced', 'bbpm_custom_slugs_settings_field');

function bbpm_custom_slugs_settings_field() {
    global $bbpm_bases;
    $b = $bbpm_bases;

    if ( !$b || !is_array($b) )
        return;

    $bcs = bbpm_custom_slugs();

    foreach ( $b as $p=>$v ) {
        if ( !in_array($p, array_keys($bcs['slugs'])) ) {
            unset($b[$p]);
        }
    }

    if ( !$b )
        return;

    ?>

    <div class="postbox">
        <h3 class="hndle"><span><?php _e('Custom Slugs'); ?></span></h3>
        <div class="inside">
            <p>
                <?php foreach ( $bcs['slugs'] as $i=>$v ) : ?>
                    <label>
                        <?php echo !empty($bcs['about'][$i]) ? $bcs['about'][$i] : $i; ?>:<br/>
                        <input type="text" name="bcs_<?php echo $i; ?>" value="<?php echo trim($v) ? esc_attr($v) : $b[$i]; ?>" />
                    </label><br/>
                <?php endforeach; ?>
            </p>
        </div>
    </div>
    <?php
}

add_action('bbpm_update_admin_settings', 'bbpm_custom_slugs_update_settings');

function bbpm_custom_slugs_update_settings() {
    $bcs = bbpm_custom_slugs();

    if ( empty($bcs['slugs']) || !is_array($bcs['slugs']) )
        return;

    global $bbpm_bases;
    $opt = array();

    foreach ( array_keys($bcs['slugs']) as $prop ) {
        if ( isset($_POST["bcs_{$prop}"]) && sanitize_text_field($_POST["bcs_{$prop}"]) ) {
            $opt[$prop] = sanitize_text_field($_POST["bcs_{$prop}"]);

            if (!empty($bbpm_bases[$prop]) && $opt[$prop] == $bbpm_bases[$prop]) {
                unset($opt[$prop]);
            }
        }
    }

    if ( $opt ) {
        update_option('bbpm_custom_slugs_entries', $opt);
        bbpm_custom_slugs_entries($opt);
    } else {
        delete_option('bbpm_custom_slugs_entries');
        bbpm_custom_slugs_entries(array());
    }

    // flush rewrite rules
    delete_option('rewrite_rules');
}