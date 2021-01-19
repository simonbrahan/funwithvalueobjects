<?php

declare(strict_types = 1);

function intfromString(string $str): int
{
    $val = filter_var($str, FILTER_VALIDATE_INT);

    if ($val === false) {
        throw new Exception('Parameter cannot be converted to an integer');
    }

    return $val;
}

class Request
{
    public static function requireFromGetAsInt(string $param_name): int
    {
        $val = filter_input(INPUT_GET, $param_name, FILTER_VALIDATE_INT);

        if ($val === null) {
            throw new Exception('GET parameter ' . $param_name . ' not provided');
        }

        if ($val === false) {
            throw new Exception(
                'GET parameter ' . $param_name . ' must be an integer'
            );
        }

        return (int) $val;
    }
}

class UserRepo
{
    private PDO $db;

    public static function init()
    {
        return new UserRepo(
            new PDO(
                'mysql:dbname=users',
                'root',
                'root',
                [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ ]
            )
        );
    }

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function byId(UserId $id)
    {
        $get_user_query = $this->db->prepare('SELECT id, user_name FROM user WHERE id = ?');

        $get_user_query->execute([$id]);

        if (!$r = $get_user_query->fetch()) {
            throw new Exception('No user with id ' . $id);
        }

        return User::fromRow($r);
    }
}

class User
{
    private UserId $id;

    private UserName $user_name;

    public static function fromRow(StdClass $row): User
    {
        return new User(
            new UserId(intFromString($row->id)),
            new UserName($row->user_name)
        );
    }

    public function __construct(UserId $id, UserName $user_name)
    {
        $this->id = $id;
        $this->user_name = $user_name;
    }
}

class UserId
{
    private int $id;

    public function __construct(int $id)
    {
        if ($id < 1) {
            throw new Exception('User ID must be an integer greater than zero');
        }

        $this->id = $id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}

class UserName
{
    private string $user_name;

    public function __construct(string $user_name)
    {
        if (mb_strlen($user_name) < 1) {
            throw new Exception('User name must be a non empty string of max 255 characters');
        }

        $this->user_name = $user_name;
    }

    public function __toString()
    {
        return $this->user_name;
    }
}

echo '<pre>';

$user_id = new UserId(Request::requireFromGetAsInt('user_id'));

$user = UserRepo::init()->byId($user_id);

var_dump($user);
