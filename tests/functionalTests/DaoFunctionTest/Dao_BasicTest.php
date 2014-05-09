<?php
namespace tests\ConstructTest;

use Illuminate\Database\Capsule\Manager as Capsule;
use Users;
use WScore\functionalTests\UsersModel\UsersDao;

require_once( dirname( dirname( __DIR__ ) ) . '/autoload.php' );
require_once( dirname( __DIR__ ) . '/config.php' );

class Dao_BasicTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UsersDao
     */
    var $dao;

    /**
     * @var Capsule
     */
    var $capsule;
    
    function setup()
    {
        $this->capsule = \ConfigDB::getCapsule();
        $this->dao = UsersDao::getInstance( $this->capsule );
        \ConfigDB::setupTable();
    }

    /**
     * @return Users
     */
    function createUser()
    {
        $user = Users::create([
            'status' => 1,
            'gender' => 'F',
            'name'   => 'name:'.mt_rand(1000,9999),
            'birth_date' => '1989-01-23',
            'email'  => 'm'.mt_rand(1000,9999).'@example.com',
        ]);
        return $user;
    }
    
    function test0()
    {
        $this->assertEquals( 'Illuminate\Database\Capsule\Manager', get_class($this->capsule) );
        $this->assertEquals( 'Illuminate\Database\Eloquent\Builder', get_class(\Users::query()) );
        $this->assertEquals( 'WScore\functionalTests\UsersModel\UsersDao', get_class($this->dao) );
        $this->assertEquals( 'Users', get_class( $this->createUser() ) );
    }

    /**
     * @test
     */
    function UserDao_finds_a_user_data()
    {
        $user = $this->createUser();
        $pKey = $user->getKey();
        $found = $this->dao->where( 'user_id', '=', $pKey )->select();
        $this->assertEquals( 1, count( $found ) );
        $daoUser = $found[0];
        $this->assertTrue( is_object( $daoUser ) );
        $this->assertEquals( 'ArrayObject', get_class( $daoUser ) );
        $this->assertEquals( $pKey, $daoUser['user_id'] );
        $this->assertEquals( $user->name, $daoUser['name'] );
    }

    /**
     * @test
     */
    function UserDao_find_returns_user_data()
    {
        $user = $this->createUser();
        $pKey = $user->getKey();
        $daoUser = $this->dao->where( 'user_id', '=', $pKey )->first();
        $this->assertTrue( is_array( $daoUser ) );
        $this->assertEquals( $pKey, $daoUser['user_id'] );
        $this->assertEquals( $user->name, $daoUser['name'] );
    }
}