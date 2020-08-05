<?php

namespace sample_connector\Normalizer\Normalizer;

/**
 * Defines the most basic interface a class must implement to be normalizable.
 */
interface NormalizerInterface
{
    /**
     * Normalizes the object into an array of scalars|arrays.
     *
     * It is important to understand that the normalize() call should normalize
     * recursively all child objects of the implementor.
     *
     * @param NormalizerInterface $normalizer The normalizer is given so that you
     *                                        can use it to normalize objects contained within this object
     * @param string|null         $format     The format is optionally given to be able to normalize differently
     *                                        based on different output formats
     * @param array               $context    Options for normalizing this object
     *
     * @return array|string|int|float|bool
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = []);
}
