<?php

namespace Payplug\Payments\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Payplug\Payments\Model\Order\InstallmentPlan;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '0.0.2', '<')) {
            $this->addCustomerCardTable($setup);
        }
        if (version_compare($context->getVersion(), '0.0.3', '<')) {
            $this->addOrderProcessingTable($setup);
        }
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addInstallmentPlanTable($setup);
        }
        if (version_compare($context->getVersion(), '1.5.4', '<')) {
            $this->addInstallmentPlanPaymentColumn($setup);
        }
        if (version_compare($context->getVersion(), '1.6.0', '<')) {
            $this->addOndemandPaymentColumns($setup);
        }
    }

    private function addCustomerCardTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Prepare database for install
         */
        $installer->startSetup();

        //START table setup
        $table = $installer->getConnection()->newTable($installer->getTable('payplug_payments_card'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Entity ID'
            )
            ->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => false, 'nullable' => false, 'primary' => false, 'unsigned' => true],
                'Customer ID'
            )
            ->addColumn(
                'customer_card_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => false, 'nullable' => false, 'primary' => false, 'unsigned' => true],
                'Customer Card ID'
            )
            ->addColumn(
                'company_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => false, 'nullable' => false, 'primary' => false, 'unsigned' => true],
                'Company ID'
            )
            ->addColumn(
                'is_sandbox',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => 0],
                'Is Sandbox'
            )
            ->addColumn(
                'card_token',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false, 'default' => 'card_xxxxxxxxxxxxxxxxxxxxx'],
                'Card Token'
            )
            ->addColumn(
                'last4',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                4,
                ['nullable' => false, 'default' => '0000'],
                'Last 4 Digits'
            )
            ->addColumn(
                'exp_date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Expiration Date'
            )
            ->addColumn(
                'brand',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false, 'default' => 'Other'],
                'Card Brand'
            )
            ->addColumn(
                'country',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                3,
                ['nullable' => false, 'default' => '---'],
                'Country ISO Code'
            )
            ->addColumn(
                'metadata',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'MetaData'
            )
            ->addForeignKey(
                $installer->getFkName(
                    'payplug_payments_card',
                    'customer_id',
                    'customer_entity',
                    'entity_id'
                ),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('PayPlug Card Table');

        $installer->getConnection()->createTable($table);

        /**
         * Prepare database after install
         */
        $installer->endSetup();
    }

    private function addOrderProcessingTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Prepare database for install
         */
        $installer->startSetup();

        //START table setup
        $table = $installer->getConnection()->newTable($installer->getTable('payplug_payments_order_processing'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Entity ID'
            )
            ->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => false,'nullable' => false,'primary' => false,'unsigned' => true],
                'Order ID'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Created at'
            )
            ->addForeignKey(
                $installer->getFkName(
                    'payplug_payments_order_processing',
                    'order_id',
                    'sales_order',
                    'entity_id'
                ),
                'order_id',
                $installer->getTable('sales_order'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('PayPlug Order Processing Table');

        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addIndex(
            $setup->getTable('payplug_payments_order_processing'),
            $setup->getIdxName(
                'payplug_payments_order_processing',
                'order_id',
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            'order_id',
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        );

        /**
         * Prepare database after install
         */
        $installer->endSetup();
    }

    private function addInstallmentPlanTable(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Prepare database for install
         */
        $installer->startSetup();

        //START table setup
        $table = $installer->getConnection()->newTable($installer->getTable('payplug_payments_order_installment_plan'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true,'nullable' => false,'primary' => true,'unsigned' => true],
                'Entity ID'
            )
            ->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false],
                'Order Increment ID'
            )
            ->addColumn(
                'installment_plan_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                ['nullable' => false, 'default' => 'inst_xxxxxxxxxxxxxxxxxxxxx'],
                'Installment Plan ID'
            )
            ->addColumn(
                'is_sandbox',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => 0],
                'Is Sandbox'
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => InstallmentPlan::STATUS_NEW],
                'Installment Plan Status'
            )
            ->setComment('PayPlug Order Installment Plan Table');

        $installer->getConnection()->createTable($table);

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'payplug_payments_installment_plan_status',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => null,
                'nullable' => true,
                'comment' => 'Payplug Payments Installment Plan Status'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order_grid'),
            'payplug_payments_total_due',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                'length' => '12,4',
                'comment' => 'Total due'
            ]
        );

        /**
         * Prepare database after install
         */
        $installer->endSetup();
    }

    private function addInstallmentPlanPaymentColumn(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Prepare database for install
         */
        $installer->startSetup();

        $installer->getConnection()->addColumn(
            $installer->getTable('payplug_payments_order_payment'),
            'is_installment_plan_payment_processed',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                'length' => null,
                'nullable' => true,
                'comment' => 'Payplug Payments Installment Plan Payment Processed Flag',
            ]
        );

        /**
         * Prepare database after install
         */
        $installer->endSetup();
    }

    private function addOndemandPaymentColumns(SchemaSetupInterface $setup)
    {
        $installer = $setup;

        /**
         * Prepare database for install
         */
        $installer->startSetup();

        $columns = [
            'sent_by' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Payplug Payments On demand - sent by',
            ],
            'sent_by_value' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Payplug Payments On demand - sent by value',
            ],
            'language' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Payplug Payments On demand - language',
            ],
            'description' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '', // default text size: 1024
                'nullable' => true,
                'comment' => 'Payplug Payments On demand - description',
            ],
        ];

        $orderPaymentTable = $installer->getTable('payplug_payments_order_payment');
        foreach ($columns as $columnName => $columnDefinition) {
            $installer->getConnection()->addColumn(
                $orderPaymentTable,
                $columnName,
                $columnDefinition
            );
        }

        /**
         * Prepare database after install
         */
        $installer->endSetup();
    }
}
