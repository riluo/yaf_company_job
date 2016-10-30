<?php
namespace Gram\Entity;

/**
 * Interface EntityInterface
 * @package Gram\Entity
 */
interface ObjectInterface extends \JsonSerializable
{
    static function metadata();

    static function assemble(array $arr);

    static function disassemble($entity, $properties = []);
}