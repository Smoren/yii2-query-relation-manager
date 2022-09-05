<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit;

use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Address;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\City;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Comment;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Place;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Smoren\QueryRelationManager\Yii2\QueryRelationManager;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class CommonUsageTest extends \Codeception\Test\Unit
{
    /**
     * @throws QueryRelationManagerException
     */
    public function testAddress()
    {
        $result = QueryRelationManager::select(Address::class, 'a')
            ->withSingle('city', City::class, 'c', 'a', ['id' =>  'city_id'])
            ->withMultiple('places', Place::class, 'p', 'a', ['address_id' =>  'id'])
            ->withMultiple('comments', Comment::class, 'cm', 'p', ['place_id' =>  'id'])
            ->all();

        $this->assertCount(4, $result);

        $resultMap = ArrayHelper::index($result, 'id');

        $this->assertEquals('Moscow', $resultMap[1]['city']['name']);
        $this->assertEquals('Moscow', $resultMap[2]['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[3]['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[4]['city']['name']);

        $this->assertCount(2, $resultMap[1]['places']);
        $this->assertCount(1, $resultMap[2]['places']);
        $this->assertCount(2, $resultMap[3]['places']);
        $this->assertCount(1, $resultMap[4]['places']);

        $mapPlaceIdToCommentsCount = [
            1 => 3,
            2 => 0,
            3 => 1,
            4 => 0,
            5 => 1,
            6 => 1,
        ];

        foreach($resultMap as $addressId => &$address) {
            foreach($address['places'] as $place) {
                $this->assertCount($mapPlaceIdToCommentsCount[$place['id']], $place['comments']);
            }
        }
        unset($address);
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testPlace()
    {
        $result = QueryRelationManager::select(Place::class, 'p')
            ->withSingle('address', Address::class, 'a', 'p', ['id' => 'address_id'])
            ->withSingle('city', City::class, 'c', 'a', ['id' => 'city_id'])
            ->withMultiple('comments', Comment::class, 'cm', 'p', ['place_id' => 'id'],
                'inner', 'and cm.mark >= :mark', [':mark' => 3])
            ->modify('p', function(array &$place) {
                $place['comments_count'] = count($place['comments']);
                $place['mark_five_count'] = 0;
                $place['mark_average'] = 0;

                foreach($place['comments'] as $comment) {
                    $place['mark_average'] += $comment['mark'];
                    if($comment['mark'] == 5) {
                        $place['mark_five_count']++;
                    }
                }

                $place['mark_average'] /= $place['comments_count'];
            })
            ->all();

        $this->assertCount(4, $result);

        $resultMap = ArrayHelper::index($result, 'id');

        $this->assertEquals('Tverskaya st., 7', $resultMap[1]['address']['name']);
        $this->assertEquals('Schipok st., 1', $resultMap[3]['address']['name']);
        $this->assertEquals('Mayakovskogo st., 12', $resultMap[5]['address']['name']);
        $this->assertEquals('Galernaya st., 3', $resultMap[6]['address']['name']);

        $this->assertEquals('Moscow', $resultMap[1]['address']['city']['name']);
        $this->assertEquals('Moscow', $resultMap[3]['address']['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[5]['address']['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[6]['address']['city']['name']);

        $this->assertCount(2, $resultMap[1]['comments']);
        $this->assertCount(1, $resultMap[3]['comments']);
        $this->assertCount(1, $resultMap[5]['comments']);
        $this->assertCount(1, $resultMap[6]['comments']);

        $this->assertEquals(2, $resultMap[1]['comments_count']);
        $this->assertEquals(1, $resultMap[3]['comments_count']);
        $this->assertEquals(1, $resultMap[5]['comments_count']);
        $this->assertEquals(1, $resultMap[6]['comments_count']);

        $this->assertEquals(1, $resultMap[1]['mark_five_count']);
        $this->assertEquals(1, $resultMap[3]['mark_five_count']);
        $this->assertEquals(0, $resultMap[5]['mark_five_count']);
        $this->assertEquals(0, $resultMap[6]['mark_five_count']);

        $this->assertEquals(4, $resultMap[1]['mark_average']);
        $this->assertEquals(5, $resultMap[3]['mark_average']);
        $this->assertEquals(4, $resultMap[5]['mark_average']);
        $this->assertEquals(3, $resultMap[6]['mark_average']);
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testCity()
    {
        $cityIds = City::find()->limit(2)->offset(1)->select('id')->column();
        $this->assertCount(2, $cityIds);

        $result = QueryRelationManager::select(City::class, 'c')
            ->withMultiple('addresses', Address::class, 'a', 'c', ['city_id' => 'id'])
            ->filter(function(Query $q) use ($cityIds) {
                $q->andWhere(['c.id' => $cityIds])->orderBy(['a.id' => SORT_ASC]);
            })
            ->all();

        $this->assertCount(2, $result);
        $this->assertEquals([], array_diff(ArrayHelper::getColumn($result, 'id'), $cityIds));

        $resultMap = ArrayHelper::index($result, 'id');

        $this->assertEquals('Samara', $resultMap[3]['name']);
        $this->assertEquals('St. Petersburg', $resultMap[2]['name']);

        $this->assertCount(0, $resultMap[3]['addresses']);
        $this->assertCount(2, $resultMap[2]['addresses']);
    }

    public function testRawSql()
    {
        $q = QueryRelationManager::select(Place::class, 'p')
            ->withSingle('address', Address::class, 'a', 'p', ['id' => 'address_id'])
            ->withSingle('city', City::class, 'c', 'a', ['id' => 'city_id'])
            ->withMultiple(
                'comments',
                Comment::class,
                'cm',
                'p',
                ['place_id' => 'id'],
                'inner',
                'and cm.mark >= :mark',
                [':mark' => 3]
            )
            ->getRawSql();

        $this->assertEquals(
            'SELECT `p`.`id` AS `p_id`, `p`.`address_id` AS `p_address_id`, `p`.`name` AS `p_name`, `a`.`id` AS `a_id`, `a`.`city_id` AS `a_city_id`, `a`.`name` AS `a_name`, `c`.`id` AS `c_id`, `c`.`name` AS `c_name`, `cm`.`id` AS `cm_id`, `cm`.`place_id` AS `cm_place_id`, `cm`.`username` AS `cm_username`, `cm`.`mark` AS `cm_mark`, `cm`.`text` AS `cm_text` FROM `place` `p` left join `address` `a` ON a.id = p.address_id  left join `city` `c` ON c.id = a.city_id  inner join `comment` `cm` ON cm.place_id = p.id and cm.mark >= 3',
            $q
        );
    }
}