<?php
/**
 * @package tmail
 * @version 1.0.3
 */
/*
Plugin Name: WP TMail Lite - Multi Domain Temporary Email System
Plugin URI: https://codecanyon.net/item/tmail-multi-domain-temporary-email-system/20177819
Description: <strong>TMail</strong> is a simple to use, fast and mobile ready temproary email system with impressive feature set.<br><br>NOTE: Use of this plugin may conflict with server based cache services, and this plugin cannot support it's use on those servers
Author: Harshit Peer
Version: 1.0.3
Author URI: https://harshitpeer.com
*/

/* Admin Panel for WPTMail Lite */

add_action( 'admin_menu', 'wp_tmail_lite_menu' );
 
function wp_tmail_lite_menu() {
    add_options_page( 'WP TMail Lite Configuration', 'WP TMail Lite', 'manage_options', 'wp-tmail-lite-option', 'wp_tmail_lite_menu_options' );
}
 
function wp_tmail_lite_menu_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    wp_enqueue_style("tmail", plugin_dir_url( __FILE__ ) . "./inc/bootstrap/bootstrap.min.css");
    
    $imap_host = "";
    $imap_port = "";
    $imap_user = "";
    $imap_pass = "";
    $imap_domains = "";
    $enable_ssl = false;
    $message = "";
    if(isset($_POST["submit"])) { 
        check_admin_referer( 'admin_settings_update', 'admin_settings_update_check' );
        $imap_host = sanitize_text_field($_POST['imap_host']);
        $imap_port = sanitize_text_field($_POST['imap_port']);
        $imap_user = sanitize_text_field($_POST['imap_user']);
        $imap_pass = filter_input(INPUT_POST, 'imap_pass', FILTER_SANITIZE_SPECIAL_CHARS);
        $imap_domains = implode(",", explode("\n", sanitize_text_field($_POST['imap_domains'])));
        $enable_ssl = sanitize_text_field($_POST['enable_ssl']);
        update_option('wp_tmail_lite_imap_host', $imap_host);
        update_option('wp_tmail_lite_imap_port', $imap_port);
        update_option('wp_tmail_lite_imap_user', $imap_user);
        update_option('wp_tmail_lite_imap_pass', $imap_pass);
        update_option('wp_tmail_lite_imap_domains', $imap_domains);
        update_option('wp_tmail_lite_enable_ssl', $enable_ssl);
        $imap_domains = implode("\n", explode(",", get_option('wp_tmail_lite_imap_domains')));
        $message = '<div class="alert alert-success"><strong>Success!</strong> Settings saved successfully</div>';
    } else {
        $imap_host = get_option('wp_tmail_lite_imap_host');
        $imap_port = get_option('wp_tmail_lite_imap_port');
        $imap_user = get_option('wp_tmail_lite_imap_user');
        $imap_pass = get_option('wp_tmail_lite_imap_pass');
        $imap_domains = implode("\n", explode(",", get_option('wp_tmail_lite_imap_domains')));
        $enable_ssl = get_option('wp_tmail_lite_enable_ssl');       
    }
    ?>
    <div class="container-fluid" style="padding-right: 30px;">
        <br>
        <h2>WP TMail Lite Configuration</h2><br>
        <?php echo $message; ?>
        <fieldset>
            <legend>General Settings</legend>
            <form method="post" action=""> 
                <?php wp_nonce_field( 'admin_settings_update', 'admin_settings_update_check' ); ?>
                <div class="form-group">
                    <label for="imap-host">IMAP Hostname</label>
                    <input type="text" name="imap_host" value="<?php echo $imap_host; ?>" class="form-control" id="imap-host"/>
                </div>
                <div class="form-group">
                    <label for="imap-port">IMAP Port</label>
                    <input type="number" name="imap_port" value="<?php echo $imap_port; ?>" class="form-control" id="imap-port"/>
                </div>
                <div class="form-group">
                    <label for="imap-user">IMAP Username</label>
                    <input type="text" name="imap_user" value="<?php echo $imap_user; ?>" class="form-control" id="imap-user"/>
                </div>
                <div class="form-group">
                    <label for="imap-pass">IMAP Password</label>
                    <input type="text" name="imap_pass" value="<?php echo $imap_pass; ?>" class="form-control" id="imap-pass"/>
                </div>
                <div class="form-group">
                    <label for="imap-domains">Domains (one domain in one line)</label>
                    <textarea name="imap_domains" class="form-control" id="imap-domains"><?php echo $imap_domains; ?></textarea>
                </div>
                <div class="checkbox">
                    <label><input type="checkbox" name="enable_ssl" <?php if($enable_ssl) echo "checked"; ?>/> <span style="margin-top: 2px; display: block; padding-left: 5px;">Enable SSL</span></label>
                </div>
                <br>
                <input type="submit" value="Save" class="button button-primary" name="submit" />
            </form>
        </fieldset>
    </div>
    <?php
} 

