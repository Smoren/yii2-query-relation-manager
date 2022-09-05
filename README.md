# yii2-query-relation-manager

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/smoren/yii2-query-relation-manager)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Smoren/yii2-query-relation-manager/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Smoren/yii2-query-relation-manager/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/Smoren/yii2-query-relation-manager/badge.svg?branch=master)](https://coveralls.io/github/Smoren/yii2-query-relation-manager?branch=master)
![Build and test](https://github.com/Smoren/yii2-query-relation-manager/actions/workflows/test_master.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Implements the functionality of getting tree data from a database with one-to-one and one-to-many
relationships using only one select-query to the database with flexible conditions configuration.

### How to install to your project
```
composer require smoren/yii2-query-relation-manager
```

### Usage examples

Let's say we have these tables in DB with such columns:

 - **city** (id, name)
 - **address** (id, city_id, name)
 - **place** (id, address_id, name)
 - **comment** (id, place_id, username, mark, text)

and their corresponding **ActiveRecord** model classes:
 - app\models\\**City**
 - app\models\\**Address**
 - app\models\\**Place**
 - app\models\\**Comment**

```php
<?php

use Smoren\QueryRelationManager\Yii2\QueryRelationManager;
use Smoren\QueryRelationManager\Yii2\QueryRelationDataProvider;
use app\models\City;
use app\models\Address;
use app\models\Place;
use app\models\Comment;

// Let's select addresses with theirs relations: city, places and comments about places
$result = QueryRelationManager::select(Address::class, 'a')
    ->withSingle('city', City::class, 'c', 'a', ['id' => 'city_id'])
    ->withMultiple('places', Place::class, 'p', 'a', ['address_id' => 'id'])
    ->withMultiple('comments', Comment::class, 'cm', 'p', ['place_id' => 'id'])
    ->all();

print_r($result);
/*Array
(
    [0] => Array
        (
            [id] => 1
            [city_id] => 1
            [name] => Tverskaya st., 7
            [city] => Array
                (
                    [id] => 1
                    [name] => Moscow
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 1
                            [address_id] => 1
                            [name] => TC Tverskoy
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 1
                                            [place_id] => 1
                                            [username] => Ivan Mustafaevich
                                            [mark] => 3
                                            [text] => Not bad, not good
                                        )

                                    [1] => Array
                                        (
                                            [id] => 2
                                            [place_id] => 1
                                            [username] => Peter
                                            [mark] => 5
                                            [text] => Good place
                                        )

                                    [2] => Array
                                        (
                                            [id] => 3
                                            [place_id] => 1
                                            [username] => Mark
                                            [mark] => 1
                                            [text] => Bad place
                                        )

                                )

                        )

                    [1] => Array
                        (
                            [id] => 2
                            [address_id] => 1
                            [name] => Tverskaya cafe
                            [comments] => Array
                                (
                                )

                        )

                )

        )

    [1] => Array
        (
            [id] => 2
            [city_id] => 1
            [name] => Schipok st., 1
            [city] => Array
                (
                    [id] => 1
                    [name] => Moscow
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 3
                            [address_id] => 2
                            [name] => Stasova music school
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 4
                                            [place_id] => 3
                                            [username] => Ann
                                            [mark] => 5
                                            [text] => The best music school!
                                        )

                                )

                        )

                )

        )

    [2] => Array
        (
            [id] => 3
            [city_id] => 2
            [name] => Mayakovskogo st., 12
            [city] => Array
                (
                    [id] => 2
                    [name] => St. Petersburg
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 4
                            [address_id] => 3
                            [name] => Hostel on Mayakovskaya
                            [comments] => Array
                                (
                                )

                        )

                    [1] => Array
                        (
                            [id] => 5
                            [address_id] => 3
                            [name] => Mayakovskiy Store
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 5
                                            [place_id] => 5
                                            [username] => Stas
                                            [mark] => 4
                                            [text] => Rather good place
                                        )

                                )

                        )

                )

        )

    [3] => Array
        (
            [id] => 4
            [city_id] => 2
            [name] => Galernaya st., 3
            [city] => Array
                (
                    [id] => 2
                    [name] => St. Petersburg
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 6
                            [address_id] => 4
                            [name] => Cafe on Galernaya
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 6
                                            [place_id] => 6
                                            [username] => Stas
                                            [mark] => 3
                                            [text] => Small menu, long wait
                                        )

                                )

                        )

                )

        )

)*/


// Now let's select places with it's relations: address, city and comments, and with next conditions
// - comments are rated at least 3
// - if there are no suitable comments, the place is not included in the selection (inner join)
// - for each place we count the number of comments, the number of ratings "5" and the average rating among the ratings is not lower than 3
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

print_r($result);
/*Array
(
    [0] => Array
        (
            [id] => 1
            [address_id] => 1
            [name] => TC Tverskoy
            [address] => Array
                (
                    [id] => 1
                    [city_id] => 1
                    [name] => Tverskaya st., 7
                    [city] => Array
                        (
                            [id] => 1
                            [name] => Moscow
                        )

                )

            [comments] => Array
                (
                    [0] => Array
                        (
                            [id] => 1
                            [place_id] => 1
                            [username] => Ivan Mustafaevich
                            [mark] => 3
                            [text] => Not bad, not good
                        )

                    [1] => Array
                        (
                            [id] => 2
                            [place_id] => 1
                            [username] => Peter
                            [mark] => 5
                            [text] => Good place
                        )

                )

            [comments_count] => 2
            [mark_five_count] => 1
            [mark_average] => 4
        )

    [1] => Array
        (
            [id] => 3
            [address_id] => 2
            [name] => Stasova music school
            [address] => Array
                (
                    [id] => 2
                    [city_id] => 1
                    [name] => Schipok st., 1
                    [city] => Array
                        (
                            [id] => 1
                            [name] => Moscow
                        )

                )

            [comments] => Array
                (
                    [0] => Array
                        (
                            [id] => 4
                            [place_id] => 3
                            [username] => Ann
                            [mark] => 5
                            [text] => The best music school!
                        )

                )

            [comments_count] => 1
            [mark_five_count] => 1
            [mark_average] => 5
        )

    [2] => Array
        (
            [id] => 5
            [address_id] => 3
            [name] => Mayakovskiy Store
            [address] => Array
                (
                    [id] => 3
                    [city_id] => 2
                    [name] => Mayakovskogo st., 12
                    [city] => Array
                        (
                            [id] => 2
                            [name] => St. Petersburg
                        )

                )

            [comments] => Array
                (
                    [0] => Array
                        (
                            [id] => 5
                            [place_id] => 5
                            [username] => Stas
                            [mark] => 4
                            [text] => Rather good place
                        )

                )

            [comments_count] => 1
            [mark_five_count] => 0
            [mark_average] => 4
        )

    [3] => Array
        (
            [id] => 6
            [address_id] => 4
            [name] => Cafe on Galernaya
            [address] => Array
                (
                    [id] => 4
                    [city_id] => 2
                    [name] => Galernaya st., 3
                    [city] => Array
                        (
                            [id] => 2
                            [name] => St. Petersburg
                        )

                )

            [comments] => Array
                (
                    [0] => Array
                        (
                            [id] => 6
                            [place_id] => 6
                            [username] => Stas
                            [mark] => 3
                            [text] => Small menu, long wait
                        )

                )

            [comments_count] => 1
            [mark_five_count] => 0
            [mark_average] => 3
        )

)*/


// Let's select cities with their addresses by the list of city ids
$cityIds = City::find()->limit(2)->offset(1)->select('id')->column();
$result = QueryRelationManager::select(City::class, 'c')
    ->withMultiple('addresses', Address::class, 'a', 'c', ['city_id' => 'id'])
    ->filter(function(Query $q) use ($cityIds) {
        $q->andWhere(['c.id' => $cityIds])->orderBy(['a.id' => SORT_ASC]);
    })
    ->all();

print_r($result);
/*Array
(
    [0] => Array
        (
            [id] => 3
            [name] => Samara
            [addresses] => Array
                (
                )

        )

    [1] => Array
        (
            [id] => 2
            [name] => St. Petersburg
            [addresses] => Array
                (
                    [0] => Array
                        (
                            [id] => 3
                            [city_id] => 2
                            [name] => Mayakovskogo st., 12
                        )

                    [1] => Array
                        (
                            [id] => 4
                            [city_id] => 2
                            [name] => Galernaya st., 3
                        )

                )

        )

)*/


// Let's use QueryRelationDataProvider for pagination
$qrm = QueryRelationManager::select(City::class, 'c')
    ->withMultiple('addresses', Address::class, 'a', 'c', ['city_id' => 'id']);

$dataProvider = new QueryRelationDataProvider([
    'queryRelationManager' => $qrm,
    'pagination' => [
        'pageSize' => 2,
        'page' => 0,
    ],
]);

print_r($dataProvider->getModels());
/*Array
(
    [0] => Array
        (
            [id] => 1
            [name] => Moscow
            [addresses] => Array
                (
                    [0] => Array
                        (
                            [id] => 2
                            [city_id] => 1
                            [name] => Schipok st., 1
                        )

                    [1] => Array
                        (
                            [id] => 1
                            [city_id] => 1
                            [name] => Tverskaya st., 7
                        )

                )

        )

    [1] => Array
        (
            [id] => 2
            [name] => St. Petersburg
            [addresses] => Array
                (
                    [0] => Array
                        (
                            [id] => 4
                            [city_id] => 2
                            [name] => Galernaya st., 3
                        )

                    [1] => Array
                        (
                            [id] => 3
                            [city_id] => 2
                            [name] => Mayakovskogo st., 12
                        )

                )

        )

)*/


// Let's use a simplified syntax for building queries
// We select addresses with their relations: city, places and their comments which rated at least 3
// City:select() method added to City model by using ActiveRecordTrait
$result = Address::select('a')
    ->with('city', 'c')
    ->with('places', 'p')
    ->with(
        'comments', 'cm', 'p',
        'left', 'and cm.mark >= :mark', [':mark' => 3]
    )
    ->all();

print_r($result);
/*Array
(
    [0] => Array
        (
            [id] => 1
            [city_id] => 1
            [name] => Tverskaya st., 7
            [city] => Array
                (
                    [id] => 1
                    [name] => Moscow
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 1
                            [address_id] => 1
                            [name] => TC Tverskoy
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 1
                                            [place_id] => 1
                                            [username] => Ivan Mustafaevich
                                            [mark] => 3
                                            [text] => Not bad, not good
                                        )

                                    [1] => Array
                                        (
                                            [id] => 2
                                            [place_id] => 1
                                            [username] => Peter
                                            [mark] => 5
                                            [text] => Good place
                                        )

                                )

                        )

                    [1] => Array
                        (
                            [id] => 2
                            [address_id] => 1
                            [name] => Tverskaya cafe
                            [comments] => Array
                                (
                                )

                        )

                )

        )

    [1] => Array
        (
            [id] => 2
            [city_id] => 1
            [name] => Schipok st., 1
            [city] => Array
                (
                    [id] => 1
                    [name] => Moscow
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 3
                            [address_id] => 2
                            [name] => Stasova music school
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 4
                                            [place_id] => 3
                                            [username] => Ann
                                            [mark] => 5
                                            [text] => The best music school!
                                        )

                                )

                        )

                )

        )

    [2] => Array
        (
            [id] => 3
            [city_id] => 2
            [name] => Mayakovskogo st., 12
            [city] => Array
                (
                    [id] => 2
                    [name] => St. Petersburg
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 4
                            [address_id] => 3
                            [name] => Hostel on Mayakovskaya
                            [comments] => Array
                                (
                                )

                        )

                    [1] => Array
                        (
                            [id] => 5
                            [address_id] => 3
                            [name] => Mayakovskiy Store
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 5
                                            [place_id] => 5
                                            [username] => Stas
                                            [mark] => 4
                                            [text] => Rather good place
                                        )

                                )

                        )

                )

        )

    [3] => Array
        (
            [id] => 4
            [city_id] => 2
            [name] => Galernaya st., 3
            [city] => Array
                (
                    [id] => 2
                    [name] => St. Petersburg
                )

            [places] => Array
                (
                    [0] => Array
                        (
                            [id] => 6
                            [address_id] => 4
                            [name] => Cafe on Galernaya
                            [comments] => Array
                                (
                                    [0] => Array
                                        (
                                            [id] => 6
                                            [place_id] => 6
                                            [username] => Stas
                                            [mark] => 3
                                            [text] => Small menu, long wait
                                        )

                                )

                        )

                )

        )

)*/
```

For demo see this [repo](https://github.com/Smoren/yii2-query-relation-manager-demo).
