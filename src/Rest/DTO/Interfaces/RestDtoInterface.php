<?php
declare(strict_types = 1);
/**
 * /src/Rest/DTO/Interfaces/RestDtoInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\DTO\Interfaces;

use App\Entity\Interfaces\EntityInterface;

/**
 * Interface RestDtoInterface
 *
 * @package App\Rest\DTO\\Interfaces
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface RestDtoInterface
{
    /**
     * Getter method for visited setters. This is needed for dto patching.
     *
     * @return array
     */
    public function getVisited(): array;

    /**
     * Setter for visited data. This is needed for dto patching.
     *
     * @param string $property
     *
     * @return RestDtoInterface
     */
    public function setVisited(string $property): RestDtoInterface;

    /**
     * Method to patch current dto with another one.
     *
     * @param RestDtoInterface $dto
     *
     * @return RestDtoInterface
     *
     * @throws \BadMethodCallException
     */
    public function patch(RestDtoInterface $dto): RestDtoInterface;

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface $entity
     *
     * @return RestDtoInterface
     */
    public function load(EntityInterface $entity): RestDtoInterface;

    /**
     * Method to update specified entity with DTO data.
     *
     * @param EntityInterface $entity
     *
     * @return EntityInterface
     */
    public function update(EntityInterface $entity): EntityInterface;
}
