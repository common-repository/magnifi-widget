<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// For Modals
add_ThickBox();

global $wpdb;
$table_name = $wpdb->prefix . "mgnf_widget";
$preset_table = $wpdb->prefix . "mgnf_preset";

/*
function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . 
');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
} */


// Get Preset Configurations
function mgnf_get_preset() {
    global $wpdb;

    $table_name = $wpdb->prefix . "mgnf_widget";
    $preset_table = $wpdb->prefix . "mgnf_preset";

    $entries = $wpdb->get_results("SELECT * FROM " . $table_name . " ORDER BY id ASC");
    if (count($entries) > 0) {
        foreach ($entries as $entry) {
            if ($entry->wname != "") { // ONLY DEFAULT CONFIG ALLOWED

                // Get presets
                $response = wp_remote_get("https://widget.magnifi.io/v1/widgets/".$entry->wkey."/groups",  array(
                    'timeout' => 45
                ));

                // Check status and process
                if (wp_remote_retrieve_response_code( $response ) == 200){

                    $body = json_decode(wp_remote_retrieve_body( $response ), true);

                    // Loop through and store each preset
                    foreach ($body as $widget) {
                        $wname = sanitize_text_field(trim($widget["name"]));
                        $wiframe = $widget["iframe"]; // Only using iframe code
                        
                        $desc = $widget["description"];
            
                        // Insert preset
                        $wpdb->insert($preset_table, array(
                            'wname' => $wname,
                            'wiframe' => $wiframe,
                            'wdesc' => $desc,
                        ));

                    }
                }
            }
        }
    }
} 

// *** New Login System ***
function mgnf_update_db($email, $pass){
    
    global $wpdb;
    $table_name = $wpdb->prefix . "mgnf_widget";

    // Get widget info
    $response = wp_remote_post("https://widget.magnifi.io/v1/auth",  array(
        'body' => array('email' => $email,'password' => $pass),
        'timeout' => 45
    ));

    // Check successful login
    if (wp_remote_retrieve_response_code( $response ) == 200){
        update_option( "mgnf_user_email", $email);
        update_option( "mgnf_user_pass", $pass);
        

        $body = json_decode(wp_remote_retrieve_body( $response ), true);
        

        // Loop through and store each widget
        foreach ($body as $widget) {
            $wname = sanitize_text_field(trim($widget["name"]));
            $wkey = sanitize_text_field(trim($widget["key"]));
            $wcap = sanitize_textarea_field(trim(json_encode($widget["capabilities"])));

                // Insert Record if not part of default widgets
                $wpdb->insert($table_name, array(
                    'wname' => $wname,
                    'wkey' => $wkey,
                    'wcapabilities' => $wcap
                ));
        }
        
    } else {
        update_option( "mgnf_user_error", "true"); // User entered incorrect password
    }
}

function mgnf_clear_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . "mgnf_widget";
    $wpdb->query("TRUNCATE TABLE " . "`". $table_name ."`");
    $preset_table = $wpdb->prefix . "mgnf_preset";
    $wpdb->query("TRUNCATE TABLE " . "`". $preset_table ."`");
}

function mgnf_clear_default() {
    // Clear Default Widgets
    global $wpdb;
    $table_name = $wpdb->prefix . "mgnf_widget";
    $preset_table_name = $wpdb->prefix . "mgnf_preset";
    $wpdb->query("DELETE FROM" . "`". $table_name ."`" . "WHERE wname IN ('Customer Support - Redirect to Webpage','Instant Meeting', 'Schedule Future Meeting', 'Request Meeting', 'Customer Support - Request a Meeting', 'Customer Support - Route to Message Form')" );
    $wpdb->query("DELETE FROM" . "`". $preset_table_name ."`" . "WHERE wname NOT IN ('Customer Support - Redirect to Webpage','Instant Meeting', 'Schedule Future Meeting', 'Request Meeting', 'Customer Support - Request a Meeting', 'Customer Support - Route to Message Form')" );
}

// *** New Login System ***
if (get_option("mgnf_user_email") && get_option("mgnf_user_pass")) {
    mgnf_clear_db();
    mgnf_update_db(get_option("mgnf_user_email"), get_option("mgnf_user_pass"));
    mgnf_get_preset();
    mgnf_clear_default(); // Remove default widgets
    delete_option("mgnf_user_error"); // User entered correct password
}

