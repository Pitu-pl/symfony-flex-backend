<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/UpdateMethodTestClass.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\ControllerInterface;
use App\Rest\RestResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\Traits\RestMethodHelper;
use App\Rest\Traits\Methods\UpdateMethod;

/**
 * Class UpdateMethodTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class UpdateMethodTestClass implements ControllerInterface
{
    use UpdateMethod;
    use RestMethodHelper;

    /**
     * UpdateMethodTestClass constructor.
     *
     * @param RestResourceInterface    $resource
     * @param ResponseHandlerInterface $responseHandler
     */
    public function __construct(RestResourceInterface $resource, ResponseHandlerInterface $responseHandler)
    {
    }
}
