<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit\Models;

use Smoren\QueryRelationManager\Yii2\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "place".
 *
 * @property int $id
 * @property int $address_id
 * @property string $name
 *
 * @property Comment[] $comments
 * @property Address $address
 */
class Place extends ActiveRecord
{
    use ActiveRecordTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'place';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['address_id', 'name'], 'required'],
            [['address_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['address_id'], 'exist', 'skipOnError' => true, 'targetClass' => Address::class, 'targetAttribute' => ['address_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'address_id' => 'Address ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['place_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getAddress()
    {
        return $this->hasOne(Address::class, ['id' => 'address_id']);
    }
}
