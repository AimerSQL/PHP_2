<?php

namespace App\Utility;

use App\Entity\Message;
use Hateoas\HateoasBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Utils
 *
 * @package App\Controller
 */
class Utils
{
    /**
     * Generates a response object with the message and corresponding
     * response code (serialized according to _$format_)
     *
     * @param int $code HTTP status
     * @param array<string,mixed>|object|null $messageBody HTTP body message
     * @param null|string $format Default JSON
     * @param null|string[] $headers
     * @return Response Response object
     */
    public static function apiResponse(
        int $code,
        array|object|null $messageBody = null,
        ?string $format = 'json',
        ?array $headers = null
    ): Response {
        if (null === $messageBody) {
            $data = null;
        } else {
            $hateoas = HateoasBuilder::create()->build();
            $data = $hateoas->serialize($messageBody, $format);
        }

        $response = new Response($data, $code);
        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',   // enable CORS
            'Access-Control-Allow-Credentials' => 'true', // Ajax CORS requests with Authorization header
        ]);
        empty($headers) ?: $response->headers->add($headers);
        $response->headers->set(
            'Content-Type',
            match ($format) {
                'xml' => 'application/xml',
                // 'yml' => 'application/yaml',
                default => 'application/json',
            }
        );

        return $response;
    }

    /**
     * Return the request format [ xml | json ]
     *
     * @return string [ xml | json ]
     */
    public static function getFormat(Request $request): string
    {
        $acceptHeader = $request->getAcceptableContentTypes();
        $miFormato = ('application/xml' === ($acceptHeader[0] ?? null))
            ? 'xml'
            : 'json';

        return $request->get('_format') ?? $miFormato;
    }

    /**
     * Generates an Error Response Message
     */
    public static function errorMessage(int $statusCode, ?string $customMessage, string $format): Response
    {
        $customMessage = new Message(
            $statusCode,
            $customMessage ?? strtoupper(Response::$statusTexts[$statusCode])
        );
        return Utils::apiResponse(
            $customMessage->getCode(),
            $customMessage,
            $format
        );
    }
}
