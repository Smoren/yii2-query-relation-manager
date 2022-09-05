<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Bad;

use Smoren\QueryRelationManager\Yii2\ActiveRecordTrait;

class NonActiveRecordClass
{
    use ActiveRecordTrait;

    public static function tableName(): string
    {
        return 'not_exist';
    }
}