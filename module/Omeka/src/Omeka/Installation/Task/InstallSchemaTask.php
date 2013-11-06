<?php
namespace Omeka\Installation\Task;

use Doctrine\DBAL\DBALException;
use Omeka\Installation\Result;

class InstallSchemaTask extends AbstractTask
{
    public function perform(Result $result)
    {
        $conn = $this->getServiceLocator()->get('EntityManager')->getConnection();
        $config = $this->getServiceLocator()->get('Config');
        
        // Check if tables already exist
        // @todo filter for only the proper prefixes in the table names
        $tables = $conn->getSchemaManager()->listTableNames();
        if (!empty($tables)) {
            $result->addMessage(
                'Omeka is already installed.',
                Result::MESSAGE_TYPE_ERROR,
                'InstallSchemaTask'
            );
            return;
        }

        $schema = OMEKA_PATH . '/data/install/schema.sql';
        if (!is_readable($schema)) {
            $result->addMessage(
                'Could not read the schema installation file.',
                Result::MESSAGE_TYPE_ERROR,
                'InstallSchemaTask'
            );
            return;
        }

        $statements = file($schema);
        if (!is_array($statements)) {
            $result->addMessage(
                'Could not read the schema installation file.',
                Result::MESSAGE_TYPE_ERROR,
                'InstallSchemaTask'
            );
            return;
        }
        
        // The schema file uses "DBPREFIX_" as a placeholder table prefix.
        // Replace them here with the configured table prefix.
        foreach ($statements as $statement) {
            $prefixedStatement = str_replace(
                'DBPREFIX_',
                $config['entity_manager']['table_prefix'],
                $statement
            );
            try {
                $conn->executeQuery($prefixedStatement);
            } catch (DBALException $e) {
                $result->addMessage(
                    $e->getMessage(),
                    Result::MESSAGE_TYPE_ERROR,
                    'InstallSchemaTask'
                );
                return;
            }
        }
    }
}
