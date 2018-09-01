<?php
/**
 * @db
 */
class db
{
	//Properties
	private $host = 'localhost';
	private $user = '--username--';
	private $password = '--password';
	private $name = '--dbname--';

	//Conect
	public function connect()
	{
		$mysql_connect_str = "mysql:host=$this->host;dbname=$this->name";
		$dbConnection = new PDO($mysql_connect_str, $this->name, $this->password);
		$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbConnection;
	}

	public function insert($table, $object)
	{
		$query = "INSERT INTO {$table} (".implode(",",array_keys(get_object_vars($object))).") VALUES (:".implode(",:",array_keys(get_object_vars($object))).")";

		$db = $this->connect();
		$stmt = $db->prepare($query);

        foreach ($object as $key => $item)
        {
        	$stmt->bindValue(':'.$key, $item);
        }

		$stmt->execute();
    }
    public function update($table, $object, $id)
    {
        // get articles
		$query = "UPDATE $table SET ";
		$i = 1;
		foreach ($object as $key => $value)
		{
			$query .= $key . " = :" . $key ." ";
			if ($i < sizeof(get_object_vars($object)))
			{
				$query .= ", ";
			}
			$i++;
		}

		$query .= "WHERE id = $id";

		$db = $this->connect();
		$stmt = $db->prepare($query);

		foreach ($object as $key => $item)
		{
			$stmt->bindValue(':'.$key, $item);
		}

		$stmt->execute();

    }
    public function delete($object)
    {
        // Etc...
    }
}