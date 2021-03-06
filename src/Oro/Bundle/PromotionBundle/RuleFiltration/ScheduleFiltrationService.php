<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\CronBundle\Checker\ScheduleIntervalChecker;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class ScheduleFiltrationService implements RuleFiltrationServiceInterface
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var ScheduleIntervalChecker
     */
    private $scheduleIntervalChecker;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     * @param ScheduleIntervalChecker $scheduleIntervalChecker
     */
    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        ScheduleIntervalChecker $scheduleIntervalChecker
    ) {
        $this->filtrationService = $filtrationService;
        $this->scheduleIntervalChecker = $scheduleIntervalChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredOwners = array_values(array_filter($ruleOwners, [$this, 'isScheduleEnabled']));

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param RuleOwnerInterface $ruleOwner
     * @return bool
     */
    private function isScheduleEnabled(RuleOwnerInterface $ruleOwner): bool
    {
        return $ruleOwner instanceof Promotion && $this->isPromotionApplicable($ruleOwner);
    }

    /**
     * @param Promotion $promotion
     * @return bool
     */
    private function isPromotionApplicable(Promotion $promotion): bool
    {
        return $promotion->getSchedules()->isEmpty()
            || $this->scheduleIntervalChecker->hasActiveSchedule($promotion->getSchedules());
    }
}
