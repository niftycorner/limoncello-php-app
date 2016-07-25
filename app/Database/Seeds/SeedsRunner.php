<?php namespace App\Database\Seeds;

use App\Database\Models\Board;
use App\Database\Models\Comment;
use App\Database\Models\Model;
use App\Database\Models\Post;
use App\Database\Models\Role;
use App\Database\Models\User;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Faker\Factory;

/**
 * @package App
 */
class SeedsRunner
{
    /**
     * @param Connection $connection
     *
     * @return void
     */
    public function run(Connection $connection)
    {
        $faker = Factory::create();
        $faker->seed(1234);

        $this->seedTable($connection, 10, Board::TABLE_NAME, function () use ($faker) {
            return [
                Board::FIELD_TITLE => 'Board ' . $faker->text(20),
            ];
        });

        $this->seedTable($connection, 5, Role::TABLE_NAME, function () use ($faker) {
            return [
                Role::FIELD_NAME => 'Role ' . $faker->text(20),
            ];
        });

        $allRoles = $this->readAll($connection, Role::TABLE_NAME);
        $this->seedTable($connection, 10, User::TABLE_NAME, function () use ($faker, $allRoles) {
            return [
                User::FIELD_ID_ROLE       => $faker->randomElement($allRoles)[Role::FIELD_ID],
                User::FIELD_TITLE         => $faker->title,
                User::FIELD_FIRST_NAME    => $faker->firstName,
                User::FIELD_LAST_NAME     => $faker->lastName,
                User::FIELD_LANGUAGE      => $faker->languageCode,
                User::FIELD_EMAIL         => $faker->email,
                User::FIELD_PASSWORD_HASH => 'some_hash',
                User::FIELD_API_TOKEN     => 'some_token',
            ];
        });

        $allBoards = $this->readAll($connection, Board::TABLE_NAME);
        $allUsers  = $this->readAll($connection, User::TABLE_NAME);
        $this->seedTable($connection, 100, Post::TABLE_NAME, function () use ($faker, $allBoards, $allUsers) {
            return [
                Post::FIELD_ID_BOARD => $faker->randomElement($allBoards)[Board::FIELD_ID],
                Post::FIELD_ID_USER  => $faker->randomElement($allUsers)[User::FIELD_ID],
                Post::FIELD_TITLE    => $faker->text(50),
                Post::FIELD_TEXT     => $faker->text(),
            ];
        });

        $allPosts = $this->readAll($connection, Post::TABLE_NAME);
        $this->seedTable($connection, 400, Comment::TABLE_NAME, function () use ($faker, $allPosts, $allUsers) {
            return [
                Comment::FIELD_ID_POST => $faker->randomElement($allPosts)[Post::FIELD_ID],
                Comment::FIELD_ID_USER => $faker->randomElement($allUsers)[User::FIELD_ID],
                Comment::FIELD_TEXT    => $faker->text(),
            ];
        });
    }

    /**
     * @param Connection $connection
     * @param int        $records
     * @param string     $tableName
     * @param Closure    $fieldsClosure
     */
    private function seedTable(Connection $connection, $records, $tableName, Closure $fieldsClosure)
    {
        for ($i = 0; $i !== (int)$records; $i++) {
            $fields = $fieldsClosure();

            $fields = array_merge($fields, [Model::FIELD_CREATED_AT => date('Y-m-d H:i:s')]);
            try {
                $result = $connection->insert($tableName, $fields);
            } catch (UniqueConstraintViolationException $e) {
                // ignore non-unique records
                $result = true;
            }
            $result ?: null;
            assert('$result !== false', 'Statement execution failed');
        }
    }

    /**
     * @param Connection $connection
     * @param string     $tableName
     *
     * @return array
     */
    protected function readAll(Connection $connection, $tableName)
    {
        $result = $connection->fetchAll("SELECT * FROM `$tableName`");

        return $result;
    }
}
