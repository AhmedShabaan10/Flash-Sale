<?php

namespace Tests\Feature;

use App\Jobs\CleanupExpiredHolds;
use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HoldSystemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_oversell_on_parallel_requests()
    {
        $product = Product::factory()->create(['stock' => 1]);

        // Simulate 2 users trying to hold 1 item at the same time
        $responses = [
            $this->postJson('/api/holds', ['product_id' => $product->id, 'qty' => 1]),
            $this->postJson('/api/holds', ['product_id' => $product->id, 'qty' => 1]),
        ];

        $successCount = collect($responses)->filter(fn($r) => $r->status() === 201)->count();
        $this->assertEquals(1, $successCount, 'Only one hold should succeed');

        $this->assertEquals(0, Product::find($product->id)->available_stock);
    }

    /** @test */
    public function hold_expiry_returns_stock()
    {
        $product = Product::factory()->create(['stock' => 5]);
        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'qty' => 3,
            'expires_at' => now()->addMinutes(1),
        ]);

        // Fast-forward time
        $this->travel(2)->minute();

        // Dispatch cleanup job
        (new CleanupExpiredHolds())->handle();

        $this->assertEquals(5, Product::find($product->id)->available_stock);
        $this->assertDatabaseMissing('holds', ['id' => $hold->id]);
    }

    /** @test */
    public function webhook_idempotency_prevents_double_processing()
    {
        $order = Order::factory()->create(['order_number' => 'order123', 'status' => 'pending']);

        $payload = ['idempotency_key' => 'unique123', 'order_number' => $order->order_number, 'status' => 'success'];

        // Dispatch webhook
        $response1 = $this->postJson('api/payments/webhook', $payload)->assertStatus(200);
        $response2 = $this->postJson('api/payments/webhook', $payload)->assertStatus(200);

        $this->assertDatabaseCount('orders', 1);
    }

    /** @test */
    public function webhook_before_order_is_handled_correctly()
    {
        $payload = ['idempotency_key' => 'preorder123', 'order_number' => 'order123', 'status' => 'success'];

        // Send webhook before creating order
        $this->postJson('api/payments/webhook', $payload)->assertStatus(202);

        $order = Order::factory()->create(['order_number' => 'order123', 'status' => 'pending']);

        $this->postJson('api/payments/webhook', $payload)->assertStatus(200);
        $this->assertDatabaseHas('orders', ['order_number' => 'order123']);
        $this->assertDatabaseHas('payment_webhooks', ['idempotency_key' => 'preorder123']);

    }
}
