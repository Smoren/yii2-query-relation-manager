<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit;

use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Smoren\QueryRelationManager\Yii2\QueryRelationManager;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Bad\BadActiveRecordClass;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Bad\NonActiveRecordClass;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\City;

class ErrorsTest extends \Codeception\Test\Unit
{
    public function testBadClass()
    {
        try {
            QueryRelationManager::select('ClassNotExist', 'c');
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals("class 'ClassNotExist' does not exist", $e->getMessage());
        }

        try {
            NonActiveRecordClass::select();
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals(
                'class Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Bad\NonActiveRecordClass'.
                ' is not an instance of ActiveRecord',
                $e->getMessage()
            );
        }
    }

    public function testBadRelations()
    {
        try {
            City::select()->with('badRelation', 'br');
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals(
                'method Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\City::getBadRelation() not exists',
                $e->getMessage()
            );
        }

        try {
            City::select('c')->with('notModel', 'nm');
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals(
                'method Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\City::getNotModel() '.
                'returned non-ActiveQuery instance',
                $e->getMessage()
            );
        }

        try {
            City::select('c')->with('badActiveQuery', 'baq');
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals(
                'cannot use relations without "link" section',
                $e->getMessage()
            );
        }

        try {
            City::select('c')->with('badActiveQueryVia', 'baqv');
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals(
                'cannot use relations with "via" section yet',
                $e->getMessage()
            );
        }
    }
}