// Handle Form Request
if (isset($_POST['submit']) && isset( $_POST['verField'] ) && wp_verify_nonce( $_POST['verField'], 'verify' )) {

    // *** New Login System ***
    if(!is_null($_POST["uemail"]) && !is_null($_POST["upass"])){
        mgnf_clear_db();
        mgnf_update_db($_POST["uemail"], $_POST["upass"]);
        mgnf_get_preset();
        mgnf_clear_default(); // Remove default widgets
    }
    else {
        mgnf_clear_db();
        mgnf_clear_default(); // Remove default widgets
        delete_option("mgnf_user_email");
        delete_option("mgnf_user_pass");
        delete_option("mgnf_user_error"); // User entered correct password
        
    }
}

?>

<!-- CSS Styling -->
<head>
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<style>
body {
    background-color: #303030;
}
a {
    color: #6ae3d7;
    font-family: 'Roboto';
}
h1,h2,p,label {
    font-family: 'Roboto';
    color: #ffffff;
}
table, th, td {
    background-color: #414141 !important;
    font-family: 'Roboto' !important;
    color: #ffffff !important;
}

.mgnf_btn {
    color: #ffffff;
    background-color: #3caea3;
    border-color: #3caea3;
}

#TB_window {
    background-color: #414141 !important;
    font-family: 'Roboto' !important;
    color: #ffffff !important;
}

.mgnf-container {
  width: 150px;
  clear: both;
}

.mgnf-container input {
  width: 100%;
  clear: both;
}

</style>
</head>

