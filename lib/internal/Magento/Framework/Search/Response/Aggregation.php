<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Search\Response;

use Magento\Framework\Search\AggregationInterface;
use Magento\Framework\Search\BucketInterface;

/**
 * Faceted data
 */
class Aggregation implements AggregationInterface
{
    /**
     * Buckets array
     *
     * @var BucketInterface[]
     */
    protected $buckets;

    /**
     * @param BucketInterface[] $buckets
     */
    public function __construct(array $buckets)
    {
        $this->buckets = $buckets;
    }

    /**
     * Implementation of \IteratorAggregate::getIterator()
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->buckets);
    }

    /**
     * Get Document field
     *
     * @param string $bucketName
     * @return BucketInterface
     */
    public function getBucket($bucketName)
    {
        return isset($this->buckets[$bucketName]) ? $this->buckets[$bucketName] : null;
    }

    /**
     * Get Document field names
     *
     * @return string[]
     */
    public function getBucketNames()
    {
        return array_keys($this->buckets);
    }
}
