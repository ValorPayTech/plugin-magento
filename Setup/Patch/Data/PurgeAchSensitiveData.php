<?php
namespace ValorPay\CardPay\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;

class PurgeAchSensitiveData implements DataPatchInterface
{
    private ResourceConnection $resourceConnection;
    private EncryptorInterface $encryptor;
    private LoggerInterface $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        EncryptorInterface $encryptor,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
    }

    public function apply(): self
    {
        $connection = $this->resourceConnection->getConnection();
        $this->processTable($connection, 'sales_order_payment');
        return $this;
    }

    private function processTable($connection, string $table): void
    {
        $tableName = $this->resourceConnection->getTableName($table);

        if (!$connection->isTableExists($tableName)) {
            $this->logger->warning("Table $tableName does not exist, skipping.");
            return;
        }

        $select = $connection->select()
            ->from($tableName, ['entity_id', 'additional_information'])
            ->where('additional_information LIKE ?', '%account_number%');

        $rows = $connection->fetchAll($select);

        if (count($rows) === 0) {
            return;
        }

        foreach ($rows as $row) {
            $eid  = $row['entity_id'];
            $info = json_decode($row['additional_information'], true);

            if (!is_array($info)) {
                $this->logger->warning("entity_id $eid: additional_information is not valid JSON, skipping.");
                continue;
            }

            if (($info['payment_method_type'] ?? '') !== 'ach') {
                continue;
            }

            $changed = false;

            if (!empty($info['account_number']) && $info['payment_method_type']  === 'ach') {
                if (!$this->isEncrypted($info['account_number'])) {
                    $info['account_number'] = $this->encryptor->encrypt($info['account_number']);
                    $changed = true;
                }
            }

            if (!empty($info['routing_number']) && $info['payment_method_type'] === 'ach') {
                if (!$this->isEncrypted($info['routing_number'])) {
                    $info['routing_number'] = $this->encryptor->encrypt($info['routing_number']);
                    $changed = true;
                }
            }

            if ($changed) {
                try {
                    $connection->update(
                        $tableName,
                        ['additional_information' => json_encode($info)],
                        ['entity_id = ?' => $eid]
                    );
                } catch (\Exception $e) {
                    $this->logger->error("entity_id $eid: UPDATE failed: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Check if value is already in Magento encrypted format: digits:digits:string
     */
    private function isEncrypted(string $value): bool
    {
        return (bool) preg_match('/^\d+:\d+:.+$/', $value);
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}