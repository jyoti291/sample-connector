<?php

namespace kong_connector\Management\Normalizer;

use sample_connector\Normalizer\Normalizer\NormalizerInterface;

/**
 * Class AppNormalizer.
 */
class AppNormalizer extends implements NormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function normalize($object, $format = null, array $context = [])
    {
        /** @var object $normalized */
        $normalized = parent::normalize($object, $format, $context);
        // Remove properties that saved to attributes on apps.
        unset($normalized->displayName);
        unset($normalized->description);

        return $normalized;
    }
}
