<div x-data="{
        user: $wire.user,
    }">
    <div class="w-fit gap-4 items-center p-2 rounded-lg border-2 border-primary" x-show="$wire.client_secret === null">
        <span>
            <x-input-label for="montant" value="Montant en €" />
            <x-text-input id="montant" name="montant_transac" type="number" class="mt-1 block w-full" wire:model="amount" />
        </span>
        <span>
            <x-primary-button wire:click="generatePayment()">Créer le paiement</x-primary-button>
        </span>
    </div>

    <dialog class="p-2 rounded h-[450px] w-[300px]" id="paymentBox" wire:ignore>
        <div x-show="!$store.checkoutState.isFormSubmitted" x-ref="cardForm" class="min-h-full flex flex-col gap-2" x-transition>
            <label>
                <h3 class="m-2 font-bold">Montant à régler : <span x-text="$wire.amount + '€'"></span></h3>
                <div id="card-element"></div>
            </label>
            <span class="w-full mt-auto mb-2 flex flex-row justify-center">
                <x-primary-button id="submitPayment" class="mx-auto" x-show="$store.checkoutState.isFormLoaded">Payer</x-primary-button>
            </span>
        </div>
        <div wire:ignore x-ref="three_d_frame" id="iframe3DS" x-show="$store.checkoutState.needsAuth" x-transition></div>
        <div x-show="$store.checkoutState.isPaymentSuccess" class="h-full flex flex-col justify-center items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <h3 class="font-bold text-xl w-fit">Paiement effectué !</h3>
        </div>
    </dialog>

    @assets
    <script src="https://js.stripe.com/v3/"></script>
    @endassets

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('checkoutState', {
                isFormLoaded: false,
                isFormSubmitted: false,
                needsAuth: false,
                isPaymentSuccess: false,

                paymentSucceeded() {
                    this.isPaymentSuccess = true
                },

                showPayBtn() {
                    this.isFormLoaded = true
                },

                submit() {
                    this.isFormSubmitted = true
                },

                trigger3DS() {
                    this.needsAuth = !this.needsAuth
                }
            })
        })
    </script>

    @script
    <script>

            const stripe = Stripe('{{ env('STRIPE_PUBLIC_KEY') }}')

            $wire.on('intent-ok', () => {

                paymentBox.showModal()


                var elements = stripe.elements({
                    clientSecret: $wire.client_secret,
                    paymentMethodCreation: 'manual'
                })

                var paymentElement = elements.create('payment');
                paymentElement.mount('#card-element');

                paymentElement.on('ready', (e) => {
                    Alpine.store('checkoutState').showPayBtn();
                })

                submitPayment.addEventListener('click', function() {
                    elements.submit()
                    stripe.createPaymentMethod({
                        elements,
                        params: {
                            billing_details: {
                                name: 'John Doe'
                            }
                        }
                    }).then(function(result) {
                        const { paymentMethod, error } = result;
                        if (paymentMethod) {
                            Alpine.store('checkoutState').submit();
                            $wire.checkout(paymentMethod.id);
                        }
                    })
                })
            })

            $wire.on('needs_tree_d_secure', (e) => {
                console.log(e);
                var iframe = document.createElement('iframe');
                iframe.src = e.url;
                iframe.width = 250;
                iframe.height = 400;
                iframe.allow = 'payment';
                iframe3DS.appendChild(iframe);
                console.log(iframe3DS);
                Alpine.store('checkoutState').trigger3DS();
            })

            function on3DSComplete() {
                Alpine.store('checkoutState').trigger3DS()
                iframe3DS.remove();

                stripe.retrievePaymentIntent($wire.client_secret)
                    .then(function(result) {
                        if (result.error) {
                            console.log('paiement échoué', result.error);
                            paymentBox.close();
                        } else {
                            if (result.paymentIntent.status === 'succeeded') {
                                console.log("paiement réussi");
                                Alpine.store('checkoutState').paymentSucceeded();
                                setTimeout(() => {
                                    paymentBox.close();
                                }, 2000);
                            } else if (result.paymentIntent.status === 'requires_payment_method') {
                                console.log("Echec de l'authentification 3DSecure");
                                paymentBox.close();
                            }
                        }
                    })
            }

            window.addEventListener('message', function(ev) {
                if (ev.data === '3DS-authentication-complete') {
                    on3DSComplete();
                }
            })


    </script>
    @endscript
</div>
