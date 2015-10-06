<?php
global $wpdb;    
$emailerOptionName = 'wppizza-auto-emailer-config';
$emailerDebugAddrName = 'wppizza-auto-emailer-config-debugemail';

$emailerOptions = get_option( $emailerOptionName);
$debugAddr = get_option($emailerDebugAddrName);



if($_POST){

    $debugAddr = '';
    if (isset($_POST['debugAddr'])){
        $debugAddr = $_POST['debugAddr'];
    }

    $number_of_days = '';
    if (isset($_POST['number_of_days'])){
        $number_of_days = $_POST['number_of_days'];
    }

    $subject_line = '';
    if (isset($_POST['subject_line'])){
        $subject_line = $_POST['subject_line'];
    }

    $subject_line_ko = '';
    if (isset($_POST['subject_line_ko'])){
        $subject_line_ko = $_POST['subject_line_ko'];
    }

    $email_text = '';
    if (isset($_POST['email_text'])){
        $email_text = $_POST['email_text'];    
    }

    $email_text_ko = '';
    if (isset($_POST['email_text_ko'])){
        $email_text_ko = $_POST['email_text_ko'];    
    }

    $newEmailerOptions = array();
    for ($i=0; $i < count($email_text); $i++) {
        if(strlen($number_of_days[$i]) > 0){
            $optionItem = array(
                'email_text' => $email_text[$i],
                'email_text_ko' => $email_text_ko[$i], 
                'subject_line' => $subject_line[$i], 
                'subject_line_ko' => $subject_line_ko[$i], 
                'number_of_days' => $number_of_days[$i]);
            array_push($newEmailerOptions,  $optionItem);
        }
    }
    update_option($emailerOptionName, $newEmailerOptions);
    update_option($emailerDebugAddrName, $debugAddr);
    $emailerOptions = $newEmailerOptions;
}

?>

<div class="wrap">  
    <h1>WPPizza Auto Emailer</h1>    
    <p>Here we can configure what emails we want to send after a customer ordered the last time. To delete an Email, just empty the #Days field and press save.</p>
    <ul>
        <li><b>#Days</b> is the number of days since the last order. </li>
        <li><b>Subject</b> will be the subject line of the email</li>
        <li><b>Message</b> is the content of the email.</li>
    </ul>
    <form method="post" action="/wp-admin/edit.php?post_type=wppizza&amp;page=wppizza-auto-emailer">
        <table class="widefat">
        <thead>
            <tr>
                <th># Days</th>
                <th>Subject EN</th>                
                <th>Message EN</th>
                <th>Subject KO</th>
                <th>Message KO</th>
            </tr>
        </thead>
            <?php
            if($emailerOptions){
                foreach($emailerOptions as $option){
                    ?>
                    <tr>
                        <td><input name="number_of_days[]" type="text" value="<?php echo stripslashes($option['number_of_days']) ?>"></td>
                        <td><input name="subject_line[]" type="text" value="<?php echo stripslashes($option['subject_line']) ?>"></td>
                        <td><textarea rows="6" cols="40" name="email_text[]"><?php echo stripslashes($option['email_text']) ?></textarea></td>
                        <td><input name="subject_line_ko[]" type="text" value="<?php echo stripslashes($option['subject_line_ko']) ?>"></td>
                        <td><textarea rows="6" cols="40" name="email_text_ko[]"><?php echo stripslashes($option['email_text_ko']) ?></textarea></td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr>
                <td colspan="5"><hr></td>
            </tr>
            <tr>
                <td colspan="5">Add a new one:</td>
            </tr>
            <tr>
                <td><input name="number_of_days[]" type="text" value=""></td>
                <td><input name="subject_line[]" type="text" value=""></td>
                <td><textarea rows="6" cols="40" name="email_text[]"></textarea></td>
                <td><input name="subject_line_ko[]" type="text" value=""></td>
                <td><textarea rows="6" cols="40" name="email_text_ko[]"></textarea></td>               
            </tr>
            <tr>
                
                <td colspan="5">
                    <button type="submit">Save Everything</button>
                </td>
            </tr>
        </table>
       <p>Debug Address:</p> <input name="debugAddr" type="text" value="<?php echo $debugAddr ?>">
       <p>Useful to test this new feature. All emails will be sent to this email address instead of the customer's email address.</p>
    </form>
    <div>Next Emails will be sent: <?php 
        $timestamp = wp_next_scheduled( 'wpae_daily_sendemail' );    
        if($timestamp){
            echo date('F j, Y, g:i A', $timestamp).' GMT';
            echo '<br>Unix Timestamp: ('.$timestamp.')';
        }
        else{
            echo 'nothing scheduled';
        }
        ?>                
    </div>
</div>