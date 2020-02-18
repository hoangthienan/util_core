<?php

namespace go1\util\tests\content_job_tests;

use go1\util\tests\UtilCoreTestCase;
use go1\util\schema\mock\PlanMockTrait;
use go1\util\content_job\ContentJobContentDownloadCompleteCreate;

class ContentJobContentDownloadCompleteTest extends UtilCoreTestCase
{
    use PlanMockTrait;

    public function testContentDownloadCompleteMessage() {

      $payload = (object) [
          'id' => 1,
          'type' => 'Portal_Content_Download',
          'instanceId'=> 1,
          'configuration' => '{ "collection": "custom" }',
          'status' => 'running',
          'priority' => 1,
          'analytics' => '{ "data": "data" }',
          'reoccurringPeriod' => 'None',
          'scheduleJobId' => 1,
          'createdDate' => date("Y-m-d H:i:s"),
          'modifiedDate' => date("Y-m-d H:i:s"),
          'message' => 'heh'
      ];

      $message = ContentJobContentDownloadCompleteCreate::publish($payload);

        $this->assertEquals($message->id, $payload->id);
        $this->assertEquals($message->type, $payload->type);
        $this->assertEquals($message->instanceId, $payload->instanceId);
        $this->assertEquals($message->configuration, $payload->configuration);
        $this->assertEquals($message->status, $payload->status);
        $this->assertEquals($message->priority, $payload->priority);
        $this->assertEquals($message->analytics, $payload->analytics);
        $this->assertEquals($message->reoccurringPeriod, $payload->reoccurringPeriod);
        $this->assertEquals($message->scheduleJobId, $payload->scheduleJobId);
        $this->assertEquals($message->createdDate, $payload->createdDate);
        $this->assertEquals($message->modifiedDate, $payload->modifiedDate);
    }
}
