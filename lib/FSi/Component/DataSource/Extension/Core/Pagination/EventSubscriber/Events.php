<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Szczepan Cieslik <szczepan@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Extension\Core\Pagination\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Extension\Core\Pagination\PaginationExtension;
use FSi\Component\DataSource\DataSourceInterface;

/**
 * Class contains method called during DataSource events.
 */
class Events implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DataSourceEvents::PRE_BIND_PARAMETERS => 'preBindParameters',
            DataSourceEvents::POST_GET_PARAMETERS => array('postGetParameters', -1024),
            DataSourceEvents::POST_BUILD_VIEW => 'postBuildView',
        );
    }

    /**
     * Method called at PreBindParameters event.
     *
     * Sets proper page.
     *
     * @param DataSourceEvent\ParametersEventArgs $event
     */
    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $parameters = $event->getParameters();

        $page = isset($parameters[$datasource->getName()][PaginationExtension::PAGE]) ? (int) $parameters[$datasource->getName()][PaginationExtension::PAGE] : 1;
        $datasource->setFirstResult(($page - 1) * $datasource->getMaxResults());
    }

    public function postGetParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasource_oid = spl_object_hash($datasource);
        $datasourceName = $datasource->getName();

        $parameters = $event->getParameters();
        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $page = 1;
        } else {
            $current = $datasource->getFirstResult();
            $page = (int) floor($current/$maxresults) + 1;
        }
        unset($parameters[$datasourceName][PaginationExtension::PAGE]);
        if ($page > 1)
            $parameters[$datasourceName][PaginationExtension::PAGE] = $page;
        $event->setParameters($parameters);
    }

    /**
     * Method called at PostBuildView event.
     *
     * @param DataSourceEvent\ViewEventArgs $event
     */
    public function postBuildView(DataSourceEvent\ViewEventArgs $event)
    {
        $datasource = $event->getDataSource();
        $datasourceName = $datasource->getName();
        $view = $event->getView();
        $parameters = $view->getParameters();

        $maxresults = $datasource->getMaxResults();
        if ($maxresults == 0) {
            $all = 1;
            $page = 1;
        } else {
            $all = (int) ceil(count($datasource->getResult())/$maxresults);
            $current = $datasource->getFirstResult();
            $page = (int) floor($current/$maxresults) + 1;
        }

        unset($parameters[$datasourceName][PaginationExtension::PAGE]);
        $pages = array();
        for ($i = 1; $i <= $all; $i++) {
            if ($i > 1)
                $parameters[$datasourceName][PaginationExtension::PAGE] = $i;
            $pages[$i] = $parameters;
        }

        $view->setAttribute(PaginationExtension::VIEW_PAGE_CURRENT, $page);
        $view->setAttribute(PaginationExtension::VIEW_PAGES, $pages);
    }
}
