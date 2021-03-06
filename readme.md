### MagicFlatsFinder - feeds lid

### Установка

Для установки желательно использовать composer. 
Внутреннего автозагрузчика пока нет.

```php
composer require orbit_exp/magic_flats_finder
```

### Инициализация

Простая инициализация выглядит так

```php
    $App = new \MagicFlatsFinder\App( [
            'xml' => 'path_or_link/to/xml/file', 
            'project' => \MagicFlatsFinder\App::PROJECT_DOMUSKVERA,
        ]
    );
```
параметры для передачи
 
** - обязательные параметры

```php
    project - код проекта **
    
        PROJECT_DOMUSKVERA or domskver
        PROJECT_DOMBOR or dombor
        PROJECT_DOM128 or dom128
        PROJECT_HEADLINER or headliner
        PROJECT_AKADEM or akadem
        PROJECT_GULLIVER or gulliverperm
        PROJECT_ROYALPARK or royalpark
        PROJECT_ILOVE or ilove
        PROJECT_BAUMANHAUSE or baumanhouse
        PROJECT_HLGULLIVER or hlgulliver
        
    xml - ссылка на хмл фид **
    map_link - ссылка на json карту изображений
    fields_tmp - шаблон нейминга для полей в результирующем массиве 
    debug - true / false - флаг режима отладки, по умолчанию fasle
    
    full_xml_file - ссылка на полную xml выгрузку из crm. Файл или ссылка
    map_buildings - карта строений объекта, обязательна при указании full_xml_file
        // очередь => строения очереди
        1 =[
            //номер строения => building_id
            1 => 123
        ]
    map_merge_buildings - карта объединения нескольких сущностей корпусов в одну
       //id главной сужности => массив зависимых сущностей
        123 => [
              654,
        ]
    elastic_search - параметры для подключения к elastic search 
    
    elastic_search = [
                username => login
                password => 123
                cloudId => Id облака, если испльльзуется облако,
         ]
```

### Простой поиск

Простой вызов поиска выглядит следующим образом

**Пример**
```php
    $App->find(
        123, // integer / id строерния 
        [
            'select' => ['flats','building'], 
        ]
    );
```
**Параметры для передачи**
** - обязательные параметры
```php
    xml_file (string) - ссылка на локальный xml
    select (array) - какие данные хотим получить в выбоке, принимает значения в виде массива. 
    принимиет значения  flats и building 
    
    active (boolean) - вывести квартиры по статусы
    
    plans - параметры для планировок
    [
        format - png / jpg / svg
        search - elastic ( тип карты изображений. Если параметр не задан, то по дефолту используется карта из параметра map_link )
    ]
    filter - параметры для фильтрации 
    discount - параметры для скидки
    
```

### Фильтрация

#### Фильтрация по спискам

**Пример** 
```php
    'filter' => [
        'by' => [
              'mixed_key' => ['247_2_1_3'],
        ],
    ]
```
Фильтрация может проходить по трем типам mixed_key / guid / id
где mixed_key - составной ключ, собирается следующим образом
```php
    $mixed_key = $building_id.'_'.$section_id.'_'.$floor.'_'.$num_on_floor;
```

giud / id - по giud / id элемента

### Фильтрация по параметрам
Фильтрация по параметрам поступна **только для числовых значений.**
#### Операторы сравнения
 - больше               | >
 - меньше               | <
 - равно                | =
 - не равно             | !=
 - больше или равно     | >=
 - меньше или равно     | <=

**Пример**
```php
    'filter' => [
        [
            'floor_number' => [
                    '=' => 2,
                ],
            ],
        ],
     ]
```
floor_number - название поля из фида

#### Логические операторы
- AND
- OR

По умолчанию используется оператор AND

Для одного и того же поля можно задавать диапазоны значений, а так же задавать логический оператор

**Пример**
```php
    'filter' => [
        [
            'floor_number' => [
                    'logic' => 'OR',
                    ['=' => 2],
                    ['<' => 8],
                ],
            ],
        ],
     ]
```

