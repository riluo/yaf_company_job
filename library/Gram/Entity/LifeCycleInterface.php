<?php
namespace Gram\Entity;

/**
 * Interface LifeCycleInterface
 * @package Gram\Entity
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