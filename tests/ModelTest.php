<?php
use Avalon\Database\ConnectionManager;
use Doctrine\DBAL\Schema\Schema;

use Avalon\Tests\Models\User;

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected static $conn;

    /**
     * Connect to the database and create required tables.
     */
    public static function setUpBeforeClass(){
        static::$conn = ConnectionManager::create([
            'driver'   => 'pdo_sqlite',
            'user'     => 'root',
            'password' => 'root',
            'memory'   => true
        ]);

        $schema = new Schema;

        $users = $schema->createTable("users");
        $users->addColumn("id", "integer", ["unsigned" => true]);
        $users->addColumn("username", "string", ["length" => 32]);
        $users->setPrimaryKey(["id"]);
        $users->addUniqueIndex(["username"]);

        foreach ($schema->toSql(static::$conn->getDatabasePlatform()) as $query) {
            static::$conn->query($query);
        }
    }

    /**
     * Test Model::create([...])
     */
    public function testCreate()
    {
        $user = User::create([
            'username' => "tester",
        ]);

        $this->assertInstanceOf('Avalon\Tests\Models\User', $user);
    }

    /**
     * Test Model->save()
     */
    public function testSave()
    {
        $user = new User([
            'username' => 'another_tester'
        ]);

        $this->assertTrue($user->save());
    }

    /**
     * Test Model::find(...)
     *
     * Also happens to test Model::insert([...])
     */
    public function testFind()
    {
        User::insert(['id' => 302, 'username' => 'yet_another_test']);

        $user = User::find(302);
        $this->assertEquals('yet_another_test', $user->username);

        $this->assertFalse(User::find(404));
    }
}
