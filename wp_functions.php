// Comm Web payment gateway
function comm_web_host_checkout_payment(){
    global $wpdb;
    save_donation_data($_POST);
    $curl = curl_init();
    $apiPassword = {value here};
    $merchant ={value here};
    $apiUsername ='merchant.'.$merchant; 


    $orderid = generateRandomString(10);
    $url= 'https://paymentgateway.commbank.com.au/api/rest/version/68/merchant/{merchant_key}/session';
    $dataArray = array(
        "apiOperation" => 'INITIATE_CHECKOUT',
        "interaction" => array(
            "operation" => "PURCHASE",
            "merchant" => array(
                "name" => 'Donation'
            ),
            "displayControl" => array(
                "billingAddress"  => 'HIDE',
                "shipping"        => 'HIDE'
            )
        ),
        "order" => array(
            "id" => $orderid,
            "amount" => $_POST['donation_amount'],
            "currency" => 'AUD',
            "description" => wp_strip_all_tags($_POST['destination'])
        )   
    );
    $postData = json_encode($dataArray);
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $postData,
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic '.base64_encode("$apiUsername:$apiPassword")
      ),
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    $json = json_decode($response,true);
    $json['donationID'] = $wpdb->insert_id;
    $response = json_encode($json);
    echo $response;
    
    exit;
}
add_action('wp_ajax_comm_web_host_checkout_payment', 'comm_web_host_checkout_payment');
add_action('wp_ajax_nopriv_comm_web_host_checkout_payment', 'comm_web_host_checkout_payment');



function generateRandomString($n){
    $characters = '0123456789abcdefghijklmnopqrtwxuvsy';
    $randomString = 'DO';
    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
    return $randomString;
}

function save_donation_data($data){
    global $wpdb;
    $datadb 		 =  $wpdb->prefix.'donation_commbank';
    $created_date	= date('Y-m-d H:i:s');
    $dad = $wpdb->insert( 
    $datadb, 
    array( 
    	'firstname' 				=> $data['firstname'], 
    	'lastname' 			=> $data['lastname'],
    	'email' 			=> $data['email'],
    	'phone' 			=> $data['phone'],
    	'postaladdress' => $data['postaladdress'],
    	'donateas' 	=> $data['donateas'],
    	'company' 	=> $data['company'],		
    	'donation_amount' 	        => $data['donation_amount'],	
    	'destination' => wp_strip_all_tags($data['destination']),
    	'payment_status' => 0,
    	'mailchimp_opt' => $data['mailchimp_opt'],
    	'created_date'      => $created_date
    ), 
    array('%s','%s','%s','%s','%s','%s','%s','%d','%s','%d','%s') 
    );
}

require_once(get_stylesheet_directory().'/dompdf/vendor/autoload.php');
use Dompdf\Dompdf;
function update_payment_status(){
    $image = $_SERVER["DOCUMENT_ROOT"].'/toplogo.png';
    $donationID = $_POST['id'];
    $invoiceNo = $_POST['successIndicator'];
    global $wpdb;
    $datadb  =  $wpdb->prefix.'donation_commbank';
    $query = $wpdb->get_row("SELECT * FROM $datadb where id=".$donationID);
    $wpdb->query($wpdb->prepare("UPDATE $datadb SET payment_status=1 WHERE id=$donationID"));
    
    // Mailchimp Opt
    if($query->mailchimp_opt == 1){
        $username = '';
        $apikey = '';
        $audienceID = '';
        $auth = base64_encode( $username.':'.$apikey );
        $data = array(
           'apikey'        => $apikey,
           'email_address' => $query->email,
           'status'        => 'subscribed',
           'merge_fields'  => [
               'FNAME' => $query->firstname,
               'LNAME' => $query->lastname,
               'ADDRESS' => $query->postaladdress,
               'PHONE' => $query->phone,
               'DEST' => $query->destination
            ]
        );
        $json_data = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://us18.api.mailchimp.com/3.0/lists/'.$audienceID.'/members/');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
        'Authorization: Basic '.$auth));
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        $result = curl_exec($ch);
    }
    // instantiate and use the dompdf class
    $dompdf = new Dompdf();
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->set_option('isRemoteEnabled', true);
    ob_start();
    // $html = get_template_part('donation/email-donation-details',null,array(
    //     'id' => $_POST['id']
    // ));
    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<title>Domain</title>

