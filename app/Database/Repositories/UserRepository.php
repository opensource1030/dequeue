<?php

namespace App\Database\Repositories;

use Illuminate\Container\Container as Application;
use Carbon\Carbon;

use Log;
use Exception;
use StringHelper;
use EmailHelper;

class UserRepository extends Repository {

    use AuthenticatableTrait;

    protected $emailTemplateRepository;
    protected $uiMappingRepository;
    protected $giftRepository;
    protected $merchantRepository;

    public function __construct(
        EmailTemplateRepository $emailTemplateRepository,
        UserInviteMappingRepository $uiMappingRepository,
        GiftRepository $giftRepository,
//        SubscriptionRepository $subscriptionRepository,
        MerchantRepository $merchantRepository
    ) {
        parent::__construct(Application::getInstance());

        $this->emailTemplateRepository = $emailTemplateRepository;
        $this->uiMappingRepository = $uiMappingRepository;
        $this->giftRepository = $giftRepository;
        $this->merchantRepository = $merchantRepository;
    }

    function model() {
        return "\\App\\Database\\Models\\User";
    }

    /**
     * sign in by facebook account
     *
     * @param $data
     * @return array|\Illuminate\Database\Eloquent\Model|null|static
     */
    function signInByFacebook($data)
    {
        $model = $this->model->newQuery()
            ->where('isDeleted', 0)
            ->where('szEmail', $data['szEmail'])
            ->first();

        if (isset($model['id']) && $model['id'] > 0) {

            if ($model->iFacekbookMapped == 0) {
                $model->iFacekbookMapped = 1;
                $model->save();
            }

            if ($model->szMobileKey == '' || $model->szMobileKey == 'NULL') {
                $model->szMobileKey = StringHelper::uniqueKey();
                $model->save();
            }

            return $model;
        } else {
            return array();
//            throw new \Exception('Invalid Email');
        }
    }

    function updateUserName_($userAry)
    {
        $this->set_szFirstName($userAry->szFirstName);
        $this->set_szLastName($userAry->szLastName);

        if($userAry->szMobileKey=='')
        {
            $this->addError("szMobileKey","Invalid user id.");
        }

        $kSubscription = new cSubscription();
        $resArr=$kSubscription->getUserIdByMobilekey($userAry->szMobileKey);
        $idUser=$resArr['id'];

        if((int)$idUser==0)
        {
            $res["site_response"]=array("response"=>"ERROR");
            $res["site_message"]=array("message"=>"Invalid User Key");
            return $res;
        }

        $this->set_szEmail($userAry->szEmail);
        $this->set_szZipCode($userAry->szZipCode);

        if(strlen($this->szZipCode) != 5 &&  strlen($this->szZipCode) > 0 )
        {
            $this->addError('szZipCode',"ZipCode must be 5 digits.");
        }

        if($this->existsEmail($this->szEmail, $idUser) && $this->szEmail!='')
        {
            $this->addError("szEmail","Email address already exists!");

        }
        if($this->error == true)
        {
            $res["site_response"]=array("response"=>"ERROR");
            $res["site_message"]=array("message"=>reset($this->arErrorMessages));
            return $res;
        }

        $query="
			UPDATE
				".__DBC_SCHEMATA_USERS__."
			SET
				szFirstName='".mysql_real_escape_string($this->szFirstName)."',
				szLastName='".mysql_real_escape_string($this->szLastName)."',
				szEmail='".mysql_real_escape_string($this->szEmail)."',
				szZipCode='".mysql_real_escape_string($this->szZipCode)."'
			WHERE
				szMobileKey='".mysql_real_escape_string(trim($userAry->szMobileKey))."'
		";
        if($result = $this->exeSQL($query))
        {
            $res["site_response"]=array("response"=>"SUCCESS");
            $res["site_message"]=array("message"=>"Account updated.");
            return $res;
        }
        else
        {
            $res["site_response"]=array("response"=>"ERROR");
            $res["site_message"]=array("message"=>"Connection Error");
            return $res;
        }
    }

    function getUserDetailById($idUser)
    {
        $query="
			SELECT
            	id,
                szFirstName,
                szLastName,
                szEmail,
                szPassword,
                dtCreated,
                dtUpdated,
                keyDateTime,
                szForgotPasswordKey,
                szFileName,
                szUploadFileName,
                szZipCode,
                szPaymentToken,
                szMobileKey,
				fTotalCredit
			FROM
				".__DBC_SCHEMATA_USERS__."
            WHERE
				id='".(int)$idUser. "'
			";

        if($result = $this->exeSQL($query))
        {
            if($this->iNumRows > 0)
            {
                if($row = $this->getAssoc($result))
                {
                    return $row;
                }
            }
        }
        else
        {
            return array();
        }
    }

