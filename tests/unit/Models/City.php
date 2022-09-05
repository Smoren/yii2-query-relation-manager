<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit\Models;

use Smoren\QueryRelationManager\Yii2\ActiveRecordTrait;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Bad\NonActiveRecordClass;
use yii\base\InvalidConfigException;
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
    public static function tableName(): string
    {
        return 'city';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getAddresses(): ActiveQuery
    {
        return $this->hasMany(Address::class, ['city_id' => 'id']);
    }

    /**
     * @return array
     */
    public function getNotModel(): array
    {
        return [];
    }

    /**
     * @return ActiveQuery
     */
    public function getBadActiveQuery(): ActiveQuery
    {
        return new ActiveQuery(NonActiveRecordClass::class);
    }

    /**
     * @return ActiveQuery
     * @throws InvalidConfigException
     */
    public function getBadActiveQueryVia(): ActiveQuery
    {
        return (new ActiveQuery(NonActiveRecordClass::class))
            ->viaTable(Address::tableName(), ['id' => 'id']);
    }
}
