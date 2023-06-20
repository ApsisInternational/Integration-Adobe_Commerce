<?php

namespace Apsis\One\Service;

use Apsis\One\Logger\Logger;
use Apsis\One\Model\ProfileModel;
use Apsis\One\Model\QueueModel;
use Apsis\One\Model\ResourceModel\Profile\ProfileCollection;
use Apsis\One\Model\ResourceModel\Profile\ProfileCollectionFactory;
use Apsis\One\Observer\Subscriber\SaveUpdateObserver;
use Apsis\One\Service\Sub\SubEventService;
use Apsis\One\Service\Sub\SubProfileService;
use Apsis\One\Service\Sub\SubQueueService;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Registry;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\ResourceModel\Subscriber as SubscriberResource;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

class ProfileService extends BaseService
{
    const ENTITY_CUSTOMER = 'customer';
    const ENTITY_TYPE_SUBSCRIBER = 'subscriber';
    const ENTITY_TYPES = [
        self::ENTITY_CUSTOMER,
        self::ENTITY_TYPE_SUBSCRIBER
    ];
    const PROFILE_DELETE = 'delete';

    /**
     * @var SubEventService
     */
    private SubEventService $subEventService;

    /**
     * @var SubscriberFactory
     */
    private SubscriberFactory $subscriberFactory;

    /**
     * @var ProfileCollectionFactory
     */
    private ProfileCollectionFactory $profileCollectionFactory;

    /**
     * @var SubQueueService
     */
    private SubQueueService $subQueueService;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var SubProfileService
     */
    public SubProfileService $subProfileService;

    /**
     * @var SubscriberResource
     */
    private SubscriberResource $subscriberResource;

    /**
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param WriterInterface $writer
     * @param SubEventService $subEventService
     * @param SubscriberFactory $subscriberFactory
     * @param ProfileCollectionFactory $profileCollectionFactory
     * @param SubQueueService $subQueueService
     * @param Registry $registry
     * @param SubProfileService $subProfileService
     * @param SubscriberResource $subscriberResource
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager,
        WriterInterface $writer,
        SubEventService $subEventService,
        SubscriberFactory $subscriberFactory,
        ProfileCollectionFactory $profileCollectionFactory,
        SubQueueService $subQueueService,
        Registry $registry,
        SubProfileService $subProfileService,
        SubscriberResource $subscriberResource
    ) {
        parent::__construct($logger, $storeManager, $writer);
        $this->profileCollectionFactory = $profileCollectionFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->subEventService = $subEventService;
        $this->subQueueService = $subQueueService;
        $this->registry = $registry;
        $this->subProfileService = $subProfileService;
        $this->subscriberResource = $subscriberResource;
    }

    /**
     * @return ProfileCollection
     */
    public function getProfileCollection(): ProfileCollection
    {
        return $this->profileCollectionFactory->create();
    }

    /**
     * @return Subscriber
     */
    private function getSubscriberModel(): Subscriber
    {
        return $this->subscriberFactory->create();
    }

    /**
     * @param int $storeId
     * @param string $email
     * @param int $customerId
     * @param int $subscriberId
     *
     * @return ProfileModel|bool
     */
    public function getProfile(
        int $storeId,
        string $email,
        int $customerId = 0,
        int $subscriberId = 0
    ): ProfileModel|bool {
        try {
            if ($customerId) {
                $profile = $this->getProfileCollection()
                    ->getFirstItemFromCollection(['store_id' => $storeId, 'customer_id' => $customerId]);
            } elseif ($subscriberId) {
                $profile = $this->getProfileCollection()
                    ->getFirstItemFromCollection(['store_id' => $storeId, 'subscriber_id' => $subscriberId]);
            }

            if (isset($profile) && $profile instanceof ProfileModel) {
                return $profile;
            }

            return $this->getProfileCollection()
                ->getFirstItemFromCollection(['store_id' => $storeId, 'email' => $email]);
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
            return false;
        }
    }

