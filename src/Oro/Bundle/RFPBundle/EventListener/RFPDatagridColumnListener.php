<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener;

class RFPDatagridColumnListener
{
    const DATAGRID = 'rfp-requests-grid';

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        if ($event->getDatagrid()->getName() !== self::DATAGRID) {
            return;
        }

        $config = $event->getConfig();
        $columns = $config->offsetGetByPath('[columns]', []);
        if (!array_key_exists(WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN, $columns)) {
            return;
        }

        $columns[WorkflowStepColumnListener::WORKFLOW_STEP_COLUMN]['renderable'] = false;
        $config->offsetSetByPath('[columns]', $columns);
    }
}
