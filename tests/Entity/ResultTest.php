<?php

/**
 * @category TestEntities
 * @package  App\Tests\Entity
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://miw.etsisi.upm.es/ E.T.S. de Ingeniería de Sistemas Informáticos
 */

namespace App\Tests\Entity;

use App\Entity\Result;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testConstructor()
    {
        $result = new Result(42, new User(), new \DateTime());

        $this->assertEquals(42, $result->getResult());
        $this->assertInstanceOf(User::class, $result->getUser());
        $this->assertInstanceOf(\DateTimeInterface::class, $result->getTime());
    }

    public function testSettersAndGetters()
    {
        $result = new Result();

        $result->setId(1);
        $result->setResult(99);
        $result->setTime(new \DateTime());
        $user = new User();
        $result->setUser($user);

        $this->assertEquals(1, $result->getId());
        $this->assertEquals(99, $result->getResult());
        $this->assertInstanceOf(\DateTimeInterface::class, $result->getTime());
        $this->assertSame($user, $result->getUser());
    }

    public function testTimeFromString()
    {
        $result = new Result();
        $result->setTimeFromString('2023-01-01 12:34:56');

        $expectedTime = new \DateTime('2023-01-01 12:34:56');

        $this->assertEquals($expectedTime, $result->getTime());
    }

    public function testJsonSerialize()
    {
        $user = new User();
        $user->setEmail('testuser@alumnos.upm.es');

        $result = new Result(42, $user, new \DateTime('2023-01-01 12:34:56'));
        $result->setId(1);

        $expectedJson = [
            'user' => [
                'Id' => 1,
                'result' => 42,
                'time' => '2023-01-01 12:34:56',
                'user' => $user
            ]
        ];

        $this->assertEquals($expectedJson, $result->jsonSerialize());
    }
}
