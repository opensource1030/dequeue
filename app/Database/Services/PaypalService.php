<?php

namespace App\Database\Services;


class PaypalService
{
    public function __construct()
    {
        ini_set('max_execution_time', 360000);
        /********************************************
         * PayPal API Module
         *
         * Defines all the global variables and the wrapper functions
         ********************************************/
        $PROXY_HOST = '127.0.0.1';
        $PROXY_PORT = '808';

        $SandboxFlag = \Config::get('constants.__PAYPAL_SANDBOX_FLAG__');

//'------------------------------------
//' PayPal API Credentials
//' Replace <API_USERNAME> with your API Username
//' Replace <API_PASSWORD> with your API Password
//' Replace <API_SIGNATURE> with your Signature
//'------------------------------------
        $API_UserName = \Config::get('constants.__PAYPAL_API_USERNAME__');
        $API_Password = \Config::get('constants.__PAYPAL_API_PASSWORD__');
        $API_Signature = \Config::get('constants.__PAYPAL_API_SIGNATURE__');

// BN Code 	is only applicable for partners
        $sBNCode = "PP-ECWizard";


        /*
        ' Define the PayPal Redirect URLs.
        ' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
        ' 	change the URL depending if you are testing on the sandbox or the live PayPal site
        '
        ' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
        ' For the live site, the URL is        https://www.paypal.com/webscr&cmd=_express-checkout&token=
        */

        if ($SandboxFlag == true) {
            $API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
        } else {
            $API_Endpoint = "https://api-3t.paypal.com/nvp";
            $PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
        }

        $USE_PROXY = false;
        $version = "98";

    }


    /* An express checkout transaction starts with a token, that
       identifies to PayPal your transaction
       In this example, when the script sees a token, the script
       knows that the buyer has already authorized payment through
       paypal.  If no token was found, the action is to send the buyer
       to PayPal to first authorize payment
       */

    /*
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
    ' Inputs:
    '		paymentAmount:  	Total value of the shopping cart
    '		currencyCodeType: 	Currency code value the PayPal API
    '		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
    '		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
    '		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
    '--------------------------------------------------------------------------------------------------------------------------------------------
    */
    function CallShortcutExpressCheckout($currencyCodeType, $paymentType, $returnURL, $cancelURL, $fPaymentAmount)
    {
        $nvpstr = "";
        //------------------------------------------------------------------------------------------------------------------------------------
        // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
        $nvpstr = $nvpstr . "&RETURNURL=" . $returnURL;
        $nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_AMT=" . $fPaymentAmount;
        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_ITEMAMT=" . $fPaymentAmount;
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_NAME0" . $cnt . "=Subscription Amount";
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_AMT0" . $cnt . "=" . $fPaymentAmount;
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_QTY0" . $cnt . "=1";
        $nvpstr = $nvpstr . "&L_PAYMENTTYPE0=any";
        $nvpstr = $nvpstr . "&VERSION=98";

        $_SESSION["Payment_Amount"] = $paymentAmount;

        //'---------------------------------------------------------------------------------------------------------------
        //' Make the API call to PayPal
        //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
        //' If an error occured, show the resulting errors
        //'---------------------------------------------------------------------------------------------------------------
        $resArray = hash_call("SetExpressCheckout", $nvpstr);
        $ack = strtoupper($resArray["ACK"]);
        if ($ack == "SUCCESS") {
            $token = urldecode($resArray["TOKEN"]);
            $_SESSION['TOKEN'] = $token;
        }

        return $resArray;
    }

