<?php
// @codingStandardsIgnoreFile
namespace Dagcoin\PaymentGateway\Model;

class DagcoinPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    public $_isInitializeNeeded = false;
    public $redirect_uri;
    public $_code = 'dagcoin';
    public $_canOrder = true;
    public $_isGateway = true;
    
    public function getOrderPlaceRedirectUrl()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
                            ->get('Magento\Framework\UrlInterface')->getUrl("dagcoin/redirect");
    }
}
