<?php

namespace OroCRM\Bundle\SalesBundle\Controller\Dashboard;

use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Translation\TranslatorInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use OroCRM\Bundle\SalesBundle\Entity\Repository\SalesFunnelRepository;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/opportunities_by_lead_source/chart/{widget}",
     *      name="orocrm_sales_dashboard_opportunities_by_lead_source_chart",
     *      requirements={"widget"="[\w-]+"}
     * )
     * @Template("OroCRMSalesBundle:Dashboard:opportunitiesByLeadSource.html.twig")
     */
    public function opportunitiesByLeadSourceAction($widget)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        $data = $this->getDoctrine()
            ->getRepository('OroCRMSalesBundle:Lead')
            ->getOpportunitiesByLeadSource($this->get('oro_security.acl_helper'));

        foreach ($data as &$sourceData) {
            if (!empty($sourceData['label'])) {
                $sourceData['label'] = $translator->trans($sourceData['label']);
            }
        }

        $view = $this->getChartViewBuilder();
        $view->setArrayData($data);
        $view->setDataMapping(array('label' => 'label', 'value' => 'fraction'));
        $view->setOptions(array('name' => 'pie_chart'));

        $result = array_merge(
            ['chartView' => $view->getView()],
            $this->get('oro_dashboard.widget_attributes')->getWidgetAttributesForTwig($widget)
        );

        return $result;
    }

    /**
     * @Route(
     *      "/opportunity_state/chart/{widget}",
     *      name="orocrm_sales_dashboard_opportunity_by_state_chart",
     *      requirements={"widget"="[\w-]+"}
     * )
     * @Template("OroCRMSalesBundle:Dashboard:opportunityByStatus.html.twig")
     */
    public function opportunityByStatusAction($widget)
    {
        $items = $this->getDoctrine()
            ->getRepository('OroCRMSalesBundle:Opportunity')
            ->getOpportunitiesByStatus($this->get('oro_security.acl_helper'));

        $view = $this->getChartViewBuilder();
        $view->setArrayData($items);
        $view->setDataMapping(array('label' => 'label', 'value' => 'budget'));
        $view->setOptions(array('name' => 'line_chart'));

        return array_merge(
            array('chartView' => $view->getView()),
            $this->get('oro_dashboard.widget_attributes')->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @Route(
     *      "/sales_flow_b2b/chart/{widget}",
     *      name="orocrm_sales_dashboard_sales_flow_b2b_chart",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("OroCRMSalesBundle:Dashboard:salesFlowChart.html.twig")
     */
    public function mySalesFlowB2BAction($widget)
    {
        $dateTo = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateFrom = new \DateTime(
            $dateTo->format('Y') . '-01-' . ((ceil($dateTo->format('n') / 3) - 1) * 3 + 1),
            new \DateTimeZone('UTC')
        );

        /** @var WorkflowManager $workflowManager */
        $workflowManager = $this->get('oro_workflow.manager');
        $workflow = $workflowManager->getApplicableWorkflowByEntityClass(
            'OroCRM\Bundle\SalesBundle\Entity\SalesFunnel'
        );

        $customStepCalculations = array('won_opportunity' => 'opportunity.closeRevenue');

        /** @var SalesFunnelRepository $salesFunnerRepository */
        $salesFunnerRepository = $this->getDoctrine()->getRepository('OroCRMSalesBundle:SalesFunnel');

        return array_merge(
            array('quarterDate' => $dateFrom),
            $salesFunnerRepository->getFunnelChartData(
                $dateFrom,
                $dateTo,
                $workflow,
                $customStepCalculations,
                $this->get('oro_security.acl_helper')
            ),
            $this->get('oro_dashboard.widget_attributes')->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @return ChartViewBuilder
     */
    protected function getChartViewBuilder()
    {
        return $this->container->get('oro_chart.view_builder');
    }
}
