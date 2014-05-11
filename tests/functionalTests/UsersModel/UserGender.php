<?php
namespace WScore\functionalTests\UsersModel;

use WScore\DbGateway\Enum\AbstractEnum;

class UserGender extends AbstractEnum
{
    const MALE    = 'M';
    const FEMALE  = 'F';

    static $choices = [
        self::MALE    => 'male',
        self::FEMALE  => 'female',
    ];
}