<style type="text/css">

body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	color:#878383;
	font-family:Arial, Helvetica, sans-serif; 
}
</style>
</head>

<body>

<table  align="center" autosize="1" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="center"><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center"><img src="#" width="300"/></td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="font-family: Arial, Helvetica, sans-serif;font-size: 18px;color: #666666;"><strong>THANK YOU FOR YOUR DONATION</strong></td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="font-family: Arial, Helvetica, sans-serif;font-size: 15px;color: #868181;">Your donation will help ensure safety, security, choice and support for members of our<br />
community and will create a meaningful change in their lives.</td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="border-top: #CCC 1px solid;">&nbsp;</td>
      </tr>
      <tr>
        <td align="left" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #7d7878;">Invoice No: 000000'.$invoiceNo.'</td>
      </tr>
      <tr>
        <td align="left" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #7d7878;">Date: '.date("d/m/Y").'</td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center"><table style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #7d7878;" width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="left" style="max-width: 300px;" valign="top">DONOR DETAILS:<br />
                <span style="text-transform: uppercase;">'.$query->firstname." ".$query->lastname.'</span><br />
                <span role="gridcell" tabindex="-1">'.$query->email.'</span><br />
                <span role="gridcell" tabindex="-1">'.$query->phone.'</span><br />
                <span role="gridcell" tabindex="-1">'.$query->company.'</span><br />
                <span role="gridcell" tabindex="-1">'.$query->destination.'</span><br />
                </td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="border-top: #CCC 1px solid;">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="font-family: Arial, Helvetica, sans-serif;font-size: 18px;color: #666666;"><strong>TAX INVOICE</strong></td>
      </tr> 
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: bold; color: #FFF;">
            <td style="padding: 8px 0; border: 1px #FFFFFF solid;" width="200" align="center" bgcolor="#666666">Description</td>
            <td style="padding: 8px 0; border: 1px #FFFFFF solid;" width="100" align="center" bgcolor="#666666">Amount</td>
            <td style="padding: 8px 0; border: 1px #FFFFFF solid;" width="100" align="center" bgcolor="#666666">Total</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
          <tr style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #7d7878;">
            <td valign="top"><span style="text-transformer:uppercase">'.$query->destination.'</span></td>
            <td align="center" valign="top">$'.number_format($query->donation_amount,2).'</td>
            <td align="center" valign="top">$'.number_format($query->donation_amount,2).'</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="border-top: #CCC 1px solid;">&nbsp;</td>
      </tr>
      <tr>
        <td align="center"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #7d7878;">
            <td width="200" >GST </td>
            <td width="100" ></td>
            <td width="100" align="center">$0.00</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="border-top: #CCC 1px solid;">&nbsp;</td>
      </tr>
      <tr>
        <td align="center"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #7d7878;">
            <td width="200" >Overall Total</td>
            <td width="100" ></td>
            <td width="100" align="center">$'.number_format($query->donation_amount,2).'</td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
      <tr>
        <td align="center" style="border-top: #CCC 1px solid;">&nbsp;</td>
      </tr>
      <tr>
        <td align="center">&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>
