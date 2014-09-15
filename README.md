RestFilter
==========

This class provides safe conversion of GET-filters to SQL WHERE-CLAUSE

## Usage in code:

```php
 //            table-name, filter, escaping class
 $Filter->init('items'   , $_GET , $escaper      );
 $whereClause = $filter->getFilter();
```

## Usage in GET-queries:

Overall Syntax:

      /items?field:command=argument
      /items?(field1,field2):command=argument
      /items?field:command[]=arguments
      /items?field:command1:command2=argument

Examples: 

| Clause: | GET-Query:    | Result SQL:  |
|---------------|--------------|--------------|
| Check if field equals to value | /items?id=2 _OR_ /items?id:eq=2  | items.id = 2 |
| Select all items with id > 2| /items?id:gt=2 | items.id > 2 |
| Select all items with id that present in list | /items?id[]=1&id[]=2&id[]=4&id[]=6 | items.id IN (1, 2, 4, 6) |
| Combining several clauses at one filter | /items?id:gt=2&title:like=big | id > 2 AND title LIKE '%big%' | 
| Checking several fields with one clause | /items?(father,son):like=Luke | (items.father LIKE '%Luke%') OR (items.son LIKE '%Luke%') |
| Checking several clauses at one field | /items?category:null:eq=10 | (items.category IS NULL) OR (items.category = 10) |
| Checking several clauses at several fields | /items?(id,parent):null:lt=0 | ((items.id IS NULL) OR (items.id < 0)) OR ((items.parent IS NULL) OR (items.parent < 0))

## Available commands: 

| Section | Command | Arguments in JSON | SQL-equivalent  | 
|-----|---------|------------|----|-----|
| Comparison: | | | 
| | field:eq= | mixed x | field = x |
| | field:not= | mixed x | field != x |
| | field:gt= | mixed x | field > x |
| | field:lt= | mixed x | field < x |
| | field:gte= | mixed x | field >= x |
| | field:lte= | mixed x | field <= x |
| | field:null= | — | field IS NULL | 
| | field:notnull= | — | field IS NOT NULL |
| Range: | | | 
| | field:range= | [[a1, a2],[b1,b2],...] | (field BETWEEN a1 AND a2) OR (field BETWEEN b1, b2) OR ...  | 
| | field:notrange= | [[a1, a2],[b1,b2],...] | NOT ((field BETWEEN a1 AND a2) OR (field BETWEEN b1, b2) OR ...)  | 
| Text matching: | | |
| | field:prefix= | string x | field LIKE 'x%' |
| | field:like= | string x | field LIKE '%x%' |
| | field:suffix= | string x | field LIKE '%x' |
| Set matching: | | | 
| | field:in= | [x1, x2, ...] | field IN (x1, x2, ...) |
| | field:notin= | [x1, x2, ...] | field NOT IN (x1, x2, ...) |
| Shortcuts: | | |
| | field= | x1 | field = x1 |
| | field= | [x1, x2, ...] | field IN (x1, x2, ...) |
