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

        $this->assertTrue(count($result) == 4);

        $resultMap = ArrayHelper::index($result, 'id');

        $this->assertTrue($resultMap[1]['city']['name'] == 'Moscow');
        $this->assertTrue($resultMap[2]['city']['name'] == 'Moscow');
        $this->assertTrue($resultMap[3]['city']['name'] == 'St. Petersburg');
        $this->assertTrue($resultMap[4]['city']['name'] == 'St. Petersburg');

        $this->assertTrue(count($resultMap[1]['places']) == 2);
        $this->assertTrue(count($resultMap[2]['places']) == 1);
        $this->assertTrue(count($resultMap[3]['places']) == 2);
        $this->assertTrue(count($resultMap[4]['places']) == 1);

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
                $this->assertTrue(count($place['comments']) == $mapPlaceIdToCommentsCount[$place['id']]);
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

        $this->assertTrue(count($result) == 4);

        $resultMap = ArrayHelper::index($result, 'id');

        $this->assertTrue($resultMap[1]['address']['name'] == 'Tverskaya st., 7');
        $this->assertTrue($resultMap[3]['address']['name'] == 'Schipok st., 1');
        $this->assertTrue($resultMap[5]['address']['name'] == 'Mayakovskogo st., 12');
        $this->assertTrue($resultMap[6]['address']['name'] == 'Galernaya st., 3');

        $this->assertTrue($resultMap[1]['address']['city']['name'] == 'Moscow');
        $this->assertTrue($resultMap[3]['address']['city']['name'] == 'Moscow');
        $this->assertTrue($resultMap[5]['address']['city']['name'] == 'St. Petersburg');
        $this->assertTrue($resultMap[6]['address']['city']['name'] == 'St. Petersburg');

        $this->assertTrue(count($resultMap[1]['comments']) == 2);
        $this->assertTrue(count($resultMap[3]['comments']) == 1);
        $this->assertTrue(count($resultMap[5]['comments']) == 1);
        $this->assertTrue(count($resultMap[6]['comments']) == 1);

        $this->assertTrue($resultMap[1]['comments_count'] == 2);
        $this->assertTrue($resultMap[3]['comments_count'] == 1);
        $this->assertTrue($resultMap[5]['comments_count'] == 1);
        $this->assertTrue($resultMap[6]['comments_count'] == 1);

        $this->assertTrue($resultMap[1]['mark_five_count'] == 1);
        $this->assertTrue($resultMap[3]['mark_five_count'] == 1);
        $this->assertTrue($resultMap[5]['mark_five_count'] == 0);
        $this->assertTrue($resultMap[6]['mark_five_count'] == 0);

        $this->assertTrue($resultMap[1]['mark_average'] == 4);
        $this->assertTrue($resultMap[3]['mark_average'] == 5);
        $this->assertTrue($resultMap[5]['mark_average'] == 4);
        $this->assertTrue($resultMap[6]['mark_average'] == 3);
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testCity()
    {
        $cityIds = City::find()->limit(2)->offset(1)->select('id')->column();
        $this->assertTrue(count($cityIds) == 2);

        $result = QueryRelationManager::select(City::class, 'c')
            ->withMultiple('addresses', Address::class, 'a', 'c', ['city_id' => 'id'])
            ->filter(function(Query $q) use ($cityIds) {
                $q->andWhere(['c.id' => $cityIds])->orderBy(['a.id' => SORT_ASC]);
            })
            ->all();

        $this->assertTrue(count($result) == 2);
        $this->assertTrue(array_diff(ArrayHelper::getColumn($result, 'id'), $cityIds) == []);

        $resultMap = ArrayHelper::index($result, 'id');

        $this->assertTrue($resultMap[3]['name'] == 'Samara');
        $this->assertTrue($resultMap[2]['name'] == 'St. Petersburg');

        $this->assertTrue(count($resultMap[3]['addresses']) == 0);
        $this->assertTrue(count($resultMap[2]['addresses']) == 2);
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