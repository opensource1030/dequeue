<?php

namespace App\Database\Services;

use App\Database\Repositories\AdminRepository;
use App\Database\Repositories\UserRepository;
use App\Database\Repositories\MerchantRepository;
use App\Database\Repositories\SubscriptionRepository;
use App\Database\Repositories\OrderRepository;
use App\Database\Repositories\UserOrderSubscriptionMappingRepository;
use App\Database\Repositories\GiftRepository;
use App\Database\Repositories\LocationRepository;
use App\Database\Repositories\InviteRepository;
use App\Database\Repositories\UserInviteMappingRepository;
use App\Database\Repositories\EmailTemplateRepository;

class Service {

    public $adminRepository;
    public $userRepository;
    public $merchantRepository;
    public $subscriptionRepository;
    public $orderRepository;
    public $uosMappingRepository;
    public $giftRepository;
    public $locationRepository;
    public $inviteRepository;
    public $uiMappingRepository;
    public $emailTemplateRepository;

    public function __construct(
        AdminRepository $adminRepository,
        UserRepository $userRepository,
        MerchantRepository $merchantRepository,
        SubscriptionRepository $subscriptionRepository,
        OrderRepository $orderRepository,
        UserOrderSubscriptionMappingRepository $uosMappingRepository,
        GiftRepository $giftRepository,
        LocationRepository $locationRepository,
        InviteRepository $inviteRepository,
        UserInviteMappingRepository $uiMappingRepository,
        EmailTemplateRepository $emailTemplateRepository
    )
    {
        $this->adminRepository = $adminRepository;
        $this->userRepository = $userRepository;
        $this->merchantRepository = $merchantRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->orderRepository = $orderRepository;
        $this->uosMappingRepository = $uosMappingRepository;
        $this->giftRepository = $giftRepository;
        $this->locationRepository = $locationRepository;
        $this->inviteRepository = $inviteRepository;
        $this->uiMappingRepository = $uiMappingRepository;
        $this->emailTemplateRepository = $emailTemplateRepository;
    }
}