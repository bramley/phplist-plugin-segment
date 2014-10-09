<?php
/**
 * CriteriaPlugin for phplist
 * 
 * This file is a part of CriteriaPlugin.
 *
 * CriteriaPlugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * CriteriaPlugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * @category  phplist
 * @package   CriteriaPlugin
 * @author    Duncan Cameron
 * @copyright 2014 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * DAO class that encapsulates the database access
 * 
 * @category  phplist
 * @package   CriteriaPlugin
 */
class SegmentPlugin_DAO extends CommonPlugin_DAO
{
/*
 *  Private functions
 */

    private function exclude($messageId)
    {
        $sql = <<<END
SELECT data
FROM {$this->tables['messagedata']}
WHERE name = 'excludelist' AND id = $messageId
END;
        $excludeSubquery = '';
        
        if ($data = $this->dbCommand->queryOne($sql, 'data')) {
            $excluded = unserialize(substr($data, 4));

            if (count($excluded) > 0) {
                $inList = '(' . implode(', ', $excluded) . ')';
                $excludeSubquery = <<<END
AND u.id NOT IN (
    SELECT userid
    FROM {$this->tables['listuser']}
    WHERE listid IN $inList
)
END;
            }
        }
        return $excludeSubquery;
    }

/*
 *  Public functions
 */
    /**
     * Retrieves the values for a select/radio button attribute
     * @param array $attribute an attribute 
     * @return Iterator
     * @access public
     */
    public function selectData(array $attribute)
    {
        $tableName = $this->table_prefix . 'listattr_' . $attribute['tablename'];

        return $this->dbCommand->queryAll(<<<END
            SELECT id, name
            FROM $tableName
            ORDER BY listorder, id
END
        );
        return $this->dbCommand->queryAll($sql);
    }

    /**
     * Retrieves campaigns
     * @param string $loginId login id of the current admin
     * @param int $max Maximum number of campaigns to be returned
     * @return Iterator
     * @access public
     */

    public function campaigns($loginId, $max)
    {
        $owner = $loginId ? "AND m.owner = $loginId" : '';
        $limitClause = is_null($max) ? '' : "LIMIT $max";

        $sql = "SELECT m.id, CONCAT_WS(' - ',m.subject, DATE_FORMAT(m.sent,'%d/%m/%y')) AS subject
            FROM {$this->tables['message']} m
            WHERE m.status = 'sent'
            $owner
            ORDER BY m.sent DESC
            $limitClause
            ";
        return $this->dbCommand->queryAll($sql);
    }

    public function deleteNotSent($campaign)

    {
        $sql = "DELETE FROM {$this->tables['usermessage']}
            WHERE status = 'not sent'
            AND messageid = $campaign
        ";
        return $this->dbCommand->queryAffectedRows($sql);
    }
    /*
     *  Methods for each subscriber data type
     */ 
    public function emailSelect($operator, $value)
    {
        $value = sql_escape($value);

        switch ($operator) {
            case SegmentPlugin_Operator::MATCHES:
                $op = 'LIKE';
                break;
            case SegmentPlugin_Operator::NOTMATCHES:
                $op = 'NOT LIKE';
                break;
            case SegmentPlugin_Operator::REGEXP:
                $op = 'REGEXP';
                break;
            case SegmentPlugin_Operator::NOTREGEXP:
                $op = 'NOT REGEXP';
                break;
            case SegmentPlugin_Operator::IS:
            default:
                $op = '=';
        }

        $r = new stdClass;
        $r->join = '';
        $r->where = "u.email $op '$value'";
        return $r;
    }

    public function enteredSelect($operator, $value)
    {
        $value = sql_escape($value);
        $op = $operator == SegmentPlugin_Operator::BEFORE ? '<' 
            : ($operator == SegmentPlugin_Operator::AFTER ? '>' : '=');

        $r = new stdClass;
        $r->join = '';
        $r->where = "DATE(u.entered) $op '$value'";
        return $r;
    }