При использовании фильтрования по нелькольким параметрам такк же можно задавать логичкские операторы
                
**Пример**
```php
    'filter' => [
        [
            'logic' => 'AND',
            'floor_number' => [
                    'logic' => 'OR',
                    ['=' => 2],
                    ['<' => 8],
                ],
            ],
            
            'section_number' => [
                 '!=' => 2
            ],
        ],
     ]
```
#### Использование нескольких блоков для фильтрации

**Пример**
```php
    'filter' => [
        'logic' => 'OR',
        'by' => [
             'mixed_key' => ['247_2_1_3'],
        ],
        [
            'logic' => 'AND',
            'floor_number' => [
                    'logic' => 'OR',
                    ['=' => 2],
                    ['<' => 8],
                ],
            ],
            
            'section_number' => [
                 '!=' => 2
            ],
        ],
     ]
```

### Скидки
####Применение скидки на весь ассортимент

**Пример**
```php
       'discount' => [
            'all' => 10,
        ]
```
####Применение на предвыбранный список квартир

**Пример**
```php
       'discount' => [
            'select' => [
                 'key' => 'guid',
                 'discount' => 5,
                 'list' => ['ba521761-ca1a-e711-80d1-005056010696'],
            ]
        ]
```
На список квартир (list) будет применена скидка (discount)
**key** - тип ключей в списке list, может принимать типы **guid** / **id** / **mixed_key**

где mixed_key - составной ключ, собирается следующим образом
```php
    $mixed_key = $building_id.'_'.$section_id.'_'.$floor.'_'.$num_on_floor;
```
Бывают ситуации, когда в предвыбранном списке элементов есть разброс скидок. В этой ситуации можно задать скидку для всего списка и для конкретных элементов

**Пример** 
```php
'discount' => [
            'select' => [
                'key' => 'guid',
                'discount' => 5,
                'list' => [
                    'ba521761-ca1a-e711-80d1-005056010696:9', //зададим элементу скидку 9%
                    'be521761-ca1a-e711-80d1-005056010696' //зададим элементу скидку равную значению discount - 5%
                ],
            ]
        ]
```

#### Группировка скидок
В ситуации, когда одну скидку нужно примерить на весь ассортимент, а на пулл элементов другую - можно группировать типы скидок

**Пример** 
```php
'discount' => [
            'all' => 10, //установим скидку 10% на все элементы
            //установим список исключений из 10%
            'select' => [
                'key' => 'guid',
                'discount' => 5,
                'list' => [
                    'ba521761-ca1a-e711-80d1-005056010696:9', //зададим элементу скидку 9%
                    'be521761-ca1a-e711-80d1-005056010696' //зададим элементу скидку равную значению discount - 5%
                ],
            ]
        ]
```
### **Сортировка**

Сортировать элементы можно по целым и дробным значениям.

Параметры 
 - by - имя поле из массива элементов, ко которому будет вестись сортировка ( **обязательный параметр** )
 - type - тип сортировки **ASC** / **DESC**. По умолчанию **ASC**
 - separator - разделитель дробной части
 - key - ключ, по которому будет вестись поиск элементов. По умолчанию **guid**

```php
    'sort' => [
                'by' => 'square',
                'type' => 'DESC',
                'separator' => '.'
          ],
```

### **Ограничение количества элементов**
Можно ограничить количество выводимых элементов
```php
    'limit' => 13,
```

### **Очереди**
Когда у ЖК несколько строений, то можно формировать очереди для дальнейшей выборки

```php
$App->addToStack( 123, ['xml_file' => path_to_xml.xml'] );
$App->addToStack( 456, ['xml_file' => path_to_xml.xml'] );

$App->findByStack( $filter_and_sort_params );
```

### **Важно**

- в заданном массиве **select** обязательно должен быть задан параметр **key**
- если в массиве **select** не задан параметр **discount**, то величина скидки будет тянуться из парамера **all**. В случае, если не задан и он, то скидка применять не будет.
