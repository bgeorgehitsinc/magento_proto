<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Magento\Framework\Mview\View;

use Magento\Framework\App\Resource;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\ExpressionConverter;

class Subscription implements SubscriptionInterface
{
    /**
     * Trigger name qualifier
     */
    const TRIGGER_NAME_QUALIFIER = 'trg_';

    /**
     * Database write connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $write;

    /**
     * @var Trigger
     */
    protected $triggerFactory;

    /**
     * @var \Magento\Framework\Mview\View\CollectionInterface
     */
    protected $viewCollection;

    /**
     * @var string
     */
    protected $view;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $columnName;

    /**
     * List of views linked to the same entity as the current view
     *
     * @var array
     */
    protected $linkedViews = [];

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @param Resource $resource
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     * @param \Magento\Framework\Mview\View\CollectionInterface $viewCollection
     * @param \Magento\Framework\Mview\ViewInterface $view
     * @param string $tableName
     * @param string $columnName
     */
    public function __construct(
        Resource $resource,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Magento\Framework\Mview\View\CollectionInterface $viewCollection,
        \Magento\Framework\Mview\ViewInterface $view,
        $tableName,
        $columnName
    ) {
        $this->write = $resource->getConnection('core_write');
        $this->triggerFactory = $triggerFactory;
        $this->viewCollection = $viewCollection;
        $this->view = $view;
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->resource = $resource;
    }

    /**
     * Create subsciption
     *
     * @return \Magento\Framework\Mview\View\SubscriptionInterface
     */
    public function create()
    {
        foreach (Trigger::getListOfEvents() as $event) {
            $triggerName = $this->getTriggerName(
                $this->resource->getTableName($this->getTableName()),
                Trigger::TIME_AFTER,
                $event
            );
            /** Shorten if trigger name is too long - max is 64 characters */
            $shortenedTriggerName = $this->resource->getConnection(Resource::DEFAULT_READ_RESOURCE)
                ->getTriggerName($triggerName, self::TRIGGER_NAME_QUALIFIER);

            /** @var Trigger $trigger */
            $trigger = $this->triggerFactory->create()
                ->setName($shortenedTriggerName)
                ->setTime(Trigger::TIME_AFTER)
                ->setEvent($event)
                ->setTable($this->resource->getTableName($this->tableName));

            $trigger->addStatement($this->buildStatement($event, $this->getView()->getChangelog()));

            // Add statements for linked views
            foreach ($this->getLinkedViews() as $view) {
                /** @var \Magento\Framework\Mview\ViewInterface $view */
                $trigger->addStatement($this->buildStatement($event, $view->getChangelog()));
            }

            $this->write->dropTrigger($trigger->getName());
            $this->write->createTrigger($trigger);
        }

        return $this;
    }

    /**
     * Remove subscription
     *
     * @return \Magento\Framework\Mview\View\SubscriptionInterface
     */
    public function remove()
    {
        foreach (Trigger::getListOfEvents() as $event) {
            $triggerName = $this->getTriggerName(
                $this->resource->getTableName($this->getTableName()),
                Trigger::TIME_AFTER,
                $event
            );

            /** @var Trigger $trigger */
            $trigger = $this->triggerFactory->create()->setName(
                $triggerName
            )->setTime(
                Trigger::TIME_AFTER
            )->setEvent(
                $event
            )->setTable(
                $this->resource->getTableName($this->getTableName())
            );

            // Add statements for linked views
            foreach ($this->getLinkedViews() as $view) {
                /** @var \Magento\Framework\Mview\ViewInterface $view */
                $trigger->addStatement($this->buildStatement($event, $view->getChangelog()));
            }

            $this->write->dropTrigger($trigger->getName());

            // Re-create trigger if trigger used by linked views
            if ($trigger->getStatements()) {
                $this->write->createTrigger($trigger);
            }
        }

        return $this;
    }

    /**
     * Retrieve list of linked views
     *
     * @return array
     */
    protected function getLinkedViews()
    {
        if (!$this->linkedViews) {
            $viewList = $this->viewCollection->getViewsByStateMode(\Magento\Framework\Mview\View\StateInterface::MODE_ENABLED);

            foreach ($viewList as $view) {
                /** @var \Magento\Framework\Mview\ViewInterface $view */
                // Skip the current view
                if ($view->getId() == $this->getView()->getId()) {
                    continue;
                }
                // Search in view subscriptions
                foreach ($view->getSubscriptions() as $subscription) {
                    if ($subscription['name'] != $this->getTableName()) {
                        continue;
                    }
                    $this->linkedViews[] = $view;
                }
            }
        }
        return $this->linkedViews;
    }

    /**
     * Build trigger statement for INSER, UPDATE, DELETE events
     *
     * @param string $event
     * @param \Magento\Framework\Mview\View\ChangelogInterface $changelog
     * @return string
     */
    protected function buildStatement($event, $changelog)
    {
        switch ($event) {
            case Trigger::EVENT_INSERT:
            case Trigger::EVENT_UPDATE:
                return sprintf(
                    "INSERT IGNORE INTO %s (%s) VALUES (NEW.%s);",
                    $this->write->quoteIdentifier($this->resource->getTableName($changelog->getName())),
                    $this->write->quoteIdentifier($changelog->getColumnName()),
                    $this->write->quoteIdentifier($this->getColumnName())
                );

            case Trigger::EVENT_DELETE:
                return sprintf(
                    "INSERT IGNORE INTO %s (%s) VALUES (OLD.%s);",
                    $this->write->quoteIdentifier($this->resource->getTableName($changelog->getName())),
                    $this->write->quoteIdentifier($changelog->getColumnName()),
                    $this->write->quoteIdentifier($this->getColumnName())
                );

            default:
                return '';
        }
    }

    /**
     * Retrieve trigger name
     *
     * Build a trigger name by concatenating trigger name prefix, table name,
     * trigger time and trigger event.
     *
     * @param string $tableName
     * @param string $time
     * @param string $event
     * @return string
     */
    protected function getTriggerName($tableName, $time, $event)
    {
        return self::TRIGGER_NAME_QUALIFIER . $tableName . '_' . $time . '_' . $event;
    }

    /**
     * Retrieve View related to subscription
     *
     * @return \Magento\Framework\Mview\ViewInterface
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Retrieve table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Retrieve table column name
     *
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }
}
