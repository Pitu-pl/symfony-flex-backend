<?php
declare(strict_types = 1);
/**
 * /src/Rest/ResponseHandler.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ResponseHandler
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class ResponseHandler implements ResponseHandlerInterface
{
    /**
     * Content types for supported response output formats.
     *
     * @var array
     */
    private $contentTypes = [
        self::FORMAT_JSON   => 'application/json',
        self::FORMAT_XML    => 'application/xml'
    ];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var RestResourceInterface
     */
    private $resource;

    /**
     * ResponseHandler constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Getter for serializer
     *
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * Getter for current resource service
     *
     * @return RestResourceInterface
     */
    public function getResource(): RestResourceInterface
    {
        return $this->resource;
    }

    /**
     * Setter for resource service.
     *
     * @param RestResourceInterface $resource
     *
     * @return ResponseHandlerInterface
     */
    public function setResource(RestResourceInterface $resource): ResponseHandlerInterface
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Helper method to get serialization context for request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function getSerializeContext(Request $request): array
    {
        // Specify used populate settings
        $populate = (array)$request->get('populate', []);
        $populateAll = \array_key_exists('populateAll', $request->query->all());
        $populateOnly = \array_key_exists('populateOnly', $request->query->all());

        // Get current entity name
        $entityName = $this->getResource()->getEntityName();

        $bits = \explode('\\', $entityName);
        $entityName = \end($bits);

        $populate = $this->checkPopulateAll($populateAll, $populate, $entityName);

        $groups = \array_merge([$entityName], $populate);

        if ($populateOnly) {
            $groups = \count($populate) === 0 ? [$entityName] : $populate;
        }

        return [
            'groups' => $groups,
        ];
    }

    /**
     * Helper method to create response for request.
     *
     * @param Request      $request
     * @param mixed        $data
     * @param null|integer $httpStatus
     * @param null|string  $format
     * @param null|array   $context
     *
     * @return Response
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function createResponse(
        Request $request,
        $data,
        int $httpStatus = null,
        string $format = null,
        array $context = null
    ): Response {
        $httpStatus = $httpStatus ?? 200;
        $context = $context ?? $this->getSerializeContext($request);
        $format = $this->getFormat($request, $format);

        // Get response
        $response = $this->getResponse($data, $httpStatus, $format, $context);

        // Set content type
        $response->headers->set('Content-Type', $this->contentTypes[$format]);

        return $response;
    }

    /**
     * Method to handle form errors.
     *
     * @param FormInterface $form
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handleFormError(FormInterface $form): void
    {
        $errors = [];

        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $name = $error->getOrigin()->getName();

            $errors[] = \sprintf(
                'Field \'%s\': %s',
                $name,
                $error->/** @scrutinizer ignore-call */getMessage()
            );

            if (empty($name)) {
                \array_pop($errors);

                $errors[] = $error->getMessage();
            }
        }

        throw new HttpException(Response::HTTP_BAD_REQUEST, \implode("\n", $errors));
    }

    /**
     * @param $populateAll
     * @param $populate
     * @param $entityName
     *
     * @return array
     */
    private function checkPopulateAll($populateAll, $populate, $entityName): array
    {
        // Set all associations to be populated
        if ($populateAll && \count($populate) === 0) {
            $associations = $this->getResource()->getAssociations();

            $iterator = function (string $assocName) use ($entityName): string {
                return $entityName . '.' . $assocName;
            };

            $populate = \array_map($iterator, $associations);
        }

        return $populate;
    }

    /**
     * @param Request     $request
     * @param string|null $format
     *
     * @return string
     */
    private function getFormat(Request $request, string $format = null): string
    {
        return $format ?? ($request->getContentType() === self::FORMAT_XML ? self::FORMAT_XML : self::FORMAT_JSON);
    }

    /**
     * @param mixed  $data
     * @param int    $httpStatus
     * @param string $format
     * @param array  $context
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function getResponse($data, int $httpStatus, string $format, array $context): Response
    {
        try {
            // Create new response
            $response = new Response();
            $response->setContent($this->serializer->serialize($data, $format, $context));
            $response->setStatusCode($httpStatus);
        } catch (\Exception $error) {
            $status = Response::HTTP_BAD_REQUEST;

            throw new HttpException($status, $error->getMessage(), $error, [], $status);
        }

        return $response;
    }
}
