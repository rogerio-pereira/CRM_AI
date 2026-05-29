<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunities_relationship_returns_related_opportunities(): void
    {
        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($client)->create();

        $this->assertCount(1, $client->opportunities);
        $this->assertTrue($client->opportunities->first()->is($opportunity));
    }

    public function test_follow_ups_relationship_returns_related_follow_ups(): void
    {
        $client = Client::factory()->create();
        $followUp = FollowUp::factory()->for($client)->create();

        $this->assertCount(1, $client->followUps);
        $this->assertTrue($client->followUps->first()->is($followUp));
    }

    public function test_tasks_relationship_returns_related_tasks(): void
    {
        $client = Client::factory()->create();
        $task = Task::factory()->for($client)->create(['title' => 'Client task']);

        $this->assertCount(1, $client->tasks);
        $this->assertTrue($client->tasks->first()->is($task));
        $this->assertSame('Client task', $client->tasks->first()->title);
    }
}
