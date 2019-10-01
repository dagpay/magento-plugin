<?php

namespace Dagcoin\PaymentGateway\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $setup->getConnection()->changeColumn(
            $setup->getTable('sales_payment_transaction'),
            'txn_id',
            'txn_id',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
            ]
        );

        $setup->endSetup();
    }
}
