# yii2-query-relation-manager
Реализует функционал получения данных из БД с отношениями "один к одному" и "один ко многим" с использованием одного 
запроса к БД, а также с учетом всех ограничений в запросе при получении отношений.

### Установка в проект на Yii2
```
composer require smoren/yii2-query-relation-manager
```

### Примеры использования

Будем использовать следующие таблицы в БД с наборами полей:

 - **city** (id, name)
 - **address** (id, city_id, name)
 - **place** (id, address_id, name)
 - **comment** (id, place_id, username, mark, text)

и соответствующие им классы моделей **ActiveRecord**:
 - app\models\\**City**
 - app\models\\**Address**
 - app\models\\**Place**
 - app\models\\**Comment**

```php
<?php

use Smoren\Yii2\QueryRelationManager\ActiveRecord\QueryRelationManager;
use Smoren\Yii2\QueryRelationManager\ActiveRecord\QueryRelationDataProvider;
use app\models\City;
use app\models\Address;
use app\models\Place;
use app\models\Comment;

// Выбираем адреса с городом, местами и комментариями о местах
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


// Выбираем места с адресом и городом, а также комментариями, причем:
// - комментарии имеют оценку не ниже 3
// - если подходящих комментариев нет, место не попадает в выборку (inner join)
// - для каждого места считаем количество комментариев, количество оценок "5" и среднюю оценку среди оценок не ниже 3
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


// Получаем города из списка ID с адресами
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


// Используем QueryRelationDataProvider для пагинации
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


```

Репозиторий с демонстрацией использования расширения: https://github.com/Smoren/yii2-query-relation-manager-demo