    public function activitySelect($operator, $value)
    {
        $r = new stdClass;

        if ($operator == SegmentPlugin_Operator::CLICKED || $operator == SegmentPlugin_Operator::NOTCLICKED) {
            $op = $operator == SegmentPlugin_Operator::CLICKED ? 'IS NOT NULL' : 'IS NULL';
            $r->join = <<<END
                JOIN {$this->tables['usermessage']} um ON u.id = um.userid AND um.status = 'sent' AND um.messageid = $value
                LEFT JOIN {$this->tables['linktrack_uml_click']} uml ON u.id = uml.userid AND uml.messageid = um.messageid
END;
            $r->where = "uml.userid $op";
            
        } elseif ($operator == SegmentPlugin_Operator::OPENED || $operator == SegmentPlugin_Operator::NOTOPENED) {
            $op = $operator == SegmentPlugin_Operator::OPENED ? 'IS NOT NULL' : 'IS NULL';
            $r->join = <<<END
                JOIN {$this->tables['usermessage']} um ON u.id = um.userid AND um.status = 'sent' AND um.messageid = $value
END;
            $r->where = "um.viewed $op";
        } elseif ($operator == SegmentPlugin_Operator::SENT || $operator == SegmentPlugin_Operator::NOTSENT) {
            $op = $operator == SegmentPlugin_Operator::SENT ? 'IS NOT NULL' : 'IS NULL';
            $r->join = <<<END
                LEFT JOIN {$this->tables['usermessage']} um ON u.id = um.userid AND um.status = 'sent' AND um.messageid = $value
END;
            $r->where = "um.userid $op";
        }
        return $r;
    }
    /*
     *  Methods for each type of attribute
     */ 
    public function textSelect($attributeId, $operator, $target)
    {
        $target = sql_escape($target);

        switch ($operator) {
            case SegmentPlugin_Operator::ISNOT:
                $op = '!=';
                break;
            case SegmentPlugin_Operator::BLANK:
                $op = '=';
                $target = '';
                break;
            case SegmentPlugin_Operator::NOTBLANK:
                $op = '!=';
                $target = '';
                break;
            case SegmentPlugin_Operator::MATCHES:
                $op = 'LIKE';
                break;
            case SegmentPlugin_Operator::NOTMATCHES:
                $op = 'NOT LIKE';
                break;
            case SegmentPlugin_Operator::REGEXP:
                $op = 'REGEXP';
                break;
            case SegmentPlugin_Operator::NOTREGEXP:
                $op = 'NOT REGEXP';
                break;
            case SegmentPlugin_Operator::IS:
            default:
                $op = '=';
                break;
        }
            
        $r = new stdClass;
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId ";
        $r->where = "COALESCE(value, '') $op '$target'";
        return $r;
    }

    public function selectSelect($attributeId, $operator, $target)
    {
        $in = ($operator == SegmentPlugin_Operator::ONE ? 'IN' : 'NOT IN') . ' (' . implode(', ', $target) . ')';

        $r = new stdClass;
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId ";
        $r->where = "COALESCE(value, 0) $in";
        return $r;
    }

    public function dateSelect($attributeId, $operator, $target)
    {
        $target = sql_escape($target);
        $op = $operator == SegmentPlugin_Operator::BEFORE ? '<' 
            : ($operator == SegmentPlugin_Operator::AFTER ? '>' : '=');

        $r = new stdClass;
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId ";
        $r->where = "COALESCE(value, '') != '' AND DATE(COALESCE(value, '')) $op '$target'";
        return $r;
    }

    public function checkboxSelect($attributeId, $operator, $target)
    {
        $op = $operator == SegmentPlugin_Operator::IS ? '=' : '!=';

        $r = new stdClass;
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId ";
        $r->where = "COALESCE(value, '') $op 'on'";
        return $r;
    }

    public function checkboxgroupSelect($attributeId, $operator, $target)
    {
        $where = array();

        if ($operator == SegmentPlugin_Operator::ONE) {
            $compare = '>';
            $boolean = 'OR';
        } elseif ($operator == SegmentPlugin_Operator::ALL) {
            $compare = '>';
            $boolean = 'AND';
        } else  {
            $compare = '=';
            $boolean = 'AND';
        }

        foreach ($target as $item) {
            $where[] = "FIND_IN_SET($item, COALESCE(value, '')) $compare 0";
        }

        $r = new stdClass;
        $r->join = "LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId ";
        $r->where = '(' . implode(" $boolean ", $where) . ')';
        return $r;
    }

    public function subscribers($messageId, array $select, $combine)
    {
        $excludeSubquery = $this->exclude($messageId);
        $queries = array();

        foreach ($select as $s) {
            $queries[] = <<<END
SELECT DISTINCT u.id
FROM {$this->tables['user']} u
JOIN {$this->tables['listuser']} lu0 ON u.id = lu0.userid
JOIN {$this->tables['listmessage']} lm0 ON lm0.listid = lu0.listid AND lm0.messageid = $messageId
LEFT JOIN {$this->tables['usermessage']} um0 ON um0.userid = u.id AND um0.messageid = $messageId
$s->join
WHERE u.confirmed = 1 AND u.blacklisted = 0
AND COALESCE(um0.status, 'not sent') = 'not sent'
$excludeSubquery
AND $s->where
END;
        }

        if ($combine == SegmentPlugin_Operator::ONE) {
            $sql = implode("\nUNION\n", $queries);
        } else {
            $sql = <<<END
SELECT T0.id
FROM (
{$queries[0]}
    ) AS T0
END;

            for ($n = 1; $n < count($queries); $n++) { 
                $sql .= <<<END
\nJOIN (
{$queries[$n]}
    ) AS T$n ON T$n.id = T0.id
END;
            }
        }
        return $this->dbCommand->queryColumn($sql, 'id');
    }
}
