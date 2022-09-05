<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Bad;

use Smoren\QueryRelationManager\Yii2\ActiveRecordTrait;
use yii\db\ActiveRecord;

class BadActiveRecordClass extends ActiveRecord
{
    use ActiveRecordTrait;

    public static function tableName()
    {
        return 'test';
    }
}