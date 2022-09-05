<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit\Models;

use Smoren\QueryRelationManager\Yii2\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "address".
 *
 * @property int $id
 * @property int $city_id
 * @property string $name
 *
 * @property City $city
 * @property Place[] $places
 */
class Address extends ActiveRecord
{
    use ActiveRecordTrait;

    public static function primaryKey(): array
    {
        return ['id', 'city_id'];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return 'address';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['city_id', 'name'], 'required'],
            [['city_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [
                ['city_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => City::class,
                'targetAttribute' => ['city_id' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'city_id' => 'City ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCity(): ActiveQuery
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getPlaces(): ActiveQuery
    {
        return $this->hasMany(Place::class, ['address_id' => 'id']);
    }
}