<div class="wrap">
    <h1 style="margin-bottom: 1rem;">Magnifi Widget Settings</h1>

    <!-- DEMO GROUPS -->
    <table style="margin-bottom: 1rem;" class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <td class="manage-column column-columnname" scope="col">Preset Configurations</td>
                <td class="manage-column column-columnname" scope="col">Preset Configuration Short Code</td>
                <td class="manage-column column-columnname" scope="col">Preset Configuration Description</td>
                <!-- <td class="manage-column column-columnname" scope="col">User Management</td> -->
            </tr>
        </thead>
        <tbody>
        <?php
            //Pull Data from preset table
            $entries = $wpdb->get_results("SELECT * FROM " . $preset_table . " ORDER BY id ASC");
            if (count($entries) > 0) {
                foreach ($entries as $entry) {
                    $id = $entry->id;
                    $name = $entry->wname;
                    $desc = $entry->wdesc;
                    echo "
                            <tr class='alternate'>
                                <td class='column-columnname' scope='col'>" . $name . "</td>
                                <td class='column-columnname' scope='col'>[magnifi preset=" . $id . "] <a style='cursor: pointer;' onclick='mgnf_preset_copy(" . $id . ")'>Copy</a></td>
                                <td class='column-columnname' scope='col'>" . $desc . "</td>
                                <!-- <td class='column-columnname' scope='col'><a href='#TB_inline?&width=350&height=400&inlineId=widgetCustomize' class='thickbox' style='cursor: pointer;' onclick='mgnf_customizePreset(" . $id . ")'>User Management</a></td> -->
                            </tr>
                        ";
                }
            } else {
                echo "<tr><td>No Data</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Display Short Codes Available -->
    <table style="margin-bottom: 1rem;" class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <td class="manage-column column-columnname" scope="col">Widget ID</td>
                <td class="manage-column column-columnname" scope="col">Widget Name</td>
                <td class="manage-column column-columnname" scope="col">Widget Short Code</td>
                <!-- <td class="manage-column column-columnname" scope="col">User Management</td> -->
            </tr>
        </thead>
        <tbody>
            <?php
            //Pull Data from mgnf_widget table
            $entries = $wpdb->get_results("SELECT * FROM " . $table_name . " ORDER BY id ASC");
            if (count($entries) > 0) {
                foreach ($entries as $entry) {
                    $id = $entry->id;
                    $name = $entry->wname;
                    echo "
                            <tr class='alternate'>
                                <td class='column-columnname' scope='col'>" . $id . "</td>
                                <td class='column-columnname' scope='col'>" . $name . "</td>
                                <td class='column-columnname' scope='col'>[magnifi id=" . $id . "] <a style='cursor: pointer;' onclick='mgnf_copy(" . $id . ")'>Copy</a></td>
                                <!--<td class='column-columnname' scope='col'><a href='#TB_inline?&width=350&height=400&inlineId=widgetCustomize' class='thickbox' style='cursor: pointer;' onclick='mgnf_customizeWidget(" . $id . ")'>User Management</a></td>-->
                            </tr>
                        ";
                }
            } else {
                echo "<tr><td>No Data</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- User Login / Logout -->
    <div class="mgnf-container">
        <form method="post" action='<?= $_SERVER['REQUEST_URI'] ?>' enctype="multipart/form-data">
            
            <?php if(!get_option("mgnf_user_email") && !get_option("mgnf_user_pass")): ?> 
                <label for="uemail">Email: </label>
                <input type="text" id="uemail" name="uemail"><br><br>
                <label for="upass">Password: </label>
                <input type="password" id="upass" name="upass"><br><br>
                <input type="submit" class="mgnf_btn" name="submit" value="Sign In">
                    <?php if(get_option("mgnf_user_error")): ?>
                    <p style="color: red;font-size: larger;font-weight: bold;"> Incorrect username or password </p>
                    <?php endif; ?>
                <?php else: ?>
                <input type="submit" class="mgnf_btn" name="submit" value="Logout">
            <?php endif; ?>

            <?php  wp_nonce_field( 'verify', 'verField' ); ?>

        </form>
        <br>
    </div>
    <!-- Register -->
    <?php if(!get_option("mgnf_user_email") && !get_option("mgnf_user_pass")): ?> 
    <a href="https://widget.magnifi.io/users/promotion" target="blank">Don't have an account? Register</a>
    <?php endif; ?>
</div>

<!-- Modal -->
<?php add_thickbox(); ?>
<div id="widgetCustomize" style="display:none;">
    <center><h2>User Management</h2>
    <form>
        <table>
            <tr>
                <td align="left">Unique Identifier:</td>
                <td align="left"><input id="org_uid" type="text" name="uid" /></td>
            </tr>
            <tr>
                <td align="left">First Name:</td>
                <td align="left"><input id="org_first" type="text" name="first" /></td>
            </tr>
            <tr>
                <td align="left">Last Name:</td>
                <td align="left"><input id="org_last" type="text" name="last" /></td>
            </tr>
            <tr>
                <td align="left">Email:</td>
                <td align="left"><input id="org_email" type="email" name="email" /></td>
            </tr>
        </table>
    </form>
    <br>
    <button onclick="mgnf_createCustomShortCode()" class="mgnf_btn">Create and Copy New Short Code</button><center><br>
    <p id="newShortCode"></p>
</div>

<script>
    var currentWidget = null;
    var isPreset = false;

    function mgnf_copy(str) {
        var el = document.createElement('textarea');
        el.value = "[magnifi id=" + str + "]";
        el.setAttribute('readonly', '');
        el.style = {
            position: 'absolute',
            left: '-9999px'
        };
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }

    function mgnf_preset_copy(str) {
        var el = document.createElement('textarea');
        el.value = "[magnifi preset=" + str + "]";
        el.setAttribute('readonly', '');
        el.style = {
            position: 'absolute',
            left: '-9999px'
        };
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }

    function mgnf_customizeWidget(id) {
        currentWidget = parseInt(id);
        document.getElementById("newShortCode").innerHTML = "[magnifi id=" + currentWidget + "]";
    }

    function mgnf_customizePreset(id) {
        isPreset = true;
        currentWidget = parseInt(id);
        document.getElementById("newShortCode").innerHTML = "[magnifi preset=" + currentWidget + "]";
    }

    function mgnf_createCustomShortCode(){
        var shortcode = currentWidget;
        if(!document.getElementById("org_uid").value == ""){
            shortcode = shortcode + " uid=" + document.getElementById("org_uid").value;
        }
        if(!document.getElementById("org_first").value == ""){
            shortcode = shortcode + " first=" + document.getElementById("org_first").value;
        }
        if(!document.getElementById("org_last").value == ""){
            shortcode = shortcode + " last=" + document.getElementById("org_last").value;
        }
        if(!document.getElementById("org_email").value == ""){
            shortcode = shortcode + " email=" + document.getElementById("org_email").value;
        }
        if(isPreset==true){
            document.getElementById("newShortCode").innerHTML = "[magnifi preset=" + shortcode + "]";
            mgnf_preset_copy(shortcode);
            isPreset=false;
        }
        else {
            document.getElementById("newShortCode").innerHTML = "[magnifi id=" + shortcode + "]";
            mgnf_copy(shortcode);
        }
        
    }
</script>