    function getUserDetailByMobileKey($mobileKey)
    {
        $query="
			SELECT
            	id,
                szFirstName,
                szLastName,
                szEmail,
                szPassword,
                dtCreated,
                dtUpdated,
                keyDateTime,
                szForgotPasswordKey,
                szFileName,
                szUploadFileName,
                szZipCode
			FROM
				".__DBC_SCHEMATA_USERS__."
            WHERE
				szMobileKey='".$mobileKey. "'
			";
        if($result = $this->exeSQL($query))
        {
            if($this->iNumRows > 0)
            {
                if($row = $this->getAssoc($result))
                {
                    return $row;
                }
            }
        }
        else
        {

            return array();
        }
    }

    /**
     * function to search userdetails By Id
     * @access public
     * @param numeric $idUser
     * @return Array
     */
    function fetchUserDetailById($idUser)
    {
        $query="
			SELECT
				id,
				szFirstName,
				szLastName,
				szEmail,
				szPassword,
				szForgotPasswordKey,
				keyDateTime,
				szZipCode,
				szInviteCode
			FROM
				".__DBC_SCHEMATA_USERS__."
			WHERE
				id='".(int)$idUser."'
		";
        if( ($result = $this->exeSQL($query)) )
        {
            if($this->iNumRows>0)
            {
                if($row = $this->getAssoc($result))
                    return $row;
            }
            else
            {
                return array();
            }
        }
        else
        {
            return array();
        }
    }

    /**
     * function to insert user invite code
     * @access public
     * @param array $data
     * @return string
     */
    function add_user_invite_code($data)
    {
        if($data->szMobileKey=='')
        {
            $this->addError("szMobileKey","Mobile key is requied");
        }

        if($this->error == true)
        {
            $res["site_response"]=array("response"=>"ERROR");
            $res["site_message"]=array("message"=>reset($this->arErrorMessages));
            return $res;
        }

        $kSubscription = new cSubscription();
        $resArr=$kSubscription->getUserIdByMobilekey($data->szMobileKey);
        $idUser=$resArr['id'];

        if((int)$idUser==0)
        {
            $res["site_response"]=array("response"=>"ERROR");
            $res["site_message"]=array("message"=>"Invalid User Key");
            return $res;
        }

        $invitecode='';
        $userDetailAry=$this->fetchUserDetailById($idUser);

        if($userDetailAry['szInviteCode'] !='')
        {
            $invitecode=$userDetailAry['szInviteCode'];
        }
        else
        {
            $invitecode=generateInviteCode();

            $invitecode=$this->checkInvitecodeAlreadyExists($invitecode);
        }

        if($userDetailAry['szInviteCode']=='')
        {
            $query="
				UPDATE
					".__DBC_SCHEMATA_USERS__."
	            SET
                	szInviteCode='".mysql_real_escape_string($invitecode)."',
					dtUpdated=NOW()
	             WHERE
	             	id='".(int)$idUser."'
            ";

            if($result = $this->exeSQL($query))
            {
                $res["share_message_details"]=array("screen_message"=>$invitecode ,
                    "text_message"=>$invitecode,
                    "email_message"=>$invitecode,
                    "twitter_message"=>$invitecode,
                    "facebook_message"=>$invitecode);

                $res["site_response"]=array("response"=>"SUCCESS");
                return $res;

            }
        }
        else
        {
            $res["share_message_details"]=array("screen_message"=>$invitecode ,
                "text_message"=>$invitecode,
                "email_message"=>$invitecode,
                "twitter_message"=>$invitecode,
                "facebook_message"=>$invitecode);

            $res["site_response"]=array("response"=>"SUCCESS");
            return $res;
        }
    }

    /**
     * return users with the specified invite code
     *
     * @param $inviteCode
     * @return mixed
     */
    function checkInviteCode($inviteCode)
    {
        $result = $this->model
            ->where('isDeleted', 0)
            ->where('szInviteCode', $inviteCode)
            ->whereNotIn('szInviteCode', ['', 'NULL'])
            ->get();

        return $result;
    }

    /**
     * generate new inviteCode
     *
     * @param $inviteCode
     * @return mixed
     */
    function checkInvitecodeAlreadyExists($inviteCode)
    {
        while ($this->checkInviteCode($inviteCode)->count > 0) {
            $inviteCode = StringHelper::randomCode();
        }
        return $inviteCode;
    }

    /**
     * create a new invitation record
     *
     * @param $RefereruserAry
     * @param $idUser
     * @return mixed
     */
    function insert_invitecode_mapping($RefereruserAry, $idUser)
    {
        $attributes = array (
            'idReferUser'       => (int) $RefereruserAry['id'],
            'idSignupUser'      => (int) $idUser,
            'szInviteCode'      => $RefereruserAry['szInviteCode'],
            'fReferralcredit'   => __INVITE_REFERRAL_CREDIT__,
            'dtSignup'          => Carbon::now()->toDateTimeString()
        );
        $invitation = $this->uiMappingRepository->create($attributes);
        return $invitation;
    }
}