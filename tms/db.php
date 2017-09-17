<?php
class TMS_DB {

	public $prefix;

	private static $db;
	/**
	 * 查询操作连接
	 */
	private $_mysqli;
	/**
	 * 写操作连接
	 */
	private $_writeMysqli;

	public function __construct() {
		$this->prefix = '';
	}
	/**
	 * 获得mysql数据库查询操作连接
	 */
	private function &_getDbConn() {
		if (isset($this->_mysqli)) {
			return $this->_mysqli;
		}
		if (file_exists(TMS_APP_DIR . '/cus/db.php')) {
			/**
			 * 加载本地化配置
			 */
			include_once TMS_APP_DIR . '/cus/db.php';
			/**
			 * 缺省数据库连接
			 */
			$host = TMS_MYSQL_HOST;
			$port = TMS_MYSQL_PORT;
			$user = TMS_MYSQL_USER;
			$pwd = TMS_MYSQL_PASS;
			$dbname = TMS_MYSQL_DB;
		} else if (defined('SAE_MYSQL_HOST_M')) {
			/**
			 * 缺省部署在sae
			 */
			$host = SAE_MYSQL_HOST_M;
			$port = SAE_MYSQL_PORT;
			$user = SAE_MYSQL_USER;
			$pwd = SAE_MYSQL_PASS;
			$dbname = SAE_MYSQL_DB;
		} else {
			die('无法获得数据库连接参数');
		}

		try {
			$mysqli = new mysqli($host, $user, $pwd, $dbname, $port);
		} catch (Error $e) {
			die('数据库连接异常：' . $e->getMessage());
		}
		if ($mysqli->connect_errno) {
			die("数据库连接失败: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		}
		$mysqli->query("SET NAMES UTF8");

		$this->_mysqli = &$mysqli;

		return $this->_mysqli;
	}
	/**
	 * 获得mysql数据库写操作连接
	 */
	private function &_getDbWriteConn() {
		if (isset($this->_writeMysqli)) {
			return $this->_writeMysqli;
		}
		if (defined('SAE_MYSQL_HOST_M')) {
			$this->_writeMysqli = $this->_getDbConn();
		} else {
			if (!file_exists(TMS_APP_DIR . '/cus/db.php')) {
				die('无法获得数据库连接参数');
			}
			/* 加载本地化配置 */
			include_once TMS_APP_DIR . '/cus/db.php';
			/**
			 * 如果指定了写数据库，使用写数据库连接参数，否则使用查询数据库的连接
			 * 保证查询连接和写连接不是同一个才创建连接
			 */
			if (defined('TMS_MYSQL_HOST_W') && defined('TMS_MYSQL_PORT_W') && defined('TMS_MYSQL_USER_W') && defined('TMS_MYSQL_PASS_W') && defined('TMS_MYSQL_DB_W')) {
				if (TMS_MYSQL_HOST_W !== TMS_MYSQL_HOST || TMS_MYSQL_PORT_W !== TMS_MYSQL_PORT || TMS_MYSQL_DB_W !== TMS_MYSQL_DB || TMS_MYSQL_USER_W !== TMS_MYSQL_USER) {
					try {
						$mysqli = new mysqli(TMS_MYSQL_HOST_W, TMS_MYSQL_USER_W, TMS_MYSQL_PASS_W, TMS_MYSQL_DB_W, TMS_MYSQL_PORT_W);
					} catch (Error $e) {
						die('数据库连接异常：' . $e->getMessage());
					}

					if ($mysqli->connect_errno) {
						die("数据库连接失败: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
					}

					$mysqli->query("SET NAMES UTF8");

					$this->_writeMysqli = &$mysqli;
				} else {
					$this->_writeMysqli = $this->_getDbConn();
				}
			} else {
				$this->_writeMysqli = $this->_getDbConn();
			}
		}

		return $this->_writeMysqli;
	}

	public static function db() {
		if (!self::$db) {
			self::$db = new TMS_DB;
		}
		return self::$db;
	}

	public function get_prefix() {
		return $this->prefix;
	}

	/**
	 * 获取表名 (直接写 SQL 的时候要用这个函数, 外部程序使用 get_table() 方法)
	 */
	public function get_table($name) {
		return $this->get_prefix() . $name;
	}
	/**
	 * 插入数据
	 */
	public function insert($table, $data = null, $autoid = false) {
		if (stripos($table, 'insert') === 0) {
			$sql = $table;
		} else {
			foreach ($data as $key => $val) {
				$insert_data['`' . $key . '`'] = "'" . $val . "'";
			}

			$sql = 'INSERT INTO `' . $this->get_table($table);
			$sql .= '` (' . implode(', ', array_keys($insert_data));
			$sql .= ') VALUES (' . implode(', ', $insert_data) . ')';
		}

		$mysqli = $this->_getDbWriteConn();
		($mysqli->query($sql)) || $this->show_error('database error:' . $sql . ';' . $mysqli->error);

		if ($autoid) {
			$last_insert_id = $mysqli->insert_id;
			return $last_insert_id;
		} else {
			return true;
		}
	}
	/**
	 * 更新数据
	 */
	public function update($table, $data = null, $where = '') {
		if (stripos($table, 'update') === 0) {
			$sql = $table;
		} else {
			$where || $this->show_error('DB Update no where string.');

			$sql = 'UPDATE ' . $this->get_table($table);

			$updateStrs = [];
			foreach ($data as $key => $val) {
				$updateStrs[] = '`' . $key . "` = '" . $val . "'";
			}
			$sql .= ' SET ' . implode(', ', $updateStrs);

			/* 防止SQL注入 */
			$clauses = [];
			if (is_array($where)) {
				foreach ($where as $k => $v) {
					$clauses[] = $k . "='" . $this->escape($v) . "'";
				}
			} else {
				$clauses[] = $where;
			}
			$sql .= ' WHERE ' . implode(' and ', $clauses);
		}

		$mysqli = $this->_getDbWriteConn();
		if (!$mysqli->query($sql)) {
			throw new Exception("database error(update $table):" . $sql . ';' . $mysqli->error);
		}

		$rows_affected = $mysqli->affected_rows;

		return $rows_affected;
	}
	/**
	 * 删除数据
	 */
	public function delete($table, $where) {
		if (empty($where)) {
			throw new Exception('DB Delete no where string.');
		}

		$sql = 'DELETE FROM ' . $this->get_table($table);
		/* 防止SQL注入 */
		$clauses = [];
		if (is_array($where)) {
			foreach ($where as $k => $v) {
				$clauses[] = $k . "='" . $this->escape($v) . "'";
			}
		} else {
			$clauses[] = $where;
		}
		$sql .= ' WHERE ' . implode(' and ', $clauses);

		$mysqli = $this->_getDbWriteConn();
		if (!$mysqli->query($sql)) {
			throw new Exception("database error(delete $table):" . $sql . ';' . $mysqli->error);
		}

		$rows_affected = $mysqli->affected_rows;

		return $rows_affected;
	}
	/**
	 * 查询一行, 返回对象
	 */
	public function query_obj($select, $from = null, $where = null, $onlyWriteDbConn = false) {
		$sql = $this->_assemble_query($select, $from, $where);

		if ($onlyWriteDbConn === true) {
			$mysqli = $this->_getDbWriteConn();
		} else {
			$mysqli = $this->_getDbConn();
		}
		($db_result = $mysqli->query($sql)) || $this->show_error("database error:" . $sql . ';' . $mysqli->error);

		if ($db_result->num_rows > 1) {
			$this->show_error("database error:数据不唯一，无法返回唯一的记录");
		} else if ($db_result->num_rows === 1) {
			$row = $db_result->fetch_object();
		} else {
			$row = false;
		}

		$db_result->free();

		return $row;
	}
	/**
	 * 获取查询全部
	 */
	public function query_objs($select, $from = null, $where = null, $group = null, $order = null, $offset = null, $limit = null, $onlyWriteDbConn = false) {
		$sql = $this->_assemble_query($select, $from, $where, $group, $order, $offset, $limit);

		if ($onlyWriteDbConn === true) {
			$mysqli = $this->_getDbWriteConn();
		} else {
			$mysqli = $this->_getDbConn();
		}
		($db_result = $mysqli->query($sql)) || $this->show_error("database error:$sql;" . $mysqli->error);

		$objects = array();
		while ($obj = $db_result->fetch_object()) {
			$objects[] = $obj;
		}

		$db_result->free();

		return $objects;
	}

	/**
	 *
	 * return if rownum == 0 then return false.
	 */
	public function query_value($select, $from = null, $where = null, $onlyWriteDbConn = false) {
		$sql = $this->_assemble_query($select, $from, $where);

		if ($onlyWriteDbConn === true) {
			$mysqli = $this->_getDbWriteConn();
		} else {
			$mysqli = $this->_getDbConn();
		}
		($db_result = $mysqli->query($sql)) || $this->show_error("database error:$sql;" . $mysqli->error);

		$row = $db_result->fetch_row();

		$value = $row ? $row[0] : false;

		$db_result->free();

		return $value;
	}
	/**
	 *
	 */
	public function query_values($select, $from = null, $where = null, $onlyWriteDbConn = false) {
		$sql = $this->_assemble_query($select, $from, $where);

		if ($onlyWriteDbConn === true) {
			$mysqli = $this->_getDbWriteConn();
		} else {
			$mysqli = $this->_getDbConn();
		}
		($db_result = $mysqli->query($sql)) || $this->show_error("database error:" . $sql . ';' . $mysqli->error);

		$values = array();
		while ($row = $db_result->fetch_row()) {
			$values[] = $row[0];
		}

		$db_result->free();

		return $values;
	}

	public function found_rows() {
		return $this->query_value('SELECT FOUND_ROWS()');
	}
	/**
	 * 处理SQL注入问题
	 */
	public function escape($str) {
		if ($mysqli = $this->_getDbConn()) {
			$str = empty($str) ? '' : $mysqli->real_escape_string($str);
		}

		return $str;
	}
	/**
	 * assemble a whole sql.
	 */
	private function _assemble_query($select, $from = null, $where = null, $group = null, $order = null, $offset = null, $limit = 0) {
		$select || $this->show_error('Query was empty.');

		is_array($select) && $select = implode(',', $select);
		if (stripos($select, 'select') === false) {
			$select = 'select ' . $select;
			if ($from) {
				$select .= ' from ';
				$tables = is_array($from) ? $from : array($from);
				array_walk($tables, array($this, 'get_table'));
				$select .= implode(',', $tables);
			}
			if ($where) {
				$select .= ' where ';
				$clauses = [];
				if (is_array($where)) {
					foreach ($where as $k => $v) {
						if (!isset($v)) {
							continue;
						}
						if (is_string($v)) {
							$clauses[] = $k . "='" . $this->escape($v) . "'";
						} else if (is_array($v)) {
							$clause = $k . " in('";
							$clause .= implode("','", $v);
							$clause .= "')";
							$clauses[] = $clause;
						} else if (is_object($v)) {
							if (isset($v->op) && isset($v->pat)) {
								switch ($v->op) {
								case 'like':
									$clause = $k . " like '" . $v->pat . "'";
									$clauses[] = $clause;
									break;
								}
							}
						} else {
							$clauses[] = $k . "=" . $v;
						}
					}
				} else {
					$clauses[] = $where;
				}
				$select .= implode(' and ', $clauses);
			}
			if ($group) {
				$select .= ' ';
				if (is_array($group)) {
					$group = implode(',', $group);
				}
				if (stripos($group, 'group by') === false) {
					$select .= 'group by ';
				}
				$select .= $group;
			}
			if ($order) {
				$select .= ' ';
				if (is_array($order)) {
					$order = implode(',', $order);
				}
				if (stripos($order, 'order by') === false) {
					$select .= 'order by ';
				}
				$select .= $order;
			}
			if ($limit) {
				$select .= " limit $offset,$limit";
			}
		}

		return $select;
	}
	/**
	 *
	 */
	private function show_error($msg) {
		throw new Exception($msg);
	}
}