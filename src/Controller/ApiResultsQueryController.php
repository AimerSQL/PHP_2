<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use function in_array;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsQueryInterface::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsQueryController extends AbstractController implements ApiResultsQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**

    @see ApiResultsQueryInterface::cgetAction()*
    @Route(
    path=".{_format}/{sort?id}",
    defaults={ "_format": "json", "sort": "id" },
    requirements={
    "sort": "id|email|roles",
    "_format": "json|xml"
    },
    methods={ Request::METHOD_GET },
    name="cget"
    )
     *
    @throws JsonException
     */
    public function cgetAction(Request $request): Response{$format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED,'Unauthorized: Invalid credentials.',$format);}

        $order = strval($request->get('sort'));
        $results = $this->entityManager
            ->getRepository(Result::class)
            ->findBy([], [$order => 'ASC']);

        // No hay resultados?
        // @codeCoverageIgnoreStart
        if (empty($results)) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }
        // @codeCoverageIgnoreEnd

        // Caching with ETag
        $etag = md5((string) json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results' => array_map(fn($result) => ['result' => $result], $results)],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::getAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     *
     * @throws JsonException
     */
    public function getAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        /** @var User $user */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);    // 404
        }


        // Caching with ETag
        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
                return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::optionsAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "resultId" = 0, "_format": "json" },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_OPTIONS },
     *     name="options"
     * )
     */
    public function optionsAction(int|null $resultId): Response
    {
        $methods = $resultId !== 0
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [ Request::METHOD_GET, Request::METHOD_POST ];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::postAction()
     *
     * @Route(
     *     path=".{_format}",
     *     defaults={ "_format": "json" },
     *     requirements={ "_format": "json|xml" },
     *     methods={ Request::METHOD_POST },
     *     name="post"
     * )
     *
     * @throws JsonException
     */
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }


        $body = $request->getContent();
        $postData = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($postData[Result::RESULT_ATTR], $postData[Result::TIME_ATTR])) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, 'The result field or the time field are not passed', $format);
        }

        $userIdentifier = $this->getUser()->getUserIdentifier();
        if(isset($postData[Result::USER_ATTR])){
            $userIdentifier = $postData[Result::USER_ATTR];
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([User::EMAIL_ATTR=>$userIdentifier]);
        if(!$user){
            return Utils::errorMessage(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                "User not found for identifier: $userIdentifier",
                $format
            );
        }
        $result = new Result();
        try{
            $result->setResult($postData[Result::RESULT_ATTR])
                ->setTimeFromString($postData[Result::TIME_ATTR])
                ->setUser($user);
        }catch (\TypeError){
            return Utils::errorMessage(
                Response::HTTP_BAD_REQUEST,
                "",
                $format
            );
        }

        $this->entityManager->persist($result);
        $this->entityManager->flush();
        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    self::RUTA_API . '/' . $result->getId(),
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::putAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": "json" },
     *     requirements={
     *         "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_PUT },
     *     name="put"
     * )
     *
     * @throws JsonException
     */
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        $userIdentifier = $this->getUser()->getUserIdentifier();
        if(isset($postData[Result::USER_ATTR])){
            $userIdentifier = $postData[Result::USER_ATTR];
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([User::EMAIL_ATTR=>$userIdentifier]);
        if(!$user){
            return Utils::errorMessage(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                "User not found for identifier: $userIdentifier",
                $format
            );
        }

        $body = (string) $request->getContent();
        $postData = json_decode($body, true);

        /** @var Result $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {    // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        // Puede editar otro resultado diferente sólo si tiene ROLE_ADMIN o ese resultado pertenece a este usuario
        if (!$this->isGranted(self::ROLE_ADMIN) && $this->getUser()->getId() !== $result->getUser()->getId()) {
            return Utils::errorMessage( // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }

        // Optimistic Locking (strong validation, password included)
        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (!$request->headers->has('If-Match') || $etag != $request->headers->get('If-Match')) {
            return Utils::errorMessage(
                Response::HTTP_PRECONDITION_FAILED,
                'PRECONDITION FAILED: one or more conditions given evaluated to false',
                $format
            ); // 412
        }

        $userIdentifier = $this->getUser()->getUserIdentifier();
        if(isset($postData[Result::USER_ATTR])){
            $userIdentifier = $postData[Result::USER_ATTR];
        }
        $user = $this->entityManager->getRepository(User::class)->findOneBy([User::EMAIL_ATTR=>$userIdentifier]);
        if(!$user){
            return Utils::errorMessage(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                "User not found for identifier: $userIdentifier",
                $format
            );
        }

        try{
            $result->setResult($postData[Result::RESULT_ATTR])
                ->setTimeFromString($postData[Result::TIME_ATTR])
                ->setUser($user);
        }catch (\TypeError){
            return Utils::errorMessage(
                Response::HTTP_BAD_REQUEST,
                "",
                $format
            );
        }

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(
            209,                        // 209 - Content Returned
            [ User::USER_ATTR => $user ],
            $format
        );
    }

    /**
     * @see ApiResultsQueryInterface::deleteAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": "json" },
     *     requirements={
     *         "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_DELETE },
     *     name="delete"
     * )
     *
     * @throws JsonException
     */
    public function deleteAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        /** @var Result $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {   // 404 - Not Found
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        // Puede borrar un resultado sólo si tiene ROLE_ADMIN o ese resultado pertenece a este usuario
        if (!$this->isGranted(self::ROLE_ADMIN) && $this->getUser()->getId() !== $result->getUser()->getId()) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access',
                $format
            );
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

}
