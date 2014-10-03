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
    public function emailSubquery($operator, $value)
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
            
        $sql = <<<END
            SELECT id
            FROM {$this->tables['user']}
            WHERE email $op '$value'
END;
        return $sql;
    }

    public function enteredSubquery($operator, $value)
    {
        $value = sql_escape($value);
        $op = $operator == SegmentPlugin_Operator::BEFORE ? '<' 
            : ($operator == SegmentPlugin_Operator::AFTER ? '>' : '=');
            
        $sql = <<<END
            SELECT id
            FROM {$this->tables['user']}
            WHERE DATE(entered) $op '$value'
END;
        return $sql;
    }

    public function activitySubquery($operator, $value)
    {
        $op = $operator == SegmentPlugin_Operator::OPENED ? 'IS NOT NULL' : 'IS NULL';
        $sql = <<<END
            SELECT um.userid AS id
            FROM {$this->tables['usermessage']} um
            WHERE um.viewed $op
            AND um.messageid = $value
END;
        return $sql;
    }
    /*
     *  Methods for each type of attribute
     */ 
    public function textSubquery($attributeId, $operator, $target)
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
            
        $sql = <<<END
            SELECT id
            FROM {$this->tables['user']} u
            LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId 
            WHERE COALESCE(value, '') $op '$target'
END;
        return $sql;
    }

    public function selectSubquery($attributeId, $operator, $target)
    {
        $in = ($operator == SegmentPlugin_Operator::ONE ? 'IN' : 'NOT IN') . ' (' . implode(', ', $target) . ')';
        $sql = <<<END
            SELECT id
            FROM {$this->tables['user']} u
            LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId 
            WHERE COALESCE(value, 0) $in
END;
        return $sql;
    }

    public function dateSubquery($attributeId, $operator, $target)
    {
        $target = sql_escape($target);
        $op = $operator == SegmentPlugin_Operator::BEFORE ? '<' 
            : ($operator == SegmentPlugin_Operator::AFTER ? '>' : '=');
        $sql = <<<END
            SELECT id
            FROM {$this->tables['user']} u
            LEFT JOIN {$this->tables['user_attribute']} ua  ON u.id = ua.userid AND ua.attributeid = $attributeId 
            WHERE COALESCE(value, '') != '' AND DATE(COALESCE(value, '')) $op '$target'
END;
        return $sql;
    }

    public function checkboxSubquery($attributeId, $operator, $target)
    {
        $op = $operator == SegmentPlugin_Operator::IS ? '=' : '!=';
        $sql = <<<END
            SELECT id
            FROM {$this->tables['user']} u
            LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId 
            WHERE COALESCE(value, '') $op 'on'
END;
        return $sql;
    }

    public function checkboxgroupSubquery($attributeId, $operator, $target)
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
        $where = implode(" $boolean ", $where);
        $where = "WHERE ($where)";

        $sql = <<<END
            SELECT id
            FROM {$this->tables['user']} u
            LEFT JOIN {$this->tables['user_attribute']} ua ON u.id = ua.userid AND ua.attributeid = $attributeId 
            $where
END;
        return $sql;
    }

    public function subscribers($messageId, array $subquery, $combine)
    {
        if ($combine == SegmentPlugin_Operator::ONE) {
            $join = "JOIN (\n" . implode("\nUNION\n", $subquery) . ") AS T1 ON u.id = T1.id\n";
        } else {
            $join = '';

            foreach ($subquery as $n => $s) {
                $join .= "JOIN (\n$s) AS T$n ON u.id = T$n.id\n";
            }
        }
        $sql = <<<END
            SELECT DISTINCT u.id
            FROM {$this->tables['user']} u
            JOIN {$this->tables['listuser']} lu ON u.id = lu.userid
            JOIN {$this->tables['listmessage']} lm ON lm.listid = lu.listid AND lm.messageid = $messageId
            LEFT JOIN {$this->tables['usermessage']} um ON um.userid = u.id AND um.messageid = $messageId
            $join
            WHERE confirmed = 1 AND blacklisted = 0
            AND COALESCE(um.status, 'not sent') = 'not sent'
END;
        return $this->dbCommand->queryColumn($sql, 'id');
    }
}
