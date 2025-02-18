<?php
namespace FirePHP\Database;

use ArrayAccess;
use FirePHP\Exception\DaoException;
use FirePHP\Event\Event;
use FirePHP\Database\DataBase;
use FirePHP\Database\Query;
/**
 * Dao est la classe générale et mère de toutes les Dao spécifiques d'accès au table de la base de données.
 * @author Yoann Chaumin <yoann.chaumin@gmail.com>
 * @uses DataBase
 * @uses DaoException
 * @uses DaoObserver
 * @uses Event
 * @uses Observable
 * @uses Observer
 */
abstract class Dao implements ArrayAccess
{
    /**
     * Classe de la base de données.
     * @var DataBase
     */
    protected static $_base = NULL;

	/**
     * Classe de la base de données.
     * @var string
     */
    protected static $_table_prefix = NULL;
		
	/**
	 * Observer pour notifier même les méthodes statiques.
	 * @var Observer
	 */
	protected static $_observable = NULL;
    
    /**
     * Constructeur
     * @param array $data Liste des couples nom-valeur des attributs de l'instance.
     */
    public function __construct($data=[])
    {
    	foreach($data as $name => $value)
    	{
    		if (method_exists($this, $name))
			{
				$this->$name($value);
			}
			$this->$name = $value;
    	}
    }
    
    /**
     * Retourne un attribut.
     * @param string $name Valeur de l'attribut.
     * @throws DaoException
     */
    public function __get(string $name)
    {
    	if (property_exists($this, $name) === FALSE)
    	{
    	    $caller = get_called_class();
    	    throw new DaoException('Invalid property "'.$name.'" for class "'.$caller.'"', 1);
    	}
        return $this->$name;
    }
    
    /**
     * Définie un attribut.
     * @param string $name Nom de l'attribut.
     * @param mixed $value Valeur de l'attribut.
     */
    public function __set(string $name, $value = NULL)
    {
    	if (method_exists($this, $name))
    	{
    		return $this->$name($value);
    	}
		return $this->$name = $value;
    }

	/**
     * Définie un attribut.
     * @param string $name Nom de l'attribut.
     * @param array $value Valeur de l'attribut.
     */
    public function __call(string $name, array $values)
    {
    	if (method_exists($this, $name))
    	{
			return call_user_func_array([$this, $name], $values);
    	}
		$count = count($values);
		if ($count === 1 && is_scalar($values[0]))
		{
			return $this->$name = $values[0];
		}
		if ($count === 0)
		{
			if (property_exists($this, $name) === FALSE)
			{
				$caller = get_called_class();
    	        throw new DaoException('Invalid property "'.$name.'" for class "'.$caller.'"', 1);
			}
			return $this->$name;
		}
		return FALSE;
    }
    
    /**
     * Persiste l'objet courant en base de données.
     * @return bool
     */
    public function save()
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::save', self::observable()));

