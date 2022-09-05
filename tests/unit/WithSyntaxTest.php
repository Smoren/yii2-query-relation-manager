<?php

namespace Smoren\QueryRelationManager\Yii2\Tests\Unit;

use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\Address;
use Smoren\QueryRelationManager\Yii2\Tests\Unit\Models\City;
use yii\helpers\ArrayHelper;

class WithSyntaxTest extends \Codeception\Test\Unit
{
    /**
     * @throws QueryRelationManagerException
     */
    public function testCity()
    {
        $result = City::select('c')
            ->with('addresses', 'a')
            ->with('places', 'p', 'a')
            ->all();

        $this->assertTrue($this->compareCityResultWithCorrectMap($result, [
            1 => [
                1 => [1, 2],
                2 => [3],
            ],
            2 => [
                3 => [4, 5],
                4 => [6],
            ],
            3 => [],
            4 => [],
            5 => [],
        ]));
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testAddress()
    {
        $result = Address::select('a')
            ->with('city', 'c')
            ->with('places', 'p')
            ->with(
                'comments',
                'cm',
                'p',
                'left',
                'and cm.mark >= :mark',
                [':mark' => 3]
            )
            ->all();

        $addressIds = ArrayHelper::getColumn($result, 'id');
        sort($addressIds);
        $this->assertEquals([1, 2, 3, 4], $addressIds);

        $cityIds = ArrayHelper::getColumn($result, 'city.id');
        sort($cityIds);
        $this->assertEquals([1, 1, 2, 2], $cityIds);

        $placeIdToCommentMarkMap = [
            1 => [3, 5],
            2 => [],
            3 => [5],
            4 => [],
            5 => [4],
            6 => [3],
        ];

        foreach($result as $address) {
            foreach($address['places'] as $place) {
                $placeMarks = ArrayHelper::getColumn($place['comments'], 'mark');
                $this->assertEquals($placeIdToCommentMarkMap[$place['id']], $placeMarks);
            }
        }
    }

    /**
     * @param array $result
     * @param array $correctMap
     * @return bool
     */
    protected function compareCityResultWithCorrectMap(array $result, array $correctMap): bool
    {
        $resultMap = [];
        foreach($result as $city) {
            $resultMap[$city['id']] = [];
            foreach($city['addresses'] as $address) {
                $resultMap[$city['id']][$address['id']] = [];
                foreach($address['places'] as $place) {
                    $resultMap[$city['id']][$address['id']][] = $place['id'];
                }
                sort($resultMap[$city['id']][$address['id']]);
            }
            ksort($resultMap[$city['id']]);
        }
        ksort($resultMap);

        return $resultMap == $correctMap;
    }
}