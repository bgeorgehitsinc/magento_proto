<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TestFramework\Annotation;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Validates tests isolation. Makes sure that test does not keep exceed data in DB.
 */
class TestsIsolation
{
    /**
     * This variable was created to keep initial data cached
     *
     * @var array
     */
    private $dbTableState = [];

    /**
     * @var string[]
     */
    private $testTypesToCheckIsolation = [
        'integration',
    ];

    /**
     * @var int
     */
    private $isolationLevel = 0;

    /**
     * @var string[]
     */
    private $dbStateTables = [
        'catalog_product_entity',
        'eav_attribute',
        'catalog_category_entity',
        'eav_attribute_set',
        'store',
        'store_website',
        'url_rewrite'
    ];

    /**
     * Pull data from specific table
     *
     * @param string $table
     * @return array
     */
    private function pullDbState(string $table): array
    {
        $resource = ObjectManager::getInstance()->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $select = $connection->select()->from($table);
        return $connection->fetchAll($select);
    }

    /**
     * Create DB snapshot before test run.
     *
     * @param TestCase $test
     * @param array|null $dbIsolationState
     * @return void
     */
    public function createDbSnapshot(TestCase $test, ?array $dbIsolationState): void
    {
        if (null !== $dbIsolationState
            && ($dbIsolationState !== ['enabled'])
            && ($this->checkIsolationRequired($test))
        ) {
            ++$this->isolationLevel;
            if ($this->isolationLevel === 1) {
                $this->saveDbStateBeforeTestRun($test);
            }
        }
    }

    /**
     * Check DB isolation when test ended.
     *
     * @param TestCase $test
     * @param array|null $dbIsolationState
     * @return void
     */
    public function checkTestIsolation(TestCase $test, ?array $dbIsolationState): void
    {
        if (null !== $dbIsolationState
            && ($dbIsolationState !== ['enabled'])
            && ($this->checkIsolationRequired($test))
        ) {
            --$this->isolationLevel;
            if ($this->isolationLevel === 1) {
                $this->checkResidualData($test);
            }
        }
    }

    /**
     * Saving DB snapshot before fixtures applying.
     *
     * @param TestCase $test
     * @return void
     */
    private function saveDbStateBeforeTestRun(TestCase $test): void
    {
        try {
            if (empty($this->dbTableState)) {
                foreach ($this->dbStateTables as $table) {
                    $this->dbTableState[$table] = $this->pullDbState($table);
                }
            }
        } catch (\Throwable $e) {
            $test->getTestResultObject()->addFailure($test, new AssertionFailedError($e->getMessage()), 0);
        }
    }

    /**
     * Check if test isolation is required for given scope of tests.
     *
     * @param TestCase $test
     * @return bool
     */
    private function checkIsolationRequired(TestCase $test): bool
    {
        $isRequired = false;
        if (!$test->getTestResultObject()) {
            return $isRequired;
        }
        $passedClasses = $test->getTestResultObject()->passedClasses();

        if ($passedClasses) {
            $testFilename = current($passedClasses);

            foreach ($this->testTypesToCheckIsolation as $testType) {
                if (false !== strpos($testFilename, \sprintf('/dev/tests/%s/', $testType))) {
                    $isRequired = true;
                    break;
                }
            }
        }

        return $isRequired;
    }

    /**
     * Check if there's residual data in DB after test execution.
     *
     * @param TestCase $test
     * @return void
     */
    private function checkResidualData(TestCase $test): void
    {
        $isolationProblem = [];
        foreach ($this->dbTableState as $table => $isolationData) {
            try {
                $diff = $this->dataDiff($isolationData, $this->pullDbState($table));
                if (!empty($diff)) {
                    $isolationProblem[$table] = $diff;
                }
            } catch (\Throwable $e) {
                $test->getTestResultObject()->addFailure($test, new AssertionFailedError($e->getMessage()), 0);
            }
        }

        if (!empty($isolationProblem)) {
            $test->getTestResultObject()->addFailure(
                $test,
                new AssertionFailedError(
                    "There was a problem with isolation: " . var_export($isolationProblem, true)
                ),
                0
            );
        }
    }

    /**
     * Compare data difference for m-dimensional array
     *
     * @param array $dataBefore
     * @param array $dataAfter
     * @return array
     */
    private function dataDiff(array $dataBefore, array $dataAfter): array
    {
        $diff = [];
        if (count($dataBefore) !== count($dataAfter)) {
            $diff = \array_slice($dataAfter, count($dataBefore));
        }

        return $diff;
    }
}
