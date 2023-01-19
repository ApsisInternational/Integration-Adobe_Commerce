<?php

namespace Apsis\One\Block\Adminhtml\Config\Developer;

use Apsis\One\Model\Service\Log as ApsisLogHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\State;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Throwable;

class Reset extends Field
{
    /**
     * @var ApsisLogHelper
     */
    private ApsisLogHelper $apsisLogHelper;

    /**
     * @var string
     */
    public string $buttonLabel = 'Reset';

    /**
     * @var State
     */
    private State $state;

    /**
     * @param string $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel(string $buttonLabel): static
    {
        $this->buttonLabel = $buttonLabel;
        return $this;
    }

    /**
     * Url constructor.
     *
     * @param Context $context
     * @param State $state
     * @param ApsisLogHelper $apsisLogHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        State $state,
        ApsisLogHelper $apsisLogHelper,
        array $data = []
    ) {
        $this->state = $state;
        $this->apsisLogHelper = $apsisLogHelper;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function _getElementHtml(AbstractElement $element): string
    {
        try {
            $elm = $this->getLayout()
                ->createBlock(Button::class)
                ->setId('apsis_reset_button')
                ->setType('button')
                ->setLabel($this->buttonLabel);
            if ($this->state->getMode() === State::MODE_PRODUCTION) {
                $elm->setDisabled('disabled');
            }
            return $elm->toHtml();
        } catch (Throwable $e) {
            $this->apsisLogHelper->logError(__METHOD__, $e);
            return parent::_getElementHtml($element);
        }
    }
}
