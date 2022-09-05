<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit\Models;

use Smoren\QueryRelationManager\Yii2\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "comment".
 *
 * @property int $id
 * @property int $place_id
 * @property string $username
 * @property int $mark
 * @property string $text
 *
 * @property Place $place
 */
class Comment extends ActiveRecord
{
    use ActiveRecordTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'comment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['place_id', 'username', 'mark', 'text'], 'required'],
            [['place_id', 'mark'], 'integer'],
            [['text'], 'string'],
            [['username'], 'string', 'max' => 255],
            [['place_id'], 'exist', 'skipOnError' => true, 'targetClass' => Place::class, 'targetAttribute' => ['place_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'place_id' => 'Place ID',
            'username' => 'Username',
            'mark' => 'Mark',
            'text' => 'Text',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getPlace()
    {
        return $this->hasOne(Place::class, ['id' => 'place_id']);
    }
}
