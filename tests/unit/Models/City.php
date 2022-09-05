<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit\Models;

use Smoren\QueryRelationManager\Yii2\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "city".
 *
 * @property int $id
 * @property string $name
 *
 * @property Address[] $addresses
 */
class City extends ActiveRecord
{
    use ActiveRecordTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'city';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getAddresses()
    {
        return $this->hasMany(Address::class, ['city_id' => 'id']);
    }
}