</body>
</html>
';
    
    $dompdf->loadHtml($html);
    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    // Render the HTML as PDF
    $dompdf->render();
    $output = $dompdf->output();
    $file = dirname(__FILE__).'/invoice/Donation_Tax_Receipt.pdf';
    $success = file_put_contents($file, $output);
    ob_flush();

    $subjectToCustomer = "Thank you for your generous donation";
    
    $messageToCustomer = '
    <p>Dear '.$query->firstname." ".$query->lastname.',</p>

    <p>Thank you for your generous donation.</p>
    
    <p>Your donation will help ensure safety, security, choice and support for members of our community and will create a meaningful change in their lives.</p>
    
    <p>Please find your tax-deductible donation receipt attached.</p>
    
    <p>If you have any questions, please contact our friendly team on 1300 900 091 or <a href="mailto:info@domain.com">info@domain.com</a></p>';
    
    $subjectToAdmin = "You have received a donation!";
    $messageToAdmin = '
    <p>Firstname: '.$query->firstname.'</p>
    <p>Lastname: '.$query->lastname.'</p>
    <p>Email: '.$query->email.'</p>
    <p>Phone: '.$query->phone.'</p>
    <p>Postal Address: '.$query->postaladdress.'</p>
    <p>Donate As: '.$query->donateas.'</p>';
    if(!empty($query->company)){
        $messageToAdmin .= '<p>Company: '.$query->company.'</p>';
    }
    $messageToAdmin .= '<p>'.$query->destination.'</p><p>Donation Amount: $'.$query->donation_amount.'</p>
    ';
    
    $from = "info@domain.com";
    
    $headers= "From: Domain <info@domain.com>\r\n";  
    
    $header .= "MIME-Version: 1.0\r\n";
    
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $adminEmail = 'communications@domain.com';
    wp_mail($query->email,$subjectToCustomer,$messageToCustomer,$headers,array($file));
    wp_mail($adminEmail,$subjectToAdmin,$messageToAdmin,$headers,array($file));    

}

add_action('wp_ajax_update_payment_status', 'update_payment_status');
add_action('wp_ajax_nopriv_update_payment_status', 'update_payment_status');


function send_mannual_invoice($donationID){

    $image = $_SERVER["DOCUMENT_ROOT"].'/toplogo.png';
    $invoiceNo = $_POST['successIndicator'];
    global $wpdb;
    $datadb  =  $wpdb->prefix.'donation_commbank';
    $query = $wpdb->get_row("SELECT * FROM $datadb where id=".$donationID);
    // instantiate and use the dompdf class
    $dompdf = new Dompdf();
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->set_option('isRemoteEnabled', true);
    ob_start();
    $html = 'Content';
    
    $dompdf->loadHtml($html);
    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    // Render the HTML as PDF
    $dompdf->render();
    $output = $dompdf->output();
    $file = dirname(__FILE__).'/invoice/Donation_Tax_Receipt.pdf';
    $success = file_put_contents($file, $output);
    ob_flush();

    $subjectToCustomer = "Thank you for your generous donation";
    
    $messageToCustomer = '
    <p>Dear '.$query->firstname." ".$query->lastname.',</p>

    <p>Thank you for your generous donation.</p>
        
    <p>If you have any questions, please contact our friendly team on 1300 900 091 or <a href="mailto:info@domain.com">info@domain.com</a></p>';
    
    $subjectToAdmin = "You have received a donation!";
    $messageToAdmin = '
    <p>Firstname: '.$query->firstname.'</p>
    <p>Lastname: '.$query->lastname.'</p>
    <p>Email: '.$query->email.'</p>
    <p>Phone: '.$query->phone.'</p>
    <p>Postal Address: '.$query->postaladdress.'</p>
    <p>Donate As: '.$query->donateas.'</p>';
    if(!empty($query->company)){
        $messageToAdmin .= '<p>Company: '.$query->company.'</p>';
    }
    $messageToAdmin .= '<p>'.$query->destination.'</p><p>Donation Amount: $'.$query->donation_amount.'</p>
    ';
    
    $from = "info@domain.com";
    
    $headers= "From: Momentum Collective <info@domain.com>\r\n";  
    
    $header .= "MIME-Version: 1.0\r\n";
    
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    $adminEmail = 'test@email.com';
    wp_mail($query->email,$subjectToCustomer,$messageToCustomer,$headers,array($file));
    wp_mail($adminEmail,$subjectToAdmin,$messageToAdmin,$headers,array($file));    
}