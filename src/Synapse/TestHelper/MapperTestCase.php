<?php

namespace Synapse\TestHelper;

use PHPUnit_Framework_TestCase;
use stdClass;
use Synapse\Stdlib\Arr;
use Zend\Db\Adapter\Platform\Mysql as MysqlPlatform;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\SqlInterface;
use Zend\Db\Sql\Update;

/**
 * Class for testing mappers.  Currently expects that you are using Mysqli.
 *
 * To use:
 * 1. Call parent::setUp() from setUp
 * 2. Instantiate the mapper
 * 3. Call setSqlFactory($this->mockSqlFactory) on the mapper.
 * 4. In your tests, get query strings with $this->getSqlStrings().
 */
abstract class MapperTestCase extends PHPUnit_Framework_TestCase
{
    use SecurityAwareTestCaseTrait;
    use DbAdapterTestCaseTrait;

    const LOGGED_IN_USER_ID = 42;
    const GENERATED_ID      = 123;

    protected $sqlStrings = [];

    protected $queries = [];

    protected $fallbackTableName = 'table';

    public function setUp()
    {
        $this->sqlStrings = [];

        $this->mockResultCallback = function ($mockResult, $index) {
            // No-op
        };

        $this->mockResultCount = 0;

        $this->setUpMockAdapter();

        $this->setUpMockSqlFactory();
    }

    /**
     * Set up a callback that is called for every mock result generated.
     * The callback should accept the mock object as its first argument
     * and an index as its second that is incremented for every mock result
     * generated.
     *
     * @param callable $callback [description]
     */
    public function setUpMockResultCallback(callable $callback)
    {
        $this->mockResultCallback = $callback;
    }

    public function getPlatform()
    {
        $mockMysqli = $this->getMock('mysqli');

        $mockMysqli->expects($this->any())
            ->method('real_escape_string')
            ->will($this->returnCallback(function ($value) {
                return addslashes($value);
            }));

        return new MysqlPlatform($mockMysqli);
    }

    public function getQueryAsSqlString(SqlInterface $query)
    {
        return $query->getSqlString($this->getPlatform());
    }

    public function getMockResult()
    {
        $mockResult = $this->getMock('Zend\Db\Adapter\Driver\ResultInterface');

        $mockResult->expects($this->any())
            ->method('getGeneratedValue')
            ->will($this->returnValue(self::GENERATED_ID));

        call_user_func($this->mockResultCallback, $mockResult, $this->mockResultCount);

        $this->mockResultCount += 1;

        return $mockResult;
    }

    public function getMockStatement()
    {
        $mockStatement = $this->getMock('Zend\Db\Adapter\Driver\StatementInterface');

        $mockStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($this->getMockResult()));

        return $mockStatement;
    }

    public function getMockSql()
    {
        $mockSql = $this->getMockBuilder('Zend\Db\Sql\Sql')
            ->setMethods(['select', 'insert', 'update', 'delete', 'prepareStatementForSqlObject'])
            ->disableOriginalConstructor()
            ->getMock();

        $mockSql->expects($this->any())
            ->method('prepareStatementForSqlObject')
            ->will($this->returnValue($this->getMockStatement()));

        $mockSql->expects($this->any())
            ->method('select')
            ->will($this->returnCallback(function () use ($mockSql) {
                $table = $mockSql->getTable() ?: (
                    $this->mapper ?
                    $this->mapper->getTableName() :
                    $this->fallbackTableName
                );
                $select = new Select($table);

                $this->queries[] = $select;

                return $select;
            }));

        $mockSql->expects($this->any())
            ->method('insert')
            ->will($this->returnCallback(function () use ($mockSql) {
                $table = $mockSql->getTable() ?: (
                    $this->mapper ?
                    $this->mapper->getTableName() :
                    $this->fallbackTableName
                );
                $insert = new Insert($table);

                $this->queries[] = $insert;

                return $insert;
            }));

        $mockSql->expects($this->any())
            ->method('update')
            ->will($this->returnCallback(function () use ($mockSql) {
                $table = $mockSql->getTable() ?: (
                    $this->mapper ?
                    $this->mapper->getTableName() :
                    $this->fallbackTableName
                );
                $update = new Update($table);

                $this->queries[] = $update;

                return $update;
            }));

        $mockSql->expects($this->any())
            ->method('delete')
            ->will($this->returnCallback(function () use ($mockSql) {
                $table = $mockSql->getTable() ?: (
                    $this->mapper ?
                    $this->mapper->getTableName() :
                    $this->fallbackTableName
                );
                $delete = new Delete($table);

                $this->queries[] = $delete;

                return $delete;
            }));

        return $mockSql;
    }

    public function setUpMockSqlFactory()
    {
        $this->mockSqlFactory = $this->getMock('Synapse\Mapper\SqlFactory');

        $this->mockSqlFactory->expects($this->any())
            ->method('getSqlObject')
            // Using returnCallback, because otherwise a reference to the same object will
            // be returned every time.
            ->will($this->returnCallback(function () {
                return $this->getMockSql();
            }));
    }

    protected function getSqlStrings()
    {
        $stringifiedQueries = array_map(function ($query) {
            return $this->getQueryAsSqlString($query);
        }, $this->queries);

        return array_merge($stringifiedQueries, $this->sqlStrings);
    }

    protected function getSqlString($key = 0)
    {
        $sqlStrings = $this->getSqlStrings();

        return Arr::get($sqlStrings, $key);
    }

    protected function assertRegExpOnSqlString($regexp, $sqlStringKey = 0)
    {
        $sqlString = $this->getSqlString($sqlStringKey);

        $this->assertRegExp($regexp, $sqlString);
    }
}
