<?php 

class Network_frontpage_class {

    private $tags_list_table = '';
    private $tags_table = '';
    private $prefs_table = '';

    public function __construct(){

        global $wpdb;

        $this->tags_list_table = $wpdb->base_prefix . 'net_front_tags_list';
        $this->tags_table = $wpdb->base_prefix . 'net_front_tags';
        $this->prefs_table = $wpdb->base_prefix . 'net_front_prefs';
    }

    public function install(){

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // create tables

        $sql = "CREATE TABLE " . $this->prefs_table . ' (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            blog_id bigint(20),
            pref tinyint(1),
            PRIMARY KEY (id),
            UNIQUE KEY id (id),
            UNIQUE KEY blog_id (blog_id),
            FOREIGN KEY (blog_id) REFERENCES ' . $wpdb->base_prefix . 'blogs(blog_id) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8';

        dbDelta($sql);

        $sql = "CREATE TABLE " . $this->tags_list_table . ' (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tag_name varchar(40),
            PRIMARY KEY (id),
            UNIQUE KEY id (id),
            UNIQUE KEY tag_name (tag_name)
            ) DEFAULT CHARSET=utf8';

        dbDelta($sql);

        $sql = "CREATE TABLE " . $this->tags_table . ' (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tag_id bigint(20) UNSIGNED,
            blog_id bigint(20),
            PRIMARY KEY (id),
            UNIQUE KEY id (id),
            FOREIGN KEY (tag_id) REFERENCES ' . $this->tags_list_table . '(id) ON DELETE CASCADE,
            FOREIGN KEY (blog_id) REFERENCES ' . $wpdb->base_prefix . 'blogs(blog_id) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8';

        dbDelta($sql);

        // insert some basic tags

        $tags = array(
            "Personal",
            "Awesome",
            "Code",
            "Cyberlaw",
            "Students"
            );

        foreach ($tags as $tag){
            $wpdb->query(
                $wpdb->prepare("INSERT INTO " . $this->tags_list_table . " (tag_name) VALUES (%s)", array($tag))
            );
        }

    }

    public function custom_page(){

        global $current_blog;

        // check if submitted any data

        if (isset($_POST['nf-submit'])){
            $new_tags = $_POST['tags'];
            $new_pref = ($_POST['network_frontpage_opt_radio'] == '1');
            $this->update_tags($new_tags, $current_blog->blog_id);
            $this->update_preference($new_pref, $current_blog->blog_id);
        }
        
        // Get Data

        $json_tags = json_encode($this->get_tag_list());

        $user_pref = $this->get_preference($current_blog->blog_id);

        $user_tags = $this->get_tags($current_blog->blog_id);

        // Create tags HTML
        $user_tags_html = '';
        foreach($user_tags as $user_tag) {
            $user_tags_html .= ('<li>' . __($user_tag) . '</li>');
        }

        // Display the page

        wp_enqueue_style(
            'network-frontpage-style.css',
            plugins_url('/css/style.css',dirname(__FILE__))
        );
        wp_enqueue_style(
            'tag-it.css',
            plugins_url('/lib/tag-it/jquery.tagit.css',dirname(__FILE__))
        );
        wp_enqueue_style(
            'tag-it-theme.css',
            plugins_url('/lib/tag-it/tagit.ui-zendesk.css',dirname(__FILE__))
        );
        wp_enqueue_script(
            'tag-it.min.js',
            plugins_url('/lib/tag-it/tag-it.min.js',dirname(__FILE__)),
            array(
                'jquery',
                'jquery-ui-core',
                'jquery-ui-widget',
                'jquery-ui-autocomplete'
            )
        );
        wp_enqueue_script(
            'network-frontpage-script.js',
            plugins_url('/js/script.js',dirname(__FILE__))
        );
        
        // START HTML DOCUMENT
        ?>

            <h2><?php _e('Front Page Options'); ?></h2>
            <form method='POST' action=''>
                <div class='network_frontpage_opt'>
                    <h3><?php _e("Opt-in to the network's frontpage?"); ?></h3>
                    <input name='network_frontpage_opt_radio' id='network_frontpage_opt_radio_yes' value='1' <?php if ($user_pref) echo("checked='checked'"); ?> type='radio' />
                    <label for='network_frontpage_opt_radio_yes'><?php _e("Yes"); ?></label>
                    <br />
                    <input name='network_frontpage_opt_radio' selected='selected' id='network_frontpage_opt_radio_no' value='0' <?php if (!$user_pref) echo("checked='checked'"); ?>  type='radio' />
                    <label for='network_frontpage_opt_radio_no'><?php _e("No"); ?></label>
                </div>

                <div>
                    <h3>Tags that Describe your Blog:</h3>
                    <ul id='network-frontpage-tag'>
                        <?php echo($user_tags_html); ?>
                    </ul>
                </div>

                <div>
                    <input type="submit" name="nf-submit" id="nf-submit" class="button-primary" value="<?php _e('Update Options'); ?>"  />
                </div> 

            </form>

            <script type='text/javascript'>
                window.nf_tags = <?php echo($json_tags); ?>;
            </script>

        <?php
        // END HTML DOCUMENT

    }

    public function update_tags($tags, $blog_id){

        global $wpdb;

        $wpdb->query(
            $wpdb->prepare("DELETE FROM " . $this->tags_table . " WHERE blog_id = %d", array($blog_id))
        );

        foreach ($tags as $tag){

            $tag_id = $this->get_tag_id($tag);

            if (!is_null($tag_id)){

                $wpdb->query(
                    $wpdb->prepare("INSERT INTO " . $this->tags_table . " (tag_id, blog_id) VALUES (%d, %d)", array($tag_id, $blog_id))
                );

            }

        }

    }

    public function update_preference($preference, $blog_id){

        global $wpdb;

        $wpdb->query(
            $wpdb->prepare("INSERT INTO " . $this->prefs_table . " (blog_id, pref) VALUES (%d, %d) ON DUPLICATE KEY UPDATE pref = %d", array($blog_id, (int)$preference, (int)$preference))
        );

    }

    public function get_tags($blog_id){

        global $wpdb;

        return $wpdb->get_col(
            $wpdb->prepare('SELECT a.tag_name FROM ' . $this->tags_table . ' t INNER JOIN ' . $this->tags_list_table . ' a ON a.id = t.tag_id WHERE t.blog_id = %d', [$blog_id])
        );        
    }

    public function get_preference($blog_id){

        global $wpdb;

        $pref_int = $wpdb->get_var(
            $wpdb->prepare('SELECT pref FROM ' . $this->prefs_table .' WHERE blog_id = %d', [$blog_id])
        );

        return $pref_int == 1;

    }

    public function get_tag_id($name){
        global $wpdb;
        return $wpdb->get_var(
            $wpdb->prepare('SELECT id FROM ' . $this->tags_list_table .' WHERE tag_name = %s', [$name])
        );
    }

    public function get_tag_list(){
        global $wpdb;
        return $wpdb->get_col(
            $wpdb->prepare('SELECT tag_name FROM ' . $this->tags_list_table)
        );
    }

}

?>