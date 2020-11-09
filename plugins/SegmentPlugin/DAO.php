<?php
/**
 * CriteriaPlugin for phplist.
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
 *
 * @author    Duncan Cameron
 * @copyright 2014-2016 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * DAO class that encapsulates the database access.
 *
 * @category  phplist
 */
class SegmentPlugin_DAO extends CommonPlugin_DAO
{
    private function formatInList(array $values)
    {
        return '(' . implode(', ', $values) . ')';
    }

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

            if (($key = array_search(-1, $excluded)) !== false) {
                unset($excluded[$key]);
            }

            if (count($excluded) > 0) {
                $inList = $this->formatInList($excluded);
                $excludeSubquery = <<<END
u.id NOT IN (
    SELECT userid
    FROM {$this->tables['listuser']}
    WHERE listid IN $inList
)
END;
            }
        }

        return $excludeSubquery;
    }

    private function buildSubscriberQuery($select, $messageId, array $joins, $combine, $limit = 0)
    {
        $excludeSubquery = '';

        if (USE_LIST_EXCLUDE) {
            if ($excludeSubquery = $this->exclude($messageId)) {
                $excludeSubquery = "AND (\n$excludeSubquery\n)";
            }
        }

        $booleanOp = ($combine == SegmentPlugin_Operator::ONE) ? 'OR' : 'AND';
        $extraJoin = '';

        if (count($joins) > 0) {
            $extraWhere = array();

            foreach ($joins as $p) {
                $extraJoin .= $p->join ? $p->join . "\n" : '';
                $extraWhere[] = $p->where;
            }
            $w = "AND (\n" . implode("\n$booleanOp ", $extraWhere) . "\n)";
        } else {
            $w = '';
        }
        $query = <<<END
SELECT $select
FROM {$this->tables['user']} u
JOIN {$this->tables['listuser']} lu0 ON u.id = lu0.userid
JOIN {$this->tables['listmessage']} lm0 ON lm0.listid = lu0.listid AND lm0.messageid = $messageId
LEFT JOIN {$this->tables['usermessage']} um0 ON um0.userid = u.id AND um0.messageid = $messageId
$extraJoin
WHERE u.confirmed = 1
AND u.blacklisted = 0
AND (um0.status IS NULL OR um0.status IN ('not sent', 'todo'))
$excludeSubquery
$w
END;

        if ($limit > 0) {
            $query .= <<<END
LIMIT 0, $limit
END;
        }

        return $query;
    }

    /**
     * Retrieves the values for a select/radio button attribute.
     *
     * @param array $attribute an attribute
     *
     * @return Iterator
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
     * Retrieves campaigns.
     *
     * @param string $loginId login id of the current admin
     * @param int    $max     Maximum number of campaigns to be returned
     * @param array  $lists   Lists to which the campaign is to be sent
     *
     * @return Iterator
     */
    public function campaigns($loginId, $max, $lists)
    {
        $owner = $loginId ? "AND m.owner = $loginId" : '';
        $inList = $this->formatInList($lists);

        $sql = <<<END
SELECT DISTINCT m.id, CONCAT_WS(' - ',m.subject, DATE_FORMAT(m.sent,'%d/%m/%y')) AS subject, m.sent
FROM {$this->tables['message']} m
JOIN {$this->tables['listmessage']} lm ON m.id = lm.messageid AND lm.listid IN $inList
WHERE m.status = 'sent'
$owner
ORDER BY m.sent DESC
LIMIT $max
END;

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

    /**
     * Returns the id of each subscriber to which the message will be sent.
     *
     * @param int   $messageId message id
     * @param array $joins
     * @param int   $combine   whether to AND or OR conditions
     *
     * @return Iterator
     */
    public function subscribers($messageId, array $joins, $combine)
    {
        $query = $this->buildSubscriberQuery('DISTINCT u.id', $messageId, $joins, $combine);

        return $this->dbCommand->queryAll($query);
    }

    /**
     * Returns the total number of subscribers to which the message will be sent and the email address of the
     * first N subscribers.
     *
     * @param int   $messageId message id
     * @param array $joins
     * @param int   $combine   whether to AND or OR conditions
     * @param int   $limit     the maximum number of subscriber email addresses to return
     *
     * @return array [int, Iterator]
     */
    public function calculateSubscribers($messageId, array $joins, $combine, $limit)
    {
        $query = $this->buildSubscriberQuery('SQL_CALC_FOUND_ROWS DISTINCT u.email', $messageId, $joins, $combine, $limit);
        $subscribers = $this->dbCommand->queryAll($query);
        $totalRows = $this->dbCommand->queryOne('SELECT FOUND_ROWS()');

        return [$totalRows, $subscribers];
    }

    /**
     * Returns the highest value of id from the user table.
     *
     * @return int the highest value of id
     */
    public function highestSubscriberId()
    {
        $sql = "
            SELECT MAX(id)
            FROM {$this->tables['user']}
        ";

        return $this->dbCommand->queryOne($sql);
    }
}