/* Intializing Plugin Files */

add_action("wp_enqueue_scripts", "wp_tmail_lite_files_loader");

function wp_tmail_lite_files_loader() {
    wp_enqueue_style("tmail", plugin_dir_url( __FILE__ ) . "./inc/bootstrap/bootstrap.min.css");
	wp_enqueue_script("tmail", plugin_dir_url( __FILE__ ) . "./inc/bootstrap/bootstrap.min.js" , array("jquery"), "", true);
	wp_enqueue_script("jquery-ui-accordion");
}

if (!class_exists('PhpImap\Mailbox')) {
    include( plugin_dir_path( __FILE__ ) . './inc/PhpImap/__autoload.php');
}

/* Initiating Session */

function wp_tmail_lite_register_session() {
    if(function_exists('wp_tmail_lite_initialize')) {
        if(!session_id()) {
            session_start();
        }
    }
}

add_action('init','wp_tmail_lite_register_session');

/* ShortCode Function */

add_shortcode("tmail", "wp_tmail_lite_initialize");

function wp_tmail_lite_initialize( $atts, $content = null ) {
    if(isset($_GET['e'])) {
        $_SESSION["tmail_address"] = $_GET['e'];
    }
    $config = array(
        "title" => "TMail Lite",
        "host" => get_option('wp_tmail_lite_imap_host'),
        "user" => get_option('wp_tmail_lite_imap_user'),
        "pass" => get_option('wp_tmail_lite_imap_pass'),
        "domains" => explode(",", get_option('wp_tmail_lite_imap_domains')),
        "ssl" => get_option('wp_tmail_lite_enable_ssl')
    );
    try {
        if($config['ssl']) {
            $mailbox = new PhpImap\Mailbox('{'.$config['host'].'/imap/ssl}INBOX', $config['user'], $config['pass'], __DIR__);
        } else {
            $mailbox = new PhpImap\Mailbox('{'.$config['host'].'/imap/novalidate-cert}INBOX', $config['user'], $config['pass'], __DIR__);
        }
        $mailbox->setAttachmentsIgnore(true);
        $random = true;
        if(isset($_SESSION["tmail_address"])) {
            $address = sanitize_email($_SESSION["tmail_address"]);
            $check = explode('@', $address);
            if(in_array($check[1], $config['domains'])) {
                $random = false;
            }
        }
        if(isset($_POST['action'])) {
            if(sanitize_text_field($_POST['action']) == "new") {
                unset($_SESSION['tmail_address']);
                $random = true;
            } else {
                $toList = "TO ".$address;
                $ccList = "CC ".$address;
                $bccList = "BCC ".$address;
                $mailIdsTo = $mailbox->searchMailbox($toList);
                $mailIdsCc = $mailbox->searchMailbox($ccList);
                $mailIdsBcc = $mailbox->searchMailbox($bccList);
                $mailsIds = array_reverse(array_unique(array_merge($mailIdsTo,$mailIdsCc,$mailIdsBcc)));
                foreach ($mailsIds as $mailID) {
                    $mailbox->deleteMail($mailID);
                }
                unset($_SESSION['tmail_address']);
                $random = true;
            }
        }
        if($random) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < 10; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            $address = $randomString."@".$config["domains"][rand(0, count($config["domains"]) - 1)];
            $_SESSION["tmail_address"] = $address;
        }
        $toList = "TO ".$address;
        $ccList = "CC ".$address;
        $bccList = "BCC ".$address;
        $mailIdsTo = $mailbox->searchMailbox($toList);
        $mailIdsCc = $mailbox->searchMailbox($ccList);
        $mailIdsBcc = $mailbox->searchMailbox($bccList);
        $mailsIds = array_reverse(array_unique(array_merge($mailIdsTo,$mailIdsCc,$mailIdsBcc)));
        $return = "<style>.tmail-actions button { width: 100%;} .btn-label {position: relative;left: -12px;display: inline-block;padding: 6px 12px;border-radius: 3px 0 0 3px;}.btn-labeled {padding-top: 0;padding-bottom: 0;}.btn { margin-bottom:10px; }.title{font-weight:300;font-size:28px;text-align:center;margin:50px 0}button.accordion{background-color:#eee;color:#444;cursor:pointer;padding:20px 30px;width:100%;border:none;text-align:left;outline:0;font-size:15px;transition:.4s}button.accordion.active,button.accordion:hover{background-color:#ccc}div.panel{padding:0 30px;background-color:#efefef;max-height:0;overflow:hidden;transition:max-height .2s ease-out}.tmail_actions{text-align:center;background:#eee;cursor:pointer}.tmail_actions:hover{background:#ddd}</style>";
        $return .= '
        <div class="">
            <div class="row">
                <div class="col-sm-12">
                    <div class="alert alert-info">
                        Your Email ID is <strong>'.$address.'</strong>
                    </div>
                </div>            
                <div class="col-sm-4 tmail-actions">
                <button type="button" class="btn btn-labeled btn-success" onclick="document.getElementById(\'newForm\').submit();">
                    <span class="btn-label"><i class="glyphicon glyphicon-repeat"></i></span>New
                </button>
                </div>
                <div class="col-sm-4 tmail-actions">
                <button type="button" class="btn btn-labeled btn-primary" onclick="window.location = \'./\'">
                    <span class="btn-label"><i class="glyphicon glyphicon-refresh"></i></span>Refresh
                </button>
                </div>
                <div class="col-sm-4 tmail-actions">
                <button type="button" class="btn btn-labeled btn-danger" onclick="document.getElementById(\'deleteForm\').submit();">
                    <span class="btn-label"><i class="glyphicon glyphicon-off"></i></span>Delete
                </button>
                </div>
            </div>
        </div>
        <form method="POST" action="" id="newForm">
            <input type="hidden" name="action" value="new">
        </form>
        <form method="POST" action="" id="deleteForm">
            <input type="hidden" name="action" value="delete">
        </form>';
        foreach ($mailsIds as $mailID) {
            $mail = $mailbox->getMail($mailID);
            $return .= '<div id="mail'.$mailID.'"><button class="accordion">'.$mail->subject.'<br>From : '.$mail->fromName.'&lt;'.$mail->fromAddress.'&gt;<span style="float: right">'.$mail->date.'</span></button><div class="panel"><br>';
    		if ($mail->textHtml == "") {
    			$return .= '<div>'.$mail->textPlain.'</div>';
    		} else {
    			$return .= '<div>'.$mail->textHtml.'</div>';
    		}
    		$return .= '<br></div></div>';
        }
        return $return.'<script>var $jq=jQuery.noConflict();$jq(window).on("load",function(){$jq("#accordion").accordion()});var i,acc=document.getElementsByClassName("accordion");for(i=0;i<acc.length;i++)acc[i].onclick=function(){this.classList.toggle("active");var i=this.nextElementSibling;i.style.maxHeight?i.style.maxHeight=null:i.style.maxHeight=i.scrollHeight+"px"};</script>';
    } catch(Exception $e) {
        $error = '<div class="alert alert-danger"><strong>Error!</strong> '.$e->getMessage().'</div>';
        return $error;
    }
}
