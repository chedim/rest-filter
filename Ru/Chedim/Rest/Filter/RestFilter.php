<?php

namespace Ru\Chedim\Rest\Filter;

/**
 *
 * This class provides safe conversion of GET-filters to SQL WHERE-CLAUSE
 * Usage: $Filter->init($_GET, $escaper, [...]); $whereClause = $filter->getFilter();
 * Usage in GET-queries:
 *  — Overall Syntax:
 *      /items?field1:command1=arguments1&field2:command2=arguments2...fieldN:commandN=argumentsN
 *  — Select all items with id = 2 (result clause: items.id = 2):
 *      /items?id=2 OR /items?id:eq=2
 *  — Select all items with id > 2:
 *      /items?id:gt=2
 *  — Select all items with id that present in list 1, 2, 4, 6 (result clause: "items.id IN (1, 2, 4, 6)" ):
 *      /items?id[]=1&id[]=2&id[]=4&id[]=6
 *  — Select all items with title like '%big%' (result clause: items.title LIKE "%big%"):
 *      /items?title:like=big
 *  — Executing several joined by "AND" commands at single field (result: likes IS NOT NULL AND likes != 0):
 *      /items?likes:notnull:not=0
 *  — checking several fields with one clause (result: items.owner LIKE '%Luke%' OR items.son LIKE '%Luke%') :
 *      /items?(father,son):like=Luke
 *
 * All filters can be combined through symbol '&':
 *      /items?id:gt=2&title:like=big
 */
class RestFilter
{
    const CMD_SEPARATOR = ':';

    private $source;
    private $compiled;
    private $allowed_fields = null;
    private $disallowed_fields = null;
    private $table = null;
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param $table name of the filtering table
     * @param $source $_GET filter
     * @param Escaper $escaper Class that escapes values and fields
     * @param null $allowed_fields optional list of allowed in filter fields
     * @param null $disallowed_fields
     */
    public function init($table, $source, Escaper $escaper, $allowed_fields = null, $disallowed_fields = null)
    {
        $this->table = $table;
        $this->source = $source;
        $this->escaper = $escaper;
        $this->allowed_fields = $allowed_fields;
        $this->disallowed_fields = $disallowed_fields;
        $this->compiled = $this->parse($source);
    }

    /**
     * Execute all commands in filter
     * @param $commands
     * @param $field
     * @param $arguments
     * @return string
     * @throws \Exception
     */
    protected function execCommands($commands, $field, $arguments)
    {
        if (!$this->isAllowed($field)) {
            throw new \Exception('Field '.$field.' is not accessible');
        }
        if (count($commands) == 1) {
            return $this->exec($commands[0], $field, $arguments);
        } else {
            $basic = array();
            foreach ($commands as $command) {
                if ($this->allowed_fields != null && array_search($field, $this->allowed_fields) === false) continue;
                if ($this->disallowed_fields != null && array_search($field, $this->disallowed_fields) !== false) continue;
                $basic[] = $this->exec($command, $field, $arguments);
            }
            $basic = '(' . implode(' OR ', $basic) . ')';
            return $basic;
        }
    }

    /**
     * Executes command from GET-filter.
     * Command must be represented as method of this class named as "command".$commandName
     * This method MUST return String WHERE-clause for passed arguments
     * @param $command
     * @param $field
     * @param $arguments
     * @return string
     * @throws \Exception
     */
    protected function exec($command, $field, $arguments)
    {
        $method = 'command' . ucfirst($command);
        if (method_exists($this, $method)) {
            if (is_array($field)) {
                $field = implode(self::CMD_SEPARATOR, $field);
            }
            $field = $this->escaper->field($field);
            if (is_array($arguments)) {
                for ($i = 0; $i < count($arguments); $i++) {
                    $arguments[$i] = $this->escaper->value($arguments[$i]);
                }
            } else {
                $arguments = $this->escaper->value($arguments);
            }
            return '(' . $this->{$method}($this->table.'.'.$field, $arguments) . ')';
        } else {
            // TODO: throw valid Exception
            throw new \Exception("Filter " . $command . " not supported");
        }
    }

    private function isAllowed($field) {
        if ($this->allowed_fields == null) return true;
        if (array_search($field, $this->allowed_fields) !== false) return true;
        return false;
    }

