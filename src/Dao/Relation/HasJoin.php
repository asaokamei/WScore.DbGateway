<?php
namespace WScore\Models\Dao\Relation;

use WScore\Models\Dao;
use WScore\Models\Entity\Magic;

/**
 * @property mixed dao
 */
class HasJoin extends RelationAbstract
{

    /**
     * @param      $target
     */
    public function __construct( $target )
    {
        $this->info = array(
            'target'    => $target,
        );
        $this->setupHasJoin();
    }

    /**
     * @param      $key
     * @param null $joinKey
     * @return $this
     */
    public function targetKey( $key, $joinKey=null )
    {
        $this->info = array_merge(
            $this->info,
            [
                'targetKey'  => $key,
                'joinTargetKey' => $joinKey,
            ]
        );
        return $this;
    }

    /**
     * @param $joinBy
     * @return $this
     */
    public function joinBy($joinBy)
    {
        $this->info = array_merge(
            $this->info,
            [
                'joinBy'  => $joinBy,
            ]
        );
        return $this;
    }

    /**
     * @param      $key
     * @param null $joinKey
     * @return $this
     */
    public function sourceKey( $key, $joinKey=null )
    {
        $this->info = array_merge(
            $this->info,
            [
                'sourceKey'     => $key,
                'joinSourceKey' => $joinKey,
            ]
        );
        return $this;
    }

    /**
     * 
     */
    protected function setupHasJoin()
    {
        $info = &$this->info;
        $target = $info['target'];
        if( !isset( $info['joinBy'] ) ) {
            $joinBy = [$this->dao->getTable(), Dao::dao($target)->getTable()];
            sort( $joinBy );
            $info['joinBy'] = implode( '_', $joinBy );
        }
        if( !isset( $info['targetKey'] ) ) {
            $info['targetKey'] = Dao::dao($target)->getKeyName();
        }
        if( !isset( $info['targetBy'] ) ) {
            $info['targetBy'] = $info['targetKey'];
        }
        if( !isset( $info['joinTargetKey'] ) ) {
            $info['joinTargetKey'] = $info['targetKey'];
        }
        if( !isset( $info['sourceKey'] ) ) {
            $info['sourceKey'] = $this->dao->getKeyName();
        }
        if( !isset( $info['joinSourceKey'] ) ) {
            $info['joinSourceKey'] = $info['sourceKey'];
        }
    }

    /**
     * @return bool
     */
    public function relate()
    {
        $sourceId = Magic::get( $this->source, $this->info['sourceKey']);
        $targetId = Magic::get( $this->target, $this->info['targetKey']);
        $join = Dao::dao( $targetId->info['joinBy']);
        foreach( $targetId as $id ) {
            $join->insert( [
                $this->info['joinSourceKey'] => $sourceId,
                $this->info['joinTargetKey'] => $id,
            ] );
        }
        return $this->isLinked = true;
    }
}