    /**
     * @param Subscriber|Customer $object
     *
     * @return void
     */
    public function createProfile(Subscriber|Customer $object): void
    {
        try {
            if ($object instanceof Subscriber) {
                $profile = $this->subProfileService->createProfile(
                    (int) $object->getStoreId(),
                    (string) $object->getEmail(),
                    $this,
                    (int) $object->getSubscriberId(),
                    null,
                    null,
                    $object->getSubscriberStatus()
                );

                if ($object->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED) {
                    $subscription = QueueModel::CONSENT_OPT_IN;
                } elseif ($object->getSubscriberStatus() === Subscriber::STATUS_UNSUBSCRIBED) {
                    $subscription = QueueModel::CONSENT_OPT_OUT;
                }

                if (isset($subscription) && $profile instanceof ProfileModel) {
                    $this->subQueueService->registerItem($profile, $this, $subscription);
                }
            } elseif ($object instanceof Customer) {
                $this->subProfileService->createProfile(
                    (int) $object->getStoreId(),
                    (string) $object->getEmail(),
                    $this,
                    null,
                    (int) $object->getId(),
                    (int) $object->getGroupId()
                );
            }
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
        }
    }

    /**
     * @param Subscriber|Customer $object
     * @param ProfileModel $profile
     *
     * @return void
     */
    public function updateProfile(Subscriber|Customer $object, ProfileModel $profile): void
    {
        try {
            if ($object instanceof Subscriber) {
                if ((int) $object->getSubscriberStatus() === Subscriber::STATUS_UNSUBSCRIBED) {
                    $this->subEventService->registerSubscriberUnsubscribeEvent($object, $profile, $this);
                    $subscription = QueueModel::CONSENT_OPT_OUT;
                } elseif ((int) $object->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED) {
                    $subscription = QueueModel::CONSENT_OPT_IN;
                    if ($profile->getIsCustomer() && ! $profile->getIsSubscriber()) {
                        $this->subEventService->registerCustomerBecomesSubscriberEvent($object, $profile, $this);
                    }
                }

                if (isset($subscription)) {
                    $this->subQueueService->registerItem($profile, $this, $subscription);
                }
            } elseif ($object instanceof Customer) {
                if ($profile->getIsSubscriber() && ! $profile->getIsCustomer()) {
                    $this->subEventService->registerSubscriberBecomesCustomerEvent($object, $profile, $this);
                }

                if ($object->getEmail() != $profile->getEmail()) {
                    $this->subEventService
                        ->eventResource
                        ->updateEventsEmail($profile->getEmail(), $object->getEmail(), $this);
                    $profile->setEmail($object->getEmail());
                }
            }
            $this->subProfileService->updateProfile($profile, $object, $this);
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
        }
    }

    /**
     * @param ProfileModel $profile
     * @param string $type
     *
     * @return void
     */
    public function deleteProfile(ProfileModel $profile, string $type): void
    {
        try {
            $status = $this->subProfileService->deleteProfile($profile, $type, $this);
            if ($type === self::ENTITY_TYPE_SUBSCRIBER && $status === false) {
                // Register consent update
                $this->subQueueService->registerItem($profile, $this, QueueModel::CONSENT_OPT_OUT);
            }
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
        }
    }

    /**
     * @param int $profileId
     *
     * @return bool|int
     */
    public function updateSubscription(int $profileId): bool|int
    {
        try {
            /** @var ProfileModel|bool $profile */
            $profile = $this->getProfileCollection()
                ->getFirstItemFromCollection('id', $profileId);
            if (! $profile) {
                return 404;
            }

            if (! $profile->getSubscriberId()) {
                return 404;
            }

            $subscriber = $this->getSubscriberModel();
            $this->subscriberResource->load($subscriber, $profile->getSubscriberId());
            if (! $subscriber->getId()) {
                return 404;
            }

            $this->registry->register($subscriber->getEmail() . SaveUpdateObserver::REGISTRY_NAME, true, true);
            $subscriber->unsubscribe();
            $profile->setSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED);
            $this->subProfileService->profileResource->save($profile);
            return true;
        } catch (Throwable $e) {
            $this->logError(__METHOD__, $e);
            return 500;
        }
    }
}
