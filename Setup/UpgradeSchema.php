<?php

namespace ValorPay\CardPay\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Add fee, token, rrn, auth_code
        $this->addColumn($setup, 'quote', 'valorpay_gateway_fee', 'Valorpay Gateway Fee');
        $this->addColumn($setup, 'quote', 'base_valorpay_gateway_fee', 'Base Valorpay Gateway Fee');
        $this->addColumn($setup, 'sales_order', 'valorpay_gateway_fee', 'Valorpay Gateway Fee');
        $this->addColumn($setup, 'sales_order', 'base_valorpay_gateway_fee', 'Base Valorpay Gateway Fee');
        $this->addColumn($setup, 'sales_invoice', 'valorpay_gateway_fee', 'Valorpay Gateway Fee');
        $this->addColumn($setup, 'sales_invoice', 'base_valorpay_gateway_fee', 'Base Valorpay Gateway Fee');
        $this->addColumn($setup, 'sales_creditmemo', 'valorpay_gateway_fee', 'Valorpay Gateway Fee');
        $this->addColumn($setup, 'sales_creditmemo', 'base_valorpay_gateway_fee', 'Base Valorpay Gateway Fee');
        $this->addColumn($setup, 'sales_order_payment', 'valor_token', 'ValorPay Token');
        $this->addColumn($setup, 'sales_order_payment', 'valor_rrn', 'ValorPay RRN');
        $this->addColumn($setup, 'sales_order_payment', 'valor_auth_code', 'ValorPay Auth Code');
        
        $setup->endSetup();
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param string $table
     * @param string $name
     * @param string $description
     */
    public function addColumn(SchemaSetupInterface $setup, $table, $name, $description)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable($table),
            $name,
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '100',
                'default' => '',
                'nullable' => true,
                'comment' => $description
            ]
        );
    }
}