<?php
namespace Gram\Domain\Entity;

/**
 * Interface LifeCycleInterface
 * @package Gram\Domain
 */
interface LifeCycleInterface
{
    /**
     * @return mixed
     */
    function onCreate();

    /**
     * @return mixed
     */
    function onUpdate();

    /**
     * @return mixed
     */
    function onDelete();
}