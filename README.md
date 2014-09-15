RestFilter
==========

 This class provides safe conversion of GET-filters to SQL WHERE-CLAUSE

## Usage in code:
***

```php
    $Filter->init($tableName, $_GET, $escaper, [...]);
    $whereClause = $filter->getFilter();

## Usage in GET-queries:
***

####  Overall Syntax:

      /items?field1:command1=arguments1&field2:command2=arguments2...fieldN:commandN=argumentsN

Select all items with id = 2:
***

$$    /items?id=2

OR:

    /items?id:eq=2

Result:

    items.id = 2

####  Select all items with id > 2:

      /items?id:gt=2

####  Select all items with id that present in list 1, 2, 4, 6 (result clause: "items.id IN (1, 2, 4, 6)" ):

      /items?id[]=1&id[]=2&id[]=4&id[]=6

####  Select all items with title like '%big%' (result clause: items.title LIKE "%big%"):

      /items?title:like=big

####  Executing several joined by "AND" commands at single field (result: likes IS NOT NULL AND likes != 0):

      /items?likes:notnull:not=0

####  Checking several fields with one clause (result: items.owner LIKE '%Luke%' OR items.son LIKE '%Luke%') :

      /items?(father,son):like=Luke

#### All filters can be combined through symbol '&':

      /items?id:gt=2&title:like=big
