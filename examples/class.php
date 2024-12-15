<?php

use TypedArray\TypedArray;

require __DIR__ . '/../vendor/autoload.php';

class User
{
    public string $name;
    public string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}

class InvalidClass
{
}

class AdminUser extends User
{
}

class UserArray extends TypedArray
{
    protected string $expected_type = User::class;
}

try {
    // Example of usage without keys
    $userArray = new UserArray([
        new User('John Doe', 'john@example.com'),
        new User('Jane Smith', 'jane@example.com'),
        new AdminUser('Admin User', 'admin@example.com'),
    ]);
    echo json_encode($userArray, JSON_PRETTY_PRINT) . PHP_EOL;

    $userArray[] = new User('Alice Johnson', 'alice@example.com');
    echo json_encode($userArray, JSON_PRETTY_PRINT) . PHP_EOL;

    // Example of usage with keys
    $keyedUserArray = new UserArray([
        'admin' => new AdminUser('Admin User', 'admin@example.com'),
        'editor' => new User('Editor User', 'editor@example.com'),
    ]);
    echo json_encode($keyedUserArray, JSON_PRETTY_PRINT) . PHP_EOL;

    $keyedUserArray['viewer'] = new User('Viewer User', 'viewer@example.com');
    echo json_encode($keyedUserArray, JSON_PRETTY_PRINT) . PHP_EOL;

    // Example of invalid value
    $invalidUserArray = new UserArray([
        new User('Valid User', 'valid@example.com'),
        'invalid-value',
    ]);
    echo json_encode($invalidUserArray, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Error $e) {
    echo $e::class . ": " . $e->getMessage() . PHP_EOL;
}