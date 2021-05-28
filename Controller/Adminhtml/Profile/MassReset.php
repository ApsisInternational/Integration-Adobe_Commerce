<?php

namespace Apsis\One\Controller\Adminhtml\Profile;

use Apsis\One\Model\Profile;
use Apsis\One\Model\Service\Core as ApsisCoreHelper;
use Exception;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action;
use Apsis\One\Model\ResourceModel\Profile as ProfileResource;
use Apsis\One\Model\ResourceModel\Profile\CollectionFactory as ProfileCollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;

class MassReset extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Apsis_One::profile';

    /**
     * @var ProfileResource
     */
    public $profileResource;

    /**
     * @var ProfileCollectionFactory
     */
    public $profileCollectionFactory;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var ApsisCoreHelper
     */
    private $apsisCoreHelper;

    /**
     * MassDelete constructor.
     *
     * @param Context $context
     * @param ApsisCoreHelper $apsisLogHelper
     * @param ProfileResource $subscriberResource
     * @param Filter $filter
     * @param ProfileCollectionFactory $subscriberCollectionFactory
     */
    public function __construct(
        Context $context,
        ApsisCoreHelper $apsisLogHelper,
        ProfileResource $subscriberResource,
        Filter $filter,
        ProfileCollectionFactory $subscriberCollectionFactory
    ) {
        $this->apsisCoreHelper = $apsisLogHelper;
        $this->filter = $filter;
        $this->profileCollectionFactory = $subscriberCollectionFactory;
        $this->profileResource = $subscriberResource;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $collection = $this->profileCollectionFactory->create();
            $collection = $this->filter->getCollection($collection);
            $collectionSize = $collection->getSize();
            $ids = $collection->getAllIds();
            $this->profileResource->resetProfiles($this->apsisCoreHelper, [], $ids);
            $this->profileResource->resetProfiles(
                $this->apsisCoreHelper,
                [],
                $ids,
                Profile::SYNC_STATUS_NA,
                ['condition' => 'is_', 'value' => Profile::NO_FLAGGED]
            );
            $this->apsisCoreHelper->debug(
                __METHOD__,
                ['Total Reset' => $collectionSize, 'Profile Ids' => implode(", ", $ids)]
            );
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been reset.', $collectionSize));
        } catch (Exception $e) {
            $this->apsisCoreHelper->logError(__METHOD__, $e);
            $this->messageManager->addErrorMessage(__('An error happen during execution. Please check logs'));
        }
        return $resultRedirect->setPath('*/*/');
    }
}
