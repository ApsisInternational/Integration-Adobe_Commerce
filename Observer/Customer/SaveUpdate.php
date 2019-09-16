<?php

namespace Apsis\One\Observer\Customer;

use Apsis\One\Helper\Core as ApsisCoreHelper;
use Apsis\One\Model\EventFactory;
use Apsis\One\Model\Event;
use Apsis\One\Model\ResourceModel\Event as EventResource;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Customer;
use Apsis\One\Helper\Config as ApsisConfigHelper;
use Apsis\One\Model\ResourceModel\Profile\CollectionFactory as ProfileCollectionFactory;
use Apsis\One\Model\ResourceModel\Profile as ProfileResource;
use Apsis\One\Model\ProfileFactory;
use Apsis\One\Model\Profile;
use \Exception;

class SaveUpdate implements ObserverInterface
{
    /**
     * @var ApsisCoreHelper
     */
    private $apsisCoreHelper;

    /**
     * @var EventFactory
     */
    private $eventFactory;

    /**
     * @var EventResource
     */
    private $eventResource;

    /**
     * @var ProfileCollectionFactory
     */
    private $profileCollectionFactory;

    /**
     * @var ProfileResource
     */
    private $profileResource;

    /**
     * @var ProfileFactory
     */
    private $profileFactory;

    /**
     * SaveUpdate constructor.
     *
     * @param ApsisCoreHelper $apsisCoreHelper
     * @param EventFactory $eventFactory
     * @param EventResource $eventResource
     * @param ProfileCollectionFactory $profileCollectionFactory
     * @param ProfileResource $profileResource
     * @param ProfileFactory $profileFactory
     */
    public function __construct(
        ApsisCoreHelper $apsisCoreHelper,
        EventFactory $eventFactory,
        EventResource $eventResource,
        ProfileCollectionFactory $profileCollectionFactory,
        ProfileResource $profileResource,
        ProfileFactory $profileFactory
    ) {
        $this->profileResource = $profileResource;
        $this->profileCollectionFactory = $profileCollectionFactory;
        $this->profileFactory = $profileFactory;
        $this->eventFactory = $eventFactory;
        $this->apsisCoreHelper = $apsisCoreHelper;
        $this->eventResource = $eventResource;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        $account = (boolean) $this->apsisCoreHelper->getStoreConfig(
            $customer->getStore(),
            ApsisConfigHelper::CONFIG_APSIS_ONE_ACCOUNTS_OAUTH_ENABLED
        );

        if ($account) {
            $found = $this->profileCollectionFactory->create()->loadCustomerById($customer->getEntityId());
            $profile = ($found) ? $found : $this->profileCollectionFactory->create()->loadByEmailAndStoreId(
                $customer->getEmail(),
                $customer->getStoreId()
            );

            if (! $profile) {
                $this->createProfile($customer);
            } else {
                $this->registerEvent($customer, $profile);
                $this->updateProfile($customer, $profile);
            }
        }

        return $this;
    }

    /**
     * @param Customer $customer
     * @param Profile $profile
     */
    private function registerEvent(Customer $customer, Profile $profile)
    {
        $event = (boolean) $this->apsisCoreHelper->getStoreConfig(
            $customer->getStore(),
            ApsisConfigHelper::CONFIG_APSIS_ONE_EVENTS_SUBSCRIBER_2_CUSTOMER
        );
        $sync = (boolean) $this->apsisCoreHelper->getStoreConfig(
            $customer->getStore(),
            ApsisConfigHelper::CONFIG_APSIS_ONE_SYNC_SETTING_SUBSCRIBER_ENABLED
        );

        if ($event && $sync && $profile->getIsSubscriber() && ! $profile->getIsCustomer()) {
            $eventModel = $this->eventFactory->create()
                ->setEventType(Event::EVENT_TYPE_SUBSCRIBER_BECOMES_CUSTOMER)
                ->setEventData($this->apsisCoreHelper->serialize($this->getDataArr($customer, $profile)))
                ->setSubscriberId($profile->getSubscriberId())
                ->setCustomerId($customer->getEntityId())
                ->setStoreId($customer->getStoreId())
                ->setEmail($customer->getEmail())
                ->setStatus(Profile::SYNC_STATUS_PENDING);
            try {
                $this->eventResource->save($eventModel);
            } catch (Exception $e) {
                $this->apsisCoreHelper->logMessage(__METHOD__, $e->getMessage());
            }
        }
    }

    /**
     * @param Customer $customer
     * @param Profile $profile
     *
     * @return array
     */
    private function getDataArr(Customer $customer, Profile $profile)
    {
        $data = [
            'subscriber_id' => (int) $profile->getSubscriberId(),
            'customer_id' => (int) $customer->getEntityId(),
            'register_at' => (string) $this->apsisCoreHelper
                ->formatDateForPlatformCompatibility($customer->getCreatedAt()),
            'website_name' => (string) $this->apsisCoreHelper
                ->getWebsiteNameFromStoreId($customer->getStoreId()),
            'store_name' => (string) $this->apsisCoreHelper->getStoreNameFromId($customer->getStoreId())
        ];
        return $data;
    }

    /**
     * @param Customer $customer
     * @param Profile $profile
     */
    private function updateProfile(Customer $customer, Profile $profile)
    {
        try {
            if ($customer->getEmail() != $profile->getEmail()) {
                $this->eventResource->updateEventsEmail($profile->getEmail(), $customer->getEmail());
                $profile->setEmail($customer->getEmail());
            }

            if ($profile->getCustomerSyncStatus()) {
                $profile->setCustomerSyncStatus(Profile::SYNC_STATUS_PENDING);
            }

            $profile->setCustomerId($customer->getEntityId())
                ->setIsCustomer(Profile::IS_FLAGGED);

            $this->profileResource->save($profile);
        } catch (Exception $e) {
            $this->apsisCoreHelper->logMessage(__METHOD__, $e->getMessage());
        }
    }

    /**
     * @param Customer $customer
     */
    private function createProfile(Customer $customer)
    {
        try {
            $profile = $this->profileFactory->create()
                ->setStoreId($customer->getStoreId())
                ->setCustomerId($customer->getEntityId())
                ->setEmail($customer->getEmail())
                ->setIsCustomer(Profile::IS_FLAGGED);
            $this->profileResource->save($profile);
        } catch (Exception $e) {
            $this->apsisCoreHelper->logMessage(__METHOD__, $e->getMessage());
        }
    }
}