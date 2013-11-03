<?php
namespace WScore\DbGateway\Filter;

use WScore\DbGateway\QueryInterface;

class SetTimeCreatedAt extends FilterAbstract
{
    use SetTimeTrait;
    
    /**
     * @param QueryInterface $query
     */
    protected function onInsert( $query )
    {
        $this->setTime( $query, $this->column_name );
    }

}