    	$table = self::table_name();
    	$fields = $this->fields();
    	$sql = "INSERT INTO `".$table."` VALUES (".implode(',', array_fill(0, count($fields), '?')).") ON DUPLICATE KEY UPDATE";
    	$value_sql = array();
    	$last = end($fields);
    	foreach ($fields as $f)
    	{
    		$sql .= " `".$f."`= VALUES(`".$f."`)";
    		if ($f != $last)
    		{
    			$sql .= ',';
    		}
    		$value_sql[] = (isset($this->$f)) ? ($this->$f) : (NULL);
    	}
		$result = self::$_base->query($sql, $value_sql);
		if (property_exists($this, "id"))
		{
			$id = self::$_base->last_id();
			$this->id = ($id == 0) ? ($this->id) : ($id);
		}
    	return ($result !== FALSE);
    }
    
    /**
     * Supprime l'instance courante de la base de données.
     * @return bool
     */
    public function remove()
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::remove', self::observable()));

    	$keys = self::keys();
    	$last = end($keys);
    	$where = [];
    	foreach ($keys as $k)
    	{
    		$where[$k] = $this->$k;
    	}
    	return self::query()->delete()->where($where)->run();
    }
    
    /**
     * Retourne un tableau contenant les éventuelles dépendances d'un enregistrement.
     * @return array Liste des tables et des enregistrements dépendants.
     */
    public function dependances()
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::dependances', self::observable()));

    	$constraintes = self::$_base->foreign_keys(self::table_name());
    	$tables = array();
    	$done = array();
    	foreach ($constraintes as $c)
    	{
    		$table = $c['TABLE_NAME'];
    		if (array_key_exists($table, $done) == FALSE)
    		{
    			$foreign_field = $c['COLUMN_NAME'];
    			$field = $c['REFERENCED_COLUMN_NAME'];
    			$rows = self::$_base->query("
				 SELECT *
						FROM ".$table."
						WHERE
						".$foreign_field." = '".($this->$field)."';
						");
    			if (is_array($rows))
    			{
    				$tables[$table] = $rows;
    			}
    			$done[$table] = TRUE;
    		}
    	}
    	return $tables;
    }
    
    /**
     * Définie la connexion avec la base de données.
     * @param DataBase $base Instance de connexion et de requêtage à la base de données.
     * @return DataBase 
     */
    public static function base(DataBase $base=NULL)
    {
		if ($base !== NULL)
		{
    		self::$_base = $base;
		}
		return self::$_base;
    }

    /**
     * Retourne l'observable.
     * @return Observable
     */
    public static function observable() : DaoObservable
    {
		if (self::$_observable === NULL)
		{
    		self::$_observable = new DaoObservable();
		}
		return self::$_observable;
    }

	/**
     * Définie la connexion avec la base de données.
     * @param string $prefix Préfixe utilisé pour les tables.
     * @return string
     */
    public static function table_prefix(string $prefix = NULL) : string
    {
    	if ($prefix !== NULL)
		{
    		self::$_table_prefix = $prefix;
		}
		return self::$_table_prefix;
    }

	/**
     * Retourne le nom de la table correspondant au Dao.
     * @return string 
     */
	public static function table_name() : string
	{
		// La fonction basename() permet de traiter les classes avec namespace.
		return strtolower(self::$_table_prefix . basename(str_replace("\\", "/", get_called_class())));
	}    

    /**
     * Retourne une instance de Query qui permet d'effectuer des requêtes spécifiques.
     * @return Query
     */
    public static function query() : Query
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::query', self::observable()));

    	return (new Query(self::base(), get_called_class(), self::table_name(), self::table_prefix()));
    }
    
    /**
     * Renvoie la liste des champs de la table.
     * @return array Liste des champs.
     */
    public static function fields() : array
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::fields', self::observable()));

    	$fields_details = self::$_base->fields(self::table_name());
    	$names = [];
    	if (is_array($fields_details))
    	{
	    	foreach ($fields_details as $f)
	    	{
	    		$names[] = $f['Field'];
	    	}
    	}
    	return $names;
    }
    
    /**
     * Renvoie la liste des clés primaires de la table.
     * @return array Liste des champss.
     */
    public static function keys() : array
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::keys', self::observable()));

    	$fields_details = self::$_base->fields(self::table_name());
    	$names = [];
    	if (is_array($fields_details))
    	{
    		foreach ($fields_details as $f)
    		{
    			if ($f['Key'] === 'PRI')
    			{
    				$names[] = $f['Field'];
    			}
    		}
    	}
    	return $names;
    }
    
    /**
     * Charge une intance à partir d'un enregistrement de la base de données.
     * @param array|string $values Identifiant ou liste d'identifiants. Prends un nombre quelconque de paramètres pour chaque clé primaire.
     * @return object Instance de l'enregistrement.
     */
    public static function load($values) : ?Dao
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::load', self::observable()));

    	// Génération des bons paramètres.
    	$values = (is_array($values)) ? ($values) : (func_get_args());
    	
    	// Génération des informations des champs.
    	$keys = self::keys();
    	if (count($keys) !== count($values))
    	{
    		return NULL;
    	}
    	// Exécution de la requête.    	
    	$result = self::query()->where(array_combine($keys, $values))->run();
    	if (count($result) === 1)
    	{
    		return $result[0];
    	}
    	return NULL;
    }
    
    /**
     * Recherche des instances, si on ne précise aucun argument, la fonction renvoie tous les enregistrements.
     * @param array $fields Tableau associatif de champs et leur valeur.
     * @param int $begin Position du premier enregistrement.
     * @param int $end Position du dernier enregistrement.
     * @param array $order Tableau associatif avec en clée les noms des champs et en valeur l'ordre.
     * @return object[] Liste des objets trouvés ou un tableau vide.
     */
    public static function search($fields=[], $begin=NULL, $end=NULL, $order=[]) : array
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::search', self::observable()));

    	$query = self::query()->where($fields);
    	foreach ($order as $n => $o)
    	{
    		$query->order($n, $o);
    	}
    	if ($begin !== NULL)
    	{
    		$query->limit($begin, $end);
			}
    	return $query->run();
    }
    
    /**
     * Compte le nombre d'enregistrements.
     * @param array $fields Tableau associatif d'égalité entre champ valeur.
     * @return int Nombre d'enregistrements.
     */
    public static function count(array $fields=[]) : int
    {
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::count', self::observable()));

    	return self::query()->count()->where($fields)->run();
    }   

	/**
	 * Vide la table.
	 */
	public static function clear()
	{
		// Notification.
		self::observable()->notify(new Event(get_called_class().'::clean', self::observable()));

		self::query()->clear();
	}

	/**
     * Retourne la liste des paramètres de l'objet.
     * @param array|object $data
     * @return array Liste attribut / valeur.
     */
	public function data($data = NULL) : array
	{
		$data = $data ?? $this;
		$array = [];
		foreach ($data as $name => $value)
		{
			if (is_object($value) || is_array($value))
			{
				$array[$name] = $this->data($value);
			}
			else 
			{
				$array[$name] = $value;
			}
		}
		return $array;
    }
    
    /**
     * ArrayAccess : offsetSet
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) : void
    {
        $this->__set($offset, $value);
    }

    /**
     * ArrayAccess : offsetExists
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return property_exists($this, $offset);
    }

    /**
     * ArrayAccess : offsetUnset
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset) : void
    {
        unset($this->$offset);
    }

    /**
     * ArrayAccess : offsetGet
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset) 
    {
        return $this->__get($offset);
    }
}
?>