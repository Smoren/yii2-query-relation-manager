# yii2-query-relation-manager
Реализует функционал получения данных из БД с отношениями "один к одному" и "один ко многим" с использованием одного 
запроса к БД, а также с учетом всех ограничений в запросе при получении отношений.

### Установка в проект на Yii2
```
composer require smoren/yii2-query-relation-manager
```

### Примеры использования

Будем использоват следующие таблицы в БД с наборами полей:

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

use Smoren\Yii2\QueryRelationManager\QueryRelationManager;
use app\models\City;
use app\models\Address;
use app\models\Place;
use app\models\Comment;

// Выбираем адреса с городом, местами и комментариями о местах
$result = QueryRelationManager::select(Address::class, 'a')
    ->withSingle('city', City::class, 'c', 'a', 'id', 'city_id')
    ->withMultiple('places', Place::class, 'p', 'a', 'address_id', 'id')
    ->withMultiple('comments', Comment::class, 'cm', 'p', 'place_id', 'id')
    ->all();

print_r($result);


// Выбираем места с адресом и городом, а также комментариями, при чем:
// - комментарии имеют оценку не ниже 3
// - если подходящх комментариев нет, место не попадает в выборку (inner join)
// - для каждого места считаем количество комментариев, количество оценок "5" и среднюю оценку среди оценок не ниже 3
$result = QueryRelationManager::select(Place::class, 'p')
    ->withSingle('address', Address::class, 'a', 'p', 'id', 'address_id')
    ->withSingle('city', City::class, 'c', 'a', 'id', 'city_id')
    ->withMultiple('comments', Comment::class, 'cm', 'p', 'place_id', 'id',
        'inner', 'and cm.mark >= :mark', [':mark' => 3])
    ->modify('cm', function(array &$comment, array &$place) {
        if(!isset($place['comments_count'])) {
            $place['comments_count'] = 0;
        }

        if(!isset($place['mark_five_count'])) {
            $place['mark_five_count'] = 0;
        }

        if(!isset($place['mark_average'])) {
            $place['mark_average'] = 0;
        }

        $place['comments_count']++;
        $place['mark_average'] += $comment['mark'];

        if($comment['mark'] == 5) {
            $place['mark_five_count']++;
        }
    })
    ->modify('p', function(array &$place) {
        if(!isset($place['mark_average'])) {
            $place['mark_average'] = 0;
        } else {
            $place['mark_average'] /= $place['comments_count'];
        }
    })
    ->all();

print_r($result);


// Получаем города из списка с адресами
$cityIds = City::find()->limit(2)->offset(1)->select('id')->column();
$result = QueryRelationManager::select(City::class, 'c')
    ->withMultiple('addresses', Address::class, 'a', 'c', 'city_id', 'id')
    ->filter(function(Query $q) use ($cityIds) {
        $q->andWhere(['c.id' => $cityIds]);
    })
    ->all();

print_r($result);

```

Репозиторий с демонстрацией использования расширения: https://github.com/Smoren/yii2-query-relation-manager-demo