    /**
     * Parses GET-filter
     * @param $filter
     * @return null|string
     */
    public function parse(array $filter)
    {
        if ($filter == null) return null;
        $command = null;
        $query = array();
        // getting filter keys as commands and field names and filter values as arguments to commands
        foreach ($filter as $field => $value) {
//            $field = str_replace('_', '.', $field);
            $commands = explode(RestFilter::CMD_SEPARATOR, $field);
            if (count($commands) > 1) {
                // found one ore more commands, extracting string before first command separator (usually semicolon)
                $field = array_shift($commands);
            } else {
                // no commands found, trying to guess shortcut to command: eq|in
                if (is_array($value)) {
                    // got array of arguments without command; this is a shortcut to 'in' command
                    $commands = array('in');
                } else {
                    // got scalar value without any commands; this is a shortcut to 'eq' command
                    $commands = array('eq');
                    if (is_numeric($value)) {
                        // converting value to numeric because of stupid PHP
                        $value = floatval($value);
                    }
                }
            }
            if (preg_match('/\\(([^\\)]+)\\)/', $field, $matches)) {
                // commands can be applied to several fields
                $fields = explode(',', $matches[1]);
                $base = array();
                foreach ($fields as $field) {
                    $base[] = $this->execCommands($commands, $field, $value);
                }
                $query[] = '(' . implode(' OR ', $base) . ')';
            } else {
                $query[] = $this->execCommands($commands, $field, $value);
            }
        }
        return implode(' AND ', $query);
    }

    protected function processFilterExpression($fields, $command, $arguments)
    {

    }

    /**
     * @Arguments: value that should not be in resultset
     * @param $field
     * @param $value
     * @return string
     */
    private function commandNot($field, $value)
    {
        return $field . ' != ' . $value;
    }

    /**
     * @Arguments: value, from which field should start
     * @param $field
     * @param $value
     * @return string
     */
    private function commandPrefix($field, $value)
    {
        return $field . ' LIKE '.$value." '%'";
    }

    /**
     * @Arguments: value, which must be contained by field
     * @param $field
     * @param $value
     * @return string
     */
    private function commandLike($field, $value)
    {
        return $field . " LIKE '%' ".$value." '%'";
    }

    /**
     * @Arguments: list of values (passed as array) that must be in result set
     * @param $field
     * @param $value
     * @return string
     */
    private function commandIn($field, $value)
    {
        return $field . ' IN ('.implode(', ', $value).')';
    }

    /**
     * @Arguments: minimal and maximum+1 numbers, values between which should be in result set
     * @param $field
     * @param $value
     * @return string
     */
    private function commandRange($field, $value)
    {
        $e = array();
        foreach ($value as $i => $range) {
            $e[] = '('.$field .' >= '.$range[0].' AND '.$field.' < '.$range[1].')';
        }
        return implode(' OR ', $e);
    }

    /**
     * @Arguments: minimal and maximum+1 numbers, values between which shouldn't be in result set
     * @param $field
     * @param $value
     * @return string
     */
    private function commandNotRange($field, $value)
    {
        $e = array();
        foreach ($value as $i => $range) {
            $e[] = '('.$field .' < '.$range[0].' AND '.$field.' >= '.$range[1].')';
        }
        return implode(' AND ', $e);
    }

    /**
     * @Arguments: value to compare with GTE operation
     * @param $field
     * @param $value
     * @return string
     */
    private function commandGte($field, $value)
    {
        return $field . ' >= '.$value;
    }

    /**
     * @Arguments: value to compare with GT operation
     * @param $field
     * @param $value
     * @return string
     */
    private function commandGt($field, $value) {
        return $field . ' > ' . $value;
    }

    /**
     * @Arguments: value to compare
     * @param $field
     * @param $value
     * @return string
     */
    private function commandEq($field, $value)
    {
        return $field . ' = ' . $value;
    }

    /**
     * Limit result set with records where specified field IS NULL
     * @Arguments: none
     * @param $field
     * @param $value
     * @return string
     */
    private function commandNull($field, $value)
    {
        return $field . ' IS NULL';
    }

    /**
     * Returns compiled WHERE-CLAUSE or null in case of error;
     * @return null|string
     */
    public function getFilter() {
        if ($this->compiled) return 'WHERE '.$this->compiled;
        return null;
    }

    /**
     * @see Filter::getFilter
     * @return null|string
     */
    public function __toString() {
        return $this->getFilter();
    }
}