    /*
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
    ' Inputs:
    '		paymentAmount:  	Total value of the shopping cart
    '		currencyCodeType: 	Currency code value the PayPal API
    '		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
    '		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
    '		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
    '		shipToName:		the Ship to name entered on the merchant's site
    '		shipToStreet:		the Ship to Street entered on the merchant's site
    '		shipToCity:			the Ship to City entered on the merchant's site
    '		shipToState:		the Ship to State entered on the merchant's site
    '		shipToCountryCode:	the Code for Ship to Country entered on the merchant's site
    '		shipToZip:			the Ship to ZipCode entered on the merchant's site
    '		shipToStreet2:		the Ship to Street2 entered on the merchant's site
    '		phoneNum:			the phoneNum  entered on the merchant's site
    '--------------------------------------------------------------------------------------------------------------------------------------------
    */
    function CallMarkExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL,
                                     $cancelURL, $shipToName, $shipToStreet, $shipToCity, $shipToState,
                                     $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum
    )
    {
        $nvpstr = "";
        //------------------------------------------------------------------------------------------------------------------------------------
        // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

        $nvpstr = "&Amt=" . $paymentAmount;
        $nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
        $nvpstr = $nvpstr . "&ReturnUrl=" . $returnURL;
        $nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
        $nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCodeType;
        $nvpstr = $nvpstr . "&ADDROVERRIDE=1";
        $nvpstr = $nvpstr . "&SHIPTONAME=" . $shipToName;
        $nvpstr = $nvpstr . "&SHIPTOSTREET=" . $shipToStreet;
        $nvpstr = $nvpstr . "&SHIPTOSTREET2=" . $shipToStreet2;
        $nvpstr = $nvpstr . "&SHIPTOCITY=" . $shipToCity;
        $nvpstr = $nvpstr . "&SHIPTOSTATE=" . $shipToState;
        $nvpstr = $nvpstr . "&SHIPTOCOUNTRYCODE=" . $shipToCountryCode;
        $nvpstr = $nvpstr . "&SHIPTOZIP=" . $shipToZip;
        $nvpstr = $nvpstr . "&PHONENUM=" . $phoneNum;

        $_SESSION["currencyCodeType"] = $currencyCodeType;
        $_SESSION["PaymentType"] = $paymentType;

        //'---------------------------------------------------------------------------------------------------------------
        //' Make the API call to PayPal
        //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
        //' If an error occured, show the resulting errors
        //'---------------------------------------------------------------------------------------------------------------
        $resArray = hash_call("SetExpressCheckout", $nvpstr);
        $ack = strtoupper($resArray["ACK"]);
        if ($ack == "SUCCESS") {
            $token = urldecode($resArray["TOKEN"]);
            $_SESSION['TOKEN'] = $token;
        }

        return $resArray;
    }

    /*
    '-------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:
    '		None
    ' Returns:
    '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '-------------------------------------------------------------------------------------------
    */
    function GetShippingDetails($token)
    {
        $nvpstr = "";
        //'--------------------------------------------------------------
        //' At this point, the buyer has completed authorizing the payment
        //' at PayPal.  The function will call PayPal to obtain the details
        //' of the authorization, incuding any shipping information of the
        //' buyer.  Remember, the authorization is not a completed transaction
        //' at this state - the buyer still needs an additional step to finalize
        //' the transaction
        //'--------------------------------------------------------------

        //'---------------------------------------------------------------------------
        //' Build a second API request to PayPal, using the token as the
        //'  ID to get the details on the payment authorization
        //'---------------------------------------------------------------------------
        $nvpstr = "&TOKEN=" . $token;

        //'---------------------------------------------------------------------------
        //' Make the API call and store the results in an array.
        //'	If the call was a success, show the authorization details, and provide
        //' 	an action to complete the payment.
        //'	If failed, show the error
        //'---------------------------------------------------------------------------
        $resArray = hash_call("GetExpressCheckoutDetails", $nvpstr);

        $ack = strtoupper($resArray["ACK"]);
        if ($ack == "SUCCESS") {
            $_SESSION['payer_id'] = $resArray['PAYERID'];
            $_SESSION['token'] = $token;
        }
        return $resArray;
    }

    /*
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:
    '		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
    ' Returns:
    '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '--------------------------------------------------------------------------------------------------------------------------------------------
    */
    function ConfirmPayment($FinalPaymentAmt)
    {
        $nvpstr = "";

        /* Gather the information to make the final call to
           finalize the PayPal payment.  The variable nvpstr
           holds the name value pairs
           */


        //Format the other parameters that were stored in the session from the previous calls
        $token = urlencode($_SESSION['token']);

        $paymentType = __PAYPAL_PAYMENT_TYPE__; //= urlencode($_SESSION['paymentType']);
        $currencyCodeType = urlencode($_SESSION['currencyCodeType']);
        $payerID = urlencode($_SESSION['payer_id']);

        $serverName = urlencode($_SERVER['SERVER_NAME']);

        $nvpstr = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTACTION=' . $paymentType . '&AMT=' . $FinalPaymentAmt;
        $nvpstr .= '&CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName;

        /* Make the call to PayPal to finalize payment
           If an error occured, show the resulting errors
           */
        $resArray = hash_call("DoExpressCheckoutPayment", $nvpstr);

        /* Display the API response back to the browser.
           If the response from PayPal was a success, display the response parameters'
           If the response was an error, display the errors received using APIError.php.
           */
        $ack = strtoupper($resArray["ACK"]);

        return $resArray;
    }

    /**
     * '----------------------------------------------------------------------------------------------------------------
     * hash_call: Function to perform the API call to PayPal using API signature
     * @methodName is name of API  method.
     * @nvpStr is nvp string.
     * returns an associtive array containing the response from the server.
     * '----------------------------------------------------------------------------------------------------------------
     */
    function hash_call($methodName, $nvpStr)
    {
        //declaring of global variables
        global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
        global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
        global $gv_ApiErrorURL;
        global $sBNCode;

        //setting the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
        //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php
        if ($USE_PROXY)
            curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST . ":" . $PROXY_PORT);

        //NVPRequest for submitting to server
        $nvpreq = "METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);

        //setting the nvpreq as POST FIELD to curl
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        //getting response from server
        $response = curl_exec($ch);

        //convrting NVPResponse to an Associative Array
        $nvpResArray = deformatNVP($response);
        $nvpReqArray = deformatNVP($nvpreq);
        $_SESSION['nvpReqArray'] = $nvpReqArray;

        if (curl_errno($ch)) {
            // moving to display page to display curl errors
            $_SESSION['curl_error_no'] = curl_errno($ch);
            $_SESSION['curl_error_msg'] = curl_error($ch);

            //Execute the Error handling module to display errors.
        } else {
            //closing the curl
            curl_close($ch);
        }

        return $nvpResArray;
    }

    /*'-------------------------------------------------------------------------------
     Purpose: Redirects to PayPal.com site.
     Inputs:  NVP string.
     Returns:
    ----------------------------------------------------------------------------------
    */
    function RedirectToPayPal($token)
    {
        global $PAYPAL_URL;

        // Redirect to paypal.com here
        $payPalURL = $PAYPAL_URL . $token . "&useraction=commit";
        header("Location: " . $payPalURL);
    }


    /*'----------------------------------------------------------------------------------
     * This function will take NVPString and convert it to an Associative Array and it will decode the response.
      * It is usefull to search for a particular key and displaying arrays.
      * @nvpstr is NVPString.
      * @nvpArray is Associative Array.
       ----------------------------------------------------------------------------------
    */
    function deformatNVP($nvpstr)
    {
        $intial = 0;
        $nvpArray = array();

        while (strlen($nvpstr)) {
            //postion of Key
            $keypos = strpos($nvpstr, '=');
            //position of value
            $valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);

            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval = substr($nvpstr, $intial, $keypos);
            $valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] = urldecode($valval);
            $nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
        }
        return $nvpArray;
    }

    /*
     * An express checkout transaction starts with a token, that
     * identifies to PayPal your transaction
     * In this example, when the script sees a token, the script
     * knows that the buyer has already authorized payment through
     * paypal.  If no token was found, the action is to send the buyer
     * to PayPal to first authorize payment
    */
    function CallShortcutExpressCheckoutForRecurringPayments($currencyCodeType, $paymentType, $returnURL, $cancelURL, $initialPayment, $szRecurringPeriod)
    {
        $nvpstr = "";

        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
        $nvpstr = $nvpstr . "&RETURNURL=" . $returnURL;
        $nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_ITEMAMT=" . $initialPayment;
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_NAME0" . $cnt . "=Pass";
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_AMT0" . $cnt . "=" . $initialPayment;
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_QTY0" . $cnt . "=1";
        $nvpstr = $nvpstr . "&L_PAYMENTTYPE0=any";
        $nvpstr = $nvpstr . "&L_BILLINGTYPE0=RecurringPayments";
        $nvpstr = $nvpstr . "&L_BILLINGAGREEMENTDESCRIPTION0=" . $szRecurringPeriod . " Subscription";
        $nvpstr = $nvpstr . "&PAYMENTREQUEST_0_AMT=$initialPayment";
        $nvpstr = $nvpstr . "&MAXAMT=25";
        $nvpstr = $nvpstr . "&VERSION=98";

        $resArray = hash_call("SetExpressCheckout", $nvpstr);
        return $resArray;
    }

    /**
     * The CreateRecurringPaymentsProfile API operation creates a recurring payments profile.
     * You must invoke the CreateRecurringPaymentsProfile API operation for each profile you want to create. The API operation creates a profile and an associated billing agreement.
     * Note: There is a one-to-one correspondence between billing agreements and recurring payments profiles. To associate a recurring payments profile with its billing agreement, you must ensure that the description in the recurring payments profile matches the description of a billing agreement. For version 54.0 and later, use SetExpressCheckout to initiate creation of a billing agreement.
     */
    function CreateRecurringPaymentsProfile($token, $email, $amount, $szRecurringPeriod)
    {

        $nvpstr = "";
        $nvpstr = $nvpstr . "&TOKEN=" . $token;
        if ($szRecurringPeriod == 'Yearly') {
            $nvpstr = $nvpstr . "&PROFILESTARTDATE=" . date("Y-m-d", mktime(0, 0, 0, date("m", time()), date("d", time()), date("Y", time()) + 1)) . "T04:45:00Z";
            $period = 'Year';
            $frequency = '1';
        } elseif ($szRecurringPeriod == 'Monthly') {
            $nvpstr = $nvpstr . "&PROFILESTARTDATE=" . date("Y-m-d", mktime(0, 0, 0, date("m", time()) + 1, date("d", time()), date("Y", time()))) . "T04:45:00Z";
            $period = 'Month';
            $frequency = '1';
        }

        $nvpstr = $nvpstr . "&DESC=Package Subscription";
        $nvpstr = $nvpstr . "&BILLINGPERIOD=" . $period;
        $nvpstr = $nvpstr . "&BILLINGFREQUENCY=" . $frequency;
        $nvpstr = $nvpstr . "&DESC=" . $szRecurringPeriod . " Subscription";
        $nvpstr = $nvpstr . "&AMT=" . $amount;
        $nvpstr = $nvpstr . "&CURRENCYCODE=USD";
        $nvpstr = $nvpstr . "&EMAIL=" . $email;
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_ITEMCATEGORY0=Physical";
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_NAME0=Advertisement";
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_AMT0=" . $amount;
        $nvpstr = $nvpstr . "&L_PAYMENTREQUEST_0_QTY0=1";

        $resArray = hash_call("CreateRecurringPaymentsProfile", $nvpstr);
        return $resArray;
    }

    /**
     * Obtain information about a recurring payments profile.
     */
    function GetRecurringPaymentsProfileDetails($profile_id)
    {
        $nvpstr = "";
        $nvpstr = $nvpstr . "&PROFILEID=" . $profile_id;

        $resArray = hash_call("GetRecurringPaymentsProfileDetails", $nvpstr);
        return $resArray;
    }

    /**
     * function to update status of any existing subscription
     * @desc The ManageRecurringPaymentsProfileStatus API operation cancels, suspends, or reactivates a recurring payments profile.
     * @access public
     * @param $profile_id , $action
     * ACTION must be one of the following-
     * 1. Cancel � Only profiles in Active or Suspended state can be canceled.
     * 2. Suspend � Only profiles in Active state can be suspended.
     * 3. Reactivate � Only profiles in a suspended state can be reactivated.
     * @return array
     */
    function ManageRecurringPaymentsProfileStatus($profile_id, $action)
    {
        $nvpstr = "";
        $nvpstr = $nvpstr . "&PROFILEID=" . $profile_id;
        $nvpstr = $nvpstr . "&ACTION=" . $action;

        $resArray = hash_call("ManageRecurringPaymentsProfileStatus", $nvpstr);
        return $resArray;
    }
}