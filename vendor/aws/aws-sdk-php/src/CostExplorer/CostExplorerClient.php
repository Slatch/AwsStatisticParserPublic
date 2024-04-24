<?php
namespace Aws\CostExplorer;

use Aws\AwsClient;

/**
 * This client is used to interact with the **AWS Cost Explorer Service** service.
 * @method \Aws\Result createAnomalyMonitor(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAnomalyMonitorAsync(array $args = [])
 * @method \Aws\Result createAnomalySubscription(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAnomalySubscriptionAsync(array $args = [])
 * @method \Aws\Result createCostCategoryDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCostCategoryDefinitionAsync(array $args = [])
 * @method \Aws\Result deleteAnomalyMonitor(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAnomalyMonitorAsync(array $args = [])
 * @method \Aws\Result deleteAnomalySubscription(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAnomalySubscriptionAsync(array $args = [])
 * @method \Aws\Result deleteCostCategoryDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteCostCategoryDefinitionAsync(array $args = [])
 * @method \Aws\Result describeCostCategoryDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeCostCategoryDefinitionAsync(array $args = [])
 * @method \Aws\Result getAnomalies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAnomaliesAsync(array $args = [])
 * @method \Aws\Result getAnomalyMonitors(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAnomalyMonitorsAsync(array $args = [])
 * @method \Aws\Result getAnomalySubscriptions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAnomalySubscriptionsAsync(array $args = [])
 * @method \Aws\Result getApproximateUsageRecords(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getApproximateUsageRecordsAsync(array $args = [])
 * @method \Aws\Result getCostAndUsage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCostAndUsageAsync(array $args = [])
 * @method \Aws\Result getCostAndUsageWithResources(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCostAndUsageWithResourcesAsync(array $args = [])
 * @method \Aws\Result getCostCategories(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCostCategoriesAsync(array $args = [])
 * @method \Aws\Result getCostForecast(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCostForecastAsync(array $args = [])
 * @method \Aws\Result getDimensionValues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getDimensionValuesAsync(array $args = [])
 * @method \Aws\Result getReservationCoverage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getReservationCoverageAsync(array $args = [])
 * @method \Aws\Result getReservationPurchaseRecommendation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getReservationPurchaseRecommendationAsync(array $args = [])
 * @method \Aws\Result getReservationUtilization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getReservationUtilizationAsync(array $args = [])
 * @method \Aws\Result getRightsizingRecommendation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getRightsizingRecommendationAsync(array $args = [])
 * @method \Aws\Result getSavingsPlanPurchaseRecommendationDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSavingsPlanPurchaseRecommendationDetailsAsync(array $args = [])
 * @method \Aws\Result getSavingsPlansCoverage(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSavingsPlansCoverageAsync(array $args = [])
 * @method \Aws\Result getSavingsPlansPurchaseRecommendation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSavingsPlansPurchaseRecommendationAsync(array $args = [])
 * @method \Aws\Result getSavingsPlansUtilization(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSavingsPlansUtilizationAsync(array $args = [])
 * @method \Aws\Result getSavingsPlansUtilizationDetails(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getSavingsPlansUtilizationDetailsAsync(array $args = [])
 * @method \Aws\Result getTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTagsAsync(array $args = [])
 * @method \Aws\Result getUsageForecast(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getUsageForecastAsync(array $args = [])
 * @method \Aws\Result listCostAllocationTagBackfillHistory(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCostAllocationTagBackfillHistoryAsync(array $args = [])
 * @method \Aws\Result listCostAllocationTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCostAllocationTagsAsync(array $args = [])
 * @method \Aws\Result listCostCategoryDefinitions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCostCategoryDefinitionsAsync(array $args = [])
 * @method \Aws\Result listSavingsPlansPurchaseRecommendationGeneration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSavingsPlansPurchaseRecommendationGenerationAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result provideAnomalyFeedback(array $args = [])
 * @method \GuzzleHttp\Promise\Promise provideAnomalyFeedbackAsync(array $args = [])
 * @method \Aws\Result startCostAllocationTagBackfill(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startCostAllocationTagBackfillAsync(array $args = [])
 * @method \Aws\Result startSavingsPlansPurchaseRecommendationGeneration(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startSavingsPlansPurchaseRecommendationGenerationAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateAnomalyMonitor(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateAnomalyMonitorAsync(array $args = [])
 * @method \Aws\Result updateAnomalySubscription(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateAnomalySubscriptionAsync(array $args = [])
 * @method \Aws\Result updateCostAllocationTagsStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateCostAllocationTagsStatusAsync(array $args = [])
 * @method \Aws\Result updateCostCategoryDefinition(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateCostCategoryDefinitionAsync(array $args = [])
 */
class CostExplorerClient extends AwsClient {}
