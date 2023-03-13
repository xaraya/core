<?php
/**
 * Response trait for PSR-7 and PSR-15 compatible middleware controllers
 */

namespace Xaraya\Bridge\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use JsonException;

/**
 * For documentation purposes only - available via DefaultResponseTrait
 */
interface DefaultResponseInterface
{
    public function getResponseFactory(): ResponseFactoryInterface;
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void;
    public function getStreamFactory(): StreamFactoryInterface;
    public function setStreamFactory(StreamFactoryInterface $streamFactory): void;
    public function createResponse(string $body, string $mediaType = 'text/html; charset=utf-8'): ResponseInterface;
    public function createJsonResponse(mixed $result, string $mediaType = 'application/json; charset=utf-8', bool $numeric = true): ResponseInterface;
    public function createNotFoundResponse(string $path): ResponseInterface;
    public function createUnauthorizedResponse($status = 401): ResponseInterface;
    public function createForbiddenResponse($status = 403): ResponseInterface;
    public function createRedirectResponse(string $redirectURL, int $status = 302): ResponseInterface;
    public function createExceptionResponse(Throwable $e, mixed $result = null): ResponseInterface;
    public function createFileResponse(string $path, ?string $mediaType = null): ResponseInterface;
}

trait DefaultResponseTrait
{
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;
    protected array $options = [];

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        // @todo replace with actual stream factory instead of re-using response factory (= same for nyholm/psr7)
        if (empty($this->streamFactory) && $this->responseFactory instanceof StreamFactoryInterface) {
            return $this->responseFactory;
        }
        return $this->streamFactory;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    public function createResponse(string $body, string $mediaType = 'text/html; charset=utf-8'): ResponseInterface
    {
        if (strpos($mediaType, '; charset=') === false) {
            $mediaType .= '; charset=utf-8';
        }
        $response = $this->getResponseFactory()->createResponse()->withHeader('Content-Type', $mediaType);
        $response->getBody()->write($body);
        return $response;
    }

    public function createJsonResponse(mixed $result, string $mediaType = 'application/json; charset=utf-8', bool $numeric = true): ResponseInterface
    {
        if (strpos($mediaType, '; charset=') === false) {
            $mediaType .= '; charset=utf-8';
        }
        $response = $this->getResponseFactory()->createResponse()->withHeader('Content-Type', $mediaType);
        try {
            //$output = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR);
            if ($numeric) {
                $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR;
            } else {
                $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
            }
            $body = json_encode($result, $flags);
        } catch (JsonException $e) {
            $body = '{"JSON Exception": ' . json_encode($e->getMessage()) . '}';
        }
        $response->getBody()->write($body);
        return $response;
    }

    public function createNotFoundResponse(string $path): ResponseInterface
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus(404);
        $response->getBody()->write('Nothing to see here at ' . htmlspecialchars($path));
        return $response;
    }

    public function createUnauthorizedResponse($status = 401): ResponseInterface
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus(401)->withHeader('WWW-Authenticate', 'Token realm="Xaraya Site Login", created=');
        $response->getBody()->write('This operation is unauthorized, please authenticate.');
        return $response;
    }

    public function createForbiddenResponse($status = 403): ResponseInterface
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus(403);
        $response->getBody()->write('This operation is forbidden.');
        return $response;
    }

    public function createRedirectResponse(string $redirectURL, int $status = 302): ResponseInterface
    {
        $response = $this->getResponseFactory()->createResponse();
        $response = $response->withStatus($status)->withHeader('Location', $redirectURL);
        $response->getBody()->write('Nothing to see here...');
        return $response;
    }

    public function createExceptionResponse(Throwable $e, mixed $result = null): ResponseInterface
    {
        $body = "Exception: " . $e->getMessage();
        if ($e->getPrevious() !== null) {
            $body .= "\nPrevious: " . $e->getPrevious()->getMessage();
        }
        $body .= "\nTrace:\n" . $e->getTraceAsString();
        $here = explode('\\', static::class);
        $class = array_pop($here);
        $response = $this->getResponseFactory()->createResponse(422, $class . ' Exception')->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->getBody()->write($body);
        return $response;
    }

    public function createFileResponse(string $path, ?string $mediaType = null): ResponseInterface
    {
        if (!empty($mediaType)) {
            if (strpos($mediaType, '; charset=') === false) {
                $mediaType .= '; charset=utf-8';
            }
            $response = $this->getResponseFactory()->createResponse()->withHeader('Content-Type', $mediaType);
        } else {
            $response = $this->getResponseFactory()->createResponse();
        }
        // @todo replace with actual stream factory instead of re-using response factory (= same for nyholm/psr7)
        $response = $response->withBody($this->getStreamFactory()->createStreamFromFile($path));
        //$response = $response->withBody($this->getStreamFactory()->createStream(file_get_contents($path)));
        return $response;
    }
}
