<?php

namespace App\Controller;

use App\Entity\Partner;
use App\Entity\PartnerDistributionMethod;
use App\Entity\PartnerFulfillmentPeriod;
use App\Entity\PartnerProfile;
use App\Transformers\ClientTransformer;
use App\Transformers\PartnerTransformer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\Transition;

/**
 * Class PartnerController
 * @package App\Controller
 *
 * @Route(path="/api/partners")
 */
class PartnerController extends StorageLocationController
{
    protected $defaultEntityName = Partner::class;

    /** @var Registry */
    protected $workflowRegistry;

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @Route("/", methods={"GET"})
     */
    public function index(Request $request)
    {
        $partners = $this->getRepository(Partner::class)->findAll();

        return $this->serialize($request, $partners);
    }

    /**
     * @Route("", methods={"POST"})
     */
    public function store(Request $request)
    {
        $params = $this->getParams($request);

        $partner = new Partner($params['title']);
        $partnerProfile = new PartnerProfile();
        $partner->setProfile($partnerProfile);

        $partner->applyChangesFromArray($params);

        if ($params['distributionMethod']['id']) {
            $newMethod = $this->getEm()->find(PartnerDistributionMethod::class, $params['distributionMethod']['id']);
            $partner->setDistributionMethod($newMethod);
        }

        if ($params['fulfillmentPeriod']['id']) {
            $newPeriod = $this->getEm()->find(PartnerFulfillmentPeriod::class, $params['fulfillmentPeriod']['id']);
            $partner->setFulfillmentPeriod($newPeriod);
        }

        $this->getEm()->persist($partner);

        $this->getEm()->flush();

        return $this->serialize($request, $partner);
    }

    /**
     * @Route("/{id<\d+>}", methods={"GET"})
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerById($id);
        $meta = [
            'enabledTransitions' => $this->getEnabledTransitions($partner),
        ];

        return $this->serialize($request, $partner, null, $meta);
    }

    /**
     * @Route(path="/{id<\d+>}", methods={"PATCH"})
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, int $id)
    {
        $params = $this->getParams($request);

        $partner = $this->getPartnerById($id);
        $partner->applyChangesFromArray($params);

        if ($params['distributionMethod']['id']) {
            $newMethod = $this->getEm()->find(PartnerDistributionMethod::class, $params['distributionMethod']['id']);
            $partner->setDistributionMethod($newMethod);
        }

        if ($params['fulfillmentPeriod']['id'] !== $partner->getFulfillmentPeriod()->getId()) {
            $newPeriod = $this->getEm()->find(PartnerFulfillmentPeriod::class, $params['fulfillmentPeriod']['id']);
            $partner->setFulfillmentPeriod($newPeriod);
        }

        $this->getEm()->persist($partner);
        $this->getEm()->flush();

        return $this->serialize($request, $partner);
    }

    /**
     * @Route("/{id<\d+>}/clients", methods={"GET"})
     */
    public function clients(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerById($id);

        return $this->serialize($request, $partner->getClients()->getValues(), new ClientTransformer);
    }

    /**
     * @Route("/{id<\d+>}/transition", methods={"PATCH"})
     */
    public function transition(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerById($id);

        $params = $this->getParams($request);

        if ($params['transition']) {
            $this->workflowRegistry
                ->get($partner)
                ->apply($partner, $params['transition']);

            $this->getEm()->flush();
        }

        $meta = [
            'enabledTransitions' => $this->getEnabledTransitions($partner),
        ];

        return $this->serialize($request, $partner, null, $meta);
    }

    protected function getDefaultTransformer()
    {
        return new PartnerTransformer;
    }

    protected function getPartnerById($id): Partner
    {
        /** @var Partner $partner */
        $partner = $this->getRepository()->find($id);

        if (!$partner) {
            throw new NotFoundHttpException(sprintf('Unknown Partner ID: %d', $id));
        }

        return $partner;
    }

    protected function getEnabledTransitions(Partner $partner): array
    {
        $enabledTransitions = $this->workflowRegistry
            ->get($partner)
            ->getEnabledTransitions($partner);

        return array_map(function (Transition $transition) {
            return $transition->getName();
        }, $enabledTransitions);
    }
}
