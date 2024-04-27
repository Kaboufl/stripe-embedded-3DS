<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Models\User;
use Livewire\Component;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Auth;

class Checkout3DS extends Component
{
    public $user;

    public $amount;
    public $paymentMethod;
    public $transaction;
    public $client_secret;

    public function mount() {
        $user = User::with('transactions')->find(Auth::id());
        $this->user = $user->toArray();

        if ($user->transactions->isNotEmpty()) {
            $this->transaction = $user->transactions->first();
            $this->client_secret = $this->getClientSecret();
            $this->dispatch('intent-ok')->self();
        }
    }

    private function getClientSecret() {
        $stripe = app(StripeClient::class);

        $intent = $stripe->paymentIntents->retrieve($this->transaction->intent_id);
        $this->amount = $intent->amount / 100;
        return $intent->client_secret;
    }

    public function generatePayment() {
        $stripe = app(StripeClient::class);

        $intent = $stripe->paymentIntents->create([
            'amount' => intval($this->amount) * 100,
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'payment_method_options' => [
                'card' => [ 'request_three_d_secure' => "challenge" ]
            ],
        ]);
        $this->client_secret = $intent->client_secret;


        $this->transaction = User::find(Auth::id())->transactions()->create([
            'amount' => intval($this->amount) * 100,
            'intent_id' => $intent->id,
        ]);


        $this->dispatch('intent-ok')->self();
    }

    public function checkout($paymentMethodId) {
        $stripe = app(StripeClient::class);

        $paymentMethod = $stripe->paymentMethods->retrieve($paymentMethodId);
        $confirmation = $stripe->paymentIntents->confirm(
            $this->transaction->intent_id,
            [
                'payment_method' => $paymentMethod,
                'return_url' => route('stripe_return')
            ]
        );

        switch ($confirmation->status) {
            case 'requires_action':
                $url = $confirmation->next_action->redirect_to_url->url;
                $returnUrl = $confirmation->next_action->redirect_to_url->return_url;
                $this->dispatch('needs_tree_d_secure', url: $url, returnUrl: $returnUrl)->self();
                break;

            default:
                # code...
                break;
        }
        //dd($confirmation);
    }

    public function render()
    {
        return view('livewire.checkout3-d-s');
    }
}
