<?php
namespace WScore\DbGateway;


use ArrayObject;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Query\Builder;

/** @noinspection PhpHierarchyChecksInspection */
/**
 * Class Dao
 * @package WScore\Dao
 *
 * for PhpStorm: not to check for the missing methods in Builder
 *
 * @noinspection PhpHierarchyChecksInspection
 */
class Dao implements DaoInterface
{
    /**
     * @var Manager
     */
    protected $db;

    /**
     * @var Converter
     */
    protected $convert;

    /**
     * @var Builder
     */
    protected $lastQuery;

    /**
     * table name of the db.
     *
     * @var string
     */
    protected $table;

    /**
     * primary key (id) of the table.
     *
     * @var string
     */
    protected $primaryKey;

    /**
     * set to false when using insert, instead of insertId.
     *
     * @var string
     */
    protected $insertSerial = 'insertGetId';

    /**
     * datetime related format used in the database.
     *
     * @var array
     */
    protected $date_formats = 'Y-m-d H:i:s';

    /**
     * list of columns as array.
     *
     * @var array
     */
    protected $columns = array();

    /**
     * keep the last data to be inserted, updated, or selected.
     *
     * @var array|ArrayObject|mixed
     */
    protected $data;

    /*
     * fields for automated datetime columns.
     */
    protected $created_at   = 'created_at';
    protected $created_date = '';
    protected $created_time = '';
    protected $updated_at   = 'updated_at';
    protected $updated_date = '';
    protected $updated_time = '';

    // +----------------------------------------------------------------------+
    //  managing object.
    // +----------------------------------------------------------------------+
    /**
     * @param Manager $db
     * @param Converter $convert
     */
    public function __construct( $db, $convert )
    {
        $this->hooks( 'constructing' );
        $this->db = $db;
        $this->convert = $convert;

        if( !$this->table ) {
            $name = get_class($this);
            if( false!==strpos($name, '\\') ) {
                $name = substr( $name, strrpos($name,'\\')+1 );
            }
            $this->table = $name;
        }
        if( !$this->primaryKey ) {
            $this->primaryKey = $this->table . '_id';
        }
        $this->convert->setDao( $this );
        $this->convert->setDateTime( $this->created_at,   $this->date_formats );
        $this->convert->setDateTime( $this->updated_at,   $this->date_formats );
        $this->query();
        $this->hooks( 'constructed' );
    }

    /**
     * @return Builder
     */
    public function query()
    {
        $this->lastQuery = $this->db->table( $this->table );
        $this->hooks( 'newQuery' );
        return $this->lastQuery;
    }

    /**
     * @param $method
     * @param $args
     * @return $this
     * @throws \RuntimeException
     */
    public function __call( $method, $args )
    {
        if( method_exists( $this, $scope = 'scope'.ucfirst($method) ) ) {
            call_user_func_array( [$this, $scope], $args );
            return $this;
        }
        elseif( $this->lastQuery && method_exists( $this->lastQuery, $method ) ) {
            call_user_func_array( [$this->lastQuery, $method ], $args );
            return $this;
        }
        throw new \RuntimeException( 'no such method: '.$method );
    }

    /**
     * bad method! must rewrite!
     *
     * @return \DateTime
     */
    protected function getCurrentTime()
    {
        static $now;
        if( !$now ) $now = new \DateTime();
        return $now;
    }

    /**
     * dumb hooks for various events. $data are all string.
     * available events are:
     * - constructing, constructed, newQuery,
     * - selecting, selected, inserting, inserted,
     * - updating, updated, deleting, deleted,
     *
     * @param string $event
     * @param mixed  $values
     */
    protected function hooks( $event, $values=null )
    {
        if( method_exists( $this, $scope = 'on'.ucfirst($event) ) ) {
            call_user_func_array( [$this, $scope], $values );
        }
        /* example of a hook.
        if( $event == 'updating' ) {
            $this->lastQuery->lockForUpdate();
        }
        */
    }

    // +----------------------------------------------------------------------+
    //  Basic CRUD methods.
    // +----------------------------------------------------------------------+
    /**
     * @param array|ArrayObject $data
     * @return bool
     */
    public function insert( $data )
    {
        $this->data = $data;
        $this->updateTimeStamps( true );
        $values = $this->toString( $this->data );
        // insert data
        $this->hooks( 'inserting', $values );
        if( $this->insertSerial ) {
            $id = $this->lastQuery->insertGetId( $values );
            $this->convert->set( $values, $this->primaryKey, $id );
            $this->convert->set( $this->data, $this->primaryKey, $id );
        } else {
            $this->lastQuery->insert( $values );
            $id = true;
        }
        $this->hooks( 'inserted', $values );
        $this->query();
        return $id;
    }

    /**
     * @param array|ArrayObject $data
     * @return int
     */
    public function update( $data )
    {
        $this->data = $data;
        $this->updateTimeStamps();
        $values = $this->toString( $this->data );
        // update data
        $this->hooks( 'updating', $values );
        $ok = $this->lastQuery->update( $values );
        $this->hooks( 'updated', $values );

        $this->query();
        return $ok;
    }

    /**
     * @param array $columns
     * @return bool|string
     */
    public function select( $columns=array() )
    {
        $this->hooks( 'selecting' );
        $data = $this->lastQuery->select( $columns )->get();
        // select data
        $this->hooks( 'selected', $data );
        foreach( $data as $key => $td ) {
            $data[$key] = $this->toObject( $td );
        }
        $this->data = $data;
        $this->query();
        return $data;
    }

    /**
     * @param string $id
     * @return int
     */
    public function delete($id=null)
    {
        $this->hooks( 'deleting', $id );
        $result = $this->lastQuery->delete($id);
        $this->hooks( 'deleted', $id );
        $this->query();
        return $result;
    }

    /**
     * @param bool $insert
     */
    protected function updateTimeStamps( $insert=false )
    {
        $now = $this->getCurrentTime();
        if( $this->updated_at ) {
            $this->convert->set( $this->data, $this->updated_at, $now->format($this->date_formats) );
        }
        if( $this->updated_date ) {
            $this->convert->set( $this->data, $this->updated_date, $now->format('Y-m-d') );
        }
        if( $this->updated_time ) {
            $this->convert->set( $this->data, $this->updated_time, $now->format('H:i:s') );
        }
        if( !$insert ) return;

        if( $this->created_at ) {
            $this->convert->set( $this->data, $this->created_at, $now->format($this->date_formats) );
        }
        if( $this->created_date ) {
            $this->convert->set( $this->data, $this->created_date, $now->format('Y-m-d') );
        }
        if( $this->created_time ) {
            $this->convert->set( $this->data, $this->created_time, $now->format('H:i:s') );
        }
    }
    // +----------------------------------------------------------------------+
    //  managing data
    // +----------------------------------------------------------------------+
    /**
     * @param array $data
     * @return array
     */
    public function getColumns( $data=array() )
    {
        if( $this->columns ) return $this->columns;
        return array_keys( (array) $data );
    }

    /**
     * @param array $data
     * @return array|object
     */
    protected function toObject( $data )
    {
        return $this->convert->toEntity( $data );
    }

    /**
     * @param array|ArrayObject $data
     * @return array
     */
    protected function toString( $data )
    {
        return $this->convert->toArray( $data );
    }

    // +----------------------------------------------------------------------+
}