<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Result;
use Faker\Factory as FakerFactoryAlias;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsQueryController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;

    /** @var array<string,string> $userHeaders */
    private static array $userHeaders;

    protected function setUp(): void
    {
        parent::setUp();
        self::$adminHeaders = self::getTokenHeaders(self::$role_admin[User::EMAIL_ATTR],self::$role_admin[User::PASSWD_ATTR]);
        self::$userHeaders = self::getTokenHeaders(self::$role_user[User::EMAIL_ATTR],self::$role_user[User::PASSWD_ATTR]);
    }
    protected function classSetUp(): void
    {
        parent::classSetUp();
        $this->createTestData();
    }

    private function createTestData(): void
    {
        $resultData = [
            Result::RESULT_ATTR => 100,
            Result::USER_ATTR => self::$role_user[User::EMAIL_ATTR],
            Result::TIME_ATTR => '2023-12-12 10:10:10'
        ];
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($resultData))
        );
    }

    /**
     * Test OPTIONS /result[/resultId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsResultAction204NoContent(): void
    {
        // OPTIONS /api/v1/results
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }

    /**
     * Test POST /results 201 Created
     *
     * @return array result data
     */
    public function testPostResultAction201Created(): array
    {
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(0, 100),
            Result::TIME_ATTR => self::$faker->time('Y-m-d H:i:s'),
            Result::USER_ATTR => "user2@alumnos.upm.es",
        ];

        // 201
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->headers->get('Location'));
        self::assertJson(strval($response->getContent()));
        $result = json_decode(strval($response->getContent()), true)[Result::RESULT_ATTR];
        self::assertNotEmpty($result['id']);
        self::assertSame($p_data[Result::RESULT_ATTR], $result[Result::RESULT_ATTR]);
        self::assertSame(
            (new \DateTime($p_data[Result::TIME_ATTR]))->format('Y-m-d H:i:s'),
            (new \DateTime($result[Result::TIME_ATTR]))->format('Y-m-d H:i:s')
        );

        return $result;
    }


    /**
     * Test GET /results 200 Ok
     *
     * @depends testPostResultAction201Created
     *
     * @return string ETag header
     */
    public function testCGetResultAction200Ok(): string
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], self::$adminHeaders,);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        $r_body = strval($response->getContent());
        self::assertJson($r_body);
        $results = json_decode($r_body, true);
        self::assertArrayHasKey('results', $results);

        return (string) $response->getEtag();
    }

    /**
     * Test GET /results 304 NOT MODIFIED
     *
     * @param string $etag returned by testCGetResultAction200Ok
     *
     * @depends testCGetResultAction200Ok
     */
    public function testCGetResultAction304NotModified(string $etag): void
    {
        $headers = array_merge(
            self::$adminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test GET /results/{resultId} 200 Ok
     *
     * @param array<int,string,mixed,\DateTime> $result result returned by testPostResultAction201()
     * @return  string ETag header
     * @depends testPostResultAction201Created
     */
    public function testGetResultAction200Ok(array $result): string
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($response->getEtag());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        return (string) $response->getEtag();
    }

    /**
     * Test GET /result/{resultId} 304 NOT MODIFIED
     *
     * @param array $result result returned by testPostResultAction201Created()
     * @depends testPostResultAction201Created
     * @param string $etag returned by testGetResultAction200Ok
     * @depends testGetResultAction200Ok
     */
    public function testGetResultAction304NotModified(array $result, string $etag): string
    {
        $headers = array_merge(
            self::$adminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(Request::METHOD_GET, self::RUTA_API . '/' . $result['id'], [], [], $headers);
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        return $etag;
    }

    /**
     * Test POST /results 400 Bad Request
     *
     * @param   array $result result returned by testPostResultAction201Created()
     * @depends testPostResultAction201Created
     */
    public function testPostResultAction400BadRequest(array $result): void
    {
        $p_data = [
            Result::RESULT_ATTR => $result[Result::RESULT_ATTR],
            Result::USER_ATTR => $result[Result::USER_ATTR],
            Result::TIME_ATTR => "falsa fecha"
        ];
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Test PUT /results/{resultId} 209 Content Returned
     *
     * @param   array $result result returned by testPostResultAction201Created()
     * @param   string $etag returned by testGetResultAction304NotModified()
     * @return  array<string,string> modified result data
     * @depends testPostResultAction201Created
     * @depends testGetResultAction304NotModified
     * @depends testCGetResultAction304NotModified
     * @depends testPostResultAction400BadRequest
     */
    public function testPutResultAction209ContentReturned(array $result, string $etag): array
    {
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(0, 100),
            Result::TIME_ATTR => self::$faker->time('Y-m-d H:i:s'),
            Result::USER_ATTR => "user2@alumnos.upm.es",
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                [ 'HTTP_If-Match' => $etag ]
            ),
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();

        self::assertSame(209, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $resultData = json_decode($r_body, true);

        return $result;
    }

    /**
     * Test PUT /results/{resultId} 400 Bad Request
     *
     * @param   array $result result returned by testPutResultAction209()
     * @return  void
     * @depends testPutResultAction209ContentReturned
     */
    public function testPutResultAction400BadRequest(array $result): void
    {
        $p_data = [
            User::EMAIL_ATTR => $result[Result::USER_ATTR]
        ];
        self::$client->request(
            Request::METHOD_HEAD,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );
        $etag = self::$client->getResponse()->getEtag();
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                [ 'HTTP_If-Match' => $etag ]
            ),
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test PUT /results/{resultId} 412 PRECONDITION_FAILED
     *
     * @param   array $result result returned by testPutResultAction209ContentReturned()
     * @return  void
     * @depends testPutResultAction209ContentReturned
     */
    public function testPutResultAction412PreconditionFailed(array $result): void
    {
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_PRECONDITION_FAILED);
    }


    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param   string $etag etag returned by testGetResultAction200Ok()
     * @return  void
     * @depends testGetResultAction200Ok
     */
    public function testDeleteResultAction204NoContent(string $etag): void
    {
        $resultData = [
            Result::RESULT_ATTR => 100,
            Result::USER_ATTR => self::$role_user[User::EMAIL_ATTR],
            Result::TIME_ATTR => '2023-12-12 10:10:10'
        ];
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($resultData))
        );
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/'. 2 ,
            [],
            [],
            self::$adminHeaders,
        );
        $response = self::$client->getResponse();
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );

    }


    /**
     * Test GET    /results 401 UNAUTHORIZED
     * Test POST   /results 401 UNAUTHORIZED
     * Test GET    /results/{resultId} 401 UNAUTHORIZED
     * Test PUT    /results/{resultId} 401 UNAUTHORIZED
     * Test DELETE /results/{resultId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes401
     * @return void
     */
    public function testResultStatus401Unauthorized(string $method, string $uri): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            [ 'HTTP_ACCEPT' => 'application/json' ]
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Test GET    /results/{resultId} 404 NOT FOUND
     * Test PUT    /results/{resultId} 404 NOT FOUND
     * Test DELETE /results/{resultId} 404 NOT FOUND
     *
     * @param string $method
     * @return void
     * @dataProvider providerRoutes404
     */
    public function testResultStatus404NotFound(string $method): void
    {
        self::$client->request(
            $method,
            self::RUTA_API . '/' . 99,
            [],
            [],
            self::$adminHeaders
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
    *Test POST /results 403 FORBIDDEN
    *Test PUT /results/{resultId} 403 FORBIDDEN
    *Test DELETE /results/{resultId} 403 FORBIDDEN*
    *@param string $method
    *@param string $uri
    *@dataProvider providerRoutes403
    *@return void
     */
    public function testResultStatus403Forbidden(string $method, string $uri): void
    {
        $resultData = [
            'id' => 50,
            Result::RESULT_ATTR=>100,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR],
            Result::TIME_ATTR=>'2023-12-12 10:10:10'
        ];

        self::$client->request($method, $uri, [], [], self::$userHeaders,strval(json_encode($resultData)) );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * * * * * * * * * *
     * P R O V I D E R S
     * * * * * * * * * *
     */

    /**
     * User provider (incomplete) -> 422 status code
     *
     * @return Generator name => [result, time, user]
     */
    #[ArrayShape([
        'no_result' => 'array',
        'no_time' => 'array',
        'no_user' => 'array',
    ])]
    public function invalidInputProvider(): Generator
    {
        $faker = FakerFactoryAlias::create('es_ES');

        yield 'no_result' => [null, $faker->time(), $faker->email()];
        yield 'no_time' => [$faker->numberBetween(), null, $faker->email()];
        yield 'no_user' => [$faker->numberBetween(), $faker->time(), null];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => 'array',
        'getAction401' => 'array',
        'postAction401' => 'array',
        'putAction401' => 'array',
        'deleteAction401' => 'array',
    ])]
    public function providerRoutes401(): Generator
    {
        yield 'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API ];
        yield 'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ];
        yield 'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }

    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'getAction404' => 'array',
        'putAction404' => 'array',
        'deleteAction404' => 'array',
    ])]
    public function providerRoutes404(): Generator
    {
        yield 'getAction404'    => [ Request::METHOD_GET ];
        yield 'putAction404'    => [ Request::METHOD_PUT ];
        yield 'deleteAction404' => [ Request::METHOD_DELETE ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'postAction403' => 'array',
        'putAction403' => 'array',
        'deleteAction403' => 'array',
    ])]
    public function providerRoutes403(): Generator
    {
        yield 'putAction403'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction403' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